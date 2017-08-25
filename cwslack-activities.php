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

// Authorization array. Auto encodes API key for auhtorization above.
$header_data = postHeader($companyname, $apipublickey, $apiprivatekey);

if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slackactivitiestoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_REQUEST['text'])) die("No text provided."); //If there is no text added, kill the connection.
$exploded = explode("|",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.

//Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
if ($exploded[0]=="help") {
    die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true))); //Encode a JSON response with a help URL.
}

//Timeout Fix Block
if($timeoutfix == true)
{
    ob_end_clean();
    header("Connection: close");
    ob_start();
    echo ('{"response_type": "in_channel"}');
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
    session_write_close();
    if($sendtimeoutwait==true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral", "text" => "Please wait..."));
    }
}
//End timeout fix block

$urlactivities = $connectwise . "/$connectwisebranch/apis/3.0/sales/activities/";
$activityurl = $connectwise . '/$connectwisebranch/ConnectWise.aspx?fullscreen=false&locale=en_US#startscreen=activity_detail&state={"p":"activity_detail", "s":{"p":{"pid":3, "rd":';
$activityurl2 = ' ,"compId":0, "contId":0, "oppid":0}}}';

$command=NULL; //Create a command variable and set it to Null
if (array_key_exists(0,$exploded)) //If a string exists in the slash command array, make it the command.
{
	$command = $exploded[0];
}

//Need to create array before hand to ensure no errors occur.
$dataResponse = array();

if($command=="new") {
    $dataResponse = cURLPost(
        $urlactivities,
        $header_data,
        "POST",
        array("name"=>$exploded[1],"status"=>array("id"=>1),"assignTo"=>array("identifier"=>$exploded[2])));
}

$return="Unknown command.";
if($command == "new") //If command is new.
{
	$return =array(
		"parse" => "full", //Parse all text.
		"response_type" => "in_channel", //Send the response in the channel
		"attachments"=>array(array(
			"fallback" => "New Activity Created: " . $dataResponse->name, //Fallback for notifications
			"title" => "New Activity Created: " . $dataResponse->name, //Set bolded title text
			"pretext" => "Activity #" . $dataResponse->id . " has been created and assigned to " . $exploded[2], //Set pretext
			"text" => "Click <" . $activityurl . $dataResponse -> id . $activityurl2 . "|here> to open the activity.", //Set text to be returned
			"mrkdwn_in" => array( //Set markdown values
				"text",
				"pretext"
				)
			))
		);
}
else
{
    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => $return));
    } else {
        die($return); //Post to slack
    }
	die();
}
if ($timeoutfix == true) {
    cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", $return);
} else {
    die(json_encode($return, JSON_PRETTY_PRINT)); //Return properly encoded arrays in JSON for Slack parsing.
}
?>