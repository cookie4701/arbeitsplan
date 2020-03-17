<?php


// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require("../cors.php");

cors();

require ("../jwt.php");

$data = getJwtData();

$mydata = array();
$mydata['username'] = $data['username'];
$mydata['userid'] = $data['id'];
$mydata['startdate']       = '2010-01-01';
$mydata['overhoursbefore'] = 0;

// get user startdate
require_once('../../helper.class.php');
$helper = new Helper();
$sql = "SELECT startdate FROM aplan_users WHERE id=?";
$db = $helper->getDatabaseConnection()->getDatabaseConnection();

$stmt = $db->stmt_init();

if (! $stmt->prepare($sql)) {
    echo "Prepare failed";
    die;
}

if (! $stmt->bind_param("i", $mydata['userid'] )) {
    echo "Bind failed";
    die;
}

if ( !$stmt->execute() ) {
    echo "Execute empty";
    die;
}

$stmt->bind_result($mydata['startdate']);
$stmt->fetch();
$stmt->close();

echo json_encode($mydata);
