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

//Receive connector for Connectwise Callbacks
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php'; //Require the config file.

$apicompanyname = strtolower($companyname); //Company name all lower case for api auth. 
$authorization = base64_encode($apicompanyname . "+" . $apipublickey . ":" . $apiprivatekey); //Encode the API, needed for authorization.

//Dates required for URL to function
$datenow = gmdate("Y-m-d\TH:i", strtotime("-10 minutes"));
$date2hours = gmdate("Y-m-d\TH:i", strtotime("+2 hours"));

$url = $connectwise. "/v4_6_release/apis/3.0/schedule/entries?conditions=status/Name=%27Firm%27%20and%20dateStart%20%3E%20[" . $datenow . "]%20and%20dateStart%20%3C%20[". $date2hours . "]&orderBy=dateStart%20desc";
$ticketurl = $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Set the URL required for ticket links.


//Set headers for cURL requests. $header_data covers API authentication while $header_data2 covers the Slack output.
$header_data =array(
 "Authorization: Basic ". $authorization,
);
$header_data2 =array(
 "Content-Type: application/json"
);

//Block for cURL connections to the schedule API
$ch = curl_init(); //Initiate a curl session

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

var_dump($dataTData);

foreach($dataTData as $entry)
{
	$reminder = $entry->reminder->name;
	$user = $entry->member->identifier;
	$username = $entry->member->name;
	$namearray = explode(" - ",$entry->name);
	var_dump($namearray);
	$companyarray = explode(" / ",$namearray[0]);
	$company = $companyarray[0];
	$summary = $namearray[1];
	$datenow = date("Y-m-d\TH:i"); //Reusing datenow as non-GMT based time.
	$datestart = date("Y-m-d\TH:i",strtotime($entry->dateStart));
	
	if($reminder != "0 minutes" && $posttousers == 1)
	{
		$datereminder = date("Y-m-d\TH:i",strtotime($entry->dateStart . " -" . $reminder));
		
		if($datenow==$datereminder)
		{
			$postfieldspre = array(
				"channel"=>"@".$user,
				"attachments"=>array(array(
					"fallback" => "Firm with " . $company . " in " . $reminder . ".",
					"title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary,
					"pretext" => "Firm starting in " . $reminder,
					"text" =>  "You have a firm ticket with " . $company . " coming up. Please wrap up work on current ticket.",
					"mrkdwn_in" => array(
						"text",
						"pretext"
						)
					))
				);
			$ch = curl_init();
			$postfields = json_encode($postfieldspre);

			$curlOpts = array(
				CURLOPT_URL => $webhookurl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => $header_data2,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POSTFIELDS => $postfields,
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 1,
			);
			curl_setopt_array($ch, $curlOpts);
			$answer = curl_exec($ch);

			// If there was an error, show it
			if (curl_error($ch)) {
				die(curl_error($ch));
			}
			curl_close($ch);
		}
	}
	
	if($datenow == $datestart)
	{
		if($posttousers==1)
		{
			$postfieldspre = array(
				"channel"=>"@".$user,
				"attachments"=>array(array(
					"fallback" => "Firm with " . $company . " starting now.",
					"title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary,
					"pretext" => "Firm starting now.",
					"text" =>  "You have a firm ticket with " . $company . " starting now. You should be calling the client now.",
					"mrkdwn_in" => array(
						"text",
						"pretext"
						)
					))
				);
			$ch = curl_init();
			$postfields = json_encode($postfieldspre);

			$curlOpts = array(
				CURLOPT_URL => $webhookurl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => $header_data2,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POSTFIELDS => $postfields,
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 1,
			);
			curl_setopt_array($ch, $curlOpts);
			$answer = curl_exec($ch);

			// If there was an error, show it
			if (curl_error($ch)) {
				die(curl_error($ch));
			}
			curl_close($ch);
		}
		if($posttochan==1)
		{
			$postfieldspre = array(
				"channel"=>$timechan,
				"attachments"=>array(array(
					"fallback" => "Firm for " . $username . " with " . $company . " now.",
					"title" => "<" . $ticketurl . $entry->objectId . "&companyName=" . $companyname . "|#" . $entry->objectId . ">: " . $summary,
					"pretext" => $username . " has a firm starting now.",
					"text" =>  "Please remind the technician of this appointment with " . $company . ". They should be on the phone with them or calling them shortly.",
					"mrkdwn_in" => array(
						"text",
						"pretext"
						)
					))
				);
			$ch = curl_init();
			$postfields = json_encode($postfieldspre);

			$curlOpts = array(
				CURLOPT_URL => $webhookurl,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER => $header_data2,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POSTFIELDS => $postfields,
				CURLOPT_POST => 1,
				CURLOPT_HEADER => 1,
			);
			curl_setopt_array($ch, $curlOpts);
			$answer = curl_exec($ch);

			// If there was an error, show it
			if (curl_error($ch)) {
				die(curl_error($ch));
			}
			curl_close($ch);
		}
	}
	
	echo $datereminder . "\n\n";
	echo $datenow . "\n\n";
	echo $datestart . "\n\n";
	var_dump($entry);
}
?>