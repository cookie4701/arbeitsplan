<html>
<head>
<link rel="stylesheet" href="css/default.css" type="text/css">
</head>
<body>

<?php

require_once('config.php');
require_once('admin_panel.php');

/* src (f) and dst (t) are known */
if ( isset( $_GET["t"]) && isset( $_GET["f"]) ) {
	$T = $_GET["t"];
	$F = $_GET["f"];
	rename("$backupdir/$F", "$backupdir/$T");
	
	echo "<a href=\"backup_list.php\"> Done! Return to list </a>"; 
}

/* only src (f) is set */
if ( isset( $_GET["f"]) && !isset( $_GET["t"] ) ) {
	$F = $_GET["f"];
	echo "<form action=\"backup_rename_file.php\" method=\"get\" >";
	echo "Quelldatei: <input name=\"f\" type=\"text\" size=\"30\" value=\"$F\"> <br>";
	echo "Zieldatei: <input name=\"t\" type=\"text\" size=\"30\" value=\"$F\"> <br>";
	echo "<input type=\"submit\">";
	echo "</form>";
	echo "<a href=\"backup_list.php\"> Cancel </a>";
}

/* nothing is set -> problem */
if ( !isset($_GET["f"]) && !isset($_GET["t"]) ) {
	echo "There was a problem";
}
?>

</body>
</html>