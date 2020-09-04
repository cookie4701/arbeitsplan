<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


require("../cors.php");
cors();

require("../jwt.php");
$data = getJwtData();
$userid = $data['id'];


include_once("../../helper.class.php");

$helper = new Helper();
$list = $helper->restapi_vacation_read($userid);

if ( $list["status"] == 200 ) {
	header("HTTP/1.1 200");
	echo json_encode($list["data"]);
} else {
	header("HTTP/1.1 500");
}

