<?php

// Schedule your cron tab using code below. Windows Task Scheduler and other scheduled systems should also work, if all else fails you can do a MySQL event for it.
// 0 0 * * * /usr/bin/php /var/www/cwslack-lunch-cron.php >/dev/null 2>&1
// May have to adjust locations of php and this file if they are different on your system

$mysql = mysqli_connect($dbhost, $dbusername, $dbpassword, $dbdatabase); //Connect MySQL

if (!$mysql) //Check for errors
{
    die("Connection Error: " . mysqli_connect_error()); //Return properly encoded arrays in JSON for Slack parsing.
}

$sql = "UPDATE lunch SET lunchtoday = 0, lunchon = 0, lunchend = NULL, lunchstart = NULL";
if (mysqli_query($mysql, $sql)) {
    //Table cleared successfully
} else {
    die("lunch Table Clear Error: " . mysqli_error($mysql));
}