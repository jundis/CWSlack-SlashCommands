<?php
/*
	CWSlack-SlashCommands
    Copyright (C) 2017  jundis

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//Receive connector for Connectwise Callbacks
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php'; //Require the config file.
require_once 'functions.php';

//Dates required for URL to function
$datenow = gmdate("Y-m-d\TH:i", strtotime("-10 minutes")); //Date set to 10 minutes prior to now, to catch for tickets happening right now.
$date2hours = gmdate("Y-m-d\TH:i", strtotime("+2 hours")); //Date set to 2 hours out so reminders up to 2 hours function.

$url = $connectwise. "/$connectwisebranch/apis/3.0/schedule/entries?conditions=status/Name=%27Firm%27%20and%20dateStart%20%3E%20[" . $datenow . "]%20and%20dateStart%20%3C%20[". $date2hours . "]&orderBy=dateStart%20desc"; //URL to access the schedule API
$ticketurl = $connectwise . "/$connectwisebranch/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Set the URL required for ticket links.


//Set headers for cURL requests. $header_data covers API authentication while $header_data2 covers the Slack output.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);

$header_data2 =array(
 "Content-Type: application/json"
);

// Pre-connect mysql if it will be needed in the loop.
if($usedatabase==1) {
    $mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

    if (!$mysql) //Check for errors
    {
        die("Connection Error: " . mysqli_connect_error()); //Return error
    }
}

$dataTData = cURL($url, $header_data); //Decode the JSON returned by the CW API.

foreach($dataTData as $entry) //For each schedule entry returned
{
	$reminder = $entry->reminder->name; //Set the reminder type
	$user = $entry->member->identifier; //Set the user's ConnectWise username (e.g. jdoe)
	$username = $entry->member->name; //Set the user's name (e.g. John Doe)
	$namearray = explode(" - ",$entry->name); //Explode the summary field
	$companyarray = explode(" / ",$namearray[0]); //Explode that into company/ticket number array.
	$company = $companyarray[0]; //Set company to first part of second explode.
	$summary = $namearray[1]; //Set the ticket summary to second part of first explode.
	$datenow = date("Y-m-d\TH:i"); //Reusing datenow as non-GMT based time.
	$datestart = date("Y-m-d\TH:i",strtotime($entry->dateStart)); //Start time of the ticket.

    //Username mapping code
    if($usedatabase==1)
    {
        $val1 = mysqli_real_escape_string($mysql,$user);
        $sql = "SELECT * FROM `usermap` WHERE `cwname`=\"" . $val1 . "\""; //SQL Query to select all ticket number entries

        $result = mysqli_query($mysql, $sql); //Run result
        $rowcount = mysqli_num_rows($result);
        if($rowcount > 1) //If there were too many rows matching query
        {
            die("Error: too many users somehow?"); //This should NEVER happen.
        }
        else if ($rowcount == 1) //If exactly 1 row was found.
        {
            $row = mysqli_fetch_assoc($result); //Row association.

            $user = $row["slackuser"]; //Return the slack username portion of the found row as the $user variable to be used as part of the notification.
        }
        //If no rows are found here, then it just uses whatever if found as $user previously from the ticket.
    }

	if($reminder != "0 minutes" && $posttousers == 1) //If reminder is not 0 minutes, proceed. Pointless to have 0 minute reminder as that is handled below.
	{
		$datereminder = date("Y-m-d\TH:i",strtotime($entry->dateStart . " -" . $reminder)); //Set the reminder date to a readable comparable format.

		if($datenow==$datereminder) //If datenow and datereminder are the same..
		{
		    //Setup the slack return text
			$postfieldspre = array(
				"channel"=>"@".$user, //Send to user
				"attachments"=>array(array(
					"fallback" => "Firm with " . $company . " in " . $reminder . ".", //Notification, since there's only attachment and no text it will always use fallback.
					"title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary, //Title in bold
					"pretext" => "Firm starting in " . $reminder, //Text before title.
					"text" =>  "You have a firm ticket with " . $company . " coming up. Please wrap up work on current ticket.", //Reminder text.
					"mrkdwn_in" => array(
						"text",
						"pretext"
						)
					))
				);

            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
		}
	}

	if($datenow == $datestart) //If the start of the ticket is right now..
	{
		if($posttousers==1) //And user post is on.
		{
			$postfieldspre = array(
				"channel"=>"@".$user,
				"attachments"=>array(array(
					"fallback" => "Firm with " . $company . " starting now.",
					"title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary,
					"pretext" => "Firm starting now.",
					"text" =>  "You have a firm ticket with " . $company . " starting now. You should be calling the client now.",
					"mrkdwn_in" => array(
						"text",
						"pretext"
						)
					))
				);

            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
		}
		if($posttochan==1) //If channel post is on
		{
		    if($usetimechan==1)
            {
                $postfieldspre = array(
                    "channel"=>$timechan, //Post to channel set in config.php
                    "attachments"=>array(array(
                        "fallback" => "Firm for " . $username . " with " . $company . " now.",
                        "title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary,
                        "pretext" => $username . " has a firm starting now.",
                        "text" =>  "Please remind the technician of this appointment with " . $company . ". They should be on the phone with them or calling them shortly.",
                        "mrkdwn_in" => array(
                            "text",
                            "pretext"
                        )
                    ))
                );
            }
			else
            {
                $postfieldspre = array(
                    "channel"=>$firmalertchan, //Post to channel set in config.php
                    "attachments"=>array(array(
                        "fallback" => "Firm for " . $username . " with " . $company . " now.",
                        "title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary,
                        "pretext" => $username . " has a firm starting now.",
                        "text" =>  "Please remind the technician of this appointment with " . $company . ". They should be on the phone with them or calling them shortly.",
                        "mrkdwn_in" => array(
                            "text",
                            "pretext"
                        )
                    ))
                );
            }

            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
		}
	}
}
?>