<?php

/*
 * server.php
 * 
 * This file contains all code requiered to export a complete year of a user on
 * the server side.
 * 
 * (C) by PaKu 2017 <cookie4rent@gmail.com>
 */

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

phpinfo();
 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../config.php');
require_once('../database.class.php');


if ( PHP_VERSION_ID < 50600 ) {
	iconv_set_encoding("internal_encoding", "UTF-8");
	iconv_set_encoding("output_encoding", "UTF-8");
	iconv_set_encoding("input_encoding", "UTF-8");
	
}

else {
	ini_set('default_charset', 'UTF-8');
}

setlocale(LC_TIME, 'de_DE.utf8');


// open database
$dbx = new DatabaseConnection(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);
if ( ! isset($dbx) ) {
	// error, no database connection available
	echo "Error, no database connection available";
	die;
}
$dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
$dbx->getDatabaseConnection()->set_charset("utf8");

$app = 0;

// check what to do and execute appropiate function
if ( isset($_GET["app"]) ) {
	$app = $_GET["app"];
}

echo $app;

switch ($app) {
	case 0:
		break;
		
	case 1:
		// get user list
		echo getUserlist($dbx);
		break;
		
		
	default:
		break;
}

// close database connection when finished
$dbx->getDatabaseConnection()->close();

function getUserlist($db) {
	$stmt = $db->getDatabaseConnection->stmt_init();
	if ( ! isset($stmt) ) {
		echo "Error while statement init";
		die;
	}
	if ( ! $stmt->prepare("SELECT id, uname FROM " . CConfig::$db_tbl_prefix . "users") ) {
		echo "Error while statement preparation " . $db->getDatabaseConnection()->error;
		die;
	}
	
	$users = array();
	$index = 0;
	
	$stmt->bind_result($id, $uname);
	
	while ($stmt->fetch() ) {
		$users[] = array();
		$users[$index]['id'] = $id;
		$users[$index]['uname'] = $uname;
		$index++;
	}
	
	echo json_encode($users);
	
	$stmt->close();
	
}

?>
