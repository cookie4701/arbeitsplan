<html>

<head>
	<title>Watchlist</title>
	<link rel="stylesheet" href="css/default.css" type="text/css">
	<meta http-equiv="cache-control" content="no-cache">
	<meta http-equiv="expires" content="0">
	<meta http-equiv="pragma" content="no-cache">
</head>

<body>

<?php
$maxcells = 20;

require_once('../config.php');

require_once('admin_panel.php');

// Tests
if ( !isset($_GET["id"]) ) {
	echo "<p>Keine ID &uuml;bergeben</p>";
	die;
}

if ( !isset($_GET["sdate"]) ) {
	echo "<p>Kein Startdatum &uuml;bergeben!</p>";
	die;
}

if ( !isset($_GET["edate"])) {
	echo "<p>Kein Enddatum &uuml;bergeben</p>";
	die;
}

$sdate = $_GET["sdate"];
$edate = $_GET["edate"];

if ( strlen($sdate) != 10 ) {
	echo "<p>Startdatum falsch!</p>";
	die;
}

if ( strlen($edate) != 10 ) {
	echo "<p>Enddatum falsch!</p>";
	die;
}

// Tests end

$timeStart = mktime(0,0, 0, substr($sdate, 3,2), substr($sdate,0,2), substr($sdate, 6,4));
$timeEnd = mktime(0,0,0, substr($edate, 3,2), substr($edate,0,2), substr($edate, 6,4));
$id = $_GET["id"];

$dbconn = new mysqli(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);

if ( $dbconn->connect_error ) {
	$check = 0;
	echo "<p>Warning! Database problem</p>";
	die;
}
$nbrCells = 0;
?>

<table class="watchtable">
<tr>
<?php

for ( $timeStart; $timeStart <= $timeEnd; $timeStart = mktime(0,0,0, date("m", $timeStart) , date("d", $timeStart)+1, date('Y', $timeStart) ) ) {
	$res = 0;
	$lookdate = $lookdate = date("Y-m-d H:i:s", $timeStart);
	
	$stmt = $dbconn->prepare("SELECT user_id FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND dateofday=?");
	$stmt->bind_param("is", $id, $lookdate);
	$stmt->execute();
	$stmt->bind_result($res);
	$stmt->fetch();
	if ( $nbrCells >= $maxcells ) {
			echo "\n</tr>\n<tr>";
			$nbrCells = 0;
	}
	if ( $res != 0 ) {
		// print out work done that day
		echo "<td class=\"workdone\"> ". date("d.m", $timeStart). "</td>";
	}
	else {
		echo "<td class=\"nowork\"> ". date("d.m", $timeStart) . "</td>";
	}
	
	
	$nbrCells++;
	$stmt->close();
}

?>

</tr>
</table>

</body>

</html>
