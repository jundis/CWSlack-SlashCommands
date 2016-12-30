<?php
/* 	
	CWSlack-SlashCommands
    Copyright (C) 2016  jundis

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

// This is a development branch, please use with caution as things will frequently be changing.

ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';
require_once 'functions.php';

// Authorization array. Auto encodes API key for auhtorization above.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 = postHeader($companyname, $apipublickey, $apiprivatekey);

if(empty($_GET['token']) || ($_GET['token'] != $slacktoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die("No text provided."); //If there is no text added, kill the connection.
$exploded = explode(" ",$_GET['text']); //Explode the string attached to the slash command for use in variables.


//This section checks if the ticket number is not equal to 6 digits (our tickets are in the hundreds of thousands but not near a million yet) and kills the connection if it's not.
if(!is_numeric($exploded[0])) {
	//Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
	if ($exploded[0]=="help") {
		die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true)));
	}
	if ($exploded[0]=="new")
	{
		// Do nothing
	}
	else //Else close the connection.
	{
		die("Unknown entry for ticket number.");
	}
}
$ticketnumber = $exploded[0]; //Set the ticket number to the first string
$command=NULL; //Create a command variable and set it to Null
$option3=NULL; //Create a option variable and set it to Null
if (array_key_exists(1,$exploded)) //If a second string exists in the slash command array, make it the command.
{
	$command = $exploded[1];
}
if (array_key_exists(2,$exploded)) //If a third string exists in the slash command array, make it the option for the command.
{
	$option3 = $exploded[2];
}
//Set URLs
$urlticketdata = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber; //Set ticket API url
$ticketurl = $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Ticket URL for connectwise.
$timeurl = $connectwise . "/v4_6_release/apis/3.0/time/entries?conditions=chargeToId=" . $ticketnumber . "&chargeToType=%27ServiceTicket%27&orderBy=dateEntered%20desc"; //Set the URL required for cURL requests to the time entry API.
if($command == "initial" || $command == "first" || $command == "note") //Set noteurl to use ascending if an initial note command is passed, else use descending.
{
	$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20asc";
}
else
{
	$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20desc";
}

//Need to create 3 arrays before hand to ensure no errors occur.
$dataTNotes = array();
$dataTData = array();
$dataTCmd = array();

if (strpos(strtolower($exploded[0]), "new") !== false)
{
	unset($exploded[0]);
	$exploded = implode(" ", $exploded);
	$ticketstuff = explode("|",$exploded);

	if($useboards == 1)
	{
		if(!array_key_exists(2, $ticketstuff)) {
			die("Not enough values specified. Please use /t new board|company|summary");
		}
		$companyurl = $connectwise . "/v4_6_release/apis/3.0/company/companies?conditions=name%20contains%20%27" . $ticketstuff[1] . "%27";
		$companydata = cURL($companyurl, $header_data);

		$boardurl = $connectwise . "/v4_6_release/apis/3.0/service/boards?conditions=name%20contains%20%27" .$ticketstuff[0]. "%27";
		$boarddata = cURL($boardurl, $header_data);

		$postarray = array(
			"summary" => $ticketstuff[2],
			"company" => array(
				"id" => $companydata[0]->id
			),
			"board" => array(
				"id" => $boarddata[0]->id
			));
	}
	else
	{
		if(!array_key_exists(1, $ticketstuff)) {
			die("Not enough values specified. Please use /t new company|summary");
		}

		$companyurl = $connectwise . "/v4_6_release/apis/3.0/company/companies?conditions=name%20contains%20%27" . $ticketstuff[1] . "%27";
		$companydata = cURL($companyurl, $header_data);

		$postarray = array(
			"summary" => $ticketstuff[1],
			"company" => array(
				"name" => $ticketstuff[0]
			));
	}
	//Username mapping code
	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

		if (!$mysql) //Check for errors
		{
			die("Connection Error: " . mysqli_connect_error());
		}

		$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $_GET["user_name"] . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result
		$rowcount = mysqli_num_rows($result);
		if($rowcount > 1) //If there were too many rows matching query
		{
			die("Error: too many users somehow?"); //This should NEVER happen.
		}
		else if ($rowcount == 1) //If exactly 1 row is found.
		{
			$row = mysqli_fetch_assoc($result); //Row association.

			$postarray["enteredBy"] = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
			$postarray["owner"] = array("identifier"=>$row["cwname"]); //Return the connectwise name of the row found as the CW member name.
		}
		else //If no rows are found
		{
			if($usecwname==1) //If variable enabled
			{
				$postarray["enteredBy"] = $_GET['user_name'];
				$postarray["owner"] = array("identifier"=>$_GET['user_name']); //Return the slack username as the user for the ticket note. If the user does not exist in CW, it will use the API username.
			}
		}
	}
	else
	{
		if($usecwname==1)
		{
			$postarray["enteredBy"] = $_GET['user_name'];
			$postarray["owner"] = array("identifier"=>$_GET['user_name']);
		}
	}

	$dataTCmd = cURLPost( //Function for POST requests in cURL
		$connectwise . "/v4_6_release/apis/3.0/service/tickets", //URL
		$header_data2, //Header
		"POST", //Request type
		$postarray
	);

	die("New ticket #<" . $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid=" . $dataTCmd->id . "|" . $dataTCmd->id . "> has been created.");
}

//-
//Ticket data section
//-
$dataTData = cURL($urlticketdata, $header_data); //Decode the JSON returned by the CW API.

if($dataTData==NULL) 
{
	die("Array not returned in line 195. Please check your connectwise URL variable in config.php and ensure it is accessible via the web at " . $urlticketdata);
}

//-
//Priority command
//- 
if($command=="priority") { //Check if the second string in the text array from the slash command is priority

	$priority = "0"; //Set priority = 0.

	//Check what $option3 was set to, the third string in the text array from the slash command.
	if ($option3 == "moderate") //If moderate
	{
		$priority = "4"; //Set to moderate ID
	} else if ($option3=="critical")
	{
		$priority = "1";
	} else if ($option3=="low")
	{
		$priority = "3";
	}
	else //If unknown
	{
		die("Failed to get priority code: " . $option3); //Send error message. Anything not Slack JSON formatted will return just to the user who submitted the slash command. Don't need to spam errors.
	}

	$dataTCmd = cURLPost( //Function for POST requests in cURL
		$urlticketdata, //URL
		$header_data2, //Header
		"PATCH", //Request type
		array(array("op" => "replace", "path" => "/priority/id", "value" => $priority)) //POST Body
	);

	$return =array(
		"parse" => "full", //Parse all text.
		"response_type" => "ephemeral", //Send the response to the user only
		"attachments"=>array(array(
			"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
			"title" => "Ticket Summary: " . $dataTData->summary, //Set bolded title text
			"pretext" => "Ticket #" . $dataTData->id . "'s priority has been set to " . $option3, //Set pretext
			"text" => "Click <" . $ticketurl . $dataTData -> id . "&companyName" . $companyname . "|here> to open the ticket.", //Set text to be returned
			"mrkdwn_in" => array( //Set markdown values
				"text",
				"pretext"
			)
		))
	);

	die(json_encode($return, JSON_PRETTY_PRINT)); //Return properly encoded arrays in JSON for Slack parsing.
}

//-
//Ticket Status change command.
//-
if($command=="status") {
	$status = "0";
	if ($option3 == "scheduled" || $option3=="schedule")
	{
		$status = "124";
	} else if ($option3=="completed")
	{
		$status = "31";
	} else if ($option3=="n2s" || $option3=="needtoschedule" || $option3=="ns")
	{
		$status = "121";
	}
	else
	{
		die("Failed to get status code: " . $option3);
	}
	$dataTCmd = cURLPost(
		$urlticketdata,
		$header_data2,
		"PATCH",
		array(array("op" => "replace", "path" => "/status/id", "value" => $status))
	);

	$return =array(
		"parse" => "full",
		"response_type" => "ephemeral", //Send the response to the user only
		"attachments"=>array(array(
			"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
			"title" => "Ticket Summary: " . $dataTData->summary,
			"pretext" => "Ticket #" . $dataTData->id . "'s status has been set to " . $option3,
			"text" => "Click <" . $ticketurl . $dataTData -> id . "&companyName" . $companyname . "|here> to open the ticket.",
			"mrkdwn_in" => array(
				"text",
				"pretext"
			)
		))
	);

	die(json_encode($return, JSON_PRETTY_PRINT)); //Return properly encoded arrays in JSON for Slack parsing.
}

if($command=="scheduleme")
{

	$cwuser = NULL;
	//Username mapping code
	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

		if (!$mysql) //Check for errors
		{
			die("Connection Error: " . mysqli_connect_error());
		}

		$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $_GET["user_name"] . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result
		$rowcount = mysqli_num_rows($result);
		if($rowcount > 1) //If there were too many rows matching query
		{
			die("Error: too many users somehow?"); //This should NEVER happen.
		}
		else if ($rowcount == 1) //If exactly 1 row is found.
		{
			$row = mysqli_fetch_assoc($result); //Row association.

			$cwuser = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
		}
		else //If no rows are found
		{
			if($usecwname==1) //If variable enabled
			{
				$cwuser = $_GET['user_name'];
			}
		}
	}
	else
	{
		if($usecwname==1)
		{
			$cwuser = $_GET['user_name'];
		}
		else
		{
			die("Error: Name " .  $_GET['user_name'] . " not found");
		}
	}
	unset($exploded[0]);
	unset($exploded[1]);
	$removal = implode(" ", $exploded);
	if($removal==NULL)
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime("12:00AM"));
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	else
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal));
		$dateend = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal. " +30 minutes"));
	}
	if(strpos($datestart, 'T06:00:00Z') !== false)
	{
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	if(strpos($datestart, 'T00:00:00Z') !== false)
	{
		$dateend = $datestart;
	}

	$postarray = array("objectId" => $ticketnumber, "member" => array("identifier" => $cwuser), "type" => array("id" => 4), "dateStart" => $datestart, "dateEnd" => $dateend, "allowScheduleConflictsFlag" => true);

	$dataTCmd = cURLPost(
		$connectwise . "/v4_6_release/apis/3.0/schedule/entries",
		$header_data2,
		"POST",
		$postarray
	);

	if($removal==NULL)
	{
		$timingdate = explode("T", $datestart);
		die("You have been properly scheduled for ticket #" . $dataTCmd->objectId . " for $timingdate[0]");
	}
	else
	{
		die("You have been properly scheduled for ticket #" . $dataTCmd->objectId . " at " . $removal);
	}
}

if($command=="schedule")
{

	$cwuser = NULL;
	if($option3 == NULL)
	{
		die("No user specified.");
	}
	$username = $option3;
	//Username mapping code
	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

		if (!$mysql) //Check for errors
		{
			die("Connection Error: " . mysqli_connect_error());
		}

		$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $username . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result
		$rowcount = mysqli_num_rows($result);
		if($rowcount > 1) //If there were too many rows matching query
		{
			die("Error: too many users somehow?"); //This should NEVER happen.
		}
		else if ($rowcount == 1) //If exactly 1 row is found.
		{
			$row = mysqli_fetch_assoc($result); //Row association.

			$cwuser = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
		}
		else //If no rows are found
		{
			if($usecwname==1) //If variable enabled
			{
				$cwuser = $username;
			}
		}
	}
	else
	{
		if($usecwname==1)
		{
			$cwuser = $username;
		}
		else
		{
			die("Error: Name " .  $username . " not found");
		}
	}
	unset($exploded[0]);
	unset($exploded[1]);
	unset($exploded[2]);
	$removal = implode(" ", $exploded);
	if($removal==NULL)
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime("12:00AM"));
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	else
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal));
		$dateend = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal. " +30 minutes"));
	}
	if(strpos($datestart, 'T06:00:00Z') !== false)
	{
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	if(strpos($datestart, 'T00:00:00Z') !== false)
	{
		$dateend = $datestart;
	}

	$postarray = array("objectId" => $ticketnumber, "member" => array("identifier" => $cwuser), "type" => array("id" => 4), "dateStart" => $datestart, "dateEnd" => $dateend, "allowScheduleConflictsFlag" => true);

	$dataTCmd = cURLPost(
		$connectwise . "/v4_6_release/apis/3.0/schedule/entries",
		$header_data2,
		"POST",
		$postarray
	);

	if($removal==NULL)
	{
		$timingdate = explode("T", $datestart);
		die("$username has been properly scheduled for ticket #" . $dataTCmd->objectId . " for $timingdate[0]");
	}
	else
	{
		die("$username has been properly scheduled for ticket #" . $dataTCmd->objectId . " at " . $removal);
	}
}

if($posttext==1) //Block for curl to get latest note
{
	$createdby = "Error"; //Create with error just in case.
	$notetext = "Error"; //Create with error just in case.

	$dataTNotes = cURL($noteurl, $header_data); // Get the JSON returned by the CW API for $noteurl.

	$dataTimeData = cURL($timeurl, $header_data); // Get the JSON returned by the CW API for $timeurl.

	if($command == "full" || $command == "notes" || $command == "all")
	{
		$dataTNotes2 = cURL($connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20asc", $header_data); // Get the JSON returned by the CW API for ticket notes.
	}
	if(!array_key_exists(0, $dataTNotes))
	{
		if(array_key_exists(0, $dataTimeData))
		{
			$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
			$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
			$notedate = $dataTimeData[0]->dateEntered;

			$date2 = strtotime($notedate);
			$date2format = date('m-d-Y g:i:sa', $date2);
		}
		else
		{
			$posttext=0;
		}
	}
	else if($dataTNotes[0]->text != NULL || $dataTimeData[0]->text != NULL) //Makes sure that if both text values == null, then there is no text to post.
	{
		if($dataTNotes[0]->text != NULL) {

			$createdby = $dataTNotes[0]->createdBy; //Set $createdby to the ticket note creator.
			$notetime = new DateTime($dataTNotes[0]->dateCreated); //Create new datetime object based on ticketnote note.
			$notedate = $dataTNotes[0]->dateCreated;

			$text = $dataTNotes[0]->text; //Set $text to the ticket text.
			if (array_key_exists(0, $dataTNotes) && array_key_exists(0, $dataTimeData) && $command != "initial" && $command != "first" && $command != "note") //Check if arrays exist properly.
			{
				$timetime = new DateTime($dataTimeData[0]->dateEntered); //Create new time object based on time entry note.


				if ($timetime > $notetime) //If the time entry is newer than latest ticket note.
				{
					$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
					$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
					$notedate = $dataTimeData[0]->dateEntered;
				}
			}

			$date2 = strtotime($notedate);
			$date2format = date('m-d-Y g:i:sa', $date2);
		}
		else
		{
			$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
			$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
			$notedate = $dataTimeData[0]->dateEntered;

			$date2 = strtotime($notedate);
			$date2format = date('m-d-Y g:i:sa', $date2);
		}
	}
	else
	{
		$posttext=0;
	}
}

$date=strtotime($dataTData->dateEntered); //Convert date entered JSON result to time.
$dateformat=date('m-d-Y g:i:sa',$date); //Convert previously converted time to a better time string.
$return="Nothing!"; //Create return value and set to a basic message just in case.
$contact="None"; //Set None for contact in case no contact exists for "Catch All" tickets.
$resources="No resources"; //Just in case resources are null, have something to return.
$hours="No time entered."; //Just in case time is null, have something to return.

if($dataTData->actualHours != NULL) //If time is not NULL
{
	$hours="Time: ". $dataTData->actualHours . " Hours"; //Set $hours to a formatted time line.
}

if($dataTData->resources != NULL)
{
	$resources=$dataTData->resources;
}

if(!$dataTData->contact==NULL) { //Check if contact name exists in array.
	$contact = $dataTData->contact->name; //Set contact variable to contact name.
}

if($command == "initial" || $command == "first" || $command == "note")
{
	if($posttext==0)
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$return =array(
			"parse" => "full",
			"response_type" => "ephemeral",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Initial ticket note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				))
			);

	}
}
else if($command == "full" || $command == "notes" || $command == "all")
{
	if($posttext==0)
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$date3=strtotime($dataTNotes2[0]->dateCreated);
		$date3format=date('m-d-Y g:i:sa',$date3);
		$return =array(
			"parse" => "full",
			"response_type" => "ephemeral",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Latest Note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				),
				array(
					"pretext" => "Initial ticket note (" . $date3format  . ") from: " . $dataTNotes2[0]->createdBy,
					"text" =>  $dataTNotes2[0]->text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				))
			);

	}
}
else //If no command is set, or if it's just random gibberish after ticket number.
{
	if($posttext==0)
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Latest Note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				))
			);

	}
}

echo json_encode($return, JSON_PRETTY_PRINT); //Return properly encoded arrays in JSON for Slack parsing.
?>
