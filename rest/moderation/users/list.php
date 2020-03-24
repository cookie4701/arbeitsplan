<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../cors.php");
cors();

require("../../jwt.php");
$data = getJwtData();
$userid = $data['id'];

$returnPage = 1;
$returnItems = 10;

include_once("../../../helper.class.php");

$helper = new Helper();

require_once ("../../member.php");
$code = "";

if ( !isModerator($userid) ) {
    header("HTTP/1.1 401 Unauthorized");
} else if (!isset($_GET["orgacode"]) ) {
    header("HTTP/1.1 401 Unauthorized");
} else {
    $code = $_GET["orgacode"];

if (isset($_GET["page"]) ) {
	$returnPage = $_GET["page"];
}

if ( isset($_GET["nbritems"]) ) {
	$returnItems = $_GET["nbritems"];
}

    $listUsers = moderatesUsers($userid, $code, $returnPage, $returnItems);
	header('Content-Type: application/json;charset=utf-8');
    echo json_encode($listUsers);

	if ( json_last_error() != 0 ) {
		echo "JSON Error " . json_last_error() ;
	}
}
