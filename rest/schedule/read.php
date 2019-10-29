<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../helper.class.php");

$helper = new Helper();
$userid = $helper->TransformUserToId($_COOKIE["username"]);

$schedules = $helper->restapi_schedule_read($userid);
http_response_code(200);

echo json_encode($schedules);



