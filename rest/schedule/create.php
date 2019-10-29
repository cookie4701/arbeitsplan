<?php

include_once("../../helper.class.php");

$helper = new Helper();

$userid = $helper->TransformUserToId($_COOKIE["username"]);
$data = file_get_contents("php://input");

$msg = $helper->restapi_schedule_create($userid, $data);

echo $msg;

