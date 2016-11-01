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
header('Content-Type: application/json'); //Set the header to return JSON, required by Slack
require_once 'config.php';
require_once 'functions.php';

if(empty($_GET['token']) || ($_GET['token'] != $slacktaskstoken)) die("Slack token invalid."); //If Slack token is not correct, kill the connection. This allows only Slack to access the page for security purposes.
if(empty($_GET['text'])) die("No text provided."); //If there is no text added, kill the connection.

$exploded = explode(" ",$_GET['text']); //Explode the string attached to the slash command for use in variables.

//This section checks if the ticket number is not equal to 6 digits (our tickets are in the hundreds of thousands but not near a million yet) and kills the connection if it's not.
if(!is_numeric($exploded[0])) {
    //Check to see if the first command in the text array is actually help, if so redirect to help webpage detailing slash command use.
    if ($exploded[0]=="help") {
        die(json_encode(array("parse" => "full", "response_type" => "in_channel","text" => "Please visit " . $helpurl . " for more help information","mrkdwn"=>true))); //Encode a JSON response with a help URL.
    }
    else //Else close the connection.
    {
        die("Unknown entry for ticket number. Please use [ticket number] [list/update/complete/open/new] [task number]");
    }
}

//Set NULL Variables
$ticketnumber = $exploded[0];
$command = NULL;
$task = NULL;
$sentence = NULL;

// Authorization array. Auto encodes API key for auhtorization above.
$header_data = authHeader($companyname, $apipublickey, $apiprivatekey);
// Authorization array, with extra json content-type used in patch commands to change tickets.
$header_data2 = postHeader($companyname, $apipublickey, $apiprivatekey);

//Set URL
$taskurl = $connectwise . "/v4_6_release/apis/3.0/service/tickets/" . $ticketnumber . "/tasks";

if (array_key_exists(1, $exploded)) //If a second string exists in the slash command array, make it the command.
{
    $command = $exploded[1];
    if ($command!="new"&&$command!="add"&&array_key_exists(2, $exploded)) //If a third string exists in the slash command array, make it the task number.
    {
        $task = $exploded[2];
    }
    if($command=="new"||$command=="add")
    {
        if (array_key_exists(2, $exploded)) //If a third string exists in the slash command array, make it the sentence for notes.
        {
            unset($exploded[0]);
            unset($exploded[1]);
            $sentence = implode(" ", $exploded); //Set the sentence
        }
    }
    else
    {
        if (array_key_exists(3, $exploded)) //If a fourth string exists in the slash command array, make it the sentence for notes.
        {
            unset($exploded[0]);
            unset($exploded[1]);
            unset($exploded[2]);
            $sentence = implode(" ", $exploded); //Set the sentence
        }
    }
}
else
{
    die("Please use [ticket number] [list/update/complete/open/new] [task number]");
}

