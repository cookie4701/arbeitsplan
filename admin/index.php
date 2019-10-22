<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="pragma" content="no-cache">

<link rel="stylesheet" href="css/default.css" type="text/css">
<title>Neuen Benutzer anlegen</title>
</head>

<body>

<?php

require("../config.php");

require_once('admin_panel.php');

if ( isset( $_POST["username"] ) ) {
	$username = $_POST["username"];
	$displayname = $_POST["displayname"];
	$pw = $_POST["pw"];
	$startdate = TransformDateToUS($_POST["startdate"]);
	$email = $_POST["email"];
	
	$isql = "INSERT INTO " . CConfig::$db_tbl_prefix . "users (uname, email, reg_date, password, status, startdate, dname) VALUES ('$username', '$email', NOW(), '$pw', 2, '$startdate', '$displayname')";
	//echo "<p>$isql</p>";
	
	global $dbserver;
	global $dbuser;
	global $dbpass;
	global $dbname;
		
	$dbx = new DatabaseConnection(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);
	if ($dbx->ExecuteSql($isql) ) {
		echo "<p>Datensatz erfolgreich eingef&uuml;gt</p>";
	}
	
 	
}



?>
<h1>Neuen Benutzeranlegen
<form action="index.php" method="POST">

<table>
<tr>
	<td>
		Benutzername
	</td>
	<td>
		<input type="text" name="username">
	</td>
</tr>
<tr>
	<td>
		Anzeigename
	</td>
	<td>
		<input type="text" name="displayname">
	</td>
</tr>
<tr>
	<td>
		Passwort
	</td>
	<td>
		<input type="password" name="pw">
	</td>
</tr>
<tr>
	<td>
		E-Mail
	</td>
	<td>
		<input type="text" name="email">
	</td>
</tr>
<tr>
	<td>
		Startdatum (Form: TT.MM.JJJJ)
	</td>
	<td>
		<input type="text" size=10 name="startdate">
	</td>
</tr>
</table>

<input type="submit" value="Speichern"> <input type="reset" value="Reset">

</form>

</body>
</html>
