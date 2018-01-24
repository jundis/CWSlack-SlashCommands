<?php
/*
	CWSlack-SlashCommands
    Copyright (C) 2018  jundis

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
    <title>CWSlack Updater</title>
</head>
<body>
<div class="container">
    <div class="jumbotron">
        <h3>CWSlack-SlashCommands Updater</h3>
        <?php
        if(isset($_GET["page"])) {
            if ($_GET["page"] == "Proceed" || $_GET["page"] == "Skip to Config.php Settings") {
                echo "<p>Settings Configuration</p>";
                echo "<h5>Any field left blank will not change the setting, however yes/no questions must be updated any time you save this.</h5>";
                echo "<div class='row'><form action=\"update-2.3-2.4-to-2.5.php?page=Save Settings\" method='post'>
                    <div class=\"col-sm-12\"><h4>General</h4></div>
                    <div class=\"col-sm-7\"><label for='scheduleStatus'>Status to change to for /t schedule: </label></div><div class=\"col-sm-6\"><input type='text' name='schedulestatus' id='scheduleStatus' placeholder='Scheduled'></div>
                    <div class=\"col-sm-12\"><h4>Notes Module</h4></div>
                    <div class=\"col-sm-6\"><label for='defaultNoteType'>Default note type for /note without type specified: </label></div><div class=\"col-sm-6\"><input type='text' name='defaultnotetype' id='defaultNoteType' placeholder='internal'></div>
                    <div class=\"col-sm-12\"><h4>TimeAlerts Module</h4></div>
                    <div class=\"col-sm-6\"><label for='specialTimeUsers'>Users who don't follow business open/close time: </label></div><div class=\"col-sm-6\"><input type='text' name='specialtimeusers' id='specialTimeUsers' placeholder='user1,7:00am-4:00pm|user2,9:00am-6:00pm'></div>
                    <div class=\"col-sm-12\"><h4>PriorityAlerts Module</h4></div>
                    <div class=\"col-sm-6\"><label for='priorityList'>List of priorities to alert on: </label></div><div class=\"col-sm-6\"><input type='text' name='prioritylist' id='priorityList' placeholder='High|Critical'></div>
                    <div class=\"col-sm-6\"><label for='priorityStatus'>List of statuses to check priority of: </label></div><div class=\"col-sm-6\"><input type='text' name='prioritystatus' id='priorityStatus' placeholder='Scheduled|Scheduled - Notify'></div>
                    <div class=\"col-sm-6\"><label for='priorityWait'>How long to wait after schedule is missed (1-119 min): </label></div><div class=\"col-sm-6\"><input type='text' name='prioritywait' id='priorityWait' placeholder='30'></div>
                    <div class=\"col-sm-12\"><h4>Lunch Module</h4></div>
                    <div class=\"col-sm-6\"><label for='slackLunchToken'>Slack /lunch token: </label></div><div class=\"col-sm-6\"><input type='text' name='slacklunchtoken' id='slackLunchToken' placeholder='Your token here'></div>
                    <div class=\"col-sm-6\"><label for='lunchChargeCode'>Charge code ID to use for time entry: </label></div><div class=\"col-sm-6\"><input type='text' name='lunchchargecode' id='lunchChargeCode' placeholder='41'></div>
                    <div class=\"col-sm-6\"><label for='lunchTime'>Normal lunch time (Minutes): </label></div><div class=\"col-sm-6\"><input type='text' name='lunchtime' id='lunchTime' placeholder='60'></div>
                    <div class=\"col-sm-6\"><label for='lunchMax'>Max lunch period before cancel (Minutes): </label></div><div class=\"col-sm-6\"><input type='text' name='lunchmax' id='lunchMax' placeholder='120'></div>
                    <div class=\"col-sm-6\"><label for='lunchSlackChannel'>Channel to send to: </label></div><div class=\"col-sm-6\"><input type='text' name='lunchslackchannel' id='lunchSlackChannel' placeholder='#general'></div>
                    <div class=\"col-sm-7\"><label for='lunchSendSlack'>Send Slack channel messages: </label></div><div class=\"col-sm-5\"><input type='radio' name='lunchsendslack' value='yes' id='lunchSendSlack' checked> Yes <input type='radio' name='lunchsendonoff' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='lunchSendOnOff'>Send when user goes on lunch: </label></div><div class=\"col-sm-5\"><input type='radio' name='lunchsendonoff' value='yes' id='lunchSendOnOff' checked> Yes <input type='radio' name='lunchsendonoff' value='no' > No </div>
                    <div class=\"col-sm-7\"><label for='lunchCreateSched'>Submit schedule entry: </label></div><div class=\"col-sm-5\"><input type='radio' name='lunchcreatesched' value='yes' id='lunchCreateSched'> Yes <input type='radio' name='lunchcreatesched' value='no' checked> No </div>
                    <div class=\"col-sm-7\"><label for='lunchSaveTime'>Submit time entry: </label></div><div class=\"col-sm-5\"><input type='radio' name='lunchsavetime' value='yes' id='lunchSaveTime'> Yes <input type='radio' name='lunchsavetime' value='no' checked> No </div>
                    <br><br><div class=\"col-sm-6\"><input type=\"submit\" name='page' class=\"btn btn-primary\" value=\"Save Settings\" /></div></form></div>";
                echo "</div></div></body></html>";
                //Template for future use
                //<div class="col-sm-7"><label for=''>: </label></div><div class="col-sm-5"><input type='radio' name='' value='yes' id=''> Yes <input type='radio' name='' value='no' checked> No </div>
                //<div class="col-sm-6"><label for=''>: </label></div><div class="col-sm-6"><input type='text' name='' id=''></div>
                die();
            }
            if ($_GET["page"] == "Save Settings") {
                $filedata = file('config.php');
                $newdata = array();
                $line1 = false; //For later use

                foreach ($filedata as $data) {
                    if (stristr($data, '$schedulestatus =')) {
                        if (!empty($_POST["schedulestatus"])) {
                            $newdata[] = '$schedulestatus = "' . $_POST["schedulestatus"] . '";  //Set to the name of your status (e.x. "Scheduled") if you want the [/t # schedule] functions to update the status' . PHP_EOL;
                            $line1=true;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$defaultnotetype =')) {
                        if (!empty($_POST["defaultnotetype"])) {
                            $newdata[] = '$defaultnotetype = "' . $_POST["defaultnotetype"] . '"; //Set to internal, external, or externalemail and this will be used if they do not specify a type. Leave blank to have no default and return an error if they don\'t specify.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$specialtimeusers =')) {
                        if (!empty($_POST["specialtimeusers"])) {
                            $newdata[] = '$specialtimeusers = "' . $_POST["specialtimeusers"] . '"; //Usernames of users who should be alerted on, but who have special hours different from default start-close. Seperate user and time with comma, seperate different users with pipe |. No spaces' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$prioritylist =')) {
                        if (!empty($_POST["prioritylist"])) {
                            $newdata[] = '$prioritylist = "' . $_POST["prioritylist"] . '"; // Name of the priority(ies) to look out for. Separate by pipe if more than one needed.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$prioritystatus =')) {
                        if (!empty($_POST["prioritystatus"])) {
                            $newdata[] = '$prioritystatus = "' . $_POST["prioritystatus"] . '"; // Status(es), seperated by pipe | symbol, which the priority alerts will check for and send alerts on.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$prioritywait =')) {
                        if (!empty($_POST["prioritywait"])) {
                            $newdata[] = '$prioritywait = ' . $_POST["prioritywait"] . '; // Number of minutes to wait after a high-priority event before alerting the technician. Maximum 119 minutes.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$slacklunchtoken =')) {
                        if (!empty($_POST["slacklunchtoken"])) {
                            $newdata[] = '$slacklunchtoken = "' . $_POST["slacklunchtoken"] . '"; // Set your token for the lunch slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$lunchchargecode =')) {
                        if (!empty($_POST["lunchchargecode"])) {
                            $newdata[] = '$lunchchargecode = ' . $_POST["lunchchargecode"] . '; // Set to your "Break" charge code that lunches should be put under' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$lunchtime =')) {
                        if (!empty($_POST["lunchtime"])) {
                            $newdata[] = '$lunchtime = ' . $_POST["lunchtime"] . '; // Expected number of MINUTES that a user is on lunch' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$lunchmax =')) {
                        if (!empty($_POST["lunchmax"])) {
                            $newdata[] = '$lunchmax = ' . $_POST["lunchmax"] . '; // Number of minutes to allow before cancelling the lunch entry, does not submit time' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    }  else if (stristr($data, '$lunchslackchannel =')) {
                        if (!empty($_POST["lunchslackchannel"])) {
                            $newdata[] = '$lunchslackchannel = "' . $_POST["lunchslackchannel"] . '"; // Channel to send Slack messages to' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                    } else if (stristr($data, '$lunchsendslack =')) {
                        if ($_POST["lunchsendslack"] == "yes") {
                            $newdata[] = '$lunchsendslack = true; // Send messages to a slack channel when a user goes on/off lunch' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchsendslack = false; // Send messages to a slack channel when a user goes on/off lunch' . PHP_EOL;
                        }
                    } else if (stristr($data, '$lunchsendonoff =')) {
                        if ($_POST["lunchsendonoff"] == "yes") {
                            $newdata[] = '$lunchsendonoff = 1; // Key: 0 = No notifications, 1 = Send notifications when a user goes on lunch, 2 = Send when a user goes off lunch, 3 = Send when a user goes on lunch OR off lunch' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchsendonoff = 0; // Key: 0 = No notifications, 1 = Send notifications when a user goes on lunch, 2 = Send when a user goes off lunch, 3 = Send when a user goes on lunch OR off lunch' . PHP_EOL;
                        }
                    } else if (stristr($data, '$lunchcreatesched =')) {
                        if ($_POST["lunchcreatesched"] == "yes") {
                            $newdata[] = '$lunchcreatesched = true; // Should the script create a schedule entry on the users board' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchcreatesched = false; // Should the script create a schedule entry on the users board' . PHP_EOL;
                        }
                    } else if (stristr($data, '$lunchsavetime =')) {
                        if ($_POST["lunchsavetime"] == "yes") {
                            $newdata[] = '$lunchsavetime = true; // Should the script submit a time entry for the user for their lunch duration' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchsavetime = false; // Should the script submit a time entry for the user for their lunch duration' . PHP_EOL;
                        }
                    } else if (stristr($data, '//cwslack-activities.php') && !$line1) {
                        array_pop($newdata);
                        if (!empty($_POST["schedulestatus"])) {
                            $newdata[] = '$schedulestatus = "' . $_POST["schedulestatus"] . '";  //Set to the name of your status (e.x. "Scheduled") if you want the [/t # schedule] functions to update the status' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '//cwslack-configs.php') && !$line1) {
                        array_pop($newdata);
                        if (!empty($_POST["defaultnotetype"])) {
                            $newdata[] = '$defaultnotetype = "' . $_POST["defaultnotetype"] . '"; //Set to internal, external, or externalemail and this will be used if they do not specify a type. Leave blank to have no default and return an error if they don\'t specify.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '//cwslack-follow.php') && !$line1) {
                        array_pop($newdata);
                        if (!empty($_POST["specialtimeusers"])) {
                            $newdata[] = '$specialtimeusers = "' . $_POST["specialtimeusers"] . '"; //Usernames of users who should be alerted on, but who have special hours different from default start-close. Seperate user and time with comma, seperate different users with pipe |. No spaces' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        $newdata[] = PHP_EOL;
                        $newdata[] = "//cwslack-priorityalerts.php" . PHP_EOL;
                        $newdata[] = "//This uses all the variables from firmalerts as well, adhering to it for whether to post to users/channel and which channel" . PHP_EOL;
                        if (!empty($_POST["prioritylist"])) {
                            $newdata[] = '$prioritylist = "' . $_POST["prioritylist"] . '"; // Name of the priority(ies) to look out for. Separate by pipe if more than one needed.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["prioritystatus"])) {
                            $newdata[] = '$prioritystatus = "' . $_POST["prioritystatus"] . '"; // Status(es), seperated by pipe | symbol, which the priority alerts will check for and send alerts on.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["prioritywait"])) {
                            $newdata[] = '$prioritywait = ' . $_POST["prioritywait"] . '; // Number of minutes to wait after a high-priority event before alerting the technician. Maximum 119 minutes.' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '//cwslack-dbmanage.php') && !$line1) {
                        array_pop($newdata);
                        $newdata[] = PHP_EOL;
                        $newdata[] = "//cwslack-lunch.php" . PHP_EOL;
                        if (!empty($_POST["slacklunchtoken"])) {
                            $newdata[] = '$slacklunchtoken = "' . $_POST["slacklunchtoken"] . '"; // Set your token for the lunch slash command' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["lunchchargecode"])) {
                            $newdata[] = '$lunchchargecode = ' . $_POST["lunchchargecode"] . '; // Set to your "Break" charge code that lunches should be put under' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["lunchtime"])) {
                            $newdata[] = '$lunchtime = ' . $_POST["lunchtime"] . '; // Expected number of MINUTES that a user is on lunch' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["lunchmax"])) {
                            $newdata[] = '$lunchmax = ' . $_POST["lunchmax"] . '; // Number of minutes to allow before cancelling the lunch entry, does not submit time' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["lunchslackchannel"])) {
                            $newdata[] = '$lunchslackchannel = "' . $_POST["lunchslackchannel"] . '"; // Channel to send Slack messages to' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if ($_POST["lunchsendslack"] == "yes") {
                            $newdata[] = '$lunchsendslack = true; // Send messages to a slack channel when a user goes on/off lunch' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchsendslack = false; // Send messages to a slack channel when a user goes on/off lunch' . PHP_EOL;
                        }
                        if ($_POST["lunchsendonoff"] == "yes") {
                            $newdata[] = '$lunchsendonoff = 1; // Key: 0 = No notifications, 1 = Send notifications when a user goes on lunch, 2 = Send when a user goes off lunch, 3 = Send when a user goes on lunch OR off lunch' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchsendonoff = 0; // Key: 0 = No notifications, 1 = Send notifications when a user goes on lunch, 2 = Send when a user goes off lunch, 3 = Send when a user goes on lunch OR off lunch' . PHP_EOL;
                        }
                        if ($_POST["lunchcreatesched"] == "yes") {
                            $newdata[] = '$lunchcreatesched = true; // Should the script create a schedule entry on the users board' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchcreatesched = false; // Should the script create a schedule entry on the users board' . PHP_EOL;
                        }
                        if ($_POST["lunchsavetime"] == "yes") {
                            $newdata[] = '$lunchsavetime = true; // Should the script submit a time entry for the user for their lunch duration' . PHP_EOL;
                        } else {
                            $newdata[] = '$lunchsavetime = false; // Should the script submit a time entry for the user for their lunch duration' . PHP_EOL;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else {
                        $newdata[] = $data;
                    }
                }


                file_put_contents('config.php', implode('', $newdata));
                echo "<div class=\"alert alert-success\" role=\"alert\">";
                echo "Successfully configured the config.php file! Please test out your commands in Slack and submit any issues you have to GitHub!";
                echo "</div><div class=\"alert alert-info\" role=\"alert\">Please remove update-2.3-2.4-to-2.5.php to avoid people accessing it externally. You can re-add it anytime to configure database or settings again, or just manually edit config.php.</div></div>";
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
            echo "<form action=\"update-2.3-2.4-to-2.5.php\">
                                <input type=\"submit\" name='page' class=\"btn btn-success\" value=\"Proceed\" />
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