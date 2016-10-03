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

if(empty($_GET['token']) || ($_GET['token'] != $slackconfigstoken)) die; //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die; //If there is no text added, kill the connection.

$apicompanyname = strtolower($companyname); //Company name all lower case for api auth.
$authorization = base64_encode($apicompanyname . "+" . $apipublickey . ":" . $apiprivatekey); //Encode the API, needed for authorization.
$exploded = explode("|",$_GET['text']); //Explode the string attached to the slash command for use in variables.

//Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
if ($exploded[0]=="help") {
    $test=json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true)); //Encode a JSON response with a help URL.
    echo $test; //Return the JSON
    return; //Kill the connection.
}

$company=NULL; //Just in case
$config=NULL; //Just in case

if (array_key_exists(1,$exploded)) //If two parts of the array exists
{
    $company = $exploded[0]; //Set the first portion to company name
    $config = $exploded[1]; //Set the second portion to config name

    $url = $connectwise . "/v4_6_release/apis/3.0/company/configurations?conditions=status/name=%27active%27%20and%20company/name%20contains%20%27" . $company . "%27%20and%20name%20contains%20%27" . $config . "%27&pagesize=1";
}
else //If 2 parts don't exist
{
    $config=$exploded[0];

    $url = $connectwise . "/v4_6_release/apis/3.0/company/configurations?conditions=status/name=%27active%27%20and%20name%20contains%20%27" . $config . "%27&pagesize=1";
}


$url = str_replace(' ', '%20', $url);; //Encode URL to prevent errors with spaces.

$utc = time(); //Get the time.
// Authorization array. Auto encodes API key for auhtorization above.
$header_data =array(
    "Authorization: Basic ". $authorization,
);

//Need to create array before hand to ensure no errors occur.
$dataTData = array();

//-
//cURL connection to ConnectWise to pull Company API.
//-
$ch = curl_init(); //Initiate a curl session_cache_expire

//Create curl array to set the API url, headers, and necessary flags.
$curlOpts = array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $header_data,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => 1,
);
curl_setopt_array($ch, $curlOpts); //Set the curl array to $curlOpts

$answerTData = curl_exec($ch); //Set $answerTData to the curl response to the API.
$headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);  //Get the header length of the curl response
$curlBodyTData = substr($answerTData, $headerLen); //Remove header data from the curl string.

// If there was an error, show it
if (curl_error($ch)) {
    die(curl_error($ch));
}
curl_close($ch);

$dataTData = json_decode($curlBodyTData); //Decode the JSON returned by the CW API.

//Error handling
if(array_key_exists("errors",$dataTData)) //If connectwise returned an error.
{
    $errors = $dataTData->errors; //Make array easier to access.

    echo "ConnectWise Error: " . $errors[0]->message; //Return CW error
    die; //Kill connection
}
if($dataTData==NULL) //If no contact is returned or your API URL is incorrect.
{
    echo "No configuration found."; //Return error.
    die; //Kill the connection.
}

$return="Nothing!"; //Create return value and set to a basic message just in case.
$conf = $dataTData[0]; //Shortcut to item.
$questions = $conf->questions; //Array of questions
$notes = "None"; //Just in case
$vendornotes = "None"; //Just in case
$answers = ""; //Nothing just in case

if($questions!=NULL)
{
	foreach($questions as $q) //For each item in the Question array
	{
		if($q->answer!=NULL) //If the answer exists and is not just blank and useless.
		{
			if(strpos($q->question,":") != false) //If question contains a colon.
			{
				$answers = $answers . $q->question . " " . $q->answer . "\n"; //Return the question, answer, and new line.
			}
			else //Else, add a colon.
			{
				$answers = $answers . $q->question . ": " . $q->answer . "\n"; //Return the question, answer, and new line.
			}
		}
	}
}
else
{
	$answers="None";
}

if($conf->notes!=NULL) //If notes are not null
{
    $notes = $conf->notes; //Set $notes to the config notes
}
if($conf->vendorNotes!=NULL) //If vendornotes are not null
{
    $vendornotes = $conf->vendorNotes; //Set $vendornotes to the config vendor notes.
}

$return =array(
    "parse" => "full", //Parse all text.
    "response_type" => "in_channel", //Send the response in the channel
    "attachments"=>array(array(
        "fallback" => "Configuration Info for " . $conf->company->identifier . "\\" . $conf->name, //Fallback for notifications
        "title" => "Configuration: <". $connectwise . "/v4_6_release/services/system_io/router/openrecord.rails?locale=en_US&recordType=ConfigFv&recid=" . $conf->id . "|" . $conf->name . ">", //Set bolded title text
        "pretext" => "Configuration for:  " . $conf->company->identifier, //Set pretext
        "text" => "*_Notes_*:\n" . $notes . "\n*_Vendor Notes_*:\n" . $vendornotes . "\n*_Questions_*:\n" . $answers,//Set text to be returned
        "mrkdwn_in" => array( //Set markdown values
            "text",
            "pretext"
        )
    ))
);


echo json_encode($return, JSON_PRETTY_PRINT); //Return properly encoded arrays in JSON for Slack parsing.

?>