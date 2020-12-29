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

} else if ( isset($dataInput->idVacation) && isset($dataInput->userid) ) {
    $responseDelete = $helper->restapi_vacation_delete($dataInput);
	header("HTTP/1.1 " . $responseDelete["status"]);
} else {
	header("HTTP/1.1 501 WRONG PARAMETERS");
	die;
}
