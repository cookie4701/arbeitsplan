<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../cors.php");
cors();

require("../../jwt.php");
$data = getJwtData();
$userid = $data['id'];


include_once("../../../helper.class.php");

$helper = new Helper();

require_once ("../../member.php");


$dataInput = file_get_contents("php://input");
$dataInput = json_decode($dataInput);

if ( !isModerator($userid) ) {
    header("HTTP/1.1 401 Unauthorized");

} else if (!isset($dataInput->moduserid) || 
	!isset($dataInput->qdate) || ) {

    header("HTTP/1.1 401 Unauthorized");

} else {
    $responseArray = $helper->restapi_moderation_freeze_userinput($dataInput);
	
	if ( strcmp($responseArray["status"], "ok") == 0 ) {
		header("HTTP/1.1 200 OK");
		echo json_encode($responseArray);
	} else {
		header("HTTP/1.1 501 APPLICATION ERROR");
		echo json_encode($responseArray);
	}
}
