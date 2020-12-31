<?php

function myTerminate() {
	header("HTTP/1.1 404 NOT FOUND");
	die;
}

require_once('../../config.php');

use ReallySimpleJWT\Token;
// required headers

require("../cors.php");

cors();

require("../jwt.php");

$jwtData = getJwtData();

$data = file_get_contents("php://input");

$arr = json_decode($data);

$dbconn = new mysqli(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);


if (! isset($arr->newpassword) ) myTerminate();

$npw = $arr->newpassword;

$id = $jwtData["id"];
$stmt = $dbconn->prepare("UPDATE " . CConfig::$db_tbl_prefix . "users SET password = ? WHERE id= ?");
if ( ! $stmt->bind_param("si", $npw, $id) ) myTerminate("$npw -- $id");
if ( ! $stmt->execute() ) myTerminate();

if ( $stmt->affected_rows == 1 ) {
	header("HTTP/1.1 200 OK");
	header("Access-Control-Allow-Origin: *");
	header("Content-Type: application/json; charset=UTF-8");
}

else {
	myTerminate();
}

$stmt->close();

   
