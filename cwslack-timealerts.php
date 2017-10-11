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

//Set headers for cURL requests. $header_data covers API authentication while $header_data2 covers the Slack output.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);

$header_data2 =array(
    "Content-Type: application/json"
);


$datetoday = date("Y-m-d");
$timeurl = $connectwise . "/$connectwisebranch/apis/3.0/time/entries";
$filterurl = $timeurl . "?conditions=timeStart%20%3C%20[" . $datetoday . "T23:59:59Z]%20and%20timeStart%20%3E%20[" . $datetoday . "T00:00:00Z]&orderBy=dateEntered%20desc&pagesize=1000";

$data = cURL($filterurl, $header_data);

if ($data == NULL)
{
    die("No users have recorded time information for today.");
}

$timeset = array();
$users = array();

foreach($data as $entry)
{
    $name = $entry->member->identifier;
    if(array_key_exists($name,$timeset))
    {
        $timeset[$entry->member->identifier]["totaltime"] = $timeset[$entry->member->identifier]["totaltime"] + $entry->actualHours;
    }
    else
    {
        $timeset[$entry->member->identifier] = null;
        $timeset[$entry->member->identifier]["totaltime"] = $entry->actualHours;
    }
}

$blockedtime = explode("|",$notimeusers);
$specialusers = array();
foreach(explode("|",$specialtimeusers) as $user)
{
    $tempval = explode(",", $user);
    $specialusers[strtolower($tempval[0])] = $tempval[1];
}
foreach($timeset as $user => $val)
{

    if(array_key_exists(strtolower($user),$specialusers))
    {
        $specialtimes = explode("-",$specialusers[$user]);
        $expectedtime = round((strtotime("now") - strtotime($specialtimes[0])) / 3600,2);
        if ($expectedtime > (round((strtotime($specialtimes[1]) - strtotime($specialtimes[0])) / 3600,2)))
        {
            $expectedtime = round((strtotime($specialtimes[1]) - strtotime($specialtimes[0])) / 3600,2);
        }
    }
    else
    {
        $expectedtime = round((strtotime("now") - strtotime($timebusinessstart)) / 3600,2);

        if ($expectedtime > (round((strtotime($timebusinessclose) - strtotime($timebusinessstart)) / 3600,2)))
        {
            $expectedtime = round((strtotime($timebusinessclose) - strtotime($timebusinessstart)) / 3600,2);
        }
    }

    if($expectedtime - $val["totaltime"] >= 2 && !in_array(strtolower($user),array_map("strtolower",$blockedtime)))
    {
        $username = $user;
        //Username mapping code
        if($usedatabase==1)
        {
            $mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

            if (!$mysql) //Check for errors
            {
                die("Connection Error: " . mysqli_connect_error()); //Return error
            }

            $val1 = mysqli_real_escape_string($mysql,$username);
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

                $username = $row["slackuser"]; //Return the slack username portion of the found row as the $user variable to be used as part of the notification.
            }
            //If no rows are found here, then it just uses whatever if found as $user previously from the ticket.
        }
        $users[$username] = $user;
    }
}

foreach($users as $user => $val)
{
    $ontheclock = true;

    if(array_key_exists(strtolower($user),$specialusers))
    {
        $specialtimes = explode("-",$specialusers[$user]);
        $expectedtime = round((strtotime("now") - strtotime($specialtimes[0])) / 3600,2);

        if ($expectedtime > (round((strtotime($specialtimes[1]) - strtotime($specialtimes[0])) / 3600,2)))
        {
            $expectedtime = round((strtotime($specialtimes[1]) - strtotime($specialtimes[0])) / 3600,2);
        }

        if(strtotime("now") < strtotime($specialtimes[0]) || strtotime("now") > strtotime($specialtimes[1]))
        {
            $ontheclock = false;
        }
    }
    else
    {
        $expectedtime = round((strtotime("now") - strtotime($timebusinessstart)) / 3600,2);

        if ($expectedtime > (round((strtotime($timebusinessclose) - strtotime($timebusinessstart)) / 3600,2)))
        {
            $expectedtime = round((strtotime($timebusinessclose) - strtotime($timebusinessstart)) / 3600,2);
        }

        if(strtotime("now") < strtotime($timebusinessstart) || strtotime("now") > strtotime($timebusinessclose))
        {
            $ontheclock = false;
        }
    }

    $missingtime = $expectedtime - $timeset[$val]["totaltime"];

    if($posttousers==1)
    {
        $postfieldspre = array(
            "channel"=>"@".$user,
            "attachments"=>array(array(
                "fallback" => "Time is too far behind!",
                "title" => "Current hours: " . $timeset[$val]["totaltime"],
                "pretext" => "Your time is over 2 hours behind at this point",
                "text" =>  "Please update your time immediately as you have " . $missingtime . " hours to make up.",
                "mrkdwn_in" => array(
                    "text",
                    "pretext"
                )
            ))
        );

        if($ontheclock)
        {
            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
        }
    }

    if($posttochan==1) //If channel post is on
    {
        if($usetimechan==1)
        {
            $postfieldspre = array(
                "channel"=>$timechan, //Post to channel set in config.php
                "attachments"=>array(array(
                    "fallback" => "Time is too far behind for " . $user,
                    "title" => "Current hours for " . $val . ": " . $timeset[$val]["totaltime"],
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
                    "fallback" => "Time is too far behind for " . $user,
                    "title" => "Current hours for " . $val . ": " . $timeset[$val]["totaltime"],
                    "pretext" => "Their time is over 2 hours behind at this point",
                    "text" =>  "Please have technician update their time immediately as they have " . $missingtime . " hours to make up.",
                    "mrkdwn_in" => array(
                        "text",
                        "pretext"
                    )
                ))
            );
        }

        if($ontheclock)
        {
            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
        }
    }

}

echo "OK";

?>