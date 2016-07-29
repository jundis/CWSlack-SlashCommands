# CWSlack-SlashCommands

This script, when hosted on a PHP supported web server, will act as a bridge between the JSON requests of Slack and the JSON responses of the ConnectWise REST API.

# Installation Instructions

1. Download the cwslack.php file
2. Place on a compatible web server
3. Create a new slack slash command integration at hhttps://<SLACK TEAM>.slack.com/apps/A0F82E8CA-slash-commands
4. Set command to /t (or other if you prefer)
5. Set the URL to http://domain.tld/cwslack.php
6. Set Method to GET
7. Copy the token
8. Set a name, icon, and autocomplete text if wanted.
9. Modify the cwslack.php file and change lines 7-10 with your companies values.
10. Test it in Slack!

Note: You may need to change line 22 of the file if your ticket numbers are not always 6 digits. It is set to fail all slash commands that don't have either help or a 6 digit number as the first line.

Change: if(count_digit($exploded[0]) != 6) {
To: if(count_digit($exploded[0]) != <ticket number length>) {
