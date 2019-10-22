<?php

session_start();

iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("input_encoding", "UTF-8");

require_once("config.php");
require_once 'login.class.php';
require_once 'helper.class.php';
require_once 'database.class.php';

$log = new CLogin();
$help = new Helper();

header ('Content-Type:text/html; charset=UTF-8');

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="report.css">
<title>Gefahrene Kilometer</title>
</head>

<body>

<?php
	
   
$userid = $log->getIdUser();

global $dbserver;
global $dbuser;
global $dbpass;
global $dbname;
	
$dbx = new DatabaseConnection(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);

$dname = $log->getDisplayName();

$dbx->ExecuteSQL("SET NAMES 'utf8'");

$ssql = "SELECT year(startdate) FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
$res;
$year = "0000";



if ($res = $dbx->ExecuteSql($ssql) ) {
	if ( $ff = $res->fetch_row() ) {
		$year = $ff[0];
	}
}

else {
	echo "There was a problem: $ssql";
	die;
}

echo "<h1>Auflistung der Fahrten f&uuml;r das Jugendb&uuml;ro der DG</h1>";
echo "<h2>$dname</h2>";

echo "<table class=\"finance\" >";
echo "<tr>";
echo "<td>Tag</td><td>Anzahl Kilometer</td><td>Fahrt ab</td><td>Fahrt bis</td></tr>";

$ssql = "SELECT day, km, fromwhere, towhere FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=$userid ORDER by day, id";
if ($res = $dbx->ExecuteSql($ssql) ) {
	while ( $ff = $res->fetch_row() ) {
		echo "<tr>";
		echo "<td>" . $help->TransformDateToBelgium($ff[0]) . "</td>";
		//echo "<td>" . $ff[0] . "</td>";
		echo "<td>" . $ff[1] . "</td>";
		echo "<td>" . $ff[2] . "</td>";
		echo "<td>" . $ff[3] . "</td>";
		echo "</tr>";
	}
}

echo "</table>";

?>

</body>
</html>

