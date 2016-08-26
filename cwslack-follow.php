<?php
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';

if(empty($_GET['token']) || ($_GET['token'] != $slackfollowtoken)) die; //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die; //If there is no text added, kill the connection.

if(file_exists($dir."storage.txt"))
{
	$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES);
}
else
{
	$f = fopen($dir."storage.txt") or die("can't open file");
	fclose($f);
	$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES);
}
$exploded = explode(" ",$_GET['text']); //Explode the string attached to the slash command for use in variables.

if(!is_numeric($exploded[0])) {
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

$ticketnumber = $exploded[0];
$username = $_GET['user_name'];
$command=NULL;

if (array_key_exists(1,$exploded)) //If a second string exists in the slash command array, make it the command.
{
	$command = $exploded[1];
}

if($command=="unfollow")
{
	$lines = explode("\n",$file);

	foreach($lines as $line)
	{
		$tempex = explode("^",$line);

		if($tempex[0]!=$ticketnumber)
		{
			$output[] = $line;
		}
		else
		{
			if($tempex[1]!=$username)
			{
				$output[]=$line;
			}
			else
			{
				echo "Unfollowed ticket #" .$ticketnumber;
			}
		}
	}
	$out = implode("\n",$output);
	file_put_contents($dir."/storage.txt",$out);
}
else
{
	file_put_contents($dir."/storage.txt","\n".$ticketnumber."^".$username,FILE_APPEND);
	echo "Now following ticket #" . $ticketnumber;
}


?>