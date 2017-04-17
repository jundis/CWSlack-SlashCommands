# CWSlack-SlashCommands

[![Gitter chat](https://badges.gitter.im/CWSlack-SlashCommands.png)](https://gitter.im/CWSlack-SlashCommands)

This script, when hosted on a PHP supported web server, will act as a bridge between the JSON requests of Slack and the JSON responses of the ConnectWise REST API.

cwslack.php, cwslack-incoming.php, cwslack-activities.php, cwslack-configs.php, and cwslack-contacts.php were designed to be independent, but all rely on the config.php and functions.php files. This allows you to pick and choose what you want and for different Slack commands instead of one universal /cw tickets 249123 and /cw contact john doe it can be /t 249123 and /c john doe.

####Usage

* cwslack.php: Pull ticket information, create new tickets, change status, and change priority.
* cwslack-activities.php: Pull activity information
* cwslack-contacts.php: Pull contact information
* cwslack-configs.php: Pull configuration record information
* cwslack-tasks.php: View and update tasks on tickets
* cwslack-notes.php: Post notes to tickets
* cwslack-time.php: Post time entries to tickets and report on time sheets.
* cwslack-incoming.php: Receive ticket creation/update notices in Slack
* cwslack-follow.php: Follow a ticket to be direct messaged when updated
* cwslack-firmalerts.php: Receive notifications when firm appointments are coming up
* cwslack-dbmanage.php: Manage the MySQL user database within Slack.

# Installation Instructions

This script set and all modules require PHP version 5 and the cURL extension, and many require a MySQL or MariaDB server.

For unsupported non-MySQL installation instructions, please see README_NoMySQL.md

**See TROUBLESHOOT.md first if you have any issues. Otherwise, contact info below.**

You can reach me on the r/msp Discord, LabTechGeek Slack, or via reddit at /u/jundis if you need basic support.

You can also reach me at joey(at)und.is should you need more intense support, custom modifications, or want your install done by me.

####Update Instructions

Use the scripts found in the updates folder to upgrade from an older version to current. This will automatically update the config.php file with necessary values and create any new MySQL tables as well. You can also manually update by comparing the config file from this repository to your active one.

## cwslack.php, activities, contacts, notes, configs, tasks, time, and dbmanage

1. Download the respective php file, functions.php, install.php, and config-default.php files.
2. Place on a compatible web server
3. **Rename config-default.php to config.php**
3. Create a new slack slash command integration at https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to reflect the task necessary. E.x. /t for tickets, /act for activities, /note for notes.
5. Set the URL to https://domain.tld/cwslack.php (or other php file)
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and auto complete text if wanted.
9. Run install.php and proceed through database setup. This will also verify you have the required PHP and cURL versions.
10. Complete the install.php configuration, or manually modify the config.php file with the necessary values. Full configuration info found in config.php comments.
11. Test it in Slack!

## cwslack-incoming.php

1. Download the cwslack-incoming.php, functions.php, install.php, and config-default.php files.
2. Place on a compatible web server
3. **Rename config-default.php to config.php**
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

####Different boards to different channels

As of version 2.4 the implementation has been changed from using callback URLs to using the $boardmapping variable in config.php

## cwslack-firmalerts.php

**(Requires some variables from cwslack-incoming.php to function if you don't use that)**

1. Download the cwslack-firmalerts.php, functions.php, install.php, and config-default.php files.
2. Place on a compatible web server.
3. **Rename config-default.php to config.php**
3. Run install.php and proceed through database setup. This will also verify you have the required PHP and cURL versions.
4. Change $posttousers or $posttochan to 0 in config.php if you don't want it posting to one or the other.
5. Setup a cron job or scheduled task on your server to run this PHP file **every minute.**  
   ```Cron: * * * * * /usr/bin/php /var/www/cwslack-firmalerts.php >/dev/null 2>&1```
6. Set a firm appointment and test

## cwslack-timealerts.php

**(Requires some variables from cwslack-firmalerts.php and cwslack-time.php to function if you don't use those)**

1. Download the cwslack-timealerts.php, functions.php, install.php, and config-default.php files.
2. Place on a compatible web server.
3. **Rename config-default.php to config.php**
3. Run install.php and proceed through database setup. This will also verify you have the required PHP and cURL versions.
4. Change $posttousers or $posttochan to 0 in config.php if you don't want it posting to one or the other.
5. Setup a cron job or scheduled task on your server to run this PHP file **every 30 minutes.**  
   ```Cron: */30 * * * 1-5 /usr/bin/php /var/www/cwslack-timealerts.php >/dev/null 2>&1```
6. Fail to enter time and test! It will alert after 2 hours of time is lacking.

## cwslack-follow.php

**(Also requires cwslack-incoming.php to function)**

1. Download the cwslack-follow.php, functions.php, install.php, and config-default.php files.
2. Place on a compatible web server
3. **Rename config-default.php to config.php**
3. Create a new slack slash command integration at  https://SLACK TEAM.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /follow (or other if you prefer)
5. Set the URL to https://domain.tld/cwslack-follow.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and auto complete text if wanted.
9. Run install.php and proceed through database setup. This will also verify you have the required PHP and cURL versions.
10. Modify the config.php file with your companies values and timezone. Full configuration info found in config.php comments.
11. Test it in Slack!

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

# Command Usage

\* denotes required

## cwslack.php

/t [ticket number or new]* [command] [option3]

### status

option3 should be a valid status for your ticket. This can be partial: e.x. /t 592139 status need, can set the status to Need To Schedule if that is the only status with the word "need" in it.

### priority

option3 should be a valid priority for your ticket. Accepts partial like status above.

### full (or) notes (or) all

If $posttext=1 in config.php, shows you the latest note and the initial note. This displays to you only to avoid spam.

### initial (or) note (or) first

If $posttext=1 in config.php, shows you the initial note of the ticket. This displays to you only to avoid spam.

### new [board name]|[company name]|[ticket summary]

Use new instead of a ticket number to create a new ticket. Pipe symbols are required between [board name], [company name], and [ticket summary], but brackets are not used in final command.

Only include [board name] if you have $useboards set to 1 in config.php.

### scheduleme [time]

Schedules you for the specified ticket at the specified time. Accepts most reasonable forms of time (e.x. 4:00PM, Tomorrow 4:00PM, 1/4/2017 4:00PM, Wednesday 4:00PM)

### schedule [user] [time]

Schedules the specified user for the ticket at specified time. Accepts most reasonable forms of time like above.

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

/note [ticket number]* [internal OR external OR externalemail]* [ticket note]*

This does allow spaces for the ticket note so do not wrap in quotation marks or anything. Using "externalemail" as the option will trigger notifications according to boxes checked on ticket "Send Notes as Email"

## cwslack-configs.php

/config [company name]\*|[config name]*

Requires pipe symbol between the two, will return details on config that matches search.

## cwslack-tasks.php

/tasks [ticket number]\* [command]\* [task number] [note]

Commands:

* list : List all tasks on [ticket number]
* open/reopen : Mark a task as open, removing the done flag, requires [task number].
* close/complete/done/completed : Mark a task as done, requires [task number]. Can also add [note] for a resolution note.
* update/change/note : Change the note on a task, requires [task number] and [note].
* new/add : Add a new task to the end of the priority list, requires [note] but do not include [task number].

## cwslack-time.php

/times [ticket number]\* [type]\* [time]\* [note]\*

* [ticket number] = A valid ticket number
* [type] = Eitehr detailed, internal, or resolution. Also accepts d/i/r instead
* [time] = Shorthand time, use digits then h or m to designate units. E.x. 1.5h, 35m, 80m. NOT 1.5 hours, 35 minutes, etc
* [note] = Any sentence to be used as the ticket note.

/times report [user]

* Accepts a username (direct CW or Slack mapped) and outputs their daily time information
* If no user name is specified, it uses your Slack username as the target

/times reportall

* No input, just outputs a list of users who have entered time and their time information.

## cwslack-dbmanage.php

/dbm [command]* [options]

Commands available: 
* help - Display this help text
* listmap - List all username mappings between CW and Slack
* addmap [slackname]* [cwname]* - Associate the two names
* removemap [slackname]* - Remove a mapping
* clearfollow confirm* - Clears the follow database
