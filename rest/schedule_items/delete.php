<?php

include_once("../../helper.class.php");

$helper = new Helper();

$userid = $helper->TransformUserToId($_COOKIE["username"]);
$data = file_get_contents("php://input");

$msg = $helper->restapi_scheduleitems_delete($userid, $data);

echo $msg;

