<?php

//SET THESE VARIABLES
//

//General configuration, Required!!
$connectwise = "https://cw.domain.com"; //Set your Connectwise URL
$companyname = "MyCompany"; //Set your company name from Connectwise. This is the company name field from login.
$apipublickey = "Key"; //Public API key
$apiprivatekey = "Key"; //Private API key
$slacktoken = "Slack Token Here"; //Set token from the Slack slash command screen.
$timezone = "America/Chicago"; //Set your timezone here. 

//cwslack-activities.php
$slackactivitiestoken = "Slack Token Here"; //Set your token for the activities slash command

//cwslack-incoming.php
$webhookurl = "https://hooks.slack.com/services/tokens"; //Change this to the URL retrieved from incoming webhook setup for Slack.
$postadded = 1; //Set this to post new tickets to slack.
$postupdated = 0; //Set this to post updated tickets to slack. Defaults to off to avoid spam
$allowzadmin = 0; //Set this to allow posts from zAdmin, warning as zAdmin does workflow rules so update spam is countered, however new client tickets are through zAdmin. To avoid insane spam, do not have this turned on while $postupdated is turned on. 
$badboard = "Alerts"; //Set to any board name you want to fail, to avoid ticket creation/updates from this board posting to Slack.
$badstatus = "Closed"; //Set to any status name you want to fail, to avoid ticket creation/updates with this status from posting to Slack.
$badcompany = "CatchAll (for email connector)"; //Set to any company name you want to fail, to avoid ticket creation for catchall from posting to Slack.
$posttext = 1; //Set to 1 if you want it to post the latest note from the ticket into chat whenever a ticket is created or updated.

//cwslack-follow.php
$slackfollowtoken = "Slack Token Here"; //Set your token for the follow slash command
$followenabled=0; //When set to 1, follow commands and the follow scripts will be enabled.
$dir="/var/www/storage/"; //Needs to be set to a directory writeable by PHP for storage of the storage.txt file.


//Change optional
$helpurl = "https://github.com/jundis/CWSlack-SlashCommands"; //Set your help article URL here.

//
//Don't modify below unless you know what you're doing!
//

//Timezone Setting to be used for all files.
date_default_timezone_set($timezone);
if ( !file_exists($dir) ) {
     $oldmask = umask(0);  // helpful when used in linux server  
     mkdir ($dir, 0744);
 }

?>
