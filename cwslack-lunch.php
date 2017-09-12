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
if(empty($_REQUEST['token']) || ($_REQUEST['token'] != $slacklunchtoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_REQUEST['text']))
{
    $blanktext = true;
}
else
{
    $blanktext = false;
}

$exploded = explode(" ",$_REQUEST['text']); //Explode the string attached to the slash command for use in variables.
if ($exploded[0]=="help") {
    die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true)));
}

// Authorization array. Auto encodes API key for auhtorization above.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 = postHeader($companyname, $apipublickey, $apiprivatekey);

$timeurl = $connectwise . "/$connectwisebranch/apis/3.0/time/entries";
$schedurl = $connectwise . "/$connectwisebranch/apis/3.0/schedule/entries";

$slackname = $_REQUEST["user_name"];

//REMOVE LATER
$timeoutfix = false;

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

$sql = "CREATE TABLE IF NOT EXISTS lunch (slackuser VARCHAR(25) PRIMARY KEY, lunchstart DATETIME, lunchend DATETIME, lunchtoday BOOLEAN NOT NULL DEFAULT 0, lunchon BOOLEAN NOT NULL DEFAULT 0)";
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


if($_REQUEST['text'] == "list" || $_REQUEST['text'] == "status")
{
    $sql = "SELECT * FROM `lunch`";

    $result = mysqli_query($mysql, $sql); //Run result
    $output = "```User           | Status    | Lunch Start Today\n";
    if(mysqli_num_rows($result) > 0) //If there were too many rows matching query
    {
        while($row = mysqli_fetch_assoc($result))
        {
            $formatuser = "";
            $formatstatus = "";
            $formatstart = "";

            if(strlen($row["slackuser"]) > 14)
            {
                $formatuser = substr($row["slackuser"],0,11) . "... ";
            }
            else if(strlen($row["slackuser"]) == 14)
            {
                $formatuser = $row["slackuser"] . " ";
            }
            else
            {
                $formatuser = str_pad($row["slackuser"], 15);
            }

            if($row["lunchon"] == 1)
            {
                $formatstatus = " On Lunch  ";
            }
            else if($row["lunchon"] == 0 && $row["lunchtoday"] == 1)
            {
                $formatstatus = " Off Lunch ";
            }
            else
            {
                $formatstatus = " Not Taken ";
            }

            if($row["lunchstart"] == NULL)
            {
                $formatstart = " Not yet started";
            }
            else
            {
                $formatstart = " " . date("m/d/y g:ia", strtotime($row["lunchstart"]));
            }

            $output = $output . $formatuser . "|" . $formatstatus . "|" . $formatstart . "\n";
        }
        $output = $output . "```";
        if ($timeoutfix == true) {
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "in_channel","fallback" => "Lunch Status List","title"=>"Lunch Status List","text" => $output));
        } else {
            die($output);
        }
        die();
    }
    else
    {
        die("No user mappings found in database.");
    }
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

        if($row["lunchstart"] == NULL || !$row["lunchon"])
        {
            $golunchon = true;
        }
        else
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

    if($userdata["lunchtoday"])
    {
        if ($timeoutfix == true) {
            cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You have already taken lunch today. Please use /lunch split if you are taking additional lunch time."));
        } else {
            die("You have already taken lunch today. Please use /lunch split if you are taking additional lunch time.");
        }
        die();
    }

    //Schedule entry block
    if($lunchcreatesched)
    {
        $datestart = gmdate("Y-m-d\TH:i:s\Z"); //Start time of the ticket.
        $dateend = gmdate("Y-m-d\TH:i:s\Z",strtotime("+1 hour")); //Start time of the ticket.
        $postfieldspre = array("member"=>array("identifier"=>$cwname), "type"=>array("id"=>13), "dateStart" => $datestart, "dateEnd" => $dateend, "allowScheduleConflictsFlag"=>true, "name"=>"Lunch [Slack]");
        $dataTNotes = cURLPost($schedurl, $header_data2, "POST", $postfieldspre);
    }

    //Slack notifications block
    if($lunchsendslack)
    {
        if($lunchsendonoff == 1 || $lunchsendonoff == 3)
        {
            $offlunchat = date("g:ia", strtotime("+1 hour"));

            $postfieldspre = array(
                "channel"=>$lunchslackchannel,
                "text"=>"$cwname has taken their lunch and they will return at $offlunchat."
            );

            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
        }
    }

    //Email notifications block
    if($lunchsendemail)
    {
        if($lunchsendonoff == 1 || $lunchsendonoff == 3)
        {
            ini_set("SMTP", $smtpserver);
            ini_set("smtp_port", $smtpport);

            $headers = 'From: ' . $smtpname . '<' . $smtpfrom . ">\r\n" .
                'Reply-To: ' . $smtpfrom . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $subject = "$cwname on lunch";
            $body = "$cwname has taken their lunch and should return at $offlunchat.";

            mail($lunchemailto, $subject, $body, $headers);
        }
    }

    $starttime = date("Y-m-d H:i:s");
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "UPDATE `lunch` SET `lunchstart` = '" . $starttime . "', `lunchon` = 1 WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result

    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You have gone on lunch. Please return by $offlunchat"));
    } else {
        die("You have gone on lunch. Please return by $offlunchat"); //Post to slack
    }
    die(); //End of section
}

