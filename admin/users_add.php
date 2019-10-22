<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="pragma" content="no-cache">

<link rel="stylesheet" href="css/default.css" type="text/css">

<script type="text/javascript">

var counterLines = 0;

function addRows() {
	var t = document.getElementById('tblusers');
	var n = document.createElement("tr");
	n.innerHTML = "<td><input type='text' size='20' name='uid[]'> </td>";
	n.innerHTML += "<td><input type='text' size='20' name='realname[]'> </td>";
	n.innerHTML += "<td><input type='text' size='20' name='passwd[]'> </td>";
	n.innerHTML += "<td><input type='text' size='20' name='email[]'> </td>";
	n.innerHTML += "<td><input type='text' size='20' name='ndate[]'> </td>";
	t.appendChild(n);
	counterLines++;
}

function delRows() {
	var t = document.getElementById('tblusers');
	t.removeChild( t.lastChild );
	counterLines--;
}

function addSuffix() {
	var t = document.getElementsByName('uid[]');
	var val = document.getElementById('suffix').value;
	var i;
	for (i=0; i < counterLines; i++ ) {
		t[i].value = t[i].value + val;
	}
}

function genPasswd() {
	var t = document.getElementsByName('passwd[]');
	var i;
	
	for (i=0; i < counterLines; i++ ) {
		t[i].value = MyRandom();
	}
}

function MyRandom() {
	var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
	var string_length = 6;
	var randomstring = '';
	for (var i=0; i<string_length; i++) {
		var rnum = Math.floor(Math.random() * chars.length);
		randomstring += chars.substring(rnum,rnum+1);
	}
	return randomstring;
}

</script>
</head>
<body>

<?php
error_reporting(E_ALL);
ini_set('display_errors','On');

require_once('../config.php');

require_once('admin_panel.php');

function TransformDateToUS($inputdate) {
	$startdatum = $inputdate;
	$startdatum = substr($startdatum, 0, 10);
	$startjahr = substr($startdatum, 6, 4);
	$startmonat = substr($startdatum, 3, 2);
	$starttag = substr($startdatum, 0, 2);

	$startdatum = $startjahr . "-" . $startmonat . "-" . $starttag;
	
	return $startdatum;

}

/* get data */
$check = 0;

// check if all requiered fields are available
if ( isset( $_POST["uid"]) && isset($_POST["realname"]) && isset($_POST["passwd"]) && isset($_POST["ndate"]) ) {
	echo "<p>is set</p>";
	$check = 1;
}

if ( $check == 1 && count ($_POST["uid"]) == count($_POST["realname"]) && count($_POST["uid"]) == count($_POST["passwd"]) && count($_POST["ndate"]) == count($_POST["uid"]) ) {
	$check = 1;
}

else {
	$check = 0;
}

if ( isset($_POST["uid"]) && count($_POST["uid"]) <= 0 ) {
	$check = 0;
}

$dbconn = new mysqli(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);

if ( $dbconn->connect_error ) {
	$check = 0;
	echo "<p>Warning! Database problem</p>";
}

// if check is ok, then perform inserts but only if the record doesn't exist	
if ( $check == 1 ) {
	$tuid = "";
	$trealname = "";
	$tpasswd = "";
	$temail = "";
	$tdate = "";
		
	if ( $stmt = $dbconn->prepare("INSERT IGNORE INTO " . CConfig::$db_tbl_prefix . "users (uname, email, reg_date, password, status, startdate, dname) VALUES (?, ?, NOW(), ?, 2, ?, ?)") ) {
	
		$stmt->bind_param("sssss", $tuid, $temail, $tpasswd, $tdate, $trealname);
		
		$countItems = count($_POST["uid"]);

		for ( $i = 0; $i < $countItems; $i++ ) {
			$tuid = $_POST["uid"][$i];
			$trealname = $_POST["realname"][$i];
			$tpasswd = $_POST["passwd"][$i];
			$temail = $_POST["email"][$i];
			$tdate = TransformDateToUS($_POST["ndate"][$i]);
			
			$stmt->execute();
		}
		$stmt->close();
	}
}

$dbconn->close();

?>

<form method="post" action="users_add.php">

<table id="tblusers">
<tr>
	<td> Benutzername </td>
	<td> Voller Name </td>
	<td> Passwort </td>
	<td> E-Mail Adresse </td>
	<td> Startdatum </td>
</tr>

</table>

<input type="submit">	
</form>

<input type="button" value="+" onClick="addRows();" > <input type="button" value="-" onClick="delRows();" >

<input type="text" size="15" id="suffix"> <input type="button" value="An Benutzernamen anhängen" onclick="addSuffix();"> <br>
<input type="button" value="Generiere Passwörter" onclick="genPasswd();"> <br>
</body>

</html>