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


//SET THESE VARIABLES
//

//General configuration, Required for all PHP scripts to function!!
$connectwise = "https://cw.domain.com"; //Set your Connectwise URL
$connectwisebranch = "v4_6_release"; //Set to the portion of your CW URL shown here: https://cw.domain.com/**v4_6_release**/ConnectWise.aspx
$companyname = "MyCompany"; //Set your company name from Connectwise. This is the company name field from login.
$apipublickey = "Key"; //Public API key
$apiprivatekey = "Key"; //Private API key
$timezone = "America/Chicago"; //Set your timezone here.
$timeoutfix = false; //Enable to fix any 3000ms response from Slack.
$sendtimeoutwait = false; //Set to true to send a please wait message with every command. Only does something when $timeoutfix is set to true.

// Database Configuration, required for if you want to use MySQL/Maria DB features.
$usedatabase = 0; // Set to 0 by default, set to 1 if you want to enable MySQL.
$dbhost = "127.0.0.1"; //Your MySQL DB
$dbusername = "username"; //Your MySQL DB Username
$dbpassword = "password"; //Your MySQL DB Password
$dbdatabase = "cwslack"; //Change if you have an existing database you want to use, otherwise leave as default.

//E-mail configuration, required for lunch module mail functions
$smtpserver = "smtp.domain.com"; //Set your SMTP server her
$smtpport = 25; //Set your SMTP port. Defaults: 25 (No security), 465 (SSL), 587 (TLS)
$smtpfrom = "notifications@domain.com"; //Set to the address all mail should be sent from
$smtpname = "Company Notifications"; //Set to what you want e-mails to appear as coming from. E.x. Company Notifications <notfications@domain.com>

//cwslack.php
$slacktoken = "Slack Token Here"; //Set token from the Slack slash command screen.
$useboards = 1; //Use the board function in new tickets. /t new company|summary vs /t new board|company|summary

//cwslack-activities.php
$slackactivitiestoken = "Slack Token Here"; //Set your token for the activities slash command

//cwslack-contacts.php
$slackcontactstoken = "Slack Token Here"; //Set your token for the contacts slash command
$inactivecontacts = false; //Set to true to return inactive contacts

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
$timebusinessstart = "8:00AM"; //Set to when your business opens in your timezone
$timebusinessclose = "5:00PM"; //Set to when your business closes in your timezone

//cwslack-incoming.php
$webhookurl = "https://hooks.slack.com/services/tokens"; //Change this to the URL retrieved from incoming webhook setup for Slack.
$postadded = 1; //Set this to post new tickets to slack.
$postupdated = 0; //Set this to post updated tickets to slack. Defaults to off to avoid spam
$posttext = 1; //Set to 1 if you want it to post the latest note from the ticket into chat whenever a ticket is created or updated.
$postcompany = 1; //Set to 1 if you want the Company to be posted in the clear text of the post (general what will be seen on IRC/XMPP)
$timeenabled = 0; //Set to 1 if you want to post all tickets past $timepast to a specific channel, $timechan
$timepast = 1.0; //Set to a time in hours where once reached all updates will post to #dispatch.
$timechan = "#ticketstime"; //Set to a channel to post to for $timeenabled
//"Bad" variables to block certain things from coming through in cwslack-incoming.php. Separate by a pipe symbol | to have multiple.
$badboard = "Alerts"; //Set to any board name you want to fail, to avoid ticket creation/updates from this board posting to Slack.
$badstatus = "Closed|Canceled"; //Set to any status name you want to fail, to avoid ticket creation/updates with this status from posting to Slack.
$badcompany = "CatchAll (for email connector)"; //Set to any company name you want to fail, to avoid ticket creation for catchall from posting to Slack.
//Example $boardmapping = "Alerts|alerts,Customer Support|support,Incoming|dispatch"; This would send Alerts to #alerts, Customer Support to #support, Incoming to #dispatch, and Orders to the channel specified on Slack's webhook page.
$boardmapping = ""; //Put board to channel mappings in here. Formatted as "Board Name|channel,Board Name|channel". Any board not covered will go to the default channel for the webhook, filter boards using $badboard. Example above

