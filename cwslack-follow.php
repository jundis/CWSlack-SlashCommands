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

$link=0;

if(empty($_REQUEST['method']) || ($_REQUEST['method'] != $followtoken && $_REQUEST['method'] != $unfollowtoken)){
	if(empty($_REQUEST['token']) || $_REQUEST['token'] != $slackfollowtoken) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
	if(empty($_REQUEST['text'])) die("No text provided."); //If there is no text added, kill the connection.
	
	$exploded = explode(" ",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.
} else {
	$link=1;
}

$command=NULL; //Set a null command variable, so it has something set no matter what.

//Check for command errors.
if($link==0 && !is_numeric($exploded[0])) {
	//Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
	if ($exploded[0]=="help") {
		$test=json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true));
		echo $test;
		return;
	}
	else //Else close the connection.
	{
		echo "Unknown entry for ticket number.";
		return;
	}
}


if($link==0){
	$ticketnumber = $exploded[0]; //Read ticket number to variable for convenience.
	$username = $_REQUEST['user_name']; //Read Slack username to variable for convenience.

	if (array_key_exists(1,$exploded)) //If a second string exists in the slash command array, make it the command.
	{
		$command = $exploded[1];
	}
}
else
{
	$ticketnumber = $_REQUEST['srnumber'];
	$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase);
	if (!$mysql) //Check for errors
	{
		die("Connection Error: " . mysqli_connect_error());
	}

	$val1 = mysqli_real_escape_string($mysql,$_REQUEST['memberid']);
	$sql = "SELECT slackuser FROM usermap where cwname = '".$val1."'";

	$result = mysqli_query($mysql, $sql); //Run result
	// Check for mapping, otherwise use Connectwise
	if(mysqli_num_rows($result) > 0)
	{
		$user = mysqli_fetch_assoc($result);
		$username = $user['slackuser'];
	}
	else
	{
		$username = $_REQUEST['memberid'];
	}
	mysqli_close($mysql);

	if($_REQUEST['method']==$followtoken)
	{
		//For future use.
	}
	else if ($_REQUEST['method']==$unfollowtoken)
	{
		$command="unfollow"; //Set command to unfollow if it matches the CW unfollowtoken
	}
	else
	{
		die("Method does not match follow or unfollow tokens."); //If matches neither token, die.
	}
}

if($usedatabase==1)
{
	$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase);
	if (!$mysql)
	{
		die("Connection Error: " . mysqli_connect_error());
	}

	if ($command == "unfollow")
	{
		$val1 = mysqli_real_escape_string($mysql,$ticketnumber);
		$val2 = mysqli_real_escape_string($mysql,$username);
		$sql = "DELETE FROM `follow` WHERE `ticketnumber`=\"" . $val1 . "\" AND `slackuser`=\"" . $val2 . "\"";

		if(mysqli_query($mysql,$sql))
		{
			die("Successfully unfollowed ticket #".$ticketnumber);
		}
		else
		{
			die("MySQL Error: " . mysqli_error($mysql));
		}
	}
	else
	{
		$val1 = mysqli_real_escape_string($mysql,$ticketnumber);
		$val2 = mysqli_real_escape_string($mysql,$username);
		$sql = "INSERT INTO `follow` (`id`, `ticketnumber`, `slackuser`) VALUES (NULL, '" . $val1 . "', '" . $val2 . "');";
		if(mysqli_query($mysql,$sql))
		{
			die("Successfully followed ticket #".$ticketnumber);
		}
		else
		{
			die("MySQL Error: " . mysqli_error($mysql));
		}
	}
}
else
{
	die("Follow module requires MySQL to function.");
}



?>
