<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../helper.class.php");

$helper = new Helper();
$userid = $helper->TransformUserToId($_COOKIE["username"]);

$schedules = $helper->restapi_schedule_read($userid);

if ( count($schedules) > 0 ) {
    http_response_code(200);
    echo json_encode($schedules);
} else {
    http_response_code(204);
    $schedules = array();
    $schedules['msg'] = "No data";
    echo json_encode($schedules);
} 



