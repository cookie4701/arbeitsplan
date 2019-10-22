<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<?php
 
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'login.class.php';
include_once 'helper.class.php';
//session_start();

iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("input_encoding", "UTF-8");

setlocale(LC_TIME, 'de_DE.utf8');

	
//header ('Content-Type:text/html; charset=UTF-8');

?>


<html>
<head>
<meta http-equiv="Cache-Control" content="no-store" />
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">

</head>

<body>
<?php

$mylog = new CLogin();
$myhelp = new Helper();


if ( !isset($_POST['workrank']) ) {
	echo "need workfield";
	die;
}
	
$rank = $_POST['workrank'];

//echo $rank;

$ret = $myhelp->getWorkDoneYear($mylog->getIdUser(), $rank);

//$ret = 0;

if (!isset( $ret['msgcode'] ) ) {
	echo "<p>No message code provided</p>";
	die;
}

if ( $ret['msgcode'] != 0 ) {
	echo "Errorcode: " . $ret['msgcode'] . " / message: " . $ret['msg'] . "</body></html>";
	die;
}

$nbritems = count($ret['data']);
$index = 0;

echo "<table>";
echo "<tr><td>Datum</td> <td>Zeit</td> <td>Beschreibung</td></tr>";

for ($index = 0; $index < $nbritems; $index++ ) {
	if ( 0 != strcmp($ret['data'][$index]['mtime'], "00:00:00") ) {
		$tempdate = substr($ret['data'][$index]['mdate'],0, 10);
		$tempdate = $myhelp->TransformDateToBelgium($tempdate);
		echo "<tr>";
		echo "<td>" . $tempdate . "</td>";
		echo "<td>" . substr($ret['data'][$index]['mtime'], 0,5) . "</td>";
		echo "<td>" . $ret['data'][$index]['mdescription'] . "</td>";
		echo "</tr>";
	}
}

echo "</table>";
	
?>

</body>

</html>