//Lunch on block
if($exploded[0]=="split")
{
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "SELECT * FROM `lunch` WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result
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

    //Slack notifications block
    if($lunchsendslack)
    {
        if($lunchsendonoff == 1 || $lunchsendonoff == 3)
        {
            $offlunchat = date("g:ia", strtotime("+1 hour"));

            $postfieldspre = array(
                "channel"=>$lunchslackchannel,
                "text"=>"$cwname has gone back on a split lunch, and will return soon. Please message the tech for exact timing."
            );

            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
        }
    }

    //Email notifications block
    if($lunchsendemail)
    {
        if($lunchsendonoff == 1 || $lunchsendonoff == 3)
        {
            ini_set("SMTP", $smtpserver);
            ini_set("smtp_port", $smtpport);

            $headers = 'From: ' . $smtpname . '<' . $smtpfrom . ">\r\n" .
                'Reply-To: ' . $smtpfrom . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $subject = "$cwname on lunch";
            $body = "$cwname has gone back on a split lunch, and will return soon. Please message the tech for exact timing.";

            mail($lunchemailto, $subject, $body, $headers);
        }
    }

    $starttime = date("Y-m-d H:i:s");
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "UPDATE `lunch` SET `lunchstart` = '" . $starttime . "', `lunchend` = NULL, `lunchon` = 1 WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result

    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You have gone on lunch. As this is a split lunch, please return within your remaining lunch time."));
    } else {
        die("You have gone on lunch. As this is a split lunch, please return within your remaining lunch time."); //Post to slack
    }
    die(); //End of section
}

//Lunch off block
if(!$golunchon || $exploded[0]=="off" || $exploded[0]=="back" || $exploded[0]=="stop" || $exploded[0]=="end")
{
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "SELECT * FROM `lunch` WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result
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
        $datestart = gmdate("Y-m-d\TH:i:s\Z",strtotime($userdata["lunchstart"])); //Start time of the time entry.
        $dateend = gmdate("Y-m-d\TH:i:s\Z"); //End time of the time entry.

        $postfieldspre = array(
            "notes" => "Lunch via Slack",
            "chargeToType" => "ChargeCode",
            "chargeToId" => "$lunchchargecode",
            "timeStart" => $datestart,
            "timeEnd" => $dateend,
            "member" => array("identifier"=>$cwname)
        ); //Post time as user

        $dataTNotes = cURLPost($timeurl, $header_data2, "POST", $postfieldspre);

        if(array_key_exists("errors",$dataTNotes)) //If connectwise returned an error.
        {
            $errors = $dataTNotes->errors; //Make array easier to access.

            if ($timeoutfix == true) {
                cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "ConnectWise Error: " . $errors[0]->message));
            } else {
                die("ConnectWise Error: " . $errors[0]->message); //Post to slack
            }
            die(); //Return CW error
        }
    }

    //Slack notifications block
    if($lunchsendslack)
    {
        if($lunchsendonoff == 2 || $lunchsendonoff == 3)
        {
            $offlunchat = date("g:ia", strtotime("+1 hour"));

            $postfieldspre = array(
                "channel"=>$lunchslackchannel,
                "text"=>"$cwname has returned from lunch. They went on lunch at" . $userdata["lunchstart"]
            );

            cURLPost($webhookurl, $header_data2, "POST", $postfieldspre);
        }
    }

    //Email notifications block
    if($lunchsendemail)
    {
        if($lunchsendonoff == 2 || $lunchsendonoff == 3)
        {
            ini_set("SMTP", $smtpserver);
            ini_set("smtp_port", $smtpport);

            $headers = 'From: ' . $smtpname . '<' . $smtpfrom . ">\r\n" .
                'Reply-To: ' . $smtpfrom . "\r\n" .
                'X-Mailer: PHP/' . phpversion();
            $subject = "$cwname off lunch";
            $body = "$cwname has returned from lunch.";

            mail($lunchemailto, $subject, $body, $headers);
        }
    }

    $endtime = date("Y-m-d H:i:s");
    $val1 = mysqli_real_escape_string($mysql,$slackname);
    $sql = "UPDATE `lunch` SET `lunchend` = '" . $endtime . "', `lunchon` = 0, `lunchtoday` = 1 WHERE `slackuser`=\"" . $val1 . "\""; //

    $result = mysqli_query($mysql, $sql); //Run result

    if ($timeoutfix == true) {
        cURLPost($_REQUEST["response_url"], array("Content-Type: application/json"), "POST", array("parse" => "full", "response_type" => "ephemeral","text" => "You have gone off lunch and a time entry has been added."));
    } else {
        die("You have gone off lunch and a time entry has been added."); //Post to slack
    }
    die(); //End of section
}


?>