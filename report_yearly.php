<?php

session_start();

header ('Content-Type:text/html; charset=UTF-8');

iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("input_encoding", "UTF-8");

require_once("config.php");
require_once 'login.class.php';
require_once 'helper.class.php';
require_once 'database.class.php';

$log = new CLogin();
$help = new Helper();

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="report.css">
<title>Jahres&uuml;bersicht</title>
</head>

<body>

<?php
	
$userid = $log->getIdUser();


$dbx = new DatabaseConnection(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);

$dbx->ExecuteSQL("SET NAMES 'utf8'");

// Startdatum ermitteln
// todo: replace tablename with paramter
$ssql = "SELECT startdate FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
$res = $dbx->ExecuteSql($ssql);

if ( !$res ) {
	echo "Error: usertable not complete";
	die;
}

echo "<p> Jahresübersicht von <b>" . $log->getDisplayName() . "</b> </p>";

$ff = $res->fetch_row();
$startdate = $ff[0];
$startdate = substr($startdate, 0, 11);
$startdateparts = explode("-", $startdate);

$yearoverview[][] = array();

// get all work descriptions from the user
$ssql = "SELECT rank, description, timecapital FROM " . CConfig::$db_tbl_prefix . "workfields WHERE user=$userid ORDER BY rank";
$res = $dbx->ExecuteSql($ssql);

while ( $ff = $res->fetch_row() ) {
	if ( $ff[0] >= 0 && $ff[0] < $max_rank_workfields ) {
		$yearoverview[$ff[0]][0] = $ff[1];
		$yearoverview[$ff[0]][1] = $ff[2];
	}
}

// calculate percentages
(int) $totalhours = 0;
for ($a = 0; $a < $max_rank_workfields; $a++ ) {
	$totalhours = $totalhours + $yearoverview[$a][1] ;
}

for ($a = 0; $a <  $max_rank_workfields; $a++ ) {
	$nbr = (100 / $totalhours) * $yearoverview[$a][1];
	$yearoverview[$a][2] = number_format( $nbr, 2, ",", ".");
}

// get worked hours
$workedhours = 0;
for ($a = 0; $a < $max_rank_workfields; $a++ ) {
	$ssql = "SELECT SUM(TIME_TO_SEC(hours)) FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=$userid AND workfield_id=$a";
	$res = $dbx->ExecuteSql($ssql);
	
	if ( $ff = $res->fetch_row() ) {
		$yearoverview[$a][3] = $ff[0] / 3600;
		//$yearoverview[$a][3] = substr( $yearoverview[$a][3], 0, 5);
		$workedhours += $ff[0] / 3600;
	}
	
	else {
		$yearoverview[$a][3] = "00:00";
	}
}

// make percantage
for ($a = 0; $a < $max_rank_workfields; $a++ ) {
	//$tmp = (100 / $workedhours) * ConvertTimeToFloat($ff[0]);
	$tmp = $yearoverview[$a][3] * 100 / $workedhours;
	$yearoverview[$a][4] = number_format( $tmp, 2, ",", ".") . "%";
	$yearoverview[$a][3] = number_format($yearoverview[$a][3] , 2, ",", ".");
}
	

//$yearoverview[$a][4] .= " %";

// calculate percentage of work done compared to work to be done
for ( $a = 0; $a < $max_rank_workfields; $a++ ) {
	if ( $yearoverview[$a][1] != 0) {
		$yearoverview[$a][5] = $yearoverview[$a][3] / $yearoverview[$a][1];
		$yearoverview[$a][5] *= 100.0;
		$yearoverview[$a][5] = number_format( $yearoverview[$a][5], 2, ",", ".") . "%";
	}
	else {
		$yearoverview[$a][5] = "- %";
	}
}

// Print our table
echo "<table class=\"yearly\" >";

echo "<tr>";
echo "<td class=\"year\" >Arbeitsbereich</td>";
echo "<td class=\"year\" >Zu leistende Stunden</td>";
echo "<td class=\"year\" >Zu leistende Stunden<br>In Porzent</td>";
echo "<td class=\"year\" >Geleistete Stunden</td>";
echo "<td class=\"year\" >Geleistete Stunden<br>In Porzent</td>";
echo "<td class=\"year\" >Geleistete Stunden<br>In Porzent<br>Auf das Jahr bezogen</td>";
echo "</tr>";

$cols = 6;

for ( $a = 0; $a < $max_rank_workfields; $a++ ) {
	echo "<tr>";
	for ( $b = 0; $b < $cols; $b++ ) {
		echo "<td class=\"year\" >" . $yearoverview[$a][$b] . "</td>";
	}
	echo "</tr>";
}
// print total numbers
echo "<tr>";
echo "<td>Total:</td>";
echo "<td>$totalhours</td>";
echo "<td>100%</td>";
echo "<td>$workedhours</td>";
echo "<td>100%</td>";
echo "</tr>";

echo "</table>";

// Print out remaing hollidays


$urlaubstage_anrecht = 0;
$urlaubstage_genommen = 0;
$feiertage_anrecht = 0;
$feiertage_genommen = 0;
$urlaub_verbleibend = 0;
$feiertage_verbleibend = 0;

$ssql = "SELECT urlaubstage FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
$res = $dbx->ExecuteSql($ssql);
if ( $res ) {
	if ( $ff = $res->fetch_row() ) {
		$urlaubstage_anrecht = $ff[0];
	}
}

$ssql = "SELECT feiertage FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
$res = $dbx->ExecuteSql($ssql);
if ( $res ) {
	if ( $ff = $res->fetch_row() ) {
		$feiertage_anrecht = $ff[0];
	}
}

$ssql = "SELECT COUNT(user_id) FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=$userid AND holliday_id=2";

$res = $dbx->ExecuteSql($ssql);
if ( $res ) {
	if ( $ff = $res->fetch_row() ) {
		$urlaubstage_genommen = $ff[0];
		$urlaub_verbleibend = $urlaubstage_anrecht - $urlaubstage_genommen;
	}
}

$ssql = "SELECT COUNT(id) FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=$userid AND holliday_id=3";
$res = $dbx->ExecuteSql($ssql);
if ( $res ) {
	if ( $ff = $res->fetch_row() ) {
		$feiertage_genommen = $ff[0];
		$feiertage_verbleibend = $feiertage_anrecht - $feiertage_genommen;
	}
}

echo "<table class=\"yearly\">";
echo "<tr>";
echo "<td></td>";
echo "<td class=\"year\" >Anrecht</td>";
echo "<td class=\"year\" >Genommen</td>";
echo "<td class=\"year\" >Verbleibend</td>";
echo "</tr>";

echo "<tr>";
echo "<td class=\"year\" >Urlaub</td>";
echo "<td class=\"year\" >$urlaubstage_anrecht</td>";
echo "<td class=\"year\" >$urlaubstage_genommen</td>";
echo "<td class=\"year\" >$urlaub_verbleibend</td>";
echo "</tr>";

echo "<tr>";
echo "<td class=\"year\" >Feiertage</td>";
echo "<td class=\"year\" >$feiertage_anrecht</td>";
echo "<td class=\"year\" >$feiertage_genommen</td>";
echo "<td class=\"year\" >$feiertage_verbleibend</td>";
echo "</tr>";

echo "</table>";
?>

</body>
</html>
