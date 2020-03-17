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

$sdate = "";
if ( isset($_GET["startdate"])) {
    $sdate = $_GET["startdate"];
} else {
    echo "error";
    die;
}

$returnData = $helper->restapi_helper_getInfoDay($userid, $sdate);

echo json_encode($returnData);
