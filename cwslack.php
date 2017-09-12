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

// This is a development branch, please use with caution as things will frequently be changing.

ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';
require_once 'functions.php';

// Authorization array. Auto encodes API key for auhtorization above.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 = postHeader($companyname, $apipublickey, $apiprivatekey);

if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slacktoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_REQUEST['text'])) die("No text provided."); //If there is no text added, kill the connection.
$exploded = explode(" ",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.

//This section checks if the ticket number is not equal to 6 digits (our tickets are in the hundreds of thousands but not near a million yet) and kills the connection if it's not.
if(!is_numeric($exploded[0])) {
	//Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
	if ($exploded[0]=="help") {
		die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true)));
	}
	if ($exploded[0]=="new")
	{
		// Do nothing
	}
	else //Else close the connection.
	{
		die("Unknown entry for ticket number.");
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
$option3=NULL; //Create a option variable and set it to Null
if (array_key_exists(1,$exploded)) //If a second string exists in the slash command array, make it the command.
{
	$command = $exploded[1];
}
if (array_key_exists(2,$exploded)) //If a third string exists in the slash command array, make it the option for the command.
{
	$option3 = $exploded[2];
}
//Set URLs
$urlticketdata = $connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $ticketnumber; //Set ticket API url
$ticketurl = $connectwise . "/$connectwisebranch/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Ticket URL for connectwise.
$timeurl = $connectwise . "/$connectwisebranch/apis/3.0/time/entries?conditions=chargeToId=" . $ticketnumber . "&chargeToType=%27ServiceTicket%27&orderBy=dateEntered%20desc"; //Set the URL required for cURL requests to the time entry API.
if($command == "initial" || $command == "first" || $command == "note") //Set noteurl to use ascending if an initial note command is passed, else use descending.
{
	$noteurl = $connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20asc";
}
else
{
	$noteurl = $connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20desc";
}

//Need to create 3 arrays before hand to ensure no errors occur.
$dataTNotes = array();
$dataTData = array();
$dataTCmd = array();

if (strpos(strtolower($exploded[0]), "new") !== false)
{
	unset($exploded[0]);
	$exploded = implode(" ", $exploded);
	$ticketstuff = explode("|",$exploded);

	if($useboards == 1)
	{
		if(!array_key_exists(2, $ticketstuff)) {
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Not enough values specified. Please use /t new board|company|summary"));
			} else {
				die("Not enough values specified. Please use /t new board|company|summary"); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}
		$companyurl = $connectwise . "/$connectwisebranch/apis/3.0/company/companies?conditions=name%20contains%20%27" . urlencode($ticketstuff[1]) . "%27";
		$companydata = cURL($companyurl, $header_data);

		if(is_null($companydata))
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "No company found with the name " . $ticketstuff[0]));
			} else {
				die("No company found with the name " . $ticketstuff[0]); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}

		$boardurl = $connectwise . "/$connectwisebranch/apis/3.0/service/boards?conditions=name%20contains%20%27" .$ticketstuff[0]. "%27";
		$boarddata = cURL($boardurl, $header_data);

		$postarray = array(
			"summary" => $ticketstuff[2],
			"company" => array(
				"id" => $companydata[0]->id
			),
			"board" => array(
				"id" => $boarddata[0]->id
			));
	}
	else
	{
		if(!array_key_exists(1, $ticketstuff)) {
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Not enough values specified. Please use /t new company|summary"));
			} else {
				die("Not enough values specified. Please use /t new company|summary"); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();

		}

		$companyurl = $connectwise . "/$connectwisebranch/apis/3.0/company/companies?conditions=name%20contains%20%27" . urlencode($ticketstuff[0]) . "%27";
		$companydata = cURL($companyurl, $header_data);

		if(is_null($companydata))
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "No company found with the name " . $ticketstuff[0]));
			} else {
				die("No company found with the name " . $ticketstuff[0]); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}

		$postarray = array(
			"summary" => $ticketstuff[1],
			"company" => array(
				"id" => $companydata[0]->id
			));
	}
	//Username mapping code
	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

		if (!$mysql) //Check for errors
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Connection Error: " . mysqli_connect_error()));
			} else {
				die("Connection Error: " . mysqli_connect_error()); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}

		$val1 = mysqli_real_escape_string($mysql,$_REQUEST["user_name"]);
		$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $val1 . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result
		$rowcount = mysqli_num_rows($result);
		if($rowcount > 1) //If there were too many rows matching query
		{
			die("Error: too many users somehow?"); //This should NEVER happen.
		}
		else if ($rowcount == 1) //If exactly 1 row is found.
		{
			$row = mysqli_fetch_assoc($result); //Row association.

			$postarray["enteredBy"] = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
			$postarray["owner"] = array("identifier"=>$row["cwname"]); //Return the connectwise name of the row found as the CW member name.
		}
		else //If no rows are found
		{
			if($usecwname==1) //If variable enabled
			{
				$postarray["enteredBy"] = $_REQUEST['user_name'];
				$postarray["owner"] = array("identifier"=>$_REQUEST['user_name']); //Return the slack username as the user for the ticket note. If the user does not exist in CW, it will use the API username.
			}
		}
	}
	else
	{
		if($usecwname==1)
		{
			$postarray["enteredBy"] = $_REQUEST['user_name'];
			$postarray["owner"] = array("identifier"=>$_REQUEST['user_name']);
		}
	}

	$dataTCmd = cURLPost( //Function for POST requests in cURL
		$connectwise . "/$connectwisebranch/apis/3.0/service/tickets", //URL
		$header_data2, //Header
		"POST", //Request type
		$postarray
	);

	if($timeoutfix == true)
	{
		cURLPost($_REQUEST["response_url"],array("Content-Type: application/json"),"POST",array("parse" => "full", "response_type" => "ephemeral","text" => "New ticket #<" . $connectwise . "/$connectwisebranch/services/system_io/Service/fv_sr100_request.rails?service_recid=" . $dataTCmd->id . "|" . $dataTCmd->id . "> has been created.","mrkdwn"=>true));
	}
	else
	{
		die("New ticket #<" . $connectwise . "/$connectwisebranch/services/system_io/Service/fv_sr100_request.rails?service_recid=" . $dataTCmd->id . "|" . $dataTCmd->id . "> has been created.");
	}
	die();
}

