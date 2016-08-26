<?php
//Receive connector for Connectwise Callbacks
ini_set('display_errors', 1); //Display errors in case something occurs
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php'; //Require the config file.

$data = json_decode(file_get_contents('php://input')); //Decode incoming body from connectwise callback.
$info = json_decode(stripslashes($data->Entity)); //Decode the entity field which contains the JSON data we want.

if(empty($_GET['id']) || empty($_GET['action']) || $_GET['isInternalAnalysis']=="True" || empty($info)) die; //If anything we need doesn't exist, kill connection.

if(strtolower($_GET['memberId'])=="zadmin" && $allowzadmin == 0) die; //Die if $allowzadmin is not enabled.
if(strtolower($info->BoardName)==strtolower($badboard)) die; //Kill connection if board is listed as $badboard variable.
if(strtolower($info->StatusName)==strtolower($badstatus)) die; //Kill connection if status is listed as the $badstatus variable.

$ticketurl = $connectwise . "/v4_6_release/services/system_io/Service/fv_sr100_request.rails?service_recid=";
$header_data =array(
 "Content-Type: application/json"
);

$date=strtotime($info->EnteredDateUTC); //Convert date entered JSON result to time.
$dateformat=date('m-d-Y g:i:sa',$date); //Convert previously converted time to a better time string.
$ticket=$_GET['id'];

$ch = curl_init();

if($_GET['action'] == "added" && $postadded == 1)
{
	if(strtolower($_GET['memberId'])=="zadmin")
	{
		$postfieldspre = array(
			"attachments"=>array(array(
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
else if($_GET['action'] == "updated" && $postupdated == 1)
{
	$postfieldspre = array(
		"attachments"=>array(array(
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
	die;
}

$postfields = json_encode($postfieldspre);

$curlOpts = array(
	CURLOPT_URL => $webhookurl,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HTTPHEADER => $header_data,
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

?>
