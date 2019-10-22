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
header('Content-type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../helper.class.php');

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
$helper = new Helper();

if ( ! isset($helper) ) {
	// error, no database connection available
	echo "Error, no database connection available";
	die;
}
$app = 0;

// check what to do and execute appropiate function
if ( isset($_GET["app"]) ) {
	$app = $_GET["app"];
}

switch ($app) {
	case 0:
		break;
		
	case 1:
		// get user list
		getUserlist($helper);
		break;
	case 2: // extract startdate
		if ( ! isset($_POST["uid"]) ) {
			echo "No uid";
			die;
		}
		$uid = $_POST["uid"];
		getUserInfo($helper, $uid);
		break;
	case 3: // extract workday
		if ( ! isset($_POST["uid"]) ) {
			echo "No uid";
			die;
		}
		
		if ( ! isset($_POST["datay"])) {
			echo "No year";
			die;
		}
		
		if ( ! isset($_POST["datam"]) ) {
			echo "No month";
			die;
		}
		
		if ( ! isset($_POST["datad"])) {
			echo "No day";
			die;
		}
		
		$user = $_POST["uid"];
		$year = $_POST["datay"];
		$month = $_POST["datam"];
		$day = $_POST["datad"];
		
		getWorkday($helper, $user, $year, $month, $day);
		break;
		
	default:
		break;
}

// close database connection when finished
// by class destructor

function getUserlist($h) {
	$stmt = $h->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
	if ( ! isset($stmt) ) {
		echo "Error while statement init";
		die;
	}
	if ( ! $stmt->prepare("SELECT id, uname FROM " . CConfig::$db_tbl_prefix . "users") ) {
		echo "Error while statement preparation " . $h->getDatabaseConnection()->getDatabaseConnection()->error;
		die;
	}
	
	if ( ! $stmt->execute() ) {
		echo "Error while statement execution " . $h->getDatabaseConnection()->getDatabaseConnection()->error;
		die;
	}
	$users = array();
	$index = 0;
	
	$stmt->bind_result($id, $uname);
	$users['id'][] = array();
	$users['uname'][] = array();
	
	while ($stmt->fetch() ) {
		
		$users['id'][$index] = $id;
		$users['uname'][$index] = $uname;
		$index++;
	}
	
	echo json_encode($users, JSON_UNESCAPED_UNICODE);
	
	$stmt->close();
	
}

function getUserInfo($h, $user) {
	// get general user info
	$info = $h->GetUserInfo($user);

	$retArray = array();
	
	if ( isset($info[7]) ) $retArray['oldovertime'] = $info[7];
	if ( isset($info[8]) ) $retArray['vacationdays'] = $info[8];
	if ( isset($info[9]) ) $retArray['hollidays'] = $info[9];
	if ( isset($info[11]) ) $retArray['startdate'] = $info[11];
	if ( isset($info[12]) ) $retArray['displayname'] = $info[12];
	
	// get user work days
	$workdays = $h->GetUserWorkdays($user);
	
	$retArray['normalworkdays'] = array();
	
	for ($i = 0; $i < 7; $i++ ) {
		if ( isset( $workdays[$i][1] ) ) $retArray['normalworkdays'][$i] = $workdays[$i][1];
		else $retArray['normalworkdays'][$i] = 0;
	}
	
	// get user workareas
	$retArray['workarea_long'] = array();
	$retArray['workarea_short'] = array();
	$retArray['workarea_limits'] = array();
	
	for ($i = 0; $i < 24; $i++ ) {
		$retArray['workarea_long'][] = "";
		$retArray['workarea_short'][] = "";
		$retArray['workarea_limits'][] = 0;
	}
	
	$wa = $h->GetWorkfieldsAll($user);
	
	
	for ($i = 0; $i < count($wa); $i++ ) {
		$rank = $wa[$i][0];
		if ( isset($retArray['workarea_long'][$rank] ) ) $retArray['workarea_long'][$rank] = $wa[$i][1];
		if ( isset($retArray['workarea_short'][$rank] ) ) $retArray['workarea_short'][$rank] = $wa[$i][2];
		if ( isset($retArray['workarea_limits'][$rank] ) ) $retArray['workarea_limits'][$rank] = $wa[$i][4];
	}
	
	echo json_encode($retArray);
	
}

function getWorkday($h, $user, $year, $month, $day) {
	date_default_timezone_set("Europe/Berlin");
	
	$temp = mktime(0,0,0, $month, $day, $year);
	$wd = date("w", $temp);
	if ($wd == 0) $wd = 7;

	$workday = $h->getWorkDay($user, $day, $month, $year, $wd);
	
	echo json_encode($workday);
}

?>
