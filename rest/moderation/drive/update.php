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
    echo "not ok";

} else if (!isset($dataInput->id) ) {
    header("HTTP/1.1 401 Unauthorized");
    echo "not ok";


} else {
    $updateDriveRecompensation = $helper->restapi_driverecompensation_update(json_encode($dataInput));
    if ( $updateDriveRecompensation['message'] == 'ok' ) {
		header("HTTP/1.1 200 OK");
		echo "ok";
    } else {
		header("HTTP/1.1 500 APPLICATION ERROR");
		echo "not ok 500";
    }
}

echo $updateDriveRecompensation;
