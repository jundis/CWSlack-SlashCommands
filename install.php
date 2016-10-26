<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <meta name="description" content="">
        <meta name="author" content="">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
        <title>CWSlack Installer</title>
    </head>
    <body>
    <div class="container">
        <div class="jumbotron">
            <h3>CWSlack-SlashCommands MySQL Installer</h3>
        <?php

        if($_GET["page"]=="Proceed" || $_GET["page"]=="Retry MySQL")
        {
            echo "<p>MySQL Configuration</p>";

            echo "<div class='row'><form action=\"install.php?page=Test MySQL\" method='post'>
                    <div class=\"col-sm-6\"><label for='dbHost'>MySQL Host: </label></div><div class=\"col-sm-6\"><input type='text' name='dbhost' id='dbHost'><br></div>
                    <div class=\"col-sm-6\"><label for='dbUsername'>MySQL Username: </label></div><div class=\"col-sm-6\"><input type='text' name='dbusername' id='dbUsername'><br></div>
                    <div class=\"col-sm-6\"><label for='dbPassword'>MySQL Password: </label></div><div class=\"col-sm-6\"><input type='password' name='dbpassword' id='dbPassword'><br></div>
                    <div class=\"col-sm-6\"><label for='dbName'>Database Name: </label></div><div class=\"col-sm-6\"><input type='text' name='dbname' id='dbName'></div></div><br><br>
                    <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Test MySQL\" /></form>";
            echo "</div></div></body></html>";
            die();
        }
        if($_GET["page"]=="Setup Settings" || $_GET["page"]=="Skip to Config.php Settings")
        {
            echo "<p>Settings Configuration</p>";

            echo "<form action=\"install.php?page=Save Settings\" method='post'>
                    <div class=\"col-sm-12\">Ensure that your URL is set to https://cw.domain.tld OR if you're hosted, use https://api-country.myconnectwise.net.</div>
                    <div class=\"col-sm-6\"><label for='connectWise'>ConnectWise URL: </label></div><div class=\"col-sm-6\"><input type='text' name='connectwise' id='connectWise' placeholder='https://cw.domain.tld'><br></div>
                    <div class=\"col-sm-12\">Set company name to the one you use when logging into ConnectWise</div>
                    <div class=\"col-sm-6\"><label for='companyName'>Company Name: </label></div><div class=\"col-sm-6\"><input type='text' name='companyname' id='companyName' placeholder='mycompany'><br></div>
                    <div class=\"col-sm-12\">Set API keys according to the README.md API instructions.</div>
                    <div class=\"col-sm-6\"><label for='apiPublickey'>Public API Key: </label></div><div class=\"col-sm-6\"><input type='text' name='apipublickey' id='apiPublickey' placeholder='publickey'><br></div>
                    <div class=\"col-sm-6\"><label for='apiPrivatekey'>Private API Key: </label></div><div class=\"col-sm-6\"><input type='text' name='apiprivatekey' id='apiPrivatekey' placeholder='privatekey'><br></div>
                    <div class=\"col-sm-12\">Set time zone to PHP supported format such as America/Chicago. Full list <a href='http://php.net/manual/en/timezones.php'>here.</a></div>
                    <div class=\"col-sm-6\"><label for='timeZone'>Time Zone: </label></div><div class=\"col-sm-6\"><input type='text' name='timezone' id='timeZone' placeholder='America/Chicago'><br></div>
                    <br>
                    <div class=\"col-sm-12\"><h4>Tokens</h4></div>
                    <div class=\"col-sm-12\">Set each of these to the respective Slack Token that you've setup. Leave blank if you do not need them.</div>
                    <div class=\"col-sm-6\"><label for='slackToken'>/tickets Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacktoken' id='slackToken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackActivitiestoken'>/activities Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackactivitiestoken' id='slackActivitiestoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackContactstoken'>/contact Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackcontactstoken' id='slackContactstoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackNotestoken'>/note Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacknotestoken' id='slackNotestoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackConfigstoken'>/config Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackconfigstoken' id='slackConfigstoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackFollowtoken'>/follow Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackfollowtoken' id='slackFollowtoken'><br></div>
                    <div class=\"col-sm-12\"><h4>Incoming Module</h4></div>
                    <div class=\"col-sm-6\"><label for='webHookurl'>Web Hook URL: </label></div><div class=\"col-sm-6\"><input type='text' name='webhookurl' id='webHookurl' placeholder='https://hooks.slack.com/services/...'></div>
                    <div class=\"col-sm-7\"><label for='postAdded'>Post new tickets to Slack: </label></div><div class=\"col-sm-5\"><input type='radio' name='postadded' value='yes' id='postAdded' checked> Yes <input type='radio' name='postadded' value='no'> No</div>
                    <div class=\"col-sm-7\"><label for='postUpdated'>Post updated tickets to Slack: </label></div><div class=\"col-sm-5\"><input type='radio' name='postupdated' id='postUpdated' value='yes'> Yes <input type='radio' name='postupdated' value='no' checked> No</div>
                    <div class=\"col-sm-7\"><label for='allowZadmin'>Post zAdmin updates? Required for customer created tickets to post: </label></div><div class=\"col-sm-5\"><input type='radio' name='allowzadmin' value='yes' id='allowZadmin'> Yes <input type='radio' name='allowzadmin' value='no' checked> No </div>
                    <div class=\"col-sm-7\"><label for='postText'>Post ticket notes with /t and incoming: </label></div><div class=\"col-sm-5\"><input type='radio' name='posttext' value='yes' id='postText' checked> Yes <input type='radio' name='posttext' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='postCompany'>Add company name to notifications: </label></div><div class=\"col-sm-5\"><input type='radio' name='postcompany' value='yes' id='postCompany' checked> Yes <input type='radio' name='postcompany' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='timeEnabled'>Post all tickets past a set actual hours to a channel: </label></div><div class=\"col-sm-5\"><input type='radio' name='timeenabled' value='yes' id='timeEnabled'> Yes <input type='radio' name='timeenabled' value='no' checked> No </div>
                    <div class=\"col-sm-6\"><label for='timePast'>If above enabled, time in hours where all updates will post: </label></div><div class=\"col-sm-6\"><input type='text' name='timepast' id='timePast' placeholder='1.0'></div>
                    <div class=\"col-sm-6\"><label for='timeChan'>If above enabled, Channel to post time alerts to: </label></div><div class=\"col-sm-6\"><input type='text' name='timechan' id='timeChan' placeholder='#ticketstime'></div>
                    <div class=\"col-sm-12\">Set these to any \"bad\" things you don't want posting updates. Use the pipe symbol | to separate multiple items.
                    <div class=\"col-sm-6\"><label for='badBoard'>Board blacklist: </label></div><div class=\"col-sm-6\"><input type='text' name='badboard' id='badBoard' placeholder='Alerts'></div>
                    <div class=\"col-sm-6\"><label for='badStatus'>Status blacklist: </label></div><div class=\"col-sm-6\"><input type='text' name='badstatus' id='badStatus' placeholder='Closed|Canceled'></div>
                    <div class=\"col-sm-6\"><label for='badCompany'>Company Blacklist: </label></div><div class=\"col-sm-6\"><input type='text' name='badcompany' id='badCompany' placeholder='CatchAll'></div>
                    <div class=\"col-sm-12\"><h4>FirmAlerts Module</h4></div>
                    <div class=\"col-sm-7\"><label for=''>: </label></div><div class=\"col-sm-5\"><input type='radio' name='' value='yes' id=''> Yes <input type='radio' name='' value='no' checked> No </div>
                    <div class=\"col-sm-7\"><label for=''>: </label></div><div class=\"col-sm-5\"><input type='radio' name='' value='yes' id=''> Yes <input type='radio' name='' value='no' checked> No </div>
                    <div class=\"col-sm-7\"><label for=''>: </label></div><div class=\"col-sm-5\"><input type='radio' name='' value='yes' id=''> Yes <input type='radio' name='' value='no' checked> No </div>
                    <div class=\"col-sm-6\"><label for=''>: </label></div><div class=\"col-sm-6\"><input type='text' name='' id=''></div>

                    <div class=\"col-sm-7\"><label for=''>: </label></div><div class=\"col-sm-5\"><input type='radio' name='' value='yes' id=''> Yes <input type='radio' name='' value='no' checked> No </div>
                    <div class=\"col-sm-6\"><label for=''>: </label></div><div class=\"col-sm-6\"><input type='text' name='' id=''></div>
                    <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Save Settings\" /></form>
                    <br><br>";
            echo "</div></div></body></html>";
            die();
        }
        if($_GET["page"]=="Test MySQL")
        {
            $dbhost = $_POST["dbhost"];
            $dbusername = $_POST["dbusername"];
            $dbpassword = $_POST["dbpassword"];
            $dbdatabase = $_POST["dbname"];

            $mysql = mysqli_connect($dbhost, $dbusername, $dbpassword);

            if (!$mysql)
            {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo "Connection Error: " . mysqli_connect_error();
                echo "</div>";
                echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                die();
            }

            $dbselect = mysqli_select_db($mysql,$dbdatabase);
            if(!$dbselect)
            {
                //Select database failed
                $sql = "CREATE DATABASE " . $dbdatabase;
                if (mysqli_query($mysql,$sql))
                {
                    //Database created successfully
                    $dbselect = mysqli_select_db($mysql,$dbdatabase);
                }
                else
                {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "Database Creation Error: " . mysqli_error($mysql);
                    echo "</div>";
                    echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                    die();
                }
            }

            $sql = "CREATE TABLE IF NOT EXISTS follow (id INT(7) UNSIGNED AUTO_INCREMENT PRIMARY KEY, ticketnumber INT(10) NOT NULL, slackuser VARCHAR(25) NOT NULL)";
            if(mysqli_query($mysql,$sql))
            {
                //Table created successfully
            }
            else
            {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo "follow Table Creation Error: " . mysqli_error($mysql);
                echo "</div>";
                echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                die();
            }

            $sql = "CREATE TABLE IF NOT EXISTS usermap (slackuser VARCHAR(25) PRIMARY KEY, cwname VARCHAR(25) NOT NULL)";
            if(mysqli_query($mysql,$sql))
            {
                //Table created successfully
            }
            else
            {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo "usermap Table Creation Error: " . mysqli_error($mysql);
                echo "</div>";
                echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                die();
            }

            $sql = "CREATE TABLE IF NOT EXISTS usermap (slackuser VARCHAR(25) PRIMARY KEY, cwname VARCHAR(25) NOT NULL)";
            if(mysqli_query($mysql,$sql))
            {
                //Table created successfully
            }
            else
            {
                echo "<div class=\"alert alert-danger\" role=\"alert\">";
                echo "usermap Table Creation Error: " . mysqli_error($mysql);
                echo "</div>";
                echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                die();
            }

            echo "<div class=\"alert alert-success\" role=\"alert\">";
            echo "Successfully connected and setup MySQL Database!<br><br>You can now finish configuring the options in config.php and then test it out! You can also click the button below to configure the config.php file with this script.";
            echo "</div><div class=\"alert alert-info\" role=\"alert\">Please remove install.php to avoid people accessing it externally if you manually configure settings.</div></div>";
            echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Setup Settings\" />
                                </form></div></body></html>";

            mysqli_close($mysql);

            $filedata = file('config.php');
            $newdata = array();

            foreach ($filedata as $data) {
                if (stristr($data, '$dbhost')) {
                    $newdata[] =  '$dbhost = "'.$dbhost.'"; //Your MySQL DB' . PHP_EOL;
                }
                else if (stristr($data, '$dbusername')) {
                    $newdata[] =  '$dbusername = "'.$dbusername.'"; //Your MySQL DB Username' . PHP_EOL;
                }
                else if (stristr($data, '$dbpassword')) {
                    $newdata[] ='$dbpassword = "'.$dbpassword.'"; //Your MySQL DB Password' . PHP_EOL;
                }
                else if (stristr($data, '$dbdatabase')) {
                    $newdata[] = '$dbdatabase = "'.$dbdatabase.'"; //Change if you have an existing database you want to use, otherwise leave as default.' . PHP_EOL;
                }
                else if (stristr($data, '$usedatabase')) {
                    $newdata[] = '$usedatabase = 1; // Set to 0 by default, set to 1 if you want to enable MySQL.' . PHP_EOL;
                }
                else
                {
                    $newdata[] = $data;
                }
            }

            file_put_contents('config.php', implode('', $newdata));

            die();
        }

        $php_version=phpversion();
        preg_match("#^\d.\d#", phpversion(), $match);
        if($match[0]<5)
        {
            $php_error="Error: PHP version is ".phpversion().", Version 5 or newer is required.";
        }
        if($match[0]>6)
        {
           $php_warning="Warning: PHP version is ".phpversion().", Script tested only on Version 5.";
        }

        // declare function
        function find_SQL_Version() {
            $output = shell_exec('mysql -V');
            preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
            return @$version[0]?$version[0]:-1;
        }

        $mysql_version=find_SQL_Version();
        if($mysql_version<5)
        {
            $mysql_error="Error: MySQL version is $mysql_version. Version 5 or newer is required.";
        }

        if(!function_exists('curl_exec'))
        {
            $curl_error="Error: PHP CURL function is not enabled!";
        }
        ?>


                 <p>Checking versions...</p>

                <?php
                    if(empty($php_error) && empty($php_warning)) echo "<span style='color:green;'>Success: PHP Version $php_version - OK!</span><br><br>";
                    else if (empty($php_error)) echo "<span style='color:orange;'>$php_warning</span><br><br>";
                    else echo "<span style='color:red;'>$php_error</span><br><br>";

                    if(empty($mysql_error)) echo "<span style='color:green;'>Success: MySQL Version $mysql_version - OK!</span><br><br>";
                    else echo "<span style='color:red;'>$mysql_error</span><br><br>";

                    if(empty($curl_error)) echo "<span style='color:green;'>Success: cURL Enabled - OK!</span><br><br>";
                    else echo "<span style='color:red;'>$curl_error</span><br><br>";

                    if(empty($curl_error) && empty($mysql_error) && empty($php_error))
                    {
                        echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-success\" value=\"Proceed\" />
                                </form>";
                        echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Skip to Config.php Settings\" />
                                </form>";
                    }
                    else
                    {
                        echo "<button type='button' class=\"btn btn-danger\" disabled>Resolve errors before proceeding</button>";
                    }
                ?>
            </div>
        </div>
    </body>
</html>