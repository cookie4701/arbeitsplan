<?php

/**
 * json.php
 * 
 * This file is used to make a connection betweeen ajax calls and database queries.
 * 
 */

include_once 'login.class.php';
include_once 'helper.class.php';

$mylog = new CLogin();
$myhelp = new Helper();

$appcode = $_GET['appcode'];

if ( $appcode == 2) {
	
	if ( !isset( $_POST['week'] ) ) {
		echo "error xxx";
		die;
	}
	
	else {
		$week = $_POST['week'];
		$syear = $myhelp->getUserStartYear( $mylog->getIdUser() );
		$sdate = $myhelp->CalendarWeekStartDate($week, $syear);
		
		$day = substr($sdate, 8, 2);
		$month = substr($sdate,5,2);
		$year = substr($sdate,0,4);
		
		$ret = $myhelp->getWorkWeek($mylog->getIdUser(), $day, $month, $year);
		//print_r($ret);
		$ret = json_encode($ret);
		//echo json_last_error_msg();
		
		echo $ret;
		
	}
}

if ( $appcode == 3 ) {
	echo $mylog->getDisplayName();
}

if ( $appcode == 4) {
	
	if ( !isset($_POST['workyear']) ) {
		echo "need year";
		die;
	}
	
	if ( ! isset($_POST['workmonth'])) {
		echo "need month";
		die;
	}
	
	if ( ! isset($_POST['workday'])) {
		echo "need day";
		die;
	}
	
	$day = $_POST['workday'];
	$month = $_POST['workmonth'];
	$year = $_POST['workyear'];
	
	$ret = $myhelp->getTimes($mylog->getIdUser(), $day, $month, $year);
	$frm = json_encode($ret);
	//$to = json_encode($ret['to']);
	echo $frm;
	//echo $to;

}

if ( $appcode == 5 ) {
	$ret = $myhelp->GetWorkfieldsAll($mylog->getIdUser() );
	$ret = json_encode($ret);
	echo $ret;
}

if ( $appcode == 6 ) {
	
	if ( !isset($_POST['workyear']) ) {
		echo "need year";
		die;
	}
	
	if ( ! isset($_POST['workmonth'])) {
		echo "need month";
		die;
	}
	
	if ( ! isset($_POST['workday'])) {
		echo "need day";
		die;
	}
	
	$day = $_POST['workday'];
	$month = $_POST['workmonth'];
	$year = $_POST['workyear'];
	
	$ret = $myhelp->getWorkWeek($mylog->getIdUser(), $day, $month, $year);
}

if ( $appcode == 7 ) {
	if ( !isset($_POST['workrank']) ) {
		echo "need workfield";
		die;
	}
	
	$rank = $_POST['workrank'];
	$ret = $myhelp->getWorkDoneYear($mylog->getIdUser, $rank);
	$ret = json_encode($ret);
	echo $ret;
}

?>