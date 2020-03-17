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

$dataInput = file_get_contents("php://input");

$msg = $helper->restapi_hollidays_create($userid, $dataInput);

echo $msg;

