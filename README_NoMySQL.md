# CWSlack-SlashCommands

See README.md for full configuration details and other information. 

This file should only be used if you cannot use a MySQL or Maria DB server for this script. Note that any new updates are not supported without MySQL and the Follow functionality will not work without MySQL past version 2.2.

Please use this release if intending on working without MySQL: https://github.com/jundis/CWSlack-SlashCommands/releases/tag/2.2


# Installation Instructions

## cwslack.php, activities, contacts, notes, and configs

1. Download the respective php file, functions.php, and config.php files.
2. Place on a compatible web server
3. Create a new slack slash command integration at https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to reflect the task necessary. E.x. /t for tickets, /act for activities, /note for notes.
5. Set the URL to https://domain.tld/cwslack.php (or other php file)
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and auto complete text if wanted.
9. Modify the config.php file with your companies values and timezone. Full configuration info below.
10. Test it in Slack!

## cwslack-incoming.php

1. Download the cwslack-incoming.php, functions.php, and config.php files.
2. Place on a compatible web server
3. Create a new slack incoming web hook integration at https://my.slack.com/services/new/incoming-webhook/
4. Set a name, icon, and if wanted.
5. Set channel that you want to post to and copy the Web hook URL
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

## cwslack-firmalerts.php

**(Requires some variables from cwslack-incoming.php to function if you don't use that)**

1. Download the cwslack-firmalerts.php, functions.php, and config.php files.
2. Place on a compatible web server.
3. Change $posttousers or $posttochan to 0 in config.php if you don't want it posting to one or the other.
4. Setup a cron job or scheduled task on your server to run this PHP file **every minute.**  
   ```Cron: * * * * * /usr/bin/php /var/www/cwslack-firmalerts.php >/dev/null 2>&1```
5. Set a firm appointment and test

## cwslack-follow.php

**(Also requires cwslack-incoming.php to function)**

1. Download the cwslack-follow.php, functions.php, and config.php files.
2. Place on a compatible web server
3. Create a new slack slash command integration at  https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /follow (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack-follow.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and auto complete text if wanted.
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