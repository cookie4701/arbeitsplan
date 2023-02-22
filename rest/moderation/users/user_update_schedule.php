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

} else if (!isset($dataInput->userId) ) {
    header("HTTP/1.1 401 Unauthorized");

} else {
    $updateSchedule = $helper->restapi_schedule_update($dataInput->userId, json_encode($dataInput));
    if ( $updateSchedule == 'ok' ) {
	     header("HTTP/1.1 200 OK");
       $response["msg"] = "ok";
       $response["code"] = 200;

    } else {
	     header("HTTP/1.1 500 APPLICATION ERROR");
       $response["msg"] = $updateSchedule;
       $response["code"] = 500;
    }

    echo json_encode($response);
}