if($command=="list")
{
    $output = "";
    $taskdata = cURL($taskurl, $header_data); // Get the JSON returned by the CW API for $taskurl.
    if(empty($taskdata))
    {
        die("No tasks found on ticket #".$ticketnumber);
    }
    foreach($taskdata as $t)
    {
        $output = $output . "Task #" . $t->priority. " | Status: " . ($t->closedFlag ? "*Done*" : "*Open*") . ":\n" . $t->notes . "\n";
    }

    $return =array(
        "parse" => "full",
        "response_type" => "ephemeral",
        "attachments"=>array(array(
            "fallback" => "Tasks for Ticket #" . $ticketnumber, //Fallback for notifications
            "title" => "Task ID | Status | Notes",
            "pretext" => "Tasks for Ticket #" . $ticketnumber, //Return info string with ticket number.
            "text" => $output,
            "mrkdwn_in" => array(
                "text",
                "pretext"
            )
        ))
    );

    echo json_encode($return, JSON_PRETTY_PRINT); //Return properly encoded arrays in JSON for Slack parsing.
}
else if ($command=="open"||$command=="reopen")
{
    $taskid=NULL; //Set ID to NULL for later.

    $taskdata = cURL($taskurl, $header_data); // Get the JSON returned by the CW API for $taskurl.
    if(empty($taskdata))
    {
        die("No tasks found on ticket #".$ticketnumber);
    }
    foreach($taskdata as $t)
    {
        if($t->priority==$task)
        {
            $taskid = $t->id;
            if($t->closedFlag==false)
            {
                die("Task #" .$task . " is already open.");
            }
        }
    }
    if($taskid==NULL)
    {
        die("Task #" . $task . " not found on Ticket #" . $ticketnumber . ".");
    }

    $taskpatch = $taskurl . "/" . $taskid;

    $dataTCmd = cURLPost(
        $taskpatch,
        $header_data2,
        "PATCH",
        array(array("op" => "replace", "path" => "/closedFlag", "value" => false))
    );

    echo "Task #" . $task . " has been marked open.";
}
else if ($command=="close"||$command=="complete"||$command=="done"||$command=="completed")
{
    $taskid=NULL; //Set ID to NULL for later.

    $taskdata = cURL($taskurl, $header_data); // Get the JSON returned by the CW API for $taskurl.
    if(empty($taskdata))
    {
        die("No tasks found on ticket #".$ticketnumber);
    }
    foreach($taskdata as $t)
    {
        if($t->priority==$task)
        {
            $taskid = $t->id;
            if($t->closedFlag==true)
            {
                die("Task #" .$task . " is already marked done.");
            }
        }
    }
    if($taskid==NULL)
    {
        die("Task #" . $task . " not found on Ticket #" . $ticketnumber . ".");
    }

    $taskpatch = $taskurl . "/" . $taskid;

    $dataTCmd = cURLPost(
        $taskpatch,
        $header_data2,
        "PATCH",
        array(array("op" => "replace", "path" => "/closedFlag", "value" => true))
    );
    if($sentence != NULL) {
        $dataTCmd = cURLPost(
            $taskpatch,
            $header_data2,
            "PATCH",
            array(array("op" => "replace", "path" => "/resolution", "value" => $sentence))
        );

        echo "Task #" . $task . " has been marked completed with resolution note: " . $sentence;
    }
    else
    {
        echo "Task #" . $task . " has been marked completed.";
    }
}
else if ($command=="update"||$command=="change"||$command=="note")
{
    $taskid=NULL; //Set ID to NULL for later.

    $taskdata = cURL($taskurl, $header_data); // Get the JSON returned by the CW API for $taskurl.
    if(empty($taskdata))
    {
        die("No tasks found on ticket #".$ticketnumber);
    }
    foreach($taskdata as $t)
    {
        if($t->priority==$task)
        {
            $taskid = $t->id;
            if($t->closedFlag==true)
            {
                die("Task #" .$task . " is already marked done.");
            }
        }
    }
    if($taskid==NULL)
    {
        die("Task #" . $task . " not found on Ticket #" . $ticketnumber . ".");
    }

    $taskpatch = $taskurl . "/" . $taskid;

    if($sentence != NULL) {
        $dataTCmd = cURLPost(
            $taskpatch,
            $header_data2,
            "PATCH",
            array(array("op" => "replace", "path" => "/notes", "value" => $sentence))
        );

        echo "Task #" . $task . " has been updated with note: " . $sentence;
    }
    else
    {
        echo "No note provided for update.";
    }
}
else if ($command=="new"||$command=="add")
{
    $priority = 1;
    $taskdata = cURL($taskurl, $header_data); // Get the JSON returned by the CW API for $taskurl.
    if(empty($taskdata))
    {
        //Do nothing.
    }
    else
    {
        $priority = sizeof($taskdata) + 1;
    }
    if($sentence != NULL) {
        $dataTCmd = cURLPost(
            $taskurl,
            $header_data2,
            "POST",
            array("notes"=>$sentence,"priority"=>$priority)
        );

        echo "A new task has been created with note: " . $sentence;
    }
    else
    {
        echo "No note provided for new task.";
    }
}
else
{
    die("Unknown command. Please use [ticket number] [list/update/complete/open/new] [task number]");
}



?>