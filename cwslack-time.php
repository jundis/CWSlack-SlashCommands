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
require_once 'functions.php';

if(empty($_GET['token']) || ($_GET['token'] != $slacktimetoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die("No text provided."); //If there is no text added, kill the connection.

$exploded = explode(" ",$_GET['text']); //Explode the string attached to the slash command for use in variables.

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
$ticketnumber = $exploded[0]; //Set the ticket number to the first string
$command=NULL; //Create a command variable and set it to Null
$howlong=NULL; //Create a variable for time length and set to Null
$sentence=NULL; //Create a option variable and set it to Null


//Set URL
$timeurl = $connectwise . "/v4_6_release/apis/3.0/time/entries";
$urlticketdata = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber; //Set ticket API url

//Dates
$datenow = gmdate("Y-m-d\TH:i:s\Z"); //Date as GMT based time.
$datestart = NULL;

if (array_key_exists(1, $exploded)) //If a second string exists in the slash command array, make it the command.
{
    $command = $exploded[1];
    if (array_key_exists(2, $exploded))
    {
        if(strpos($exploded[2],"m")!==false && strpos($exploded[2],"h")==false)
        {
            $datestart = gmdate("Y-m-d\TH:i:s\Z",strtotime("-" . preg_replace('/[^0-9,.]/', "", $exploded[2]) . " minutes")); //Start time of the ticket.
        }
        else if(strpos($exploded[2],"h")!==false && strpos($exploded[2],"m")==false)
        {
            $datestart = gmdate("Y-m-d\TH:i:s\Z",strtotime("-" . preg_replace('/[^0-9,.]/', "", $exploded[2]) . " hours")); //Start time of the ticket.
        }
        else
        {
            die("Time entry does not work. Please only use a number then h or m to indicate hours or minutes. E.x. 5m or 1.5h are valid, 1hour5minutes is not");
        }
    }
    else
    {
        die("No time given.");
    }
    if (array_key_exists(3, $exploded)) //If a third string exists in the slash command array, make it the option for the command.
    {
        unset($exploded[0]);
        unset($exploded[1]);
        unset($exploded[2]);
        $sentence = implode(" ", $exploded);
    }
    else
    {
        die("No sentence given for note.");
    }
}
else
{
    die("No command given.");
}

// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data = postHeader($companyname, $apipublickey, $apiprivatekey);

//Need to create array before hand to ensure no errors occur.
$dataTNotes = array();

$ch = curl_init();
$postfieldspre = NULL; //avoid errors.
if($command == "internal" || $command == "i") //If second part of text is internal
{
    $postfieldspre = array("addToInternalAnalysisFlag" => "True", "notes" => $sentence, "chargeToType" => "ServiceTicket", "chargeToId" => $ticketnumber, "timeStart" => $datestart, "timeEnd" => $datenow, "billableOption" => "DoNotBill", "workType" => array("name" => $timeinternalworktype)); //Post ticket as API user
}
else if ($command == "external" || $command == "detail" || $command == "detailed" || $command == "d")//If second part of text is detail
{
    $postfieldspre = array("addToDetailDescriptionFlag" => "True", "notes" => $sentence, "chargeToType" => "ServiceTicket", "chargeToId" => $ticketnumber, "timeStart" => $datestart, "timeEnd" => $datenow, "billableOption" => "Billable", "workType" => array("name" => $timedetailworktype)); //Post ticket as API user
}
else if ($command == "resolution" || $command == "resolved" || $command == "r")//If second part of text is resolution
{
    $postfieldspre = array("addToResolutionFlag" => "True", "notes" => $sentence, "chargeToType" => "ServiceTicket", "chargeToId" => $ticketnumber, "timeStart" => $datestart, "timeEnd" => $datenow, "billableOption" => "Billable", "workType" => array("name" => $timeresolutionworktype)); //Post ticket as API user
}
else //If second part of text is neither external or internal
{
    die("Second part of text must be either internal, detail, or resolution (d/i/r also accepted)."); //Return error text.
}

//Username mapping code
if($usedatabase==1)
{
    $mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

    if (!$mysql) //Check for errors
    {
        die("Connection Error: " . mysqli_connect_error());
    }

    $sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $_GET["user_name"] . "\""; //SQL Query to select all ticket number entries

    $result = mysqli_query($mysql, $sql); //Run result
    $rowcount = mysqli_num_rows($result);
    if($rowcount > 1) //If there were too many rows matching query
    {
        die("Error: too many users somehow?"); //This should NEVER happen.
    }
    else if ($rowcount == 1) //If exactly 1 row is found.
    {
        $row = mysqli_fetch_assoc($result); //Row association.

        $postfieldspre["member"] = array("identifier"=>$row["cwname"]); //Return the connectwise name of the row found as the CW member name.
        $postfieldspre["enteredBy"] = $row["cwname"];
    }
    else //If no rows are found
    {
        if($usecwname==1) //If variable enabled
        {
            $postfieldspre["member"] = array("identifier"=>$_GET['user_name']); //Return the slack username as the user for the ticket note. If the user does not exist in CW, it will use the API username.
            $postfieldspre["enteredBy"] = $_GET['user_name'];
        }
    }
}
else
{
    if($usecwname==1)
    {
        $postfieldspre["member"] = array("identifier"=>$_GET['user_name']);
        $postfieldspre["enteredBy"] = $_GET['user_name'];
    }
}

$dataTNotes = cURLPost($timeurl, $header_data, "POST", $postfieldspre);

if(array_key_exists("errors",$dataTNotes)) //If connectwise returned an error.
{
    $errors = $dataTNotes->errors; //Make array easier to access.

    die("ConnectWise Error: " . $errors[0]->message); //Return CW error
}
else //No error
{
    echo "New " . $command . " time entry created on #" . $ticketnumber . ": " . $sentence; //Return new ticket posted message.
}

?>