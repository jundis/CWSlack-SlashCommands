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
                    <input type=\"submit\" name='page' value=\"Test MySQL\" /></form>";
            echo "</form></div></div></body></html>";
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
                                <input type=\"submit\" name='page' value=\"Retry MySQL\" />
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
                                <input type=\"submit\" name='page' value=\"Retry MySQL\" />
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
                                <input type=\"submit\" name='page' value=\"Retry MySQL\" />
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
                                <input type=\"submit\" name='page' value=\"Retry MySQL\" />
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
                                <input type=\"submit\" name='page' value=\"Retry MySQL\" />
                                </form>";
                die();
            }

            echo "<div class=\"alert alert-success\" role=\"alert\">";
            echo "Successfully connected and setup MySQL Database!<br><br>You can now finish configuring the options in config.php and then test it out!";
            echo "</div></div></div></body></html>";

            mysqli_close($mysql);

            $filedata = file('config.php');
            $newdata = array();

            foreach ($filedata as $data) {
                if (stristr($data, '$dbhost')) {
                    $newdata[] =  '$dbhost = "'.$dbhost.'; //Your MySQL DB';
                }
                if (stristr($data, '$dbusername')) {
                    $newdata[] =  '$dbusername = "'.$dbusername.'"; //Your MySQL DB Username';
                }
                if (stristr($data, '$dbpassword')) {
                    $newdata[] ='$dbpassword = "'.$dbpassword.'"; //Your MySQL DB Password';
                }
                if (stristr($data, '$dbdatabase')) {
                    $newdata[] = '$dbdatabase = "'.$dbdatabase.'"; //Change if you have an existing database you want to use, otherwise leave as default.';
                }
                if (stristr($data, '$usedatabase')) {
                    $newdata[] = '$usedatabase = 1; // Set to 0 by default, set to 1 if you want to enable MySQL.';
                }
                $newdata[] = $data;
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
                                <input type=\"submit\" name='page' value=\"Proceed\" />
                                </form>";
                    }
                    else
                    {
                        echo "<button type='button' disabled>Resolve errors before proceeding</button>";
                    }
                ?>
            </div>
        </div>
    </body>
</html>