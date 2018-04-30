# CWSlack-SlashCommands Troubleshooting

### Issue - Error Security: SSL is required OR Error 401: Unauthorized

If you are using hosted ConnectWise, you need to set your $connectwise variable in config.php to `https://api-na.myconnectwise.net` instead of `https://na.myconnectwise.net`

Change the country code to whichever you have when you visit the site to login.

### Issue - Peer's Certificate issuer is not recognized.
           
See the StackOverflow link below, the second answer fixes this. You need to attach the CA cert to cURL since it does not come stock with them on most Windows machines. The `cacert.pem` file includes the CA for connectwise as well as most other major sites including Slack.

http://stackoverflow.com/questions/6400300/https-and-ssl3-get-server-certificatecertificate-verify-failed-ca-is-ok

### Issue - cURL not enabled

Ensure that CURL is installed and enabled in PHP.

#### Linux

Install CURL: `sudo apt-get install php5-curl`

Modify your PHP .ini to include `extension=curl.so` and restart Apache

You can find your .ini file by running `php --ini` in a terminal

#### Windows

See here: https://www.codeooze.com/coding/php-curl-on-windows/

Or google "enable curl windows" and use your best judgement on what site to choose.

### Issue - Other?

You can reach me on the r/msp Discord, LabTechGeek Slack, or via reddit at /u/jundis if you have a bug or run into any issues, but support will be limited as I turn my focus to developing the hosted solution.

You can also reach me at joey(at)und.is should you need more intense support, custom modifications, or want your install done by me.
