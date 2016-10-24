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

<?php

if($_GET["page"]=="Proceed")
{
    die ("Thanks!");
}

$php_version=phpversion();
if($php_version<5)
{
    $error=true;
    $php_error="PHP version is $php_version. Version 5 or newer is required.";
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
    $mysql_error="MySQL version is $mysql_version. Version 5 or newer is required.";
}

if(!function_exists('curl_exec'))
{
    $curl_error="PHP CURL function is not enabled!";
}
?>

<div class="container">
    <div class="jumbotron">
        <h3>CWSlack-SlashCommands Installer</h3>
         <p>Checking versions...</p>

<?php
    if(empty($php_error)) echo "<span style='color:green;'>PHP Version $php_version - OK!</span><br><br>";
    else echo "<span style='color:red;'>$php_error</span><br><br>";

    if(empty($mysql_error)) echo "<span style='color:green;'>MySQL Version $mysql_version - OK!</span><br><br>";
    else echo "<span style='color:red;'>$mysql_error</span><br><br>";

    if(empty($curl_error)) echo "<span style='color:green;'>cURL Enabled - OK!</span><br><br>";
    else echo "<span style='color:red;'>$curl_error</span><br><br>";

    if(empty($curl_error) && empty($mysql_error) && empty($php_error))
    {
        echo "<form action=\"test.php\">
                <input type=\"submit\" name='page' value=\"Proceed\" />
                </form>";
    }
    else
    {
        echo "<button type='button' disabled>Resolve errors before proceeding</button>";
    }
?>
    </div></div>
    </body>
</html>