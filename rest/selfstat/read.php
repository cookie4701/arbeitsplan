<?php


// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../cors.php");

cors();

require ("../jwt.php");

$data = getJwtData();

if (! isset($data) || ! isset($data['id']) ) {
	header("HTTP/1.1 401 NOT AUTHORIZED");
	die;
}

$mydata = array();
$mydata['username'] = $data['username'];
$mydata['userid'] = $data['id'];
$mydata['startdate']       = '2010-01-01';
$mydata['enddate'] = '2010-12-01';

require_once('../../helper.class.php');
$helper = new Helper();
$dataInput = file_get_contents("php://input");
$dataInput = json_decode($dataInput);

if (! isset($dataInput) ) {
	header("HTTP/1.1 500 NO DATA PROVIDED");
	die;
}

if (! isset($dataInput->sdate) || ! isset($dataInput->edate) ) {
	header("HTTP/1.1 501 NOT ENOUGH DATA PROVIDED");
	die;
}

$responseArray = $helper->restapi_selfstat_workareas($mydata['userid'], $dataInput->sdate, $dataInput->edate);

if (! isset($responseArray) ) {
	header("HTTP/1.1 502 GENERAL ERROR IS READING ON MY DISK");
	die;
}

echo json_encode($responseArray);
