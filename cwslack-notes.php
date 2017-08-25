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

if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slacknotestoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_REQUEST['text'])) die("No text provided."); //If there is no text added, kill the connection.

$exploded = explode(" ",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.

//This section checks if the ticket number is not equal to 6 digits (our tickets are in the hundreds of thousands but not near a million yet) and kills the connection if it's not.
if(!is_numeric($exploded[0])) {
    //Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
    if ($exploded[0]=="help") {
        die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true))); //Encode a JSON response with a help URL.
    }
    else //Else close the connection.
    {
        echo "Unknown entry for ticket number.";
        return;
    }
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

$ticketnumber = $exploded[0]; //Set the ticket number to the first string
$command=NULL; //Create a command variable and set it to Null
$sentence=NULL; //Create a option variable and set it to Null


//Set URL
$noteurl = $connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $ticketnumber . "/notes";


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
$header_data = postHeader($companyname, $apipublickey, $apiprivatekey);

//Need to create array before hand to ensure no errors occur.
$dataTNotes = array();

$ch = curl_init();
$postfieldspre = NULL; //avoid errors.
if($command == "internal") //If second part of text is internal
{
    $postfieldspre = array("internalAnalysisFlag" => "True", "text" => $sentence); //Post ticket as API user
}
else if ($command == "external")//If second part of text is external
{
    $postfieldspre = array("detailDescriptionFlag" => "True", "text" => $sentence);
}
else if ($command == "externalemail" || $command == "emailexternal")//If second part of text is external
{
    $postfieldspre = array("detailDescriptionFlag" => "True", "processNotifications" => "True", "text" => $sentence);
}
else //If second part of text is neither external or internal
{
    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Second part of text must be either internal or external."));
    } else {
        die("Second part of text must be either internal or external."); //Return error text.
    }
    die();

}

//Username mapping code
if($usedatabase==1)
{
    $mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

    if (!$mysql) //Check for errors
    {
        if ($timeoutfix == true) { //This should NEVER happen.
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Connection Error: " . mysqli_connect_error()));
        } else {
            die("Connection Error: " . mysqli_connect_error()); //Post to slack
        }
        die();
    }

    $val1 = mysqli_real_escape_string($mysql,$_REQUEST["user_name"]);
    $sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $val1 . "\""; //SQL Query to select all ticket number entries

    $result = mysqli_query($mysql, $sql); //Run result
    $rowcount = mysqli_num_rows($result);
    if($rowcount > 1) //If there were too many rows matching query
    {
        if ($timeoutfix == true) { //This should NEVER happen.
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Error: too many users somehow?"));
        } else {
            die("Error: too many users somehow?"); //Post to slack
        }
        die();
    }
    else if ($rowcount == 1) //If exactly 1 row is found.
    {
        $row = mysqli_fetch_assoc($result); //Row association.

        $postfieldspre["member"] = array("identifier"=>$row["cwname"]); //Return the connectwise name of the row found as the CW member name.
    }
    else //If no rows are found
    {
        if($usecwname==1) //If variable enabled
        {
            $postfieldspre["member"] = array("identifier"=>$_REQUEST['user_name']); //Return the slack username as the user for the ticket note. If the user does not exist in CW, it will use the API username.
        }
    }
}
else
{
    if($usecwname==1)
    {
        $postfieldspre["member"] = array("identifier"=>$_REQUEST['user_name']);
    }
}

$dataTNotes = cURLPost($noteurl, $header_data, "POST", $postfieldspre);

if(array_key_exists("errors",$dataTNotes)) //If connectwise returned an error.
{
    $errors = $dataTNotes->errors; //Make array easier to access.

    if ($timeoutfix == true) { //Return CW error
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "ConnectWise Error: " . $errors[0]->message));
    } else {
        die("ConnectWise Error: " . $errors[0]->message); //Post to slack
    }
    die();
}
else //No error
{
    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "New " . $command . " note created on #" . $ticketnumber . ": " . $sentence));
    } else {
        echo "New " . $command . " note created on #" . $ticketnumber . ": " . $sentence; //Post to slack
    }
}

?>