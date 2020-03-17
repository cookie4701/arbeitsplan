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

$dataInput = json_decode(file_get_contents("php://input") );
$myData = array();
$myData['label'] = $dataInput->schedulename;
$myData['startdate'] = $dataInput->scheduleStart;
$myData['enddate'] = $dataInput->scheduleEnd;

$msg = $helper->restapi_schedule_create($userid, json_encode($myData));

echo $msg;

