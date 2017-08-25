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

//Receive connector for Connectwise Callbacks
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php'; //Require the config file.
require_once 'functions.php';

$data = json_decode(file_get_contents('php://input')); //Decode incoming body from connectwise callback.
if($data==NULL)
{
	die("No ticket data was submitted. This is expected behavior if you are just browsing to this page with a web browser.");
}
$info = json_decode(stripslashes($data->Entity)); //Decode the entity field which contains the JSON data we want.

//Connection kill blocks. Stops things from running if certain conditions are met.
if(empty($_REQUEST['id']) || empty($_REQUEST['action']) || empty($info)) die; //If anything we need doesn't exist, kill connection.

if($_REQUEST['action'] == "updated" && $_REQUEST['srDetailRecId']==0 && $_REQUEST['timeRecId']==0) die; //Kill connection if the update is not a note, and is something like a status change. This will prevent duplicate entries.

if($_REQUEST['isProblemDescription']=="False" && $_REQUEST['isInternalAnalysis']=="False" && $_REQUEST['isResolution']=="False") die; //Die if no actual update.

$badboards = explode("|",$badboard); //Explode with pipe seperator.
$badstatuses = explode("|",$badstatus); //Explode with pipe seperator.
$badcompanies = explode("|",$badcompany); //Explode with pipe seperator.
if (in_array($info->BoardName,$badboards)) die;
if (in_array($info->StatusName,$badstatuses)) die;
if (in_array($info->CompanyName,$badcompanies)) die;

$channel = NULL; //Set channel to NULL for future use.

if (!empty($boardmapping))
{
	$explode = explode(",",$boardmapping);
	foreach($explode as $item) {
		$temp = explode("|",$item);
		if(strcasecmp($temp[0],$info->BoardName) == 0) {
			$channel = $temp[1];
		}
	}
}
else if (!empty($_REQUEST['board']))
{
	if(strpos($_REQUEST['board'], "-") !== false)
	{
		$tempboards = explode("-", $_REQUEST['board']);
		if(!in_array($info->BoardName, $tempboards))
		{
			die("Incorrect board");
		}
	}
	else if($_REQUEST['board'] != $info->BoardName)
	{
		die("Incorrect board");
	}

	if(!empty($_REQUEST['channel']))  //If using channels in URL is set, and channel is not empty..
	{
		$channel = $_REQUEST['channel']; //Set $channel to the channel.
	}
}

/* Uncomment this block and copy/paste as many times as necessary to setup additional web hook urls.
if($info->BoardName == "BOARDNAME")
{
	$webhookurl = "url";
}
*/

//URL creation
$ticketurl = $connectwise . "/$connectwisebranch/services/system_io/Service/fv_sr100_request.rails?service_recid="; //Set the URL required for ticket links.
$noteurl = $connectwise . "/$connectwisebranch/apis/3.0/service/tickets/" . $_REQUEST['id'] . "/notes?orderBy=id%20desc"; //Set the URL required for cURL requests to ticket note API.
$timeurl = $connectwise . "/$connectwisebranch/apis/3.0/time/entries?conditions=chargeToId=" . $_REQUEST['id'] . "&chargeToType=%27ServiceTicket%27&orderBy=dateEntered%20desc"; //Set the URL required for cURL requests to the time entry API.

$dataTData = array(); //Blank array.
$dataTimeData = array(); //Blank array.

//Set headers for cURL requests. $header_data covers API authentication while $header_data2 covers the Slack output.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey); // Authorization array. Auto encodes API key for auhtorization.
$header_data2 =array(
 "Content-Type: application/json"
);

$skip = 0; //Create variable to skip posting to Slack channel while also allowing follow posts.
$date=strtotime($info->EnteredDateUTC); //Convert date entered JSON result to time.
$dateformat=date('m-d-Y g:i:sa',$date); //Convert previously converted time to a better time string.
$ticket=$_REQUEST['id'];
$usetime = 0; //For posttext internal vs external flag.
$dataarray = NULL; //For internal vs external flag.
$dateformat = "None"; //Just in case!

if($posttext==1) //Block for curl to get latest note
{
	$createdby = "Error"; //Create with error just in case.
	$notetext = "Error"; //Create with error just in case.

	$dataTData = cURL($noteurl, $header_data); //Decode the JSON returned by the CW API.

	if($posttext==1) //Verifies no curl error occurred. If one has, ignore $posttext.
	{
		$dataTimeData = cURL($timeurl, $header_data); //Decode the JSON returned by the CW API.

		if($dataTData == NULL && $dataTimeData == NULL)
		{
			$posttext = 0;
		}

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
					$text = $dataTimeData[0]->notes; //
					$usetime = 1; //Set time flag.
				}
			}
		}
		else
		{
			$posttext=0; //If text is null, ensure posttext = 0.
		}

		if ($usetime == 1)
		{
			$dataarray = $dataTimeData[0];
			$notedate = $dataTimeData[0]->dateEntered;
			$dateformat2=date('m-d-Y g:i:sa',strtotime($notedate));
		}
		else
		{
			$dataarray = $dataTData[0];
			$notedate = $dataTData[0]->dateCreated;
			$dateformat2=date('m-d-Y g:i:sa',strtotime($notedate));
		}

	}
}

