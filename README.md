# CWSlack-SlashCommands

This script, when hosted on a PHP supported web server, will act as a bridge between the JSON requests of Slack and the JSON responses of the ConnectWise REST API.

cwslack.php and cwslack-activities.php were designed to be independent, but both rely on the config.php file.



# Installation Instructions

## cwslack.php

1. Download the cwslack.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /t (or other if you prefer)
5. Set the URL to http://domain.tld/cwslack.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values and timezone.
10. Test it in Slack!

## cwslack-activities.php

1. Download the cwslack.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at  https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /act (or other if you prefer)
5. Set the URL to http://domain.tld/cwslack-activities.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values, make sure to set the specific $slackactivitiestoken to the one for the activities slash command.
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
  - Enter http://domain.tld/cwslack-incoming.php?id= for the callback URL (do not enable legacy format)
7. Modify the config.php file with your companies values and timezone, make sure to set the value for $webhookurl to the value copied in step 5.
8. Change the $postupdated and $postadded to what you prefer. Enabling $postupdated can get spammy.
9. Test it in Slack by creating a new ticket on the board you selected in step 6!

# Command Usage

\* denotes required

## cwslack.php

/t [ticket number]* [command] [option3]

### status

option3 should be n2s/scheduled/completed

### priority

option3 should be low/moderate/critical

## cwslack-activities.php

/act new\*|[activity title]\*|[assigned to]*

All are required for activities. New will be replaced with more commands in the future.