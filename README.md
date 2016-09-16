# CWSlack-SlashCommands

This script, when hosted on a PHP supported web server, will act as a bridge between the JSON requests of Slack and the JSON responses of the ConnectWise REST API.

cwslack.php, cwslack-incoming.php, cwslack-activities.php, and cwslack-contacts.php were designed to be independent, but all rely on the config.php file. This allows you to pick and choose what you want and for different Slack commands instead of one universal /cw tickets 249123 and /cw contact john doe it can be /t 249123 and /c john doe.

For requesting ticket information using /ticket #, use cwslack.php.

For creating new activities, use cwslack-activities.php.

To post new tickets or ticket updates to a Slack channel, use cwslack-incoming.php

To search for ConnectWise contact info, use cwslack-contacts.php.

To post new notes to tickets, use cwslack-notes.php

To follow tickets and get updates whenever they're updated, use cwslack-follow.php (requires cwslack-incoming.php)

# Installation Instructions

## cwslack.php

1. Download the cwslack.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /t (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values and timezone. Full configuration info below.
10. Test it in Slack!

## cwslack-activities.php

1. Download the cwslack-activities.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at  https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /act (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack-activities.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values, Full configuration info below.
10. Test it in Slack!

## cwslack-contacts.php

1. Download the cwslack-contacts.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at  https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /act (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack-contacts.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values, Full configuration info below.
10. Test it in Slack!

## cwslack-notes.php

1. Download the cwslack-notes.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /t (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack-notes.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values and timezone. Full configuration info below.
10. Test it in Slack!

## cwslack-incoming.php

1. Download the cwslack-incoming.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack incoming webhook integration at https://my.slack.com/services/new/incoming-webhook/
4. Set a name, icon, and if wanted.
5. Set channel that you want to post to and copy the Webhook URL
6. Create a new integrator login in ConnectWise:
  - Go to System > Setup Tables in the client
  - Type "int" in the table field and select Integrator Login
  - Create a new login with whatever username/password, we don't need this.
  - Set Access Level to "All Records"
  - Enable "Service Ticket API" and select the board(s) you want this to run on.
  - Enter https://domain.tld/cwslack-incoming.php?id= for the callback URL (do not enable legacy format)
7. Modify the config.php file with your companies values and timezone, make sure to set the value for $webhookurl to the value copied in step 5.
8. Change the $postupdated and $postadded to what you prefer. Enabling $postupdated can get spammy.
9. Test it in Slack by creating a new ticket on the board you selected in step 6!


## cwslack-follow.php (Also requires cwslack-incoming.php to function)

1. Download the cwslack-follow.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at  https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /follow (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack-follow.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values, Full configuration info below.
10. Test it in Slack!

To enable ConnectWise link to follow and unfollow a ticket:

1. Open Setup Tables in ConnectWise.
2. Open the "Links" table.
3. Create a new Link referencing "Service"
4. Set the Link Name to "Slack Follow"
5. Set the Link Definition to https://yourdomain.tld/cwslack-follow.php?memberid=[memberid]&srnumber=[srnumber]&method=follow
6. Create a new Link referencing "Service"
7. Set the Link Name to "Slack Unfollow"
8. Set the Link Definition to https://yourdomain.tld/cwslack-follow.php?memberid=[memberid]&srnumber=[srnumber]&method=unfollow
9. Change the "method" on these links to whatever you set your $followtoken and $unfollowtoken to in config.php.
10. Test the links!


# API Key Setup

1. Login to ConnectWise
2. In the top right, click on your name
3. Go to "My Account"
4. Select the "API Keys" tab
5. Click the Plus icon to create a new key
6. Provide a description and click the Save icon.
7. Save this information, you cannot retrieve the private key ever again so if lost you will need to create new ones.

# Config.php configuration

\* Asterisk denotes required.  
\+ Plus denotes required for cwslack-activities.php  
\- Minus denotes required for cwslack-incoming.php  
\# Pound denotes required for cwslack-follow.php  
\& Ampersand denotes required for cwslack-contacts.php  
\^ Caret denotes required for cwslack-notes.php

* $connectwise * : This value needs to be set to your main connectwise URL.
  * Users of Hosted ConnectWise will need to use https://api-na.myconnectwise.net, https://api-eu.myconnectwise.net or https://api-au.myconnectwise.net
* $companyname * : This value needs to be set to whatever your company name is that you use to login to ConnectWise.
* $apipublickey * : Set to your Public Key from API setup
* $apiprivatekey * : Set to your Private Key from API setup
* $slacktoken * : Set to the token you got when creating a new slash command integration in Slack.
* $timezone * : Set to your timezone according to http://php.net/manual/en/timezones.america.php .


* $slackactivitiestoken + : Set to the token you got when creating a new slash command integration in Slack for /activities.


* $webhookurl - : Set to the incoming webhook URL you got when creating a new incoming webhook in Slack.
* $postadded - : Set to 1 if you want it to post new tickets to chat.
* $postupdated - : Set to 1 if you want it to post new ticket updates to chat.
* $allowzadmin - : Set to 1 if you want posts from zAdmin to go to chat. Set to 0 by default to avoid spam in high volume environments.
* $badboard : Set this if you have a specific board that spams a lot, set to Alerts by default to hide alerts board posts.
* $badstatus : Set this if you have a status you want to ignore, set to Closed by default as tickets are rarely automatically closed.
* $badcompany : Set this if you have a company you want to ignore, set to CatchAll by default to avoid spam from unknown incoming e-mails.
* $posttext : Set this to 1 if you want to include the latest note with the Slack message. Set to 1 by default now.


* $slackfollowtoken # : Set to the token you got when creating a new slash command integration in Slack for /follow.
* $followenabled # : Defaults to 0, you need to change this to 1 if you want to enable follow.
* $dir # : Directory on the server to store files. Please note that the user running php (www-data in Linux) needs write/read access to this folder.
* $followtoken : Change to any value and use this in the ConnectWise link tables.
* $unfollowtoken : Change to any value and use this in the ConnectWise link tables.


* $slackcontactstoken & : Set to the token you got when creating a new slash command integration in Slack for /contact.


* $slacknotestoken ^ : Set to the token you got when creating a new slash command intregration in Slack for /notes.
* $usecwname ^ : If set to 0 which is default, notes are posted as user that is attached to API key. If set to 1, then it will post as the correct CW user but all names in Slack must be the same as in CW.


* $helpurl : Set to a help document explaining the slash command usage. Feel free to point to this GitHub repo, but ideally you would make it look pretty on your own internal site.


# Command Usage

\* denotes required

## cwslack.php

/t [ticket number]* [command] [option3]

### status

option3 should be n2s/scheduled/completed

### priority

option3 should be low/moderate/critical

### full (or) notes (or) all

If $posttext=1 in config.php, shows you the latest note and the initial note. This displays to you only to avoid spam.

### initial (or) note (or) first

If $posttext=1 in config.php, shows you the initial note of the ticket. This displays to you only to avoid spam.

## cwslack-activities.php

/act new\*|[activity title]\*|[assigned to]*

All are required for activities. New will be replaced with more commands in the future.

## cwslack-follow.php

/follow [ticket number]* (unfollow)

Add unfollow to the end of the command to stop following a ticket.

## cwslack-contacts.php

/contact [last name]* OR [first name] [last name]

Either option works, but you cannot search by first name only.

## cwslack-notes.php

/note [ticket number]* [internal OR external]* [ticket note]*

This does allow spaces for the ticket note so do not wrap in quotation marks or anything.