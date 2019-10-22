<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="pragma" content="no-cache">

<link rel="stylesheet" href="css/default.css" type="text/css">
<body>

<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once('../config.php');

require_once('admin_panel.php');

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

$dbconn = new mysqli(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);

if ( $dbconn->connect_error ) {
	$check = 0;
	echo "<p>Warning! Database problem</p>";
}

// calc nbr of pages
$nbrpages = 0;
if ( $ss = $dbconn->prepare("SELECT count(id) FROM " . CConfig::$db_tbl_prefix . "users") ) {
	$ss->execute();
	$ss->bind_result($nbrrecords);
	$ss->fetch();
	$ss->close();
}

$nbrpages = (int) ($nbrrecords / CConfig::$displayUsers);

if ( ($nbrpages * CConfig::$displayUsers) < $nbrrecords ) {
	$nbrpages += 1;
}

if ( isset($_GET["page"] )) {
	$p = $_GET["page"];
}
else {
	$p = 1;
}

$startrecord = ($p - 1) * CConfig::$displayUsers;

if ($startrecord != 0 ) {
	$startrecord++;
}
$scriptname = basename($_SERVER['PHP_SELF']);

for ($i = 1; $i <= $nbrpages; $i++ ) {
	echo "<a href=\"$scriptname?page=$i\"> $i </a>";
}
?>

<table id="tbldbusers">
<tr>
	<td> <p>User ID </p></td>
	<td> <p>Passwort </p></td>
	<td> <p>Anzeigename</p> </td>
	<td> <p>E-Mail Adresse </p></td>
	<td> <p>Arbeitsplan gültig ab </p></td>
	<td> <p>Watchlist </p></td>
	<td> </td>
</tr>

<?php




if ( $stmt = $dbconn->prepare("SELECT id, uname, email, reg_date, password, alteueberstunden, feiertage, urlaubstage, kmsatz, startdate, dname FROM " . CConfig::$db_tbl_prefix . "users LIMIT ?,?") ) {
	$stmt->bind_param("ii", $startrecord, CConfig::$displayUsers);
	$stmt->execute();
	$stmt->bind_result($id, $uname, $email, $regdate, $password, $oldhours, $closeddays, $hollidays, $km, $sdate, $displayname);
	
	while ( $stmt->fetch() ) {
		echo "<tr>";
		echo "<td class=\"tbldb\" ><p>$uname </p></td>";
		echo "<td class=\"tbldb\" ><p>$password </p></td>";
		echo "<td class=\"tbldb\" ><p>$displayname </p></td>";
		$bodytext = "Dies ist eine automatisch erstellte Nachricht! Bei Fragen wenden Sie sich bitte an Pascal Kuck %0A Benutzername: $uname %0A Passwort: $password %0A Adresse: http://app.jugendbuero.be/aplan2";
		$bodytext = str_replace(" ", "%20", $bodytext);
		echo "<td class=\"tbldb\" ><p><a href=\"mailto:$email?subject=Zugangsdaten%20f&uuml;r%20den%20Arbeitsplan&body=$bodytext\"> $email </a> </p></td>";
		echo "<td class=\"tbldb\" ><p>$sdate</p> </td>";
		
		$day = substr($sdate, 8, 2);
		$month = substr($sdate, 5,2);
		$year = substr($sdate, 0,4);
		
		echo "<td class=\"tbldb\" ><p><a href=\"watchlist.php?id=$id&sdate=$day-$month-$year&edate=31-12-$year\"> watch </a></p></td>";
		echo "<td class=\"tbldb\" ><p><a href=\"report_year_user_pdf.php?id=$id\" target=\"__BLANK\" ><img height=\"30px\" src=\"pdf.jpg\"> </a></p></td>";

		echo "<td class=\"tbldb\" ><form action=\"process_delete.php\" method=\"POST\"> <input type=\"hidden\" name=\"id\" value=\"$id\" > <input type=\"submit\" value=\"Delete user\"> </form> </td>";
		echo "</tr>";
	}
	
	$stmt->close();
}

$dbconn->close();
	
?>
</table>
<?php
for ($i = 1; $i <= $nbrpages; $i++ ) {
	echo "<a href=\"$scriptname?page=$i\"> $i </a>";
}
?>

</body>

</html>
