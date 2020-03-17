<?php
require("../cors.php");
cors();

require("../jwt.php");
$data = getJwtData();
$userid = $data['id'];

include_once("../../helper.class.php");

$helper = new Helper();

$dataInput = file_get_contents("php://input");

$msg = $helper->restapi_schedule_update($userid, $dataInput);

echo $msg;

