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

$msg = $helper->restapi_workareas_update_explanation($userid, $dataInput);

$r = array();

$r['code'] = 200;
$r['message'] = "OK";

if ($msg == "ok") {
    header('HTTP/1.1 200 OK');
        
} else {
    header('HTTP/1.1 500 Internal Server Error');
    $r['code'] = 500;
    $r['message'] = "NOT OK";
}

echo json_encode($r);