if($_REQUEST['action'] == "added" && $postadded == 1)
{
	if($posttext==0)
	{
		$postfieldspre = array(
			"channel" => ($channel!=NULL ? "#" . $channel : NULL),
			"attachments"=>array(array(
				"fallback" => (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
				"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
				"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
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
			"channel" => ($channel!=NULL ? "#" . $channel : NULL),
			"attachments"=>array(array(
				"fallback" => (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
				"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
				"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
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
					"pretext" => "Latest " . ($dataarray->internalAnalysisFlag == "true" ? "Internal" : "External") . " Note (" . $dateformat2 . ") from: " . $createdby,
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
else if($_REQUEST['action'] == "updated" && $postupdated == 1)
{
	if($posttext==0)
	{
		$postfieldspre = array(
			"channel" => ($channel!=NULL ? "#" . $channel : NULL),
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
			"channel" => ($channel!=NULL ? "#" . $channel : NULL),
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
					"pretext" => "Latest " . ($dataarray->internalAnalysisFlag == "true" ? "Internal" : "External") . " Note (" . $dateformat2 . ") from: " . $createdby,
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
	cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
}

if($followenabled==1)
{
	$alerts = array(); //Create a blank array.

	if($usedatabase==1)
	{
		$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL
		if (!$mysql) //Check for errors
		{
			die("Connection Error: " . mysqli_connect_error()); //Die with error if error found
		}

		$val1 = mysqli_real_escape_string($mysql,$ticket);
		$sql = "SELECT * FROM `follow` WHERE `ticketnumber`=\"" . $val1 . "\""; //SQL Query to select all ticket number entries

		$result = mysqli_query($mysql, $sql); //Run result

		if(mysqli_num_rows($result) > 0) //If there were rows matching query
		{
			while($row = mysqli_fetch_assoc($result)) //While we still have rows to work with
			{
				$alerts[]=$row["slackuser"]; //Add user to alerts array.
			}
		}
	}
	else
	{
		die();
	}

	if(!empty($alerts)) {
		foreach ($alerts as $username) //For each user in alerts array, set $postfieldspre to the follow message.
		{
			if ($_REQUEST['action'] == "added")
			{
				if ($posttext == 0)
				{
					$postfieldspre = array(
						"channel" => "@" . $username,
						"attachments" => array(array(
							"fallback" => (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
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
							"fallback" => (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
							"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: " . $info->Summary,
							"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
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
								"pretext" => "Latest " . ($dataarray->internalAnalysisFlag == "true" ? "Internal" : "External") . " Note (" . $dateformat2 . ") from: " . $createdby,
								"text" => $text,
								"mrkdwn_in" => array(
									"text",
									"pretext",
									"title"
								)
							))
					);
				}
			} else if ($_REQUEST['action'] == "updated") {
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
								"pretext" => "Latest " . ($dataarray->internalAnalysisFlag == "true" ? "Internal" : "External") . " Note (" . $dateformat2 . ") from: " . $createdby,
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

			cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
		}
	}
}

//Block for if ticket time reaches past X value
if($timeenabled==1 && $info->ActualHours>$timepast)
{
	if($_REQUEST['action'] == "added")
	{
		if($posttext==0)
		{
			$postfieldspre = array(
				"channel"=>$timechan,
				"attachments"=>array(array(
					"fallback" => (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"color" => "#F0E68C",
					"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
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
					"fallback" => (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) ." created #" . $ticket . " - " . ($postcompany ? "(" . $info->CompanyName . ") " : "") . $info->Summary,
					"title" => "<" . $ticketurl . $ticket . "&companyName=" . $companyname . "|#" . $ticket . ">: ". $info->Summary,
					"color" => "#F0E68C",
					"pretext" => "Ticket #" . $ticket . " has been created by " . (strtolower($_REQUEST['memberId'])=="zadmin" ? $info->ContactName : $info->UpdatedBy) . ".",
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
						"pretext" => "Latest " . ($dataarray->internalAnalysisFlag == "true" ? "Internal" : "External") . " Note (" . $dateformat2 . ") from: " . $createdby,
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
	else if($_REQUEST['action'] == "updated")
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
						"pretext" => "Latest " . ($dataarray->internalAnalysisFlag == "true" ? "Internal" : "External") . " Note (" . $dateformat2 . ") from: " . $createdby,
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

	cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
}


?>
