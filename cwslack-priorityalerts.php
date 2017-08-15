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
$datenow = gmdate("Y-m-d\TH:i", strtotime("-" . $prioritywait . " minutes")); //Date set to 10 minutes prior to now, to catch for tickets happening right now.
$date2hours = gmdate("Y-m-d\TH:i", strtotime("-2 hours")); //Date set to 2 hours out so reminders up to 2 hours function.

$url = $connectwise. "/$connectwisebranch/apis/3.0/schedule/entries?conditions=dateStart%20%3E%20[" . $date2hours . "]%20and%20dateStart%20%3C%20[". $datenow . "]&orderBy=dateStart%20desc"; //URL to access the schedule API
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

$prioritystatuses = explode("|",$prioritystatus);
$priorities = explode("|",$prioritylist);

foreach($dataTData as $entry)
{
    $user = $entry->member->identifier;
    $username = $entry->member->name;
    $ticketnumber = $entry->objectId;

    $urlticketdata = $connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $ticketnumber; //Set ticket API url
    $entryTData = cURL($urlticketdata, $header_data); //Decode the JSON returned by the CW API.

    $summary = $entryTData->summary;
    $company = $entryTData->company->name;

    if($debugmode)
    {
        echo "\nDEBUG: Ticket #" . $ticketnumber . " of user " . $user;
    }

    if(!in_array($entryTData->priority->name,$priorities))
    {
        // Priority of found ticket is not one of the ones we're looking for, exit loop
        if($debugmode)
        {
            echo "\nDEBUG: Breaking at line 73, priority (" . $entryTData->priority->name . ") is not in the list: " . implode(", ", $priorities);
        }
        continue;
    }

    if(!in_array($entryTData->status->name,$prioritystatuses))
    {
        // Status of found ticket is not one of the ones we're looking for, exit loop
        if($debugmode)
        {
            echo "\nDEBUG: Breaking at line 73, status (" . $entryTData->status->name . ") is not in the list: " . implode(", ", $prioritystatuses);
        }
        continue;
    }

    if($debugmode)
    {
        echo "\nDEBUG: Passed status and priority validation";
    }

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

    $utc = new DateTimeZone("UTC");
    $realtz = new DateTimeZone($timezone);
    $datenow = new DateTime("now", $utc);
    $dateticket = new DateTime($entry->dateStart, $utc);
    $dateticket->setTimezone($realtz);
    $dateticketformat = $dateticket->format("g:i A");

    if($posttousers==1) //And user post is on.
    {
        $postfieldspre = array(
            "channel"=>"@".$user,
            "attachments"=>array(array(
                "fallback" => "Priority ticket with " . $company . " has been missed.",
                "title" => "<" . $ticketurl . $ticketnumber . "&companyName=" . $companyname . "|#" . $ticketnumber . ">: " . $summary,
                "pretext" => "Priority ticket missed.",
                "text" =>  "You have a " . $entryTData->priority->name . " priority ticket with " . $company . " that was scheduled for " . $dateticketformat . ". You should be calling the client now.",
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
                    "fallback" => "Priority ticket for " . $username . " with " . $company . " has been missed.",
                    "title" => "<" . $ticketurl . $ticketnumber . "&companyName=" . $companyname . "|#" . $ticketnumber . ">: " . $summary,
                    "pretext" => "Priority ticket missed.",
                    "text" =>  "Please remind the technician that they have a " . $entryTData->priority->name . " priority ticket with " . $company . " that was scheduled for " . $dateticketformat,
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
                    "fallback" => "Priority ticket for " . $username . " with " . $company . " has been missed.",
                    "title" => "<" . $ticketurl . $ticketnumber . "&companyName=" . $companyname . "|#" . $ticketnumber . ">: " . $summary,
                    "pretext" => "Priority ticket missed.",
                    "text" =>  "Please remind the technician that they have a " . $entryTData->priority->name . " priority ticket with " . $company . " that was scheduled for " . $dateticketformat,
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