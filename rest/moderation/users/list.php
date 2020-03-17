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
$code = "";

if ( !isModerator($userid) ) {
    header("HTTP/1.1 401 Unauthorized");
} else if (!isset($_GET["orgacode"]) ) {
    header("HTTP/1.1 401 Unauthorized");
} else {
    $code = $_GET["orgacode"];
    $listUsers = moderatesUsers($userid, $code);
    echo json_encode($listUsers);
}