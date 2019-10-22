<html>
<head>

<title>Neuen Benutzer anlegen</title>
</head>

<body>

<?php

require("../config.php");

if ( isset( $_POST["username"] ) ) {
	$username = $_POST["username"];
	$displayname = $_POST["displayname"];
	$pw = $_POST["pw"];
	$startdate = TransformDateToUS($_POST["startdate"]);
	$email = $_POST["email"];
	
	$isql = "INSERT INTO aplan_users (uname, email, reg_date, password, status, startdate, dname) VALUES ('$username', '$email', NOW(), '$pw', 2, '$startdate', '$displayname')";
	//echo "<p>$isql</p>";
	
	global $dbserver;
	global $dbuser;
	global $dbpass;
	global $dbname;
		
	$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
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