//-
//Ticket data section
//-
$dataTData = cURL($urlticketdata, $header_data); //Decode the JSON returned by the CW API.

if($dataTData==NULL) 
{
	if ($timeoutfix == true) {
		cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Array not returned in line 195. Please check your connectwise URL variable in config.php and ensure it is accessible via the web at " . $urlticketdata));
	} else {
		die("Array not returned in line 195. Please check your connectwise URL variable in config.php and ensure it is accessible via the web at " . $urlticketdata); //Return properly encoded arrays in JSON for Slack parsing.
	}
	die();
}

//-
//Priority command
//- 
if($command=="priority") { //Check if the second string in the text array from the slash command is priority

	$priority = "0"; //Set priority = 0.
	$priorityname = "";
	$priorityurl = $connectwise . "/$connectwisebranch/apis/3.0/service/priorities?conditions=name%20like%20%27" . $option3 . "%27";
	$dataTCmd = cURL($priorityurl, $header_data);
	if(array_key_exists(0,$dataTCmd))
	{
		$priority = $dataTCmd[0]->id;
		$priorityname = $dataTCmd[0]->name;
	}
	//Check what $option3 was set to, the third string in the text array from the slash command.
	if ($priority==0)
	{
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Failed to get priority code: " . $option3));
		} else {
			die("Failed to get priority code: " . $option3); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}

	$dataTCmd = cURLPost( //Function for POST requests in cURL
		$urlticketdata, //URL
		$header_data2, //Header
		"PATCH", //Request type
		array(array("op" => "replace", "path" => "/priority/id", "value" => $priority)) //POST Body
	);

	$return =array(
		"parse" => "full", //Parse all text.
		"response_type" => "ephemeral", //Send the response to the user only
		"attachments"=>array(array(
			"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
			"title" => "Ticket Summary: " . $dataTData->summary, //Set bolded title text
			"pretext" => "Ticket #" . $dataTData->id . "'s priority has been set to " . $priorityname, //Set pretext
			"text" => "Click <" . $ticketurl . $dataTData -> id . "&companyName" . $companyname . "|here> to open the ticket.", //Set text to be returned
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
	die();
}

//-
//Ticket Status change command.
//-
if($command=="status") {
	$status = "0";
	$statusname = "";
	$statusurl = $dataTData->board->_info->board_href . "/statuses?conditions=name%20like%20%27" . $option3 . "%27";
	$dataTCmd = cURL($statusurl, $header_data);
	if(array_key_exists(0,$dataTCmd))
	{
		$status = $dataTCmd[0]->id;
		$statusname = $dataTCmd[0]->name;
	}
	if ($status == 0)
	{
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Failed to get status code: " . $status));
		} else {
			die("Failed to get status code: " . $status); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}

	$dataTCmd = cURLPost(
		$urlticketdata,
		$header_data2,
		"PATCH",
		array(array("op" => "replace", "path" => "/status/id", "value" => $status))
	);

	$return = array(
		"parse" => "full",
		"response_type" => "ephemeral", //Send the response to the user only
		"attachments" => array(array(
			"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
			"title" => "Ticket Summary: " . $dataTData->summary,
			"pretext" => "Ticket #" . $dataTData->id . "'s status has been set to " . $statusname,
			"text" => "Click <" . $ticketurl . $dataTData->id . "&companyName" . $companyname . "|here> to open the ticket.",
			"mrkdwn_in" => array(
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
	die();
}

if($command=="scheduleme")
{

	$cwuser = NULL;
	//Username mapping code
	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

		if (!$mysql) //Check for errors
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Connection Error: " . mysqli_connect_error()));
			} else {
				die("Connection Error: " . mysqli_connect_error()); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}

		$val1 = mysqli_real_escape_string($mysql,$_REQUEST["user_name"]);
		$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $val1 . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result
		$rowcount = mysqli_num_rows($result);
		if($rowcount > 1) //If there were too many rows matching query
		{
			die("Error: too many users somehow?"); //This should NEVER happen.
		}
		else if ($rowcount == 1) //If exactly 1 row is found.
		{
			$row = mysqli_fetch_assoc($result); //Row association.

			$cwuser = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
		}
		else //If no rows are found
		{
			if($usecwname==1) //If variable enabled
			{
				$cwuser = $_REQUEST['user_name'];
			}
		}
	}
	else
	{
		if($usecwname==1)
		{
			$cwuser = $_REQUEST['user_name'];
		}
		else
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Error: Name " .  $_REQUEST['user_name'] . " not found"));
			} else {
				die("Error: Name " .  $_REQUEST['user_name'] . " not found"); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}
	}
	unset($exploded[0]);
	unset($exploded[1]);
	$removal = implode(" ", $exploded);
	if($removal==NULL)
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime("12:00AM"));
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	else
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal));
		$dateend = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal. " +30 minutes"));
	}
	if(strpos($datestart, 'T06:00:00Z') !== false)
	{
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	if(strpos($datestart, 'T00:00:00Z') !== false)
	{
		$dateend = $datestart;
	}

	$postarray = array("objectId" => $ticketnumber, "member" => array("identifier" => $cwuser), "type" => array("id" => 4), "dateStart" => $datestart, "dateEnd" => $dateend, "allowScheduleConflictsFlag" => true);

	$dataTCmd = cURLPost(
		$connectwise . "/$connectwisebranch/apis/3.0/schedule/entries",
		$header_data2,
		"POST",
		$postarray
	);

	if($removal==NULL)
	{
		$timingdate = explode("T", $datestart);
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You have been properly scheduled for ticket #" . $dataTCmd->objectId . " for $timingdate[0]","mrkdwn"=>true));
		} else {
			die("You have been properly scheduled for ticket #" . $dataTCmd->objectId . " for $timingdate[0]"); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}
	else
	{
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You have been properly scheduled for ticket #" . $dataTCmd->objectId . " at " . $removal,"mrkdwn"=>true));
		} else {
			die("You have been properly scheduled for ticket #" . $dataTCmd->objectId . " at " . $removal); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}
}

