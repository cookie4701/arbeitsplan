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
	!isset($dataInput->label) || 
	!isset($dataInput->startdate) ||
	!isset($dataInput->enddate)
) {
	header("HTTP/1.1 502 WRONG PARAMETERS");
	die;

} else {
	$response = $helper->restapi_create_period($dataInput);
	
	if ($response['status'] == 200 ) {
		$data = $helper->restapi_get_workperiods();

		echo json_encode($data);
	}
}
