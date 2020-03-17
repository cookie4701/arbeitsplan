<?php

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


require("../cors.php");
cors();

require("../jwt.php");
$data = getJwtData();
$userid = $data['id'];

if (intval($userid, 10) <= 0 ) {
    echo "User id not ok";
    die;
}

include_once("../../helper.class.php");

$helper = new Helper();

$data = file_get_contents("php://input");

$msg = $helper->restapi_scheduleitems_delete($userid, $data);

echo $msg;

