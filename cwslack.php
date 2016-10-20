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

$apicompanyname = strtolower($companyname); //Company name all lower case for api auth. 
$authorization = base64_encode($apicompanyname . "+" . $apipublickey . ":" . $apiprivatekey); //Encode the API, needed for authorization.

if(empty($_GET['token']) || ($_GET['token'] != $slacktoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die("No text provided."); //If there is no text added, kill the connection.
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
$urlticketdata = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber; //Set ticket API url
$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20desc";
$ticketurl = $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Ticket URL for connectwise.
$timeurl = $connectwise . "/v4_6_release/apis/3.0/time/entries?conditions=chargeToId=" . $ticketnumber . "&chargeToType=%27ServiceTicket%27&orderBy=dateEntered%20desc"; //Set the URL required for cURL requests to the time entry API.


//Set noteurl to use ascending if an initial note command is passed.
if($command == "initial" || $command == "first" || $command == "note") 
{
	$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20asc";
}

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

//Need to create 3 arrays before hand to ensure no errors occur.
$dataTNotes = array();
$dataTData = array();
$dataTCmd = array();

//-
//Ticket data section
//-
$dataTData = cURL($urlticketdata, $header_data); //Decode the JSON returned by the CW API.

if($dataTData==NULL) 
{
	die("Array not returned in line 195. Please check your connectwise URL variable in config.php and ensure it is accessible via the web at " . $urlticketdata);
}
if(array_key_exists("code",$dataTData)) { //Check if array contains error code
	if($dataTData->code == "NotFound") { //If error code is NotFound
		echo "Connectwise ticket " . $ticketnumber . " was not found."; //Report that the ticket was not found.
		return;
	}
	if($dataTData->code == "Unauthorized") { //If error code is an authorization error
		echo "401 Unauthorized, check API key to ensure it is valid."; //Fail case.
		return;
	}
	else {
		echo "Unknown Error Occurred, check API key and other API settings." . $dataTData->code; //Fail case.
		return;
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
		$dataTNotes2 = cURL($connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/notes?orderBy=id%20asc", $header_data); // Get the JSON returned by the CW API for ticket notes.
	}
	$createdby = $dataTNotes[0]->createdBy; //Set $createdby to the ticket note creator.
	$notetime = new DateTime($dataTNotes[0]->dateCreated); //Create new datetime object based on ticketnote note.
	$notedate = $dataTNotes[0]->dateCreated;

	$text = $dataTNotes[0]->text; //Set $text to the ticket text.
	if(array_key_exists(0,$dataTNotes) && array_key_exists(0,$dataTimeData) && $command != "initial" && $command != "first" && $command != "note") //Check if arrays exist properly.
	{
		$timetime = new DateTime($dataTimeData[0]->dateEntered); //Create new time object based on time entry note.


		if($timetime>$notetime) //If the time entry is newer than latest ticket note.
		{
			$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
			$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
			$notedate = $dataTimeData[0]->dateEntered;
		}
	}
}
//-
//Priority command
//- 
if($command=="priority") { //Check if the second string in the text array from the slash command is priority

	$priority = "0"; //Set priority = 0.

	//Check what $option3 was set to, the third string in the text array from the slash command.
	if ($option3 == "moderate") //If moderate
	{
		$priority = "4"; //Set to moderate ID
	} else if ($option3=="critical")
	{
		$priority = "1";
	} else if ($option3=="low")
	{
		$priority = "3";
	}
	else //If unknown
	{
		echo "Failed to get priority code"; //Send error message. Anything not Slack JSON formatted will return just to the user who submitted the slash command. Don't need to spam errors.
		return;
	}

	$dataTCmd = cURLPost(
		$urlticketdata,
		$header_data2,
		"PATCH",
		array(array("op" => "replace", "path" => "/priority/id", "value" => $priority))
	);
}

//-
//Ticket Status change command.
//-
if($command=="status") {
	$status = "0";
	if ($option3 == "scheduled" || $option3=="schedule")
	{
		$status = "124";
	} else if ($option3=="completed")
	{
		$status = "31";
	} else if ($option3=="n2s" || $option3=="needtoschedule" || $option3=="ns")
	{
		$status = "121";
	}
	else
	{
		echo "Failed to get status code";
		return;
	}
	$dataTCmd = cURLPost(
		$urlticketdata,
		$header_data2,
		"PATCH",
		array(array("op" => "replace", "path" => "/status/id", "value" => $status))
	);
}


if(array_key_exists("code",$dataTCmd)) { //Check if array contains error code
	if($dataTCmd->code == "NotFound") { //If error code is NotFound
		echo "Connectwise ticket " . $ticketnumber . " was not found."; //Report that the ticket was not found.
		return;
	}
	if($dataTCmd->code == "Unauthorized") { //If error code is an authorization error
		echo "401 Unauthorized, check API key to ensure it is valid."; //Fail case.
		return;
	}
	else {
		echo "Unknown Error Occurred, check API key and other API settings. Error: " . $dataTCmd->code; //Fail case.
		return;
	}
}

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
if($posttext==1)
{
	$date2=strtotime($notedate);
	$date2format=date('m-d-Y g:i:sa',$date2);
}


if(!$dataTData->contact==NULL) { //Check if contact name exists in array.
	$contact = $dataTData->contact->name; //Set contact variable to contact name.
}


if($command == "priority") //If command is priority.
{
	$return =array(
		"parse" => "full", //Parse all text.
		"response_type" => "in_channel", //Send the response in the channel
		"attachments"=>array(array(
			"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
			"title" => "Ticket Summary: " . $dataTData->summary, //Set bolded title text
			"pretext" => "Ticket #" . $dataTData->id . "'s priority has been set to " . $option3, //Set pretext
			"text" => "Click <" . $ticketurl . $dataTData -> id . "&companyName" . $companyname . "|here> to open the ticket.", //Set text to be returned
			"mrkdwn_in" => array( //Set markdown values
				"text",
				"pretext"
				)
			))
		);
}
else if($command == "status") //If command is status.
{
	$return =array(
		"parse" => "full",
		"response_type" => "in_channel",
		"attachments"=>array(array(
			"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
			"title" => "Ticket Summary: " . $dataTData->summary,
			"pretext" => "Ticket #" . $dataTData->id . "'s status has been set to " . $option3,
			"text" => "Click <" . $ticketurl . $dataTData -> id . "&companyName" . $companyname . "|here> to open the ticket.",
			"mrkdwn_in" => array(
				"text",
				"pretext"
				)
			))
		);
}
else if($command == "initial" || $command == "first" || $command == "note")
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
				"\n" . $resources . " | " . $hours, //Return assigned resources
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
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Initial ticket note (" . $date2format  . ") from: " . $createdby,
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
				"\n" . $resources . " | " . $hours, //Return assigned resources
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
		$return =array(
			"parse" => "full",
			"response_type" => "ephemeral",
			"attachments"=>array(array(
				"fallback" => "Info on Ticket #" . $dataTData->id, //Fallback for notifications
				"title" => "<" . $ticketurl . $dataTData -> id . "&companyName=" . $companyname . "|#" . $dataTData->id . ">: " . $dataTData->summary, //Return clickable link to ticket with ticket summary.
				"pretext" => "Info on Ticket #" . $dataTData->id, //Return info string with ticket number.
				"text" =>  $dataTData->company->identifier . " / " . $contact . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $dataTData->status->name . //Return "Date Entered / Status" string
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Latest Note (" . $date2format  . ") from: " . $createdby,
					"text" =>  $text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				),
				array(
					"pretext" => "Initial ticket note (" . $date3format  . ") from: " . $dataTNotes2[0]->createdBy,
					"text" =>  $dataTNotes2[0]->text,
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
						)
				))
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
				"\n" . $resources . " | " . $hours, //Return assigned resources
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
				"\n" . $resources . " | " . $hours, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				),
				array(
					"pretext" => "Latest Note (" . $date2format  . ") from: " . $createdby,
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

echo json_encode($return, JSON_PRETTY_PRINT); //Return properly encoded arrays in JSON for Slack parsing.
?>
