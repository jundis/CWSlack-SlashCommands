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

ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';

$link=0;

if(empty($_GET['method']) || ($_GET['method'] != $followtoken && $_GET['method'] != $unfollowtoken)){
	if(empty($_GET['token']) || $_GET['token'] != $slackfollowtoken) die; //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
	if(empty($_GET['text'])) die; //If there is no text added, kill the connection.
	
	$exploded = explode(" ",$_GET['text']); //Explode the string attached to the slash command for use in variables.
} else {
	$link=1;
}

//File Handling block
if(file_exists($dir."storage.txt")) //Check if storage file exists.
{
	$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES); //If so, open it.
}
else
{
	$f = fopen($dir."storage.txt", 'w') or die("can't open file"); //If not, create it.
	fclose($f); //Close newly created file.
	$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES); //Open it again for reading.
}

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
	}; 
}

$command=NULL; //Set a null command variable, so it has something set no matter what.

if($link==0){
	$ticketnumber = $exploded[0]; //Read ticket number to variable for convenience.
	$username = $_GET['user_name']; //Read Slack username to variable for convenience.

	if (array_key_exists(1,$exploded)) //If a second string exists in the slash command array, make it the command.
	{
		$command = $exploded[1];
	}
} 
else 
{
	$ticketnumber = $_GET['srnumber'];
	$username = $_GET['memberid'];
	if($_GET['method']==$followtoken)
	{
		//For future use.
	} 
	else if ($_GET['method']==$unfollowtoken)
	{
		$command="unfollow"; //Set command to unfollow if it matches the CW unfollowtoken
	}
	else
	{
		die; //If matches neither token, die.
	}
}

if($command=="unfollow") //If unfollow is set in the text received from Slack.
{
	$lines = explode("\n",$file); //Explode the file into each line

	foreach($lines as $line) //For each line in the file...
	{
		$tempex = explode("^",$line); //Explode the line into parts based on character set by this file's output.

		if($tempex[0]!=$ticketnumber) //If the first part of the line is not the ticket number
		{
			$output[] = $line; //Output the line to the file again.
		}
		else //If it is not
		{
			if($tempex[1]!=$username) //If the second part is not the username of sender.
			{
				$output[]=$line; //Output the line to the file again.
			}
			else //If the ticket number and username match.
			{
				//Do not output this line.
			}
		}
	}
	echo "Unfollowed ticket #" .$ticketnumber; //Return text to Slack
	$out = implode("\n",$output); //Implode all lines.
	file_put_contents($dir."/storage.txt",$out); //Output to file again, excluding the line unfollowed.
}
else //If no command.
{
	file_put_contents($dir."/storage.txt","\n".$ticketnumber."^".$username,FILE_APPEND); //Take the ticket number and the username of the person who submitted it and output to storage file, seperated by ^ sign.
	echo "Now following ticket #" . $ticketnumber; //Return text to Slack notifying of follow.
}


?>
