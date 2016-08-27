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

if(empty($_GET['id']) || empty($_GET['action']) || $_GET['isInternalAnalysis']=="True" || empty($info)) die; //If anything we need doesn't exist, kill connection.

if(strtolower($_GET['memberId'])=="zadmin" && $allowzadmin == 0) die; //Die if $allowzadmin is not enabled.
if(strtolower($info->BoardName)==strtolower($badboard)) die; //Kill connection if board is listed as $badboard variable.
if(strtolower($info->StatusName)==strtolower($badstatus)) die; //Kill connection if status is listed as the $badstatus variable.
if(strtolower($info->CompanyName)==strtolower($badcompany)) die; //Kill connection if company is listed as the $badcompany variable.

$ticketurl = $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid=";
$noteurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $_GET['id'] . "/notes?orderBy=id%20desc";

$dataTData = array(); //Blank array.

$header_data =array(
 "Authorization: Basic ". $authorization,
);
$header_data2 =array(
 "Content-Type: application/json"
);

$skip = 0;
$date=strtotime($info->EnteredDateUTC); //Convert date entered JSON result to time.
$dateformat=date('m-d-Y g:i:sa',$date); //Convert previously converted time to a better time string.
$ticket=$_GET['id'];

if($posttext==1) //Block for curl to get latest note
{
	$ch1 = curl_init(); //Initiate a curl session_cache_expire

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
		die(curl_error($ch1));
	}
	curl_close($ch1);

	$dataTData = json_decode($curlBodyTData); //Decode the JSON returned by the CW API.
}

$ch = curl_init();

if($_GET['action'] == "added" && $postadded == 1)
{
	if($posttext==0)
	{
		if(strtolower($_GET['memberId'])=="zadmin")
		{
			$postfieldspre = array(
				"attachments"=>array(array(
					"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"pretext" => "Ticket #" . $ticket . " has been created by " . $info->ContactName . ".",
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
					"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"pretext" => "Ticket #" . $ticket . " has been created by " . $info->UpdatedBy . ".",
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
	}
	else
	{
		if(strtolower($_GET['memberId'])=="zadmin")
		{
			$postfieldspre = array(
				"attachments"=>array(array(
					"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"pretext" => "Ticket #" . $ticket . " has been created by " . $info->ContactName . ".",
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
						"pretext" => "Latest Note from: " . $dataTData[0]->createdBy,
						"text" =>  $dataTData[0]->text,
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
					"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"pretext" => "Ticket #" . $ticket . " has been created by " . $info->UpdatedBy . ".",
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
						"pretext" => "Latest Note from: " . $dataTData[0]->createdBy,
						"text" =>  $dataTData[0]->text,
						"mrkdwn_in" => array(
							"text",
							"pretext",
							"title"
							)
					))
				);
		}
	}
}
else if($_GET['action'] == "updated" && $postupdated == 1)
{
	if($posttext==0)
	{
		$postfieldspre = array(
			"attachments"=>array(array(
				"fallback" => "Updated ticket #" . $ticket . " - " . $info->Summary,
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
			"fallback" => "Updated ticket #" . $ticket . " - " . $info->Summary,
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
				"pretext" => "Latest Note from: " . $dataTData[0]->createdBy,
				"text" =>  $dataTData[0]->text,
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
	if(file_exists($dir."storage.txt"))
	{
		$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES);
	}
	else
	{
		$f = fopen($dir."storage.txt") or die("can't open file");
		fclose($f);
		$file = file_get_contents($dir."/storage.txt",FILE_SKIP_EMPTY_LINES);
	}
	$lines = explode("\n",$file);
	$alerts = array();
	foreach($lines as $line)
	{
		$tempex = explode("^",$line);

		if($tempex[0]==$ticket)
		{
			$alerts[]=$tempex[1];
		}
	}
	if(empty($alerts)) die;
	foreach($alerts as $username)
	{
		if($_GET['action'] == "added")
		{
			if($posttext==0)
			{
				if(strtolower($_GET['memberId'])=="zadmin")
				{
					$postfieldspre = array(
						"channel"=>"@".$username,
						"attachments"=>array(array(
							"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . $info->ContactName . ".",
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
						"channel"=>"@".$username,
						"attachments"=>array(array(
							"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . $info->UpdatedBy . ".",
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
			}
			else
			{
				if(strtolower($_GET['memberId'])=="zadmin")
				{
					$postfieldspre = array(
						"channel"=>"@".$username,
						"attachments"=>array(array(
							"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . $info->ContactName . ".",
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
								"pretext" => "Latest Note from: " . $dataTData[0]->createdBy,
								"text" =>  $dataTData[0]->text,
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
						"channel"=>"@".$username,
						"attachments"=>array(array(
							"fallback" => "New ticket #" . $ticket . " - " . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . $info->UpdatedBy . ".",
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
								"pretext" => "Latest Note from: " . $dataTData[0]->createdBy,
								"text" =>  $dataTData[0]->text,
								"mrkdwn_in" => array(
									"text",
									"pretext",
									"title"
									)
							))
						);
				}
			}
		}
		else if($_GET['action'] == "updated")
		{
			if($posttext==0)
			{
				$postfieldspre = array(
					"channel"=>"@".$username,
					"attachments"=>array(array(
						"fallback" => "Updated ticket #" . $ticket . " - " . $info->Summary,
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
					"channel"=>"@".$username,
					"attachments"=>array(array(
						"fallback" => "Updated ticket #" . $ticket . " - " . $info->Summary,
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
							"pretext" => "Latest Note from: " . $dataTData[0]->createdBy,
							"text" =>  $dataTData[0]->text,
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

?>
