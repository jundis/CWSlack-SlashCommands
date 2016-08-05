<?php

//SET THESE VARIABLES

//

$connectwise = "https://cw.domain.com"; //Set your Connectwise URL
$companyname = "MyCompany"; //Set your company name from Connectwise. This is the company name field from login.
$apipublickey = "Key"; //Public API key
$apiprivatekey = "Key"; //Private API key
$slacktoken = "Slack Token Here"; //Set token from the Slack slash command screen.
$slackactivitiestoken = "Slack Token Here"; //Set your token for the activities slash command, if you're using cwslack-activities.php
$helpurl = "https://companyknowledgebase.com/document"; //Set your help article URL here.
$webhookurl = "https://hooks.slack.com/services/tokens" //Change this if you intend to use cwslack-incoming.php
$postadded = 1; //Set this to post new tickets to slack.
$postupdated = 0; //Set this to post updated tickets to slack. Defaults to off to avoid spam

?>