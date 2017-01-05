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


//SET THESE VARIABLES
//

//General configuration, Required for all PHP scripts to function!!
$connectwise = "https://cw.domain.com"; //Set your Connectwise URL
$companyname = "MyCompany"; //Set your company name from Connectwise. This is the company name field from login.
$apipublickey = "Key"; //Public API key
$apiprivatekey = "Key"; //Private API key
$timezone = "America/Chicago"; //Set your timezone here.

// Database Configuration, required for if you want to use MySQL/Maria DB features.
$usedatabase = 0; // Set to 0 by default, set to 1 if you want to enable MySQL.
$dbhost = "127.0.0.1"; //Your MySQL DB
$dbusername = "username"; //Your MySQL DB Username
$dbpassword = "password"; //Your MySQL DB Password
$dbdatabase = "cwslack"; //Change if you have an existing database you want to use, otherwise leave as default.

//cwslack.php
$slacktoken = "Slack Token Here"; //Set token from the Slack slash command screen.
$useboards = 1; //Use the board function in new tickets. /t new company|summary vs /t new board|company|summary

//cwslack-activities.php
$slackactivitiestoken = "Slack Token Here"; //Set your token for the activities slash command

//cwslack-contacts.php
$slackcontactstoken = "Slack Token Here"; //Set your token for the contacts slash command

//cwlsack-notes.php
$slacknotestoken = "Slack Token Here"; //Set your token for the notes slash command
$usecwname = 1; //If set to 1, it will create tickets using the user's slack name. Command will fail if their slack name is not the same as connectwise name.

//cwslack-configs.php
$slackconfigstoken = "Slack Token Here"; //Set your token for the configs slash command
$hidepasswords = 0; //Set to 1 if you want to hide passwords.

//cwslack-tasks.php
$slacktaskstoken = "Slack Token Here"; //Set your token for the tasks slash command

//cwslack-time.php
$slacktimetoken = "Slack Token Here"; //Set your token for the time slash command
$timedetailworktype = "Remote Support"; //Set to the worktype name you want to use when a note is posted to detailed
$timeinternalworktype = "Admin"; //Set to the worktype name you want to use when a note is posted to internal
$timeresolutionworktype = "Remote Support"; //Set to the worktype name you want to use when a note is posted to resolution

//cwslack-incoming.php
$webhookurl = "https://hooks.slack.com/services/tokens"; //Change this to the URL retrieved from incoming webhook setup for Slack.
$postadded = 1; //Set this to post new tickets to slack.
$postupdated = 0; //Set this to post updated tickets to slack. Defaults to off to avoid spam
$allowzadmin = 0; //Set this to allow posts from zAdmin, warning as zAdmin does workflow rules so update spam is countered, however new client tickets are through zAdmin. To avoid insane spam, do not have this turned on while $postupdated is turned on. 
$posttext = 1; //Set to 1 if you want it to post the latest note from the ticket into chat whenever a ticket is created or updated.
$postcompany = 1; //Set to 1 if you want the Company to be posted in the clear text of the post (general what will be seen on IRC/XMPP)
$timeenabled = 0; //Set to 1 if you want to post all tickets past $timepast to a specific channel, $timechan
$timepast = 1.0; //Set to a time in hours where once reached all updates will post to #dispatch.
$timechan = "#ticketstime"; //Set to a channel to post to for $timeenabled
//"Bad" variables to block certain things from coming through in cwslack-incoming.php. Separate by a pipe symbol | to have multiple.
$badboard = "Alerts"; //Set to any board name you want to fail, to avoid ticket creation/updates from this board posting to Slack.
$badstatus = "Closed|Canceled"; //Set to any status name you want to fail, to avoid ticket creation/updates with this status from posting to Slack.
$badcompany = "CatchAll (for email connector)"; //Set to any company name you want to fail, to avoid ticket creation for catchall from posting to Slack.

//cwslack-firmalerts.php
//This uses the variables $webhookurl and $timechan from cwslack-incoming.php above.
$posttousers = 1; //When set, will post to the user whenever the appointment reminder is reached.
$posttochan = 1; //When set, will post to $timechan whenever the firm appointment starts.
$usetimechan = 1; //When set, this will use the $timechan variable instead of the one below.
$firmalertchan = "#dispatch"; //When you want to split time alerts and firm alerts into their own channels.

//cwslack-follow.php
//Requires cwslack-incoming.php to function.
$slackfollowtoken = "Slack Token Here"; //Set your token for the follow slash command
$followenabled = 0; //When set to 1, follow commands and the follow scripts will be enabled.
$followtoken = "follow"; //Change to random text to be used in your CW follow link if you use it. Defaults to follow which is fine for testing.
$unfollowtoken = "unfollow"; //Change to random text to be used in your CW unfollow link if you use it. Defaults to unfollow which is fine for testing.

//cwslack-dbmanage.php
$slackdbmantoken = "Slack Token Here"; //Set your token for the database management slash command
$adminlist = "admin1|admin2"; //Separate by pipe symbol as seen in example if you need multiple people to have access.

//Change optional
$helpurl = "https://github.com/jundis/CWSlack-SlashCommands"; //Set your help article URL here.

//
//Don't modify below unless you know what you're doing!
//

//Timezone Setting to be used for all files.
date_default_timezone_set($timezone);


?>
