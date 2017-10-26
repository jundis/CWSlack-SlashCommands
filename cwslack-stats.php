<?php
/**
 * Created by PhpStorm.
 * User: jundis
 * Date: 10/26/2017
 * Time: 9:40 AM
 */

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

$sql = "CREATE TABLE IF NOT EXISTS stats (user VARCHAR(50) NOT NULL PRIMARY KEY, lastcommand VARCHAR(10), lastused DATETIME)";
if (mysqli_query($mysql, $sql)) {
    //Table created successfully
} else {
    echo "stats Table Creation Error: " . mysqli_error($mysql);
}

$statscommand = substr($_REQUEST['command'],1);
$statsuser = $_REQUEST['user_name'];

$sql = "SELECT ".$statscommand." FROM stats"; //SQL Query to insert new map

if(!mysqli_query($mysql,$sql))
{

    $sql = "ALTER TABLE `stats` ADD COLUMN " . $statscommand . " INT DEFAULT 0";
    if(mysqli_query($mysql,$sql))
    {
        // Inserted user
    }
    else
    {
        die("MySQL Error: " . mysqli_error($mysql));
    }
}

$statsdate = date("Y-m-d H:i:s");
$sql = "SELECT * FROM `stats` WHERE `user` = '" . $statsuser . "'"; //SQL Query to select all users

$result = mysqli_query($mysql, $sql); //Run result
if($result) // If query worked
{
    $statsuserdata = mysqli_fetch_assoc($result);
    $commandcount = $statsuserdata[$statscommand] + 1;
    $sql = "UPDATE `stats` SET lastcommand = '" . $statscommand . "', lastused = '" . $statsdate . "', " . $statscommand ." = '" . $commandcount . "' WHERE `user` = '" . $statsuser . "'"; //SQL Query to insert new map

    if(mysqli_query($mysql,$sql))
    {
        // Inserted user
    }
    else
    {
        die("MySQL Error: " . mysqli_error($mysql));
    }
}
else
{
    $sql = "INSERT INTO `stats` (`user`, `lastcommand`, `lastused`, `" . $statscommand . "`) VALUES ('" . $statsuser . "', '" . $statscommand . "', '" . $statsdate . "', 1);"; //SQL Query to insert new map

    if(mysqli_query($mysql,$sql))
    {
        // Inserted user
    }
    else
    {
        die("MySQL Error: " . mysqli_error($mysql));
    }
}



?>