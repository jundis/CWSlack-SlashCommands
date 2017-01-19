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

//Receive connector for Connectwise Callbacks
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php'; //Require the config file.
require_once 'functions.php';

if(strtotime("now") < strtotime($timebusinessstart) || strtotime("now") > strtotime($timebusinessclose))
{
    die("After hours");
}

//Set headers for cURL requests. $header_data covers API authentication while $header_data2 covers the Slack output.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);

$header_data2 =array(
    "Content-Type: application/json"
);


$datetoday = date("Y-m-d");
$timeurl = $connectwise . "/v4_6_release/apis/3.0/time/entries";
$filterurl = $timeurl . "?conditions=timeStart%20%3C%20[" . $datetoday . "T23:59:59Z]%20and%20timeStart%20%3E%20[" . $datetoday . "T00:00:00Z]&orderBy=dateEntered%20desc&pagesize=1000";

$data = cURL($filterurl, $header_data);

if ($data == NULL)
{
    die("No users have recorded time information for today.");
}

$timeset = array();
$users = array();

$expected = round((strtotime("now") - strtotime($timebusinessstart)) / 3600,2);

if ($expected > (round((strtotime($timebusinessclose) - strtotime($timebusinessstart)) / 3600,2)))
{
    $expected = round((strtotime($timebusinessclose) - strtotime($timebusinessstart)) / 3600,2);
}

foreach($data as $entry)
{
    $name = $entry->enteredBy;
    if(array_key_exists($name,$timeset))
    {
        $timeset[$entry->enteredBy]["totaltime"] = $timeset[$entry->enteredBy]["totaltime"] + $entry->actualHours;
    }
    else
    {
        $timeset[$entry->enteredBy] = null;
        $timeset[$entry->enteredBy]["totaltime"] = $entry->actualHours;
    }
}

$badcompanies = explode("|",$notimeusers);

foreach($timeset as $user => $val)
{
    if($expected - $val["totaltime"] >= 2 && !in_array($user,$notimeusers))
    {
        $users[] = $user;
    }
}

$users[] = "jundis";

foreach($users as $user)
{
    $missingtime = $expected - $timeset[$user]["totaltime"];

    if($posttousers==1)
    {
        $postfieldspre = array(
            "channel"=>"@".$user,
            "attachments"=>array(array(
                "fallback" => "Time is too far behind!.",
                "title" => "Current hours: " . $timeset[$user]["totaltime"],
                "pretext" => "Your time is over 2 hours behind at this point",
                "text" =>  "Please update your time immediately as you have " . $missingtime . " hours to make up.",
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
                    "fallback" => "Time is too far behind! for .",
                    "title" => "Current hours for " . $user . ": " . $timeset[$user]["totaltime"],
                    "pretext" => "Their time is over 2 hours behind at this point",
                    "text" =>  "Please have technician update their time immediately as they have " . $missingtime . " hours to make up.",
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
                "channel"=>$firmalertchan,
                "attachments"=>array(array(
                    "fallback" => "Time is too far behind! for .",
                    "title" => "Current hours for " . $user . ": " . $timeset[$user]["totaltime"],
                    "pretext" => "Their time is over 2 hours behind at this point",
                    "text" =>  "Please have technician update their time immeidately as they have " . $missingtime . " hours to make up.",
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

echo "OK";

?>