if($command=="schedule")
{

	$cwuser = NULL;
	if($option3 == NULL)
	{
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "No user specified."));
		} else {
			die("No user specified."); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}
	$username = $option3;
	//Username mapping code
	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

		if (!$mysql) //Check for errors
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Connection Error: " . mysqli_connect_error()));
			} else {
				die("Connection Error: " . mysqli_connect_error()); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}

		$val1 = mysqli_real_escape_string($mysql,$username);
		$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $val1 . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result
		$rowcount = mysqli_num_rows($result);
		if($rowcount > 1) //If there were too many rows matching query
		{
			die("Error: too many users somehow?"); //This should NEVER happen.
		}
		else if ($rowcount == 1) //If exactly 1 row is found.
		{
			$row = mysqli_fetch_assoc($result); //Row association.

			$cwuser = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
		}
		else //If no rows are found
		{
			if($usecwname==1) //If variable enabled
			{
				$cwuser = $username;
			}
		}
	}
	else
	{
		if($usecwname==1)
		{
			$cwuser = $username;
		}
		else
		{
			if ($timeoutfix == true) {
				cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Error: Name " .  $username . " not found"));
			} else {
				die("Error: Name " .  $username . " not found"); //Return properly encoded arrays in JSON for Slack parsing.
			}
			die();
		}
	}
	unset($exploded[0]);
	unset($exploded[1]);
	unset($exploded[2]);
	$removal = implode(" ", $exploded);
	if($removal==NULL)
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime("12:00AM"));
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	else
	{
		$datestart = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal));
		$dateend = gmdate("Y-m-d\TH:i:s\Z", strtotime($removal. " +30 minutes"));
	}
	if(strpos($datestart, 'T06:00:00Z') !== false)
	{
		$timingdate = explode("T", $datestart);
		$datestart = $timingdate[0] . "T00:00:00Z";
	}
	if(strpos($datestart, 'T00:00:00Z') !== false)
	{
		$dateend = $datestart;
	}

	$postarray = array("objectId" => $ticketnumber, "member" => array("identifier" => $cwuser), "type" => array("id" => 4), "dateStart" => $datestart, "dateEnd" => $dateend, "allowScheduleConflictsFlag" => true);

	$dataTCmd = cURLPost(
		$connectwise . "/$connectwisebranch/apis/3.0/schedule/entries",
		$header_data2,
		"POST",
		$postarray
	);

	if($removal==NULL)
	{
		$timingdate = explode("T", $datestart);
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "$username has been properly scheduled for ticket #" . $dataTCmd->objectId . " for $timingdate[0]","mrkdwn"=>true));
		} else {
			die("$username has been properly scheduled for ticket #" . $dataTCmd->objectId . " for $timingdate[0]"); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}
	else
	{
		if ($timeoutfix == true) {
			cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "$username has been properly scheduled for ticket #" . $dataTCmd->objectId . " at " . $removal,"mrkdwn"=>true));
		} else {
			die("$username has been properly scheduled for ticket #" . $dataTCmd->objectId . " at " . $removal); //Return properly encoded arrays in JSON for Slack parsing.
		}
		die();
	}
}

