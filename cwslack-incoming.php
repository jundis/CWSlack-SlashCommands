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

$data = json_decode(file_get_contents('php://input')); //Decode incoming body from connectwise callback.
$info = json_decode(stripslashes($data->Entity)); //Decode the entity field which contains the JSON data we want.

//Connection kill blocks. Stops things from running if certain conditions are met.
if(empty($_GET['id']) || empty($_GET['action']) || empty($info)) die; //If anything we need doesn't exist, kill connection.

if($_GET['action'] == "updated" && $_GET['srDetailRecId']==0 && $_GET['timeRecId']==0) die; //Kill connection if the update is not a note, and is something like a status change. This will prevent duplicate entries.
if(strtolower($_GET['memberId'])=="zadmin" && $allowzadmin == 0) die; //Die if $allowzadmin is not enabled.

$badboards = explode("|",$badboard); //Explode with pipe seperator.
$badstatuses = explode("|",$badstatus); //Explode with pipe seperator.
$badcompanies = explode("|",$badcompany); //Explode with pipe seperator.
if (in_array($info->BoardName,$badboards)) die;
if (in_array($info->StatusName,$badstatuses)) die;
if (in_array($info->CompanyName,$badcompanies)) die;

//URL creation
$ticketurl = $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Set the URL required for ticket links.
$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $_GET['id'] . "/notes?orderBy=id%20desc"; //Set the URL required for cURL requests to ticket note API.
$timeurl = $connectwise . "/v4_6_release/apis/3.0/time/entries?conditions=chargeToId=" . $_GET['id'] . "&chargeToType=%27ServiceTicket%27&orderBy=dateEntered%20desc"; //Set the URL required for cURL requests to the time entry API.

$dataTData = array(); //Blank array.
$dataTimeData = array(); //Blank array.

//Set headers for cURL requests. $header_data covers API authentication while $header_data2 covers the Slack output.
$header_data =array(
 "Authorization: Basic ". $authorization,
);
$header_data2 =array(
 "Content-Type: application/json"
);

$skip = 0; //Create variable to skip posting to Slack channel while also allowing follow posts.
$date=strtotime($info->EnteredDateUTC); //Convert date entered JSON result to time.
$dateformat=date('m-d-Y g:i:sa',$date); //Convert previously converted time to a better time string.
$ticket=$_GET['id'];

if($posttext==1) //Block for curl to get latest note
{
	$createdby = "Error"; //Create with error just in case.
	$notetext = "Error"; //Create with error just in case.
	
	//Block for cURL connections to the ticket notes API
	$ch1 = curl_init(); //Initiate a curl session

	//Create curl array to set the API url, headers, and necessary flags.
	$curlOpts1 = array(
		CURLOPT_URL => $noteurl,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => $header_data,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HEADER => 1,
	);
	curl_setopt_array($ch1, $curlOpts1); //Set the curl array to $curlOpts

	$answerTData = curl_exec($ch1); //Set $answerTData to the curl response to the API.
	$headerLen = curl_getinfo($ch1, CURLINFO_HEADER_SIZE);  //Get the header length of the curl response
	$curlBodyTData = substr($answerTData, $headerLen); //Remove header data from the curl string.

	// If there was an error, show it
	if (curl_error($ch1)) {
		$posttext=0; //Sets $posttext to 0 if error ocurred.
	}
	curl_close($ch1);

	$dataTData = json_decode($curlBodyTData); //Decode the JSON returned by the CW API.
	//End ticket note block.

	if($posttext==1) //Verifies no curl error occurred. If one has, ignore $posttext.
	{
		//Block for cURL connections to Time Entries API
		$ch2 = curl_init(); //Initiate a curl session

		//Create curl array to set the API url, headers, and necessary flags.
		$curlOpts2 = array(
			CURLOPT_URL => $timeurl,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => $header_data,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => 1,
		);
		curl_setopt_array($ch2, $curlOpts2); //Set the curl array to $curlOpts

		$answerTimeData = curl_exec($ch2); //Set $answerTData to the curl response to the API.
		$headerLen = curl_getinfo($ch2, CURLINFO_HEADER_SIZE);  //Get the header length of the curl response
		$curlBodyTimeData = substr($answerTimeData, $headerLen); //Remove header data from the curl string.

		// If there was an error, show it
		if (curl_error($ch2)) {
			$posttext=0;
		}
		curl_close($ch2);
		$dataTimeData = json_decode($curlBodyTimeData); //Decode the JSON returned by the CW API.
		//End time entry block.

		if($posttext==1 && ($dataTData[0]->text != NULL || $dataTimeData[0]->text != NULL)) //Verified no curl error occurred as well as makes sure that if both text values == null, then there is no text to post.
		{
			$createdby = $dataTData[0]->createdBy; //Set $createdby to the ticket note creator.
			$text = $dataTData[0]->text; //Set $text to the ticket text.
			if (array_key_exists(0, $dataTData) && array_key_exists(0, $dataTimeData)) //Check if arrays exist properly.
			{
				$timetime = new DateTime($dataTimeData[0]->dateEntered); //Create new time object based on time entry note.
				$notetime = new DateTime($dataTData[0]->dateCreated); //Create new datetime object based on ticketnote note.

				if ($timetime > $notetime) //If the time entry is newer than latest ticket note.
				{
					$createdby = $dataTimeData[0]->enteredBy; //Set $createdby to the time entry creator.
					$text = $dataTimeData[0]->notes; //Set $text to the time entry text.
				}
			}
		}
		else
		{
			$posttext=0; //If text is null, ensure posttext = 0.
		}
}
}

