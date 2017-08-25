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
                echo "<div class='row'><form action=\"update-2.2-to-2.3.php?page=Save Settings\" method='post'>
                    <div class=\"col-sm-12\"><h4>General</h4></div>
                    <div class=\"col-sm-12\">Set below variable to True if you know your ConnectWise is slow. This will slow down the Slack integration a bit but prevent the 3000ms slack error.</div>
                    <div class=\"col-sm-7\"><label for='timeoutFix'>Enable timeout fix: </label></div><div class=\"col-sm-5\"><input type='radio' name='timeoutfix' value='yes' id='timeoutFix' > Yes <input type='radio' name='timeoutfix' value='no' checked> No </div>
                    <div class=\"col-sm-12\"><h4>TimeAlerts Module</h4></div>
                    <div class=\"col-sm-6\"><label for='noTimeUsers'>Users who should not receive time alerts if they are behind: </label></div><div class=\"col-sm-6\"><input type='text' name='notimeusers' id='noTimeUsers' placeholder='user1|user2 (Pipe separates)'></div>
                    <div class=\"col-sm-12\"><h4>Time Module</h4></div>
                    <div class=\"col-sm-6\"><label for='timeBusinessStart'>Business open time: </label></div><div class=\"col-sm-6\"><input type='text' name='timebusinessstart' id='timeBusinessStart' placeholder='8:00AM'></div>
                    <div class=\"col-sm-6\"><label for='timeBusinessClose'>Business close time: </label></div><div class=\"col-sm-6\"><input type='text' name='timebusinessclose' id='timeBusinessClose' placeholder='5:00PM'></div>
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
                    if (stristr($data, '$timeoutfix =')) {
                        if ($_POST["timeoutfix"] == "yes") {
                            $newdata[] = '$timeoutfix = true; //Enable to fix any 3000ms response from Slack.' . PHP_EOL;
                            $line1=true;
                        } else {
                            $newdata[] = '$timeoutfix = false; //Enable to fix any 3000ms response from Slack.' . PHP_EOL;
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
                    } else if (stristr($data, '//cwslack-incoming.php') && !$line1) {
                        array_pop($newdata);
                        if (!empty($_POST["timebusinessstart"])) {
                            $newdata[] = '$timebusinessstart = ' . $_POST["timebusinessstart"] . '; //Set to when your business opens in your timezone' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        if (!empty($_POST["timebusinessstart"])) {
                            $newdata[] = '$timebusinessstart = ' . $_POST["timebusinessstart"] . '; //Set to when your business opens in your timezone' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '// Database Configuration') && !$line1) {
                        array_pop($newdata);
                        if ($_POST["timeoutfix"] == "yes") {
                            $newdata[] = '$timeoutfix = true; //Enable to fix any 3000ms response from Slack.' . PHP_EOL;
                        } else {
                            $newdata[] = '$timeoutfix = false; //Enable to fix any 3000ms response from Slack.' . PHP_EOL;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '//cwslack-follow.php') && !$line1) {
                        array_pop($newdata);
                        if (!empty($_POST["notimeusers"])) {
                            $newdata[] = '//cwslack-timealerts.php';
                            $newdata[] = '//This uses all four variables above';
                            $newdata[] = '$notimeusers = ' . $_POST["notimeusers"] . ';  //Usernames of users who should not be alerted on. Useful if you have techs who occasionally enter time and you don\'t want it pinging them every day. Separate with pipe |' . PHP_EOL;
                        } else {
                            $newdata[] = $data;
                        }
                        $newdata[] = PHP_EOL . $data;
                    } else {
                        $newdata[] = $data;
                    }
                }


                file_put_contents('config.php', implode('', $newdata));
                echo "<div class=\"alert alert-success\" role=\"alert\">";
                echo "Successfully configured the config.php file! Please test out your commands in Slack and submit any issues you have to GitHub!";
                echo "</div><div class=\"alert alert-info\" role=\"alert\">Please remove update-2.2-to-2.3.php to avoid people accessing it externally. You can re-add it anytime to configure database or settings again, or just manually edit config.php.</div></div>";
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
            echo "<form action=\"update-2.2-to-2.3.php\">
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