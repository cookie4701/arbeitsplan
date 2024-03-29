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
//$dataInput = json_decode($dataInput);

if ( !isModerator($userid) ) {
    header("HTTP/1.1 401 Unauthorized");

} else if (!isset($_GET["id"]) ) {
    header("HTTP/1.1 401 Unauthorized");

} else {

    $singleuserid = $_GET["id"];
    $responseArray = $helper->restapi_monitor_get_userinfo($singleuserid);

    if ( ! isset($responseArray["status"]) || strcmp($responseArray["status"], "found") != 0 ) {
      header("HTTP/1.1 404 NOT FOUND");
      echo json_encode($responseArray);
    } else {
      header('Content-Type: application/json');
      echo json_encode($responseArray);
    }

}
