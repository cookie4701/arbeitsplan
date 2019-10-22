<html>

<head>

<script type="text/javascript">
<!--
function delayer(){
    window.location = "index.php"
}
</script>

<title>Statusinfo</title>

</head>

<body>
<?php
require_once('config.php');
require_once 'login.class.php';

$log = new CLogin();

$dbconn = new mysqli(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);

if ( $dbonn->connect_error) {
	echo "<p>Keine Verbindung zur Datenbank m&ouml;glich!</p>";
	die;
}

if ( !isset( $_POST["password"] ) )
{
	echo "<p>Kein Passwort wurde &uuml;bergeben</p>";
	die;
}

$npw = $_POST["password"];

$id = $log->getIdUser();

$stmt = $dbconn->prepare("UPDATE " . CConfig::$db_tbl_prefix . "users SET password = ? WHERE id= ?");
$stmt->bind_param("si", $npw, $id);
$stmt->execute();

if ( $stmt->affected_rows == 1 ) {
	echo "<p>Das &Auml;ndern des Passworts war erfolgreich! Sie werden in K&uuml;rze automatisch weitergeleitet. Sollte dies bei Ihnen nicht funktionieren, so klicken Sie bitte <a href=\"index.php\"> hier</a>. </p>";
	echo "<meta http-equiv=\"refresh\" content=\"5; URL=index.php\">"; 
}

else {
	echo "<p>Fehler beim Verarbeiten des neuen Passworts! Bitte bedenken Sie das Sonderzeichen wie ' oder \" nicht benutzt werden d&uuml;rfen!</p>";
}

$stmt->close();

   
?>

</body>
</html>