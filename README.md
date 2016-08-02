# CWSlack-SlashCommands

This script, when hosted on a PHP supported web server, will act as a bridge between the JSON requests of Slack and the JSON responses of the ConnectWise REST API.

cwslack.php and cwslack-activities.php were designed to be independent, but both rely on the config.php file.



# Installation Instructions

## cwslack.php

1. Download the cwslack.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at hhttps://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /t (or other if you prefer)
5. Set the URL to http://domain.tld/cwslack.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values.
10. Test it in Slack!

## cwslack-activities.php

1. Download the cwslack.php file and config.php file.
2. Place on a compatible web server
3. Create a new slack slash command integration at hhttps://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /act (or other if you prefer)
5. Set the URL to http://domain.tld/cwslack-activities.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the config.php file with your companies values, make sure to set the specific $slackactivitiestoken to the one for the activities slash command.
10. Test it in Slack!

# Command Usage

* denotes required

## cwslack.php

/t [ticket number]* [command] [option3]

### status

option3 should be n2s/scheduled/completed

### priority

option3 should be low/moderate/critical

## cwslack-activities.php

/act new*|[activity title]*|[assigned to]*

All are required for activities. New will be replaced with more commands in the future.