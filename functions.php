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

// This file contains support functions for the CWSlack stack.
// Do not modify unless you know what you're doing.

/**
 * @param $url
 * @param $header
 * @return mixed
 */
function cURL($url, $header)
{
    global $debugmode; //Require global variable $debugmode from config.php.
    $ch = curl_init(); //Initiate a curl session

    //Create curl array to set the API url, headers, and necessary flags.
    $curlOpts = array(
        CURLOPT_URL => $url, //URL to send the curl request to
        CURLOPT_RETURNTRANSFER => true, //Request data returned instead of output
        CURLOPT_HTTPHEADER => $header, //Header to include, mainly for authorization purposes
        CURLOPT_FOLLOWLOCATION => true, //Follow 301/302 redirects
        CURLOPT_HEADER => 1, //Use header
    );
    curl_setopt_array($ch, $curlOpts); //Set the curl array to $curlOpts

    $answerTData = curl_exec($ch); //Set $answerTData to the curl response to the API.
    $headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);  //Get the header length of the curl response
    $curlBodyTData = substr($answerTData, $headerLen); //Remove header data from the curl string.
    if($debugmode) //If the global $debugmode variable is set to true
    {
        var_dump($answerTData); //Dump the raw data.
    }
    // If there was an error, show it
    if (curl_error($ch)) {
        die(curl_error($ch));
    }
    curl_close($ch); //Close the curl connection for cleanup.

    $jsonDecode = json_decode($curlBodyTData); //Decode the JSON returned by the CW API.

    if(array_key_exists("code",$jsonDecode)) { //Check if array contains error code
        if($jsonDecode->code == "NotFound") { //If error code is NotFound
            die("Connectwise record was not found."); //Report that the ticket was not found.
        }
        if($jsonDecode->code == "Unauthorized") { //If error code is an authorization error
            die("401 Unauthorized, check API key to ensure it is valid."); //Fail case.
        }
        else { //Else other error
            die("Unknown Error Occurred, check API key and other API settings. Error " . $jsonDecode->code . ": " . $jsonDecode->message); //Fail case, including the message and code output from connectwise.
        }
    }
    if(array_key_exists("errors",$jsonDecode)) //If connectwise returned an error.
    {
        $errors = $jsonDecode->errors; //Make array easier to access.

        die("ConnectWise Error: " . $errors[0]->message); //Return CW error
    }

    return $jsonDecode; //Return the decoded output.
}

/**
 * @param $url
 * @param $header
 * @param $postfieldspre
 * @return mixed
 */
function cURLPost($url, $header, $request, $postfieldspre)
{
    global $debugmode; //Require global variable $debugmode from config.php
    $ch = curl_init(); //Initiate a curl session

    $postfields = json_encode($postfieldspre); //Format the array as JSON

    //Same as previous curl array but includes required information for PATCH commands.
    $curlOpts = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => $request,
        CURLOPT_POSTFIELDS => $postfields,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 1,
    );
    curl_setopt_array($ch, $curlOpts);

    $answerTCmd = curl_exec($ch);
    $headerLen = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $curlBodyTCmd = substr($answerTCmd, $headerLen);

    if($debugmode)
    {
        var_dump($answerTCmd);
    }

    // If there was an error, show it
    if (curl_error($ch)) {
        die(curl_error($ch));
    }
    curl_close($ch);
    if($curlBodyTCmd == "ok") //Slack catch
    {
        return null;
    }
    $jsonDecode = json_decode($curlBodyTCmd); //Decode the JSON returned by the CW API.

    if(array_key_exists("code",$jsonDecode)) { //Check if array contains error code
        if($jsonDecode->code == "NotFound") { //If error code is NotFound
            die("Connectwise record was not found."); //Report that the ticket was not found.
        }
        else if($jsonDecode->code == "Unauthorized") { //If error code is an authorization error
            die("401 Unauthorized, check API key to ensure it is valid."); //Fail case.
        }
        else if($jsonDecode->code == NULL)
        {
            //do nothing.
        }
        else {
            die("Unknown Error Occurred, check API key and other API settings. Error " . $jsonDecode->code . ": " . $jsonDecode->message); //Fail case.
        }
    }
    if(array_key_exists("errors",$jsonDecode)) //If connectwise returned an error.
    {
        $errors = $jsonDecode->errors; //Make array easier to access.

        die("ConnectWise Error: " . $errors[0]->message); //Return CW error
    }

    return $jsonDecode;
}

function authHeader($company, $publickey, $privatekey)
{
    $apicompanyname = strtolower($company); //Company name all lower case for api auth.
    $authorization = base64_encode($apicompanyname . "+" . $publickey . ":" . $privatekey); //Encode the API, needed for authorization.

    return array("Authorization: Basic ". $authorization);
}

function postHeader($company, $publickey, $privatekey)
{
    $apicompanyname = strtolower($company); //Company name all lower case for api auth.
    $authorization = base64_encode($apicompanyname . "+" . $publickey . ":" . $privatekey); //Encode the API, needed for authorization.

    return array(
        "Authorization: Basic " . $authorization,
        "Content-Type: application/json"
    );
}

?>