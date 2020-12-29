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



if ( !isModerator($userid) ) {
	header("HTTP/1.1 401 Unauthorized");
	die;

} 

$dataInput = file_get_contents("php://input");
$dataInput = json_decode($dataInput);

if ( isset($_GET["userid"]) ) {
	$response = $helper->restapi_get_user_overtime_list($_GET["userid"]);
	
	if ($response["status"] == 200 ) {
		echo json_encode($response);
	} else {
		header("HTTP/1.1 501 APPLICATION ERROR");
		echo json_encode($response);
		die;
	}
} else if (
	isset($dataInput->time) ||
	isset($dataInput->idUser) ||
	isset($dataInput->idPeriod)
) {
	$userid = $dataInput->idUser;
	$response = $helper->restapi_set_user_overtime($dataInput);

	if ($response["status"] != 200 ) {
		header("HTTP/1.1 501 APPLICATION UPDATE ERROR");
		echo json_encode($response);
		die;
	} else {
		$responseList = $helper->restapi_get_user_overtime_list($userid);
		
		if ($responseList["status"] != 200 ) {
			header("HTTP/1.1 502 INSERT OK FETCH FAILED");
			die;
		} else {
			echo json_encode($responseList);
		}
	}

}
