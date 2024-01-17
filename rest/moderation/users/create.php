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
    echo "not authorized!";
} else {
	$createUser = $helper->restapi_user_create(json_encode($dataInput));
	echo json_encode($createUser) . "foo";
	if ( $createUser["status"] == 200 ) {
		header("HTTP/1.1 200 OK");
	} else {
		header("HTTP/1.1 500 APPLICATION ERROR");
	}
}
