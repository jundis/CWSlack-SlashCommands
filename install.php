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

ini_set('display_errors', 1); //Display errors in case something occurs
?>
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
            <h3>CWSlack-SlashCommands Installer</h3>
        <?php
        if(isset($_GET["page"])) {
            if ($_GET["page"] == "Proceed" || $_GET["page"] == "Retry MySQL") {
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
            if ($_GET["page"] == "Setup Settings" || $_GET["page"] == "Skip to Config.php Settings") {
                echo "<p>Settings Configuration</p>";
                echo "<h5>Any field left blank will not change the setting, however yes/no questions must be updated any time you save this.</h5>";
                echo "<div class='row'><form action=\"install.php?page=Save Settings\" method='post'>
                    <div class=\"col-sm-12\"><h4>General</h4></div>
                    <div class=\"col-sm-12\">Ensure that your URL is set to https://cw.domain.tld OR if you're hosted, use https://api-country.myconnectwise.net.</div>
                    <div class=\"col-sm-6\"><label for='connectWise'>ConnectWise URL: </label></div><div class=\"col-sm-6\"><input type='text' name='connectwise' id='connectWise' placeholder='https://cw.domain.tld'><br></div>
                    <div class=\"col-sm-12\">Set company name to the one you use when logging into ConnectWise</div>
                    <div class=\"col-sm-6\"><label for='companyName'>Company Name: </label></div><div class=\"col-sm-6\"><input type='text' name='companyname' id='companyName' placeholder='mycompany'><br></div>
                    <div class=\"col-sm-12\">Set API keys according to the README.md API instructions.</div>
                    <div class=\"col-sm-6\"><label for='apiPublickey'>Public API Key: </label></div><div class=\"col-sm-6\"><input type='text' name='apipublickey' id='apiPublickey' placeholder='publickey'><br></div>
                    <div class=\"col-sm-6\"><label for='apiPrivatekey'>Private API Key: </label></div><div class=\"col-sm-6\"><input type='text' name='apiprivatekey' id='apiPrivatekey' placeholder='privatekey'><br></div>
                    <div class=\"col-sm-12\">Set time zone to PHP supported format such as America/Chicago. Full list <a href='http://php.net/manual/en/timezones.php'>here.</a></div>
                    <div class=\"col-sm-6\"><label for='timeZone'>Time Zone: </label></div><div class=\"col-sm-6\"><input type='text' name='timezone' id='timeZone' placeholder='America/Chicago'><br></div>
                    <div class=\"col-sm-6\"><label for='helpUrl'>Help URL for /command help: </label></div><div class=\"col-sm-6\"><input type='text' name='helpurl' id='helpUrl' placeholder='https://github.com/jundis/CWSlack-SlashCommands'><br></div>
                    <div class=\"col-sm-12\">Set below variable to True if you know your ConnectWise is slow. This will slow down the Slack integration a bit but prevent the 3000ms slack error.</div>
                    <div class=\"col-sm-7\"><label for='timeoutFix'>Enable timeout fix: </label></div><div class=\"col-sm-5\"><input type='radio' name='timeoutfix' value='yes' id='timeoutFix' > Yes <input type='radio' name='timeoutfix' value='no' checked> No </div>
                    <br>
                    <div class=\"col-sm-12\"><h4>Tokens</h4></div>
                    <div class=\"col-sm-12\">Set each of these to the respective Slack Token that you've setup. Leave blank if you do not need them.</div>
                    <div class=\"col-sm-6\"><label for='slackToken'>/tickets Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacktoken' id='slackToken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackActivitiestoken'>/activities Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackactivitiestoken' id='slackActivitiestoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackContactstoken'>/contact Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackcontactstoken' id='slackContactstoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackNotestoken'>/note Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacknotestoken' id='slackNotestoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackConfigstoken'>/config Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackconfigstoken' id='slackConfigstoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackFollowtoken'>/follow Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackfollowtoken' id='slackFollowtoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackTaskstoken'>/task Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacktaskstoken' id='slackTaskstoken'><br></div>
                    <div class=\"col-sm-6\"><label for='slackTimetoken'>/times Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacktimetoken' id='slackTimetoken'><br></div>
                    <div class=\"col-sm-12\">The one below is for the use of the DBManage module, not needed if you plan to do all MySQL work in a different way.</div>
                    <div class=\"col-sm-6\"><label for='slackDbmantoken'>/dbm Slack Token: </label></div><div class=\"col-sm-6\"><input type='text' name='slackdbmantoken' id='slackDbmantoken'><br></div>
                    <div class=\"col-sm-12\"><h4>Tickets Module</h4></div>
                    <div class=\"col-sm-7\"><label for='useBoards'>Use boards in new ticket creation: </label></div><div class=\"col-sm-5\"><input type='radio' name='useboards' value='yes' id='useBoards' checked> Yes <input type='radio' name='useboards' value='no' > No </div>
                    <div class=\"col-sm-12\"><h4>Incoming Module</h4></div>
                    <div class=\"col-sm-6\"><label for='webHookurl'>Web Hook URL: </label></div><div class=\"col-sm-6\"><input type='text' name='webhookurl' id='webHookurl' placeholder='https://hooks.slack.com/services/...'></div>
                    <div class=\"col-sm-7\"><label for='postAdded'>Post new tickets to Slack: </label></div><div class=\"col-sm-5\"><input type='radio' name='postadded' value='yes' id='postAdded' checked> Yes <input type='radio' name='postadded' value='no'> No</div>
                    <div class=\"col-sm-7\"><label for='postUpdated'>Post updated tickets to Slack: </label></div><div class=\"col-sm-5\"><input type='radio' name='postupdated' id='postUpdated' value='yes'> Yes <input type='radio' name='postupdated' value='no' checked> No</div>
                    <div class=\"col-sm-7\"><label for='postText'>Post ticket notes with /t and incoming: </label></div><div class=\"col-sm-5\"><input type='radio' name='posttext' value='yes' id='postText' checked> Yes <input type='radio' name='posttext' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='postCompany'>Add company name to notifications: </label></div><div class=\"col-sm-5\"><input type='radio' name='postcompany' value='yes' id='postCompany' checked> Yes <input type='radio' name='postcompany' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='timeEnabled'>Post all tickets past a set actual hours to a channel: </label></div><div class=\"col-sm-5\"><input type='radio' name='timeenabled' value='yes' id='timeEnabled'> Yes <input type='radio' name='timeenabled' value='no' checked> No </div>
                    <div class=\"col-sm-6\"><label for='timePast'>If above enabled, time in hours where all updates will post: </label></div><div class=\"col-sm-6\"><input type='text' name='timepast' id='timePast' placeholder='1.0'></div>
                    <div class=\"col-sm-6\"><label for='timeChan'>If above enabled, Channel to post time alerts to: </label></div><div class=\"col-sm-6\"><input type='text' name='timechan' id='timeChan' placeholder='#ticketstime'></div>
                    <div class=\"col-sm-12\">Set these to any \"bad\" things you don't want posting updates. Use the pipe symbol | to separate multiple items.</div>
                    <div class=\"col-sm-6\"><label for='badBoard'>Board blacklist: </label></div><div class=\"col-sm-6\"><input type='text' name='badboard' id='badBoard' placeholder='Alerts'></div>
                    <div class=\"col-sm-6\"><label for='badStatus'>Status blacklist: </label></div><div class=\"col-sm-6\"><input type='text' name='badstatus' id='badStatus' placeholder='Closed|Canceled'></div>
                    <div class=\"col-sm-6\"><label for='badCompany'>Company Blacklist: </label></div><div class=\"col-sm-6\"><input type='text' name='badcompany' id='badCompany' placeholder='CatchAll'></div>
                    <div class=\"col-sm-12\"><h4>Time Module</h4></div>
                    <div class=\"col-sm-6\"><label for='timeDetailworktype'>Detailed notes worktype: </label></div><div class=\"col-sm-6\"><input type='text' name='timedetailworktype' id='timeDetailworktype' placeholder='Remote Support'></div>
                    <div class=\"col-sm-6\"><label for='timeInternalworktype'>Internal notes worktype: </label></div><div class=\"col-sm-6\"><input type='text' name='timeinternalworktype' id='timeInternalworktype' placeholder='Admin'></div>
                    <div class=\"col-sm-6\"><label for='timeResolutionworktype'>Resolution notes worktype: </label></div><div class=\"col-sm-6\"><input type='text' name='timeresolutionworktype' id='timeResolutionworktype' placeholder='Remote Support'></div>
                    <div class=\"col-sm-6\"><label for='timeBusinessStart'>Business open time: </label></div><div class=\"col-sm-6\"><input type='text' name='timebusinessstart' id='timeBusinessStart' placeholder='8:00AM'></div>
                    <div class=\"col-sm-6\"><label for='timeBusinessClose'>Business close time: </label></div><div class=\"col-sm-6\"><input type='text' name='timebusinessclose' id='timeBusinessClose' placeholder='5:00PM'></div>
                    <div class=\"col-sm-12\"><h4>FirmAlerts Module</h4></div>
                    <div class=\"col-sm-7\"><label for='postTousers'>Send message to user for their firm appointments: </label></div><div class=\"col-sm-5\"><input type='radio' name='posttousers' value='yes' id='postTousers' checked> Yes <input type='radio' name='posttousers' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='postTochan'>Send message to a specific channel for all firm appointments: </label></div><div class=\"col-sm-5\"><input type='radio' name='posttochan' value='yes' id='postTochan' checked> Yes <input type='radio' name='posttochan' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='useTimechan'>Use the same channel as time alerts set above: </label></div><div class=\"col-sm-5\"><input type='radio' name='usetimechan' value='yes' id='useTimechan' checked> Yes <input type='radio' name='usetimechan' value='no' > No </div>
                    <div class=\"col-sm-6\"><label for='firmAlertchan'>Channel to post firm alerts to if above is no: </label></div><div class=\"col-sm-6\"><input type='text' name='firmalertchan' id='firmAlertchan' placeholder='#dispatch'></div>
                    <div class=\"col-sm-12\"><h4>TimeAlerts Module</h4></div>
                    <div class=\"col-sm-6\"><label for='noTimeUsers'>Users who should not receive time alerts if they are behind: </label></div><div class=\"col-sm-6\"><input type='text' name='notimeusers' id='noTimeUsers' placeholder='user1|user2 (Pipe separates)'></div>
                    <div class=\"col-sm-12\"><h4>Follow Module</h4></div>
                    <div class=\"col-sm-7\"><label for='followEnabled'>Enable follow module: </label></div><div class=\"col-sm-5\"><input type='radio' name='followenabled' value='yes' id='followEnabled' checked> Yes <input type='radio' name='followenabled' value='no' > No </div>
                    <div class=\"col-sm-6\"><label for='followToken'>Follow token for CW Link: </label></div><div class=\"col-sm-6\"><input type='text' name='followtoken' id='followToken' placeholder='follow'></div>
                    <div class=\"col-sm-6\"><label for='unfollowToken'>Unfollow token for CW Link: </label></div><div class=\"col-sm-6\"><input type='text' name='unfollowtoken' id='unfollowToken' placeholder='unfollow'></div>
                    <div class=\"col-sm-12\"><h4>Configs Module</h4></div>
                    <div class=\"col-sm-7\"><label for='hidePasswords'>Hide password fields: </label></div><div class=\"col-sm-5\"><input type='radio' name='hidepasswords' value='yes' id='hidePasswords'> Yes <input type='radio' name='hidepasswords' value='no' checked> No </div>
                    <div class=\"col-sm-12\"><h4>DBManage Module</h4></div>
                    <div class=\"col-sm-12\">List of Slack usernames that can access the /dbm commands. Seperate them by a pipe symbol, |</div>
                    <div class=\"col-sm-6\"><label for='adminList'>Admin List: </label></div><div class=\"col-sm-6\"><input type='text' name='adminlist' id='adminList'></div>
                    
                    <br><br><div class=\"col-sm-6\"><input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Save Settings\" /></div></form></div>";
                echo "</div></div></body></html>";
                //Template for future use
                //<div class="col-sm-7"><label for=''>: </label></div><div class="col-sm-5"><input type='radio' name='' value='yes' id=''> Yes <input type='radio' name='' value='no' checked> No </div>
                //<div class="col-sm-6"><label for=''>: </label></div><div class="col-sm-6"><input type='text' name='' id=''></div>
                die();
            }
            if ($_GET["page"] == "Test MySQL") {
                $dbhost = $_POST["dbhost"];
                $dbusername = $_POST["dbusername"];
                $dbpassword = $_POST["dbpassword"];
                $dbdatabase = $_POST["dbname"];

                $mysql = mysqli_connect($dbhost, $dbusername, $dbpassword);

                if (!$mysql) {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "Connection Error: " . mysqli_connect_error();
                    echo "</div>";
                    echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                    die();
                }

                $dbselect = mysqli_select_db($mysql, $dbdatabase);
                if (!$dbselect) {
                    //Select database failed
                    $sql = "CREATE DATABASE " . $dbdatabase;
                    if (mysqli_query($mysql, $sql)) {
                        //Database created successfully
                        $dbselect = mysqli_select_db($mysql, $dbdatabase);
                    } else {
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
                if (mysqli_query($mysql, $sql)) {
                    //Table created successfully
                } else {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "follow Table Creation Error: " . mysqli_error($mysql);
                    echo "</div>";
                    echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                    die();
                }

                $sql = "CREATE TABLE IF NOT EXISTS usermap (slackuser VARCHAR(25) PRIMARY KEY, cwname VARCHAR(25) NOT NULL)";
                if (mysqli_query($mysql, $sql)) {
                    //Table created successfully
                } else {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "usermap Table Creation Error: " . mysqli_error($mysql);
                    echo "</div>";
                    echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                    die();
                }

                $sql = "CREATE TABLE IF NOT EXISTS usermap (slackuser VARCHAR(25) PRIMARY KEY, cwname VARCHAR(25) NOT NULL)";
                if (mysqli_query($mysql, $sql)) {
                    //Table created successfully
                } else {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "usermap Table Creation Error: " . mysqli_error($mysql);
                    echo "</div>";
                    echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Retry MySQL\" />
                                </form>";
                    die();
                }

                $filedata = file('config.php');
                if($filedata === FALSE)
                {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "Could not open config.php file. Please ensure that PHP has read and write access to this file. If this is a new installation, please ensure you renamed config-default.php to config.php and uploaded install.php to the same directory.";
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


                $newdata = array();

                foreach ($filedata as $data) {
                    if (stristr($data, '$dbhost')) {
                        $newdata[] = '$dbhost = "' . $dbhost . '"; //Your MySQL DB' . PHP_EOL;
                    } else if (stristr($data, '$dbusername')) {
                        $newdata[] = '$dbusername = "' . $dbusername . '"; //Your MySQL DB Username' . PHP_EOL;
                    } else if (stristr($data, '$dbpassword')) {
                        $newdata[] = '$dbpassword = "' . $dbpassword . '"; //Your MySQL DB Password' . PHP_EOL;
                    } else if (stristr($data, '$dbdatabase')) {
                        $newdata[] = '$dbdatabase = "' . $dbdatabase . '"; //Change if you have an existing database you want to use, otherwise leave as default.' . PHP_EOL;
                    } else if (stristr($data, '$usedatabase = ')) {
                        $newdata[] = '$usedatabase = 1; // Set to 0 by default, set to 1 if you want to enable MySQL.' . PHP_EOL;
                    } else {
                        $newdata[] = $data;
                    }
                }

                file_put_contents('config.php', implode('', $newdata));

                die();
            }
            if ($_GET["page"] == "Save Settings") {
                $filedata = file('config.php');
                if($filedata === FALSE)
                {
                    echo "<div class=\"alert alert-danger\" role=\"alert\">";
                    echo "Could not open config.php file. Please ensure that PHP has read and write access to this file. If this is a new installation, please ensure you renamed config-default.php to config.php and uploaded install.php to the same directory.";
                    echo "</div>";
                    echo "<form action=\"install.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Setup Settings\" />
                                </form>";
                    die();
                }
                $newdata = array();

                foreach ($filedata as $data) {
                    if (stristr($data, '$connectwise =')) {
                        if (!empty($_POST["connectwise"])) {
                            $newdata[] = '$connectwise = "' . $_POST["connectwise"] . '"; //Set your Connectwise URL' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$companyname =')) {
                        if (!empty($_POST["companyname"])) {
                            $newdata[] = '$companyname = "' . $_POST["companyname"] . '"; //Set your company name from Connectwise. This is the company name field from login.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$apipublickey =')) {
                        if (!empty($_POST["apipublickey"])) {
                            $newdata[] = '$apipublickey = "' . $_POST["apipublickey"] . '"; //Public API key' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$apiprivatekey =')) {
                        if (!empty($_POST["apiprivatekey"])) {
                            $newdata[] = '$apiprivatekey = "' . $_POST["apiprivatekey"] . '"; //Private API key' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timezone =')) {
                        if (!empty($_POST["timezone"])) {
                            $newdata[] = '$timezone = "' . $_POST["timezone"] . '"; //Set your timezone here.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$helpurl =')) {
                        if (!empty($_POST["helpurl"])) {
                            $newdata[] = '$helpurl = "' . $_POST["helpurl"] . '"; //Set your help article URL here.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slacktoken =')) {
                        if (!empty($_POST["slacktoken"])) {
                            $newdata[] = '$slacktoken = "' . $_POST["slacktoken"] . '"; //Set token from the Slack slash command screen.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slackactivitiestoken =')) {
                        if (!empty($_POST["slackactivitiestoken"])) {
                            $newdata[] = '$slackactivitiestoken = "' . $_POST["slackactivitiestoken"] . '"; //Set your token for the activities slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slackcontactstoken =')) {
                        if (!empty($_POST["slackcontactstoken"])) {
                            $newdata[] = '$slackcontactstoken = "' . $_POST["slackcontactstoken"] . '"; //Set your token for the contacts slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slacknotestoken =')) {
                        if (!empty($_POST["slacknotestoken"])) {
                            $newdata[] = '$slacknotestoken = "' . $_POST["slacknotestoken"] . '"; //Set your token for the notes slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slackconfigstoken =')) {
                        if (!empty($_POST["slackconfigstoken"])) {
                            $newdata[] = '$slackconfigstoken = "' . $_POST["slackconfigstoken"] . '"; //Set your token for the configs slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slackfollowtoken =')) {
                        if (!empty($_POST["slackfollowtoken"])) {
                            $newdata[] = '$slackfollowtoken = "' . $_POST["slackfollowtoken"] . '"; //Set your token for the follow slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slackdbmantoken =')) {
                        if (!empty($_POST["slackdbmantoken"])) {
                            $newdata[] = '$slackdbmantoken = "' . $_POST["slackdbmantoken"] . '"; //Set your token for the database management slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slacktimetoken =')) {
                        if (!empty($_POST["slacktimetoken"])) {
                            $newdata[] = '$slacktimetoken = "' . $_POST["slacktimetoken"] . '"; //Set your token for the time slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slacktaskstoken =')) {
                        if (!empty($_POST["slacktaskstoken"])) {
                            $newdata[] = '$slacktaskstoken = "' . $_POST["slacktaskstoken"] . '"; //Set your token for the tasks slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$webhookurl =')) {
                        if (!empty($_POST["webhookurl"])) {
                            $newdata[] = '$webhookurl = "' . $_POST["webhookurl"] . '"; //Change this to the URL retrieved from incoming webhook setup for Slack.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$postadded =')) {
                        if ($_POST["postadded"] == "yes") {
                            $newdata[] = '$postadded = 1; //Set this to post new tickets to slack.' . PHP_EOL;
                        } else {
                            $newdata[] = '$postadded = 0; //Set this to post new tickets to slack.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$postupdated =')) {
                        if ($_POST["postupdated"] == "yes") {
                            $newdata[] = '$postupdated = 1; //Set this to post updated tickets to slack. Defaults to off to avoid spam' . PHP_EOL;
                        } else {
                            $newdata[] = '$postupdated = 0; //Set this to post updated tickets to slack. Defaults to off to avoid spam' . PHP_EOL;
                        }
                    } else if (stristr($data, '$posttext =')) {
                        if ($_POST["posttext"] == "yes") {
                            $newdata[] = '$posttext = 1; //Set to 1 if you want it to post the latest note from the ticket into chat whenever a ticket is created or updated.' . PHP_EOL;
                        } else {
                            $newdata[] = '$posttext = 0; //Set to 1 if you want it to post the latest note from the ticket into chat whenever a ticket is created or updated.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$postcompany =')) {
                        if ($_POST["postcompany"] == "yes") {
                            $newdata[] = '$postcompany = 1; //Set to 1 if you want the Company to be posted in the clear text of the post (general what will be seen on IRC/XMPP)' . PHP_EOL;
                        } else {
                            $newdata[] = '$postcompany = 0; //Set to 1 if you want the Company to be posted in the clear text of the post (general what will be seen on IRC/XMPP)' . PHP_EOL;
                        }
                    } else if (stristr($data, '$timeenabled =')) {
                        if ($_POST["timeenabled"] == "yes") {
                            $newdata[] = '$timeenabled = 1; //Set to 1 if you want to post all tickets past $timepast to a specific channel, $timechan' . PHP_EOL;
                        } else {
                            $newdata[] = '$timeenabled = 0; //Set to 1 if you want to post all tickets past $timepast to a specific channel, $timechan' . PHP_EOL;
                        }
                    } else if (stristr($data, '$timepast =')) {
                        if (!empty($_POST["timepast"])) {
                            $newdata[] = '$timepast = ' . $_POST["timepast"] . '; //Set to a time in hours where once reached all updates will post to #dispatch.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timechan =')) {
                        if (!empty($_POST["timechan"])) {
                            $newdata[] = '$timechan = "' . $_POST["timechan"] . '"; //Set to a channel to post to for $timeenabled' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$badboard =')) {
                        if (!empty($_POST["badboard"])) {
                            $newdata[] = '$badboard = "' . $_POST["badboard"] . '"; //Set to any board name you want to fail, to avoid ticket creation/updates from this board posting to Slack.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$badstatus =')) {
                        if (!empty($_POST["badstatus"])) {
                            $newdata[] = '$badstatus = "' . $_POST["badstatus"] . '"; //Set to any status name you want to fail, to avoid ticket creation/updates with this status from posting to Slack.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$badcompany =')) {
                        if (!empty($_POST["badcompany"])) {
                            $newdata[] = '$badcompany = "' . $_POST["badcompany"] . '"; //Set to any company name you want to fail, to avoid ticket creation for catchall from posting to Slack.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$posttousers =')) {
                        if ($_POST["posttousers"] == "yes") {
                            $newdata[] = '$posttousers = 1; //When set, will post to the user whenever the appointment reminder is reached.' . PHP_EOL;
                        } else {
                            $newdata[] = '$posttousers = 0; //When set, will post to the user whenever the appointment reminder is reached.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$posttochan =')) {
                        if ($_POST["posttochan"] == "yes") {
                            $newdata[] = '$posttochan = 1; //When set, will post to $timechan whenever the firm appointment starts.' . PHP_EOL;
                        } else {
                            $newdata[] = '$posttochan = 0; //When set, will post to $timechan whenever the firm appointment starts.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$usetimechan =')) {
                        if ($_POST["usetimechan"] == "yes") {
                            $newdata[] = '$usetimechan = 1; //When set, this will use the $timechan variable instead of the one below.' . PHP_EOL;
                        } else {
                            $newdata[] = '$usetimechan = 0; //When set, this will use the $timechan variable instead of the one below.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$firmalertchan =')) {
                        if (!empty($_POST["firmalertchan"])) {
                            $newdata[] = '$firmalertchan = "' . $_POST["firmalertchan"] . '"; //When you want to split time alerts and firm alerts into their own channels.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$followenabled =')) {
                        if ($_POST["postadded"] == "yes") {
                            $newdata[] = '$followenabled = 1; //When set to 1, follow commands and the follow scripts will be enabled.' . PHP_EOL;
                        } else {
                            $newdata[] = '$followenabled = 0; //When set to 1, follow commands and the follow scripts will be enabled.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$hidepasswords =')) {
                        if ($_POST["hidepasswords"] == "yes") {
                            $newdata[] = '$hidepasswords = 1; //Set to 1 if you want to hide passwords.' . PHP_EOL;
                        } else {
                            $newdata[] = '$hidepasswords = 0; //Set to 1 if you want to hide passwords.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$useboards =')) {
                        if ($_POST["useboards"] == "yes") {
                            $newdata[] = '$useboards = 1; //Use the board function in new tickets. /t new company|summary vs /t new board|company|summary' . PHP_EOL;
                        } else {
                            $newdata[] = '$useboards = 0; //Use the board function in new tickets. /t new company|summary vs /t new board|company|summary' . PHP_EOL;
                        }
                    } else if (stristr($data, '$timeoutfix =')) {
                        if ($_POST["timeoutfix"] == "yes") {
                            $newdata[] = '$timeoutfix = true; //Enable to fix any 3000ms response from Slack.' . PHP_EOL;
                        } else {
                            $newdata[] = '$timeoutfix = false; //Enable to fix any 3000ms response from Slack.' . PHP_EOL;
                        }
                    } else if (stristr($data, '$followtoken =')) {
                        if (!empty($_POST["followtoken"])) {
                            $newdata[] = '$followtoken = "' . $_POST["followtoken"] . '"; //Change to random text to be used in your CW follow link if you use it. Defaults to follow which is fine for testing.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$unfollowtoken =')) {
                        if (!empty($_POST["unfollowtoken"])) {
                            $newdata[] = '$unfollowtoken = "' . $_POST["unfollowtoken"] . '"; //Change to random text to be used in your CW unfollow link if you use it. Defaults to unfollow which is fine for testing.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$adminlist =')) {
                        if (!empty($_POST["adminlist"])) {
                            $newdata[] = '$adminlist = "' . $_POST["adminlist"] . '"; //Separate by pipe symbol as seen in example if you need multiple people to have access.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timedetailworktype =')) {
                        if (!empty($_POST["timedetailworktype"])) {
                            $newdata[] = '$timedetailworktype = "' . $_POST["timedetailworktype"] . '"; //Set to the worktype name you want it to change tickets to when a note is posted to detailed.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timeinternalworktype =')) {
                        if (!empty($_POST["timeinternalworktype"])) {
                            $newdata[] = '$timeinternalworktype = "' . $_POST["timeinternalworktype"] . '"; //Set to the worktype name you want it to change tickets to when a note is posted to internal.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timeresolutionworktype =')) {
                        if (!empty($_POST["timeresolutionworktype"])) {
                            $newdata[] = '$timeresolutionworktype = "' . $_POST["timeresolutionworktype"] . '"; //Set to the worktype name you want it to change tickets to when a note is posted to resolution.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timebusinessstart =')) {
                        if (!empty($_POST["timebusinessstart"])) {
                            $newdata[] = '$timebusinessstart = ' . $_POST["timebusinessstart"] . '; //Set to when your business opens in your timezone' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$timebusinessclose =')) {
                        if (!empty($_POST["timebusinessclose"])) {
                            $newdata[] = '$timebusinessclose = ' . $_POST["timebusinessclose"] . '; //Set to when your business closes in your timezone' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$notimeusers =')) {
                        if (!empty($_POST["notimeusers"])) {
                            $newdata[] = '$notimeusers = ' . $_POST["notimeusers"] . ';  //Usernames of users who should not be alerted on. Useful if you have techs who occasionally enter time and you don\'t want it pinging them every day. Separate with pipe |' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else {
                        $newdata[] = $data;
                    }
                }

                file_put_contents('config.php', implode('', $newdata));
                echo "<div class=\"alert alert-success\" role=\"alert\">";
                echo "Successfully configured the config.php file! Please test out your commands in Slack and submit any issues you have to GitHub!";
                echo "</div><div class=\"alert alert-info\" role=\"alert\">Please remove install.php to avoid people accessing it externally. You can re-add it anytime to configure database or settings again, or just manually edit config.php.</div></div>";
                echo "</div></body></html>";
                die();
            }
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