if($_GET['action'] == "added" && $postadded == 1)
{
	if($posttext==0)
	{
		$postfieldspre = array(
			"attachments"=>array(array(
				"fallback" => (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
				"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
				"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
				"text" =>  $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
				"\n" . "Priority: " . $info->Priority . " | " . $info->StatusName . //Return "Prority / Status" string
				"\n" . $info->Resources, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext",
					"title"
					)
				))
			);
	}
	else
	{
		$postfieldspre = array(
			"attachments"=>array(array(
				"fallback" => (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
				"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
				"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
				"text" =>  $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
				"\n" . "Priority: " . $info->Priority . " | " . $info->StatusName . //Return "Prority / Status" string
				"\n" . $info->Resources, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext",
					"title"
					)
				),
				array(
					"pretext" => "Latest Note from: " . $createdby,
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
else if($_GET['action'] == "updated" && $postupdated == 1)
{
	if($posttext==0)
	{
		$postfieldspre = array(
			"attachments"=>array(array(
				"fallback" => $info->UpdatedBy . " updated #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
				"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
				"pretext" => "Ticket #" . $ticket . " has been updated by " . $info->UpdatedBy . ".",
				"text" =>  $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
				"\n" . $dateformat . " | " . $info->StatusName . //Return "Date Entered / Status" string
				"\n" . $info->Resources, //Return assigned resources
				"mrkdwn_in" => array(
					"text",
					"pretext"
					)
				))
			);
	}
	else
	{
		$postfieldspre = array(
		"attachments"=>array(array(
			"fallback" => $info->UpdatedBy . " updated #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
			"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
			"pretext" => "Ticket #" . $ticket . " has been updated by " . $info->UpdatedBy . ".",
			"text" =>  $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
			"\n" . $dateformat . " | " . $info->StatusName . //Return "Date Entered / Status" string
			"\n" . $info->Resources, //Return assigned resources
			"mrkdwn_in" => array(
				"text",
				"pretext"
				)
			),
			array(
				"pretext" => "Latest Note from: " . $createdby,
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
else
{
	$skip=1;
}

if($skip==0)
{
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

if($followenabled==1)
{
	if(file_exists($dir."storage.txt")) //Check if storage file exists.
	{
		$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES); //If so, open it.
	}
	else
	{
		$f = fopen($dir."storage.txt", 'w') or die("can't open file"); //If not, create it.
		fclose($f); //Close newly created file.
		$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES); //Open it again for reading.
	}
	$lines = explode("\n",$file); //Create array with each line being it's own part of the array.
	$alerts = array(); //Create a blank array.
	foreach($lines as $line) //Read through each line in the file.
	{
		$tempex = explode("^",$line); //Explode line based on seperator from cwslack-follow.php

		if($tempex[0]==$ticket) //If the first part of the line is the ticket number..
		{
			$alerts[]=$tempex[1]; //Then add the username to the alerts array.
		}
	}
	if(!empty($alerts)) {
		foreach ($alerts as $username) //For each user in alerts array, set $postfieldspre to the follow message.
		{
			if ($_GET['action'] == "added")
			{
				if ($posttext == 0)
				{
					$postfieldspre = array(
						"channel" => "@" . $username,
						"attachments" => array(array(
							"fallback" => (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
							"text" => $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
								"\n" . "Priority: " . $info->Priority . " | " . $info->StatusName . //Return "Prority / Status" string
								"\n" . $info->Resources, //Return assigned resources
							"mrkdwn_in" => array(
								"text",
								"pretext",
								"title"
							)
						))
					);
				} else {
					$postfieldspre = array(
						"channel" => "@" . $username,
						"attachments" => array(array(
							"fallback" => (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
							"text" => $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
								"\n" . "Priority: " . $info->Priority . " | " . $info->StatusName . //Return "Prority / Status" string
								"\n" . $info->Resources, //Return assigned resources
							"mrkdwn_in" => array(
								"text",
								"pretext",
								"title"
							)
						),
							array(
								"pretext" => "Latest Note from: " . $createdby,
								"text" => $text,
								"mrkdwn_in" => array(
									"text",
									"pretext",
									"title"
								)
							))
					);
				}
			} else if ($_GET['action'] == "updated") {
				if ($posttext == 0) {
					$postfieldspre = array(
						"channel" => "@" . $username,
						"attachments" => array(array(
							"fallback" => $info->UpdatedBy . " updated #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been updated by " . $info->UpdatedBy . ".",
							"text" => $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
								"\n" . $dateformat . " | " . $info->StatusName . //Return "Date Entered / Status" string
								"\n" . $info->Resources, //Return assigned resources
							"mrkdwn_in" => array(
								"text",
								"pretext"
							)
						))
					);
				} else {
					$postfieldspre = array(
						"channel" => "@" . $username,
						"attachments" => array(array(
							"fallback" => $info->UpdatedBy . " updated #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been updated by " . $info->UpdatedBy . ".",
							"text" => $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
								"\n" . $dateformat . " | " . $info->StatusName . //Return "Date Entered / Status" string
								"\n" . $info->Resources, //Return assigned resources
							"mrkdwn_in" => array(
								"text",
								"pretext"
							)
						),
							array(
								"pretext" => "Latest Note from: " . $createdby,
								"text" => $text,
								"mrkdwn_in" => array(
									"text",
									"pretext",
									"title"
								)
							)
						)
					);
				}
			}
			$postfields = json_encode($postfieldspre);

			//cURL block.
			$ch = curl_init();
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
}

//Block for if ticket time reaches past X value
if($timeenabled==1 && $info->ActualHours>$timepast)
{
	if($_GET['action'] == "added")
	{
		if($posttext==0)
		{
			$postfieldspre = array(
				"channel"=>$timechan,
				"attachments"=>array(array(
					"fallback" => (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"color" => "#F0E68C",
					"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
					"text" =>  $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
						"\n" . "Priority: " . $info->Priority . " | " . $info->StatusName . //Return "Prority / Status" string
						"\n" . $info->Resources . " | Total Hours: *" . $info->ActualHours . "*", //Return assigned resources
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
					)
				))
			);
		}
		else
		{
			$postfieldspre = array(
				"channel"=>$timechan,
				"attachments"=>array(array(
					"fallback" => (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"color" => "#F0E68C",
					"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_GET['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
					"text" =>  $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
						"\n" . "Priority: " . $info->Priority . " | " . $info->StatusName . //Return "Prority / Status" string
						"\n" . $info->Resources . " | Total Hours: *" . $info->ActualHours . "*", //Return assigned resources
					"mrkdwn_in" => array(
						"text",
						"pretext",
						"title"
					)
				),
					array(
						"pretext" => "Latest Note from: " . $createdby,
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
	else if($_GET['action'] == "updated")
	{
		if ($posttext == 0) {
			$postfieldspre = array(
				"channel" => $timechan,
				"attachments" => array(array(
					"fallback" => $info->UpdatedBy . " updated #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
					"color" => "#F0E68C",
					"pretext" => "Ticket #" . $ticket . " has been updated by " . $info->UpdatedBy . ".",
					"text" => $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
						"\n" . $dateformat . " | " . $info->StatusName . //Return "Date Entered / Status" string
						"\n" . $info->Resources . " | Total Hours: *" . $info->ActualHours . "*", //Return assigned resources
					"mrkdwn_in" => array(
						"text",
						"pretext"
					)
				))
			);
		} else {
			$postfieldspre = array(
				"channel" => $timechan,
				"attachments" => array(array(
					"fallback" => $info->UpdatedBy . " updated #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
					"color" => "#F0E68C",
					"pretext" => "Ticket #" . $ticket . " has been updated by " . $info->UpdatedBy . ".",
					"text" => $info->CompanyName . " | " . $info->ContactName . //Return "Company / Contact" string
						"\n" . $dateformat . " | " . $info->StatusName . //Return "Date Entered / Status" string
						"\n" . $info->Resources . " | Total Hours: *" . $info->ActualHours . "*", //Return assigned resources
					"mrkdwn_in" => array(
						"text",
						"pretext"
					)
				),
					array(
						"pretext" => "Latest Note from: " . $createdby,
						"text" => $text,
						"mrkdwn_in" => array(
							"text",
							"pretext",
							"title"
						)
					)
				)
			);
		}
	}
	$postfields = json_encode($postfieldspre);

	//cURL block.
	$ch = curl_init();
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


?>
