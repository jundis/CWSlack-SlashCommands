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

if(empty($_GET['token']) || ($_GET['token'] != $slacknotestoken)) die; //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die; //If there is no text added, kill the connection.

$apicompanyname = strtolower($companyname); //Company name all lower case for api auth.
$authorization = base64_encode($apicompanyname . "+" . $apipublickey . ":" . $apiprivatekey); //Encode the API, needed for authorization.
$exploded = explode(" ",$_GET['text']); //Explode the string attached to the slash command for use in variables.

//This section checks if the ticket number is not equal to 6 digits (our tickets are in the hundreds of thousands but not near a million yet) and kills the connection if it's not.
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
$ticketnumber = $exploded[0]; //Set the ticket number to the first string
$command=NULL; //Create a command variable and set it to Null
$sentence=NULL; //Create a option variable and set it to Null


//Set URL
$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes";


if (array_key_exists(1, $exploded)) //If a second string exists in the slash command array, make it the command.
{
    $command = $exploded[1];
    if (array_key_exists(2, $exploded)) //If a third string exists in the slash command array, make it the option for the command.
    {
        unset($exploded[0]);
        unset($exploded[1]);
        $sentence = implode(" ", $exploded);
    }
}

// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data =array(
    "Authorization: Basic " . $authorization,
    "Content-Type: application/json"
);

//Need to create array before hand to ensure no errors occur.
$dataTNotes = array();

$ch = curl_init();
$postfieldspre = NULL; //avoid errors.
if($command == "internal")
{
    $postfieldspre = array("ServiceNote" => array("internalAnalysisFlag" => "True", "text" => $sentence));
}
else if ($command == "external")
{
    $postfieldspre = array("ServiceNote" => array("detailDescriptionFlag" => "True", "externalFlag" => "True", "text" => $sentence));
}
else
{
    echo "Second part of text must be either internal or external.";
}
$postfields = json_encode($postfieldspre); //Format the array as JSON
var_dump($postfields);
//Same as previous curl array but includes required information for PATCH commands.
$curlOpts = array(
    CURLOPT_URL => $noteurl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => $header_data,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POSTFIELDS => $postfields,
    CURLOPT_POST => 1,
    CURLOPT_HEADER => 1,
);
curl_setopt_array($ch, $curlOpts);

$answerTCmd = curl_exec($ch);
$headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$curlBodyTCmd = substr($answerTCmd, $headerLen);
// If there was an error, show it
if (curl_error($ch)) {
    die(curl_error($ch));
}
curl_close($ch);
$dataTNotes = json_decode($curlBodyTCmd);

var_dump($dataTNotes);

?>