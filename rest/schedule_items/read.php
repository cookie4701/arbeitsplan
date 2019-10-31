<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../../helper.class.php");

$helper = new Helper();
$userid = $helper->TransformUserToId($_COOKIE["username"]);
$data = file_get_contents("php://input");
$scheduleitems = $helper->restapi_scheduleitems_read($userid, $data);
http_response_code(200);

echo json_encode($scheduleitems);



