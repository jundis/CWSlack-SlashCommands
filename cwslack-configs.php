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
require_once 'config.php'; //Require config
require_once 'functions.php'; //Require functions

if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slackconfigstoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
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

$company=NULL; //Just in case
$config=NULL; //Just in case

if (array_key_exists(1,$exploded)) //If two parts of the array exists
{
    $company = $exploded[0]; //Set the first portion to company name
    $config = $exploded[1]; //Set the second portion to config name

    $url = $connectwise . "/$connectwisebranch/apis/3.0/company/configurations?conditions=status/name=%27active%27%20and%20company/name%20contains%20%27" . $company . "%27%20and%20name%20contains%20%27" . $config . "%27&pagesize=1";
}
else //If 2 parts don't exist
{
    $config=$exploded[0];

    $url = $connectwise . "/$connectwisebranch/apis/3.0/company/configurations?conditions=status/name=%27active%27%20and%20name%20contains%20%27" . $config . "%27&pagesize=1";
}


$url = str_replace(' ', '%20', $url); //Encode URL to prevent errors with spaces.

// Authorization array. Auto encodes API key for auhtorization.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);

//Need to create array before hand to ensure no errors occur.
$dataTData = array();

//-
//cURL connection to ConnectWise to pull Company API.
//-
$dataTData = cURL($url, $header_data); // Get the JSON returned by the CW API.

//Error handling
if($dataTData==NULL) //If no contact is returned or your API URL is incorrect.
{
    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "No configuration found."));
    } else {
        die("No configuration found."); //Return properly encoded arrays in JSON for Slack parsing.
    }
    die();
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
		    if(strpos($q->question,"Password") !== false && $hidepasswords == 1) //If question contains "Password".
            {

                if (strpos($q->question, ":") !== false) //If question contains a colon.
                {
                    $answers = $answers . $q->question . " Hidden, please view in CW\n"; //Return the question, answer, and new line.
                }
                else //Else, add a colon.
                {
                    $answers = $answers . $q->question . ": Hidden, please view in CW\n"; //Return the question, answer, and new line.
                }
            }
            else
            {
                if (strpos($q->question, ":") !== false) //If question contains a colon.
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
        "title" => "Configuration: <". $connectwise . "/$connectwisebranch/services/system_io/router/openrecord.rails?locale=en_US&recordType=ConfigFv&recid=" . $conf->id . "|" . $conf->name . ">", //Set bolded title text
        "pretext" => "Configuration for:  " . $conf->company->identifier, //Set pretext
        "text" => "*_Notes_*:\n" . $notes . "\n*_Vendor Notes_*:\n" . $vendornotes . "\n*_Questions_*:\n" . $answers,//Set text to be returned
        "mrkdwn_in" => array( //Set markdown values
            "text",
            "pretext"
        )
    ))
);

if ($timeoutfix == true) {
    cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", $return);
} else {
    die(json_encode($return, JSON_PRETTY_PRINT)); //Return properly encoded arrays in JSON for Slack parsing.
}

?>