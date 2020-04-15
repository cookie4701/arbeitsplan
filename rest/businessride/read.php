<?php


// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../cors.php");

cors();

require ("../jwt.php");

$data = getJwtData();

$mydata = array();
$mydata['username'] = $data['username'];

// get user startdate
require_once('../../helper.class.php');
$helper = new Helper();

$inputData = file_get_contents("php://input");
$inputData = json_decode($inputData);

if (! isset($inputData->sdate) || !isset($inputData->edate) ) {
	header("HTTP/1.1 501 NOT ENOUGH PARAMETERS");
	die;
}

$params = array();
$params['userid'] = $data['id'];
$params['sdate'] = $inputData->sdate;
$params['edate'] = $inputData->edate;

$response = $helper->restapi_get_rides($params);

if ( !isset($response) || !isset($response['status']) || $response['status'] != 200 ) {
	header("HTTP/1.1 502 APP ERROR");
	if (isset($response) ) {
		echo json_encode($resonse);
	}
	die;
} 

echo json_encode($response);
