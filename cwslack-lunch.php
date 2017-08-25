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
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';
require_once 'functions.php';

if($usedatabase==0) die("Unable to run this module without a MySQL database"); // Warning if you don't have MySQL enabled
if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slacktoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_REQUEST['text']))
{
    $blanktext = true;
}
else
{
    $blanktext = false;
    $exploded = explode(" ",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.
    if ($exploded[0]=="help") {
        die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true)));
    }
}

// Authorization array. Auto encodes API key for auhtorization above.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 = postHeader($companyname, $apipublickey, $apiprivatekey);

$slackname = $_REQUEST["user_name"];

//Timeout Fix Block
if($timeoutfix == true)
{
    ob_end_clean();
    header("Connection: close");
    ob_start();
    echo ('{"response_type": "in_channel"}');
    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
    session_write_close();
    if($sendtimeoutwait==true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral", "text" => "Please wait..."));
    }
}
//End timeout fix block

$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

if (!$mysql) //Check for errors
{
    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Connection Error: " . mysqli_connect_error()));
    } else {
        die("Connection Error: " . mysqli_connect_error()); //Return properly encoded arrays in JSON for Slack parsing.
    }
    die();
}

$sql = "CREATE TABLE IF NOT EXISTS lunch (slackuser VARCHAR(25) PRIMARY KEY, lunchstart VARCHAR(30), lunchend VARCHAR(30), lunchtoday BOOLEAN NOT NULL DEFAULT 0, lunchon BOOLEAN NOT NULL DEFAULT 0)";
if (mysqli_query($mysql, $sql)) {
    //Table created successfully
} else {
    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "lunch Table Creation Error: " . mysqli_error($mysql)));
    } else {
        die("lunch Table Creation Error: " . mysqli_error($mysql));
    }
    die();
}


$val1 = mysqli_real_escape_string($mysql,$slackname);
$sql = "SELECT * FROM `usermap` WHERE `slackuser`=\"" . $val1 . "\"";

$result = mysqli_query($mysql, $sql); //Run result
$rowcount = mysqli_num_rows($result);
if($rowcount > 1) //If there were too many rows matching query
{
    die("Error: too many users somehow?"); //This should NEVER happen.
}
else if ($rowcount == 1) //If exactly 1 row is found.
{
    $row = mysqli_fetch_assoc($result); //Row association.

    $cwname = $row["cwname"]; //Return the connectwise name of the row found as the CW member name.
}
else //If no rows are found
{
    if($usecwname==1) //If variable enabled
    {
        $cwname = $_REQUEST['user_name'];
    }
    else
    {
        // Die if unable to get cwname set
        if ($timeoutfix == true) {
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "Unable to find user to match to in Connectwise. Please have your admin map your username in Slack or enable the usecwname setting"));
        } else {
            die("Unable to find user to match to in Connectwise. Please have your admin map your username in Slack or enable the usecwname setting");
        }
        die();
    }
}

//Parse no-text
$golunchon = NULL;
if($blanktext)
{
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "SELECT * FROM `lunch` WHERE `slackuser`=\"" . $val1 . "\"";

    $result = mysqli_query($mysql, $sql); //Run result
    $rowcount = mysqli_num_rows($result);
    if($rowcount > 1) //If there were too many rows matching query
    {
        die("Error: too many users somehow?"); //This should NEVER happen.
    }
    else if ($rowcount == 1) //If exactly 1 row is found.
    {
        $row = mysqli_fetch_assoc($result); //Row association.

        if($row["lunchstart"] == NULL || !$row["lunchtoday"])
        {
            $golunchon = true;
        }
        else if($row["lunchend"] == NULL)
        {
            $golunchon = false;
        }
    }
    else
    {
        $val1 = mysqli_real_escape_string($mysql,$slackname);
        $sql = "INSERT INTO `lunch` (`slackuser`) VALUES ('" . $val1 . "');"; //SQL Query to insert new map

        if(mysqli_query($mysql,$sql))
        {
            $golunchon = true;
        }
        else
        {
            die("MySQL Error: " . mysqli_error($mysql));
        }
    }
}

//Lunch on block
if($golunchon || $exploded[0]=="on" || $exploded[0]=="go" || $exploded[0]=="start")
{
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "SELECT * FROM `lunch` WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result
    $rowcount = mysqli_num_rows($result);

    $userdata = mysqli_fetch_assoc($result); //Row association.

    if($userdata["lunchon"])
    {
        if ($timeoutfix == true) {
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You are already on lunch. Please use /lunch off to go off lunch"));
        } else {
            die("You are already on lunch. Please use /lunch off to go off lunch");
        }
        die();
    }

    //Schedule entry block
    if($lunchcreatesched)
    {

    }

    //Slack notifications block
    if($lunchsendslack)
    {
        if($lunchsendonoff == 1 || $lunchsendonoff == 3)
        {

        }
    }

    //Email notifications block
    if($lunchemailaddress)
    {
        if($lunchsendonoff == 1 || $lunchsendonoff == 3)
        {

        }
    }
}

//Lunch off block
if($golunchon || $exploded[0]=="off" || $exploded[0]=="back" || $exploded[0]=="stop" || $exploded[0]=="end")
{
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "SELECT * FROM `lunch` WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result
    $rowcount = mysqli_num_rows($result);

    $userdata = mysqli_fetch_assoc($result); //Row association.

    if(!$userdata["lunchon"])
    {
        if ($timeoutfix == true) {
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You are already off lunch. Please use /lunch on to go on lunch"));
        } else {
            die("You are already off lunch. Please use /lunch on to go on lunch");
        }
        die();
    }

    //Time block
    if($lunchsavetime)
    {

    }

    //Slack notifications block
    if($lunchsendslack)
    {
        if($lunchsendonoff == 2 || $lunchsendonoff == 3)
        {

        }
    }

    //Email notifications block
    if($lunchemailaddress)
    {
        if($lunchsendonoff == 2 || $lunchsendonoff == 3)
        {

        }
    }
}


?>