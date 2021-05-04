<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../cors.php");
cors();

require("../../jwt.php");
$data = getJwtData();
$userid = $data['id'];

//include_once("../../../helper.class.php");

//$helper = new Helper();

require_once ("../../member.php");
$code = "";

if ( !isModerator($userid) ) {
    header("HTTP/1.1 401 Unauthorized");
} else if (!isset($_GET["orgacode"]) ) {
    header("HTTP/1.1 401 Unauthorized");
} else {
    $code = $_GET["orgacode"];

    $nbrUsers = moderatesUsersNumber($userid, $code);
	//header('Content-Type: application/json;charset=utf-8');
    echo json_encode($nbrUsers);

	if ( json_last_error() != 0 ) {
		echo "JSON Error " . json_last_error() ;
	}
}