//cwslack-firmalerts.php
//This uses the variables $webhookurl and $timechan from cwslack-incoming.php above.
$posttousers = 1; //When set, will post to the user whenever the appointment reminder is reached.
$posttochan = 1; //When set, will post to $timechan whenever the firm appointment starts.
$usetimechan = 1; //When set, this will use the $timechan variable instead of the one below.
$firmalertchan = "#dispatch"; //When you want to split time alerts and firm alerts into their own channels.

//cwslack-timealerts.php
//This uses all four variables above
$notimeusers = "user1|user2"; //Usernames of users who should not be alerted on. Useful if you have techs who occasionally enter time and you don't want it pinging them every day. Separate with pipe |
$specialtimeusers = "user1,7:00am-4:00pm|user2,9:00am-6:00pm"; //Usernames of users who should be alerted on, but who have special hours different from default start-close. Seperate user and time with comma, seperate different users with pipe |. No spaces

//cwslack-priorityalerts.php
//This uses all the variables from firmalerts as well, adhering to it for whether to post to users/channel and which channel
$prioritylist = "High|Critical"; // Name of the priority(ies) to look out for. Separate by pipe if more than one needed.
$prioritystatus = "Scheduled|Scheduled -Notify"; // Status(es), seperated by pipe | symbol, which the priority alerts will check for and send alerts on.
$prioritywait = 30; // Number of minutes to wait after a high-priority event before alerting the technician. Maximum 119 minutes.

//cwslack-follow.php
//Requires cwslack-incoming.php to function.
$slackfollowtoken = "Slack Token Here"; //Set your token for the follow slash command
$followenabled = 0; //When set to 1, follow commands and the follow scripts will be enabled.
$followtoken = "follow"; //Change to random text to be used in your CW follow link if you use it. Defaults to follow which is fine for testing.
$unfollowtoken = "unfollow"; //Change to random text to be used in your CW unfollow link if you use it. Defaults to unfollow which is fine for testing.

//cwslack-lunch.php
//E-mail functionality requires you to setup your system for PHP mail(), info below
//Windows: https://stackoverflow.com/questions/4652566/php-mail-setup-in-xampp
//Linux: http://lukepeters.me/blog/getting-the-php-mail-function-to-work-on-ubuntu
$slacklunchtoken = "test"; // Set your token for the lunch slash command
$lunchchargecode = 41; // Set to your "Break" charge code that lunches should be put under
$lunchtime = 60; // Expected number of MINUTES that a user is on lunch
$lunchmax = 120; // Number of minutes to allow before cancelling the lunch entry, does not submit time
$lunchsendslack = true; // Send messages to a slack channel when a user goes on/off lunch
$lunchsendemail = false; // Send messages to an e-mail address when a user goes on/off lunch
$lunchsendonoff = 1; // Key: 0 = No notifications, 1 = Send notifications when a user goes on lunch, 2 = Send when a user goes off lunch, 3 = Send when a user goes on lunch OR off lunch
$lunchslackchannel = "general"; // Channel to send Slack messages to
$lunchemailto = "allstaff@domain.com"; // E-mail address to send messages to
$lunchcreatesched = true; // Should the script create a schedule entry on the users board
$lunchsavetime = true; // Should the script submit a time entry for the user for their lunch duration

//cwslack-dbmanage.php
$slackdbmantoken = "Slack Token Here"; //Set your token for the database management slash command
$adminlist = "admin1|admin2"; //Separate by pipe symbol as seen in example if you need multiple people to have access.

//Change optional
$helpurl = "https://github.com/jundis/CWSlack-SlashCommands"; //Set your help article URL here.

// Variable below used for advanced diagnostics. $timeoutfix will be set to false automatically when this is turned on.
$debugmode = false;

//
//Don't modify below unless you know what you're doing!
//

//Timezone Setting to be used for all files.
date_default_timezone_set($timezone);

//Debug mode
if($debugmode) //If debug mode is on..
{
    $timeoutfix = false; //Set timeoutfix to false so that all data is returned properly as ephemeral messages.
}


?>
