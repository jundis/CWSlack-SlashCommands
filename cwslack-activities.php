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

$apicompanyname = strtolower($companyname); //Company name all lower case for api auth. 
$authorization = base64_encode($apicompanyname . "+" . $apipublickey . ":" . $apiprivatekey); //Encode the API, needed for authorization.

if(empty($_GET['token']) || ($_GET['token'] != $slackactivitiestoken)) die; //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die; //If there is no text added, kill the connection.
$exploded = explode("|",$_GET['text']); //Explode the string attached to the slash command for use in variables.

$urlactivities = $connectwise . "/v4_6_release/apis/3.0/sales/activities/";
$activityurl = $connectwise . '/v4_6_release/ConnectWise.aspx?fullscreen=false&locale=en_US#startscreen=activity_detail&state={"p":"activity_detail", "s":{"p":{"pid":3, "rd": ';
$activityurl2 = ' ,"compId":0, "contId":0, "oppid":0}}}';

$utc = time(); //Get the time.
// Authorization array. Auto encodes API key for auhtorization above.
$header_data =array(
 "Authorization: Basic ". $authorization,
);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 =array(
"Authorization: Basic " . $authorization,
 "Content-Type: application/json"
);

$command=NULL; //Create a command variable and set it to Null
if (array_key_exists(0,$exploded)) //If a string exists in the slash command array, make it the command.
{
	$command = $exploded[0];
}

//Need to create array before hand to ensure no errors occur.
$dataResponse = array();

if($command=="new") { 
$ch = curl_init();

$postfieldspre = array("name"=>$exploded[1],"status"=>array("id"=>1),"assignTo"=>array("identifier"=>$exploded[2])); //Command array to post activity
$postfields = json_encode($postfieldspre); //Format the array as JSON

$curlOpts = array(
	CURLOPT_URL => $urlactivities,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HTTPHEADER => $header_data2,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_CUSTOMREQUEST => "POST",
	CURLOPT_POSTFIELDS => $postfields,
	CURLOPT_POST => 1,
	CURLOPT_HEADER => 1,
);
curl_setopt_array($ch, $curlOpts);

$answerResponse = curl_exec($ch);
$headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE); 
$curlResponse = substr($answerResponse, $headerLen);
// If there was an error, show it
if (curl_error($ch)) {
	die(curl_error($ch));
}
curl_close($ch);
$dataResponse = json_decode($curlResponse);
}

if(array_key_exists("code",$dataResponse)) { //Check if array contains error code
	if($dataResponse->code == "NotFound") { //If error code is NotFound
		echo "This should never occur..."; //Report that the ticket was not found.
		return;
	}
	if($dataResponse->code == "Unauthorized") { //If error code is an authorization error
		echo "401 Unauthorized, check API key to ensure it is valid."; //Fail case.
		return;
	}
	else {
		echo "Unknown Error Occurred, check API key and other API settings. Error: " . $dataResponse->code; //Fail case.
		return;
	}
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
	echo $return;
	return;
}
echo json_encode($return);
?>