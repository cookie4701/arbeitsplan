<?php
iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("input_encoding", "UTF-8");

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once('login.class.php');
include_once('helper.class.php');
include_once 'database.class.php';


$log = new CLogin();
$helper = new Helper();

header ('Content-Type:text/html; charset=UTF-8');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>


<head>

<title>Arbeitsplan - Kilometerabrechnung</title>

<link href="default.css" rel="stylesheet" type="text/css">

<body>
<div id="kmbox">
<?php 

 
$userid = $log->getIdUser();
$startweek = $_POST["datefrom"];
$endweek = $_POST["dateto"];

global $dbserver;
global $dbuser;
global $dbpass;
global $dbname;

$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);

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

// KM Money
$ssql = "SELECT kmsatz FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
$res = $dbx->ExecuteSql($ssql);
$kmsatz = 0.0;
$totalmoney = 0.0;
$kms = 0;

if ( $ff = $res->fetch_row() ) {
	$kmsatz = $ff[0];
}


$sd = $helper->CalendarWeekStartDate($startweek, $year);
$ed = $helper->CalendarWeekStartDate($endweek, $year);

$ssql = "SELECT km FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=$userid AND day >= '$sd' AND day < '$ed' ORDER BY day, id";
if ( $res = $dbx->ExecuteSql($ssql) ) {
	while ( $ff = $res->fetch_row() ) {
		$daymoney = $ff[0];
		$kms += $ff[0];
		$daymoney *= $kmsatz;
		$totalmoney += $daymoney;
	}
}

$dname = $log->getDisplayName();

echo "<h1> Gefahrene Kilometer w&auml;hrend der Arbeit f&uuml;r das Jugendb&uuml;ro </h1>";
echo "<p>Mitarbeiter(in): $dname </p>";
echo "<p>Von Woche $startweek bis Woche $endweek </p>";
echo "<p>Anzahl Kilometer: $kms </p>";
$kmsatz = number_format($kmsatz, 4, ",", ".");
echo "<p>Kilometersatz: &euro; $kmsatz </p>";
$totalmoney = number_format($totalmoney, 2, ",", ".");
echo "<p>Berechtigte Entschädigung: &euro; $totalmoney</p>";

?>



<form method="post" action="">
<?php  //echo $helper->generateKmInvoiceTable($log->getIdUser()); ?>

<!--   Offener Betrag: <input type="text" name="money"> <input type="submit" value="!"> -->

</form>
</div>
</body>

</html>
