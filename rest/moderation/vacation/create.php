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

} else if (
	!isset($dataInput->startdate) ||
	!isset($dataInput->enddate) ||
	!isset($dataInput->days) ||
	!isset($dataInput->minutes) ||
	!isset($dataInput->userid)
) {
	header("HTTP/1.1 502 WRONG PARAMETERS");
	die;

} else {
	$response = $helper->restapi_vacation_create($dataInput);
	
	if ($response['status'] == 200 ) {
		header("HTTP/1.1 200 OK");
		echo json_encode($response);
	} else {
		header("HTTP/1.1 " . $response["status"] . " UNKOWN ERROR");
		die;
	}
}
