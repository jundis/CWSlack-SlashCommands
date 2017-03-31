<?php
/*
	CWSlack-SlashCommands
    Copyright (C) 2016  jundis

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
            if ($_GET["page"] == "Save Settings" || $_GET["page"] == "Proceed" || $_GET["page"] == "Skip to Config.php Settings") {
                $filedata = file('config.php');
                $newdata = array();
                $line1 = false; //For later use
                $line2 = false;
                $line3 = false;
                $line4 = false;

                foreach ($filedata as $data) {
                    if (stristr($data, '$connectwisebranch =')) {
                        $line1=true;
                        $newdata[] = $data;
                    } else if (stristr($data, '$sendtimeoutwait =')) {
                        $line2=true;
                        $newdata[] = $data;
                    } else if (stristr($data, '$inactivecontacts =')) {
                        $line3=true;
                        $newdata[] = $data;
                    } else if (stristr($data, '$boardmapping =')) {
                        $line4=true;
                        $newdata[] = $data;
                    } else if (stristr($data, '$companyname =') && !$line1) {
                        array_pop($newdata);
                        $newdata[] = '$connectwisebranch = "v4_6_release"; //Set to the portion of your CW URL shown here: https://cw.domain.com/**v4_6_release**/ConnectWise.aspx' . PHP_EOL;
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '// Database Configuration') && !$line2) {
                        array_pop($newdata);
                        $newdata[] = '$sendtimeoutwait = true; //Set to true to send a please wait message with every command. Only does something when $timeoutfix is set to true.' . PHP_EOL;
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '//cwlsack-notes.php') && !$line3) {
                        array_pop($newdata);
                        $newdata[] = '$inactivecontacts = false; //Set to true to return inactive contacts' . PHP_EOL;
                        $newdata[] = PHP_EOL . $data;
                    } else if (stristr($data, '//cwslack-firmalerts.php') && !$line4) {
                        array_pop($newdata);
                        $newdata[] = '$boardmapping = ""; //Put board to channel mappings in here. Formatted as "Board Name|channel,Board Name|channel". Any board not covered will go to the default channel for the webhook, filter boards using $badboard. Example above' . PHP_EOL;
                        $newdata[] = PHP_EOL . $data;
                    } else {
                        $newdata[] = $data;
                    }
                }


                file_put_contents('config.php', implode('', $newdata));
                echo "<div class=\"alert alert-success\" role=\"alert\">";
                echo "Successfully configured the config.php file! Please test out your commands in Slack and submit any issues you have to GitHub!";
                echo "</div><div class=\"alert alert-info\" role=\"alert\">Please remove update-2.3-to-2.4.php to avoid people accessing it externally. You can re-add it anytime to configure database or settings again, or just manually edit config.php.</div></div>";
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
            echo "<form action=\"update-2.3-to-2.4.php\">
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