if($posttext==1) //Block for curl to get latest note
{
	$createdby = "Error"; //Create with error just in case.
	$notetext = "Error"; //Create with error just in case.

	$dataTNotes = cURL($noteurl, $header_data); // Get the JSON returned by the CW API for $noteurl.

	$dataTimeData = cURL($timeurl, $header_data); // Get the JSON returned by the CW API for $timeurl.

	if($command == "full" || $command == "notes" || $command == "all")
	{
		$dataTNotes2 = cURL($connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20asc", $header_data); // Get the JSON returned by the CW API for ticket notes.
	}
	if(!array_key_exists(0, $dataTNotes))
	{
		if(array_key_exists(0, $dataTimeData))
		{
			$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
			$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
			$notedate = $dataTimeData[0]->dateEntered;

			$date2 = strtotime($notedate);
			$date2format = date('m-d-Y g:i:sa', $date2);
			$internalflag = $dataTimeData[0]->addToInternalAnalysisFlag;
		}
		else
		{
			$posttext=0;
		}
	}
	else if($dataTNotes[0]->text != NULL || $dataTimeData[0]->text != NULL) //Makes sure that if both text values == null, then there is no text to post.
	{
		if($dataTNotes[0]->text != NULL) {

			$createdby = $dataTNotes[0]->createdBy; //Set $createdby to the ticket note creator.
			$notetime = new DateTime($dataTNotes[0]->dateCreated); //Create new datetime object based on ticketnote note.
			$notedate = $dataTNotes[0]->dateCreated;
			$internalflag = $dataTNotes[0]->internalAnalysisFlag;

			$text = $dataTNotes[0]->text; //Set $text to the ticket text.
			if (array_key_exists(0, $dataTNotes) && array_key_exists(0, $dataTimeData) && $command != "initial" && $command != "first" && $command != "note") //Check if arrays exist properly.
			{
				$timetime = new DateTime($dataTimeData[0]->dateEntered); //Create new time object based on time entry note.


				if ($timetime > $notetime) //If the time entry is newer than latest ticket note.
				{
					$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
					$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
					$notedate = $dataTimeData[0]->dateEntered;
					$internalflag = $dataTimeData[0]->addToInternalAnalysisFlag;
				}
			}

			$date2 = strtotime($notedate);
			$date2format = date('m-d-Y g:i:sa', $date2);
		}
		else
		{
			$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
			$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
			$notedate = $dataTimeData[0]->dateEntered;

			$date2 = strtotime($notedate);
			$date2format = date('m-d-Y g:i:sa', $date2);
			$internalflag = $dataTimeData[0]->addToInternalAnalysisFlag;
		}
	}
	else
	{
		$posttext=0;
	}
}

//Scheduled resource block
$scheduleurl = str_replace(' ', '%20', $dataTData->_info->scheduleentries_href);
$resourceset = cURL($scheduleurl,$header_data); //Get URL and send that to curl function, retrieve response.

if($resourceset == NULL)
{
	$resourceline = false;
}
else
{
	$latestsched = end($resourceset);

	if(!array_key_exists("dateStart",$latestsched) || $latestsched->dateStart==NULL)
	{
		$resourceline = false;
	}
	else
	{
		$scheddate = date("m-d-y",strtotime($latestsched->dateStart));
		$schedstart = date("g:iA",strtotime($latestsched->dateStart));
		$schedend = date("g:iA",strtotime($latestsched->dateEnd));

		$resourceline = "\nNext: " . $latestsched->member->identifier . " at " . $scheddate . " " . $schedstart . "-" . $schedend;
	}
}

$lastupdate = "\nUpdated: " . $dataTData->_info->updatedBy . " at " . date("m-d-y g:iA", strtotime($dataTData->_info->lastUpdated));


$date=strtotime($dataTData->dateEntered); //Convert date entered JSON result to time.
$dateformat=date('m-d-Y g:i:sa',$date); //Convert previously converted time to a better time string.
$return="Nothing!"; //Create return value and set to a basic message just in case.
$contact="None"; //Set None for contact in case no contact exists for "Catch All" tickets.
$resources="No resources"; //Just in case resources are null, have something to return.
$hours="No time entered."; //Just in case time is null, have something to return.

if($dataTData->actualHours != NULL) //If time is not NULL
{
	$hours="Time: ". $dataTData->actualHours . " Hours"; //Set $hours to a formatted time line.
}

if($dataTData->resources != NULL)
{
	$resources=$dataTData->resources;
}

if(!$dataTData->contact==NULL) { //Check if contact name exists in array.
	$contact = $dataTData->contact->name; //Set contact variable to contact name.
}

if($command == "initial" || $command == "first" || $command == "note")
{
	if($posttext==0)
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours . //Return assigned resources
				(!$resourceline ? "" : $resourceline) . //Return next resource
				$lastupdate,
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$return =array(
			"parse" => "full",
			"response_type" => "ephemeral",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours . //Return assigned resources
				(!$resourceline ? "" : $resourceline) . //Return next resource
				$lastupdate,
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Initial " . ($internalflag == "true" ? "Internal" : "External") . " ticket note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				))
			);

	}
}
else if($command == "full" || $command == "notes" || $command == "all")
{
	if($posttext==0)
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours . //Return assigned resources
				(!$resourceline ? "" : $resourceline) . //Return next resource
				$lastupdate,
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$date3=strtotime($dataTNotes2[0]->dateCreated);
		$date3format=date('m-d-Y g:i:sa',$date3);
		if(array_key_exists("internalAnalysisFlag", $dataTNotes2))
		{
			$internalflag2 = $dataTNotes2[0]->internalAnalysisFlag;
		}
		else if(array_key_exists("addToInternalAnalysisFlag", $dataTNotes2))
		{
			$internalflag2 = $dataTNotes2[0]->addToInternalAnalysisFlag;
		}
		else if(array_key_exists("internalFlag", $dataTNotes2))
		{
			$internalflag2 = $dataTNotes2[0]->internalFlag;
		}
		else
		{
			$internalflag2 = "";
		}

		$initialinfoarray = array(
			array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
					"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
					"\n" . $resources . " | " . $hours . //Return assigned resources
					(!$resourceline ? "" : $resourceline) . //Return next resource
					$lastupdate,
				"mrkdwn_in" => array(
					"text",
					"pretext"
				)
			));

		$temparray = array();

		if(array_key_exists(0, $dataTNotes)) {
			foreach ($dataTNotes as $singlenote) {
				$createdby = $singlenote->createdBy; //Set $createdby to the ticket note creator.
				$notetime = new DateTime($singlenote->dateCreated); //Create new datetime object based on ticketnote note.
				$notedate = $singlenote->dateCreated;
				$internalflag = $singlenote->internalAnalysisFlag;

				$text = $singlenote->text; //Set $text to the ticket text.

				$date2 = strtotime($notedate);
				$date2format = date('m-d-Y g:i:sa', $date2);

				$temparray[$date2] = array(
					"pretext" => ($internalflag == "true" ? "Internal" : "External") . " Note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
					));
			}
		}

		if(array_key_exists(0, $dataTimeData))
		{
			foreach($dataTimeData as $singletime)
			{
				$createdby = $singletime->enteredBy; //Set $createdby to the time entry creator.
				$notedate = $singletime->dateEntered;
				$internalflag = $singletime->addToInternalAnalysisFlag;
				$text = $singletime->notes; //Set $text to the time entry text.

				$date2 = strtotime($notedate);
				$date2format = date('m-d-Y g:i:sa', $date2);

				$temparray[$date2] = array(
					"pretext" => ($internalflag == "true" ? "Internal" : "External") . " Time Entry (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
					));
			}
		}
		ksort($temparray);

		$notesandtimes = array_merge($initialinfoarray, $temparray);

		$return =array(
			"parse" => "full",
			"response_type" => "ephemeral",
			"attachments"=>$notesandtimes
			);
	}
}
else //If no command is set, or if it's just random gibberish after ticket number.
{
	if($posttext==0)
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours . //Return assigned resources
				(!$resourceline ? "" : $resourceline) . //Return next resource
				$lastupdate,
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$return =array(
			"parse" => "full",
			"response_type" => "in_channel",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours . //Return assigned resources
				(!$resourceline ? "" : $resourceline) . //Return next resource
				$lastupdate,
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Latest " . ($internalflag == "true" ? "Internal" : "External") . " Note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				))
			);

	}
}

if ($timeoutfix == true) {
	cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", $return);
} else {
	die(json_encode($return, JSON_PRETTY_PRINT)); //Return properly encoded arrays in JSON for Slack parsing.
}
die();

?>
