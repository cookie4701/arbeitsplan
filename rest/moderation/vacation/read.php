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
	die;

} else if ( ! isset($dataInput->userId) ) {
	header("HTTP/1.1 500 Not enough parameters provided");
	die;
} else {
	$responseList = $helper->restapi_vacation_read($dataInput->userId);
	if (! isset($responseList) || ! isset($responseList["data"]) ) {
		header("HTTP/1.1 501 UNKOWN ERROR");
		die;
	}

	echo json_encode($responseList["data"]);
}
