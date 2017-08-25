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

ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';
require_once 'functions.php';

if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slackdbmantoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_REQUEST['text'])) die("No text provided."); //If there is no text added, kill the connection.
$exploded = explode(" ",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.

$explodeadmins = explode("|", $adminlist); //Explode list of acceptable admins.
if(!in_array($_REQUEST["user_name"],$explodeadmins))
{
    die("You are not authorized to access this command. Only the following users can: " . implode(", ",$explodeadmins));
}

//Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
if ($exploded[0]=="help") {
    die("The following commands are available:\nlistmap - List all username mappings between CW and Slack\naddmap (slackname) (cwname) - Associate the two names\nremovemap (slackname) - Remove a mapping\nclearfollow confirm - Clears the follow database");
}

$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

if (!$mysql) //Check for errors
{
    die("Connection Error: " . mysqli_connect_error());
}

if ($exploded[0]=="listmap")
{
    $sql = "SELECT * FROM `usermap`"; //SQL Query to select all users

    $result = mysqli_query($mysql, $sql); //Run result
    $output = "List of username mappings:\n";
    if(mysqli_num_rows($result) > 0) //If there were too many rows matching query
    {
        while($row = mysqli_fetch_assoc($result))
        {
            $output = $output . "Slack: " . $row["slackuser"] . " | ConnectWise: " . $row["cwname"] . "\n";
        }
        die($output);
    }
    else
    {
        die("No user mappings found in database.");
    }
}
else if ($exploded[0]=="addmap")
{
    if (!array_key_exists(2,$exploded))
    {
        die("Error: Please ensure you're entering the following: addmap (slack name) (connectwise username)");
    }

    $val1 = mysqli_real_escape_string($mysql,$exploded[1]);
    $val2 = mysqli_real_escape_string($mysql,$exploded[2]);
    $sql = "INSERT INTO `usermap` (`slackuser`, `cwname`) VALUES ('" . $val1 . "', '" . $val2 . "');"; //SQL Query to insert new map

    if(mysqli_query($mysql,$sql))
    {
        die("Successfully added mapping for Slack User " . $exploded[1] . " to ConnectWise User " . $exploded[2]);
    }
    else
    {
        die("MySQL Error: " . mysqli_error($mysql));
    }
}
else if ($exploded[0]=="removemap")
{
    if (!array_key_exists(1,$exploded))
    {
        die("Error: Please ensure you're entering the following: removemap (slack name)");
    }

    $val1 = mysqli_real_escape_string($mysql,$exploded[1]);
    $sql = "DELETE FROM .`usermap` WHERE `usermap`.`slackuser` = '" . $val1 . "';"; //SQL Query to remove map

    if(mysqli_query($mysql,$sql))
    {
        die("Successfully removed mapping for Slack User " . $exploded[1]);
    }
    else
    {
        die("MySQL Error: " . mysqli_error($mysql));
    }
}
else if ($exploded[0]=="clearfollow")
{
    if (!array_key_exists(1,$exploded) || $exploded[1]!="confirm")
    {
        die("Error: Please ensure you're confirming the command by entering: clearfollow confirm");
    }
    $sql = "TRUNCATE follow"; //SQL Query to remove map

    if(mysqli_query($mysql,$sql))
    {
        die("Successfully cleared follow table.");
    }
    else
    {
        die("MySQL Error: " . mysqli_error($mysql));
    }
}

?>