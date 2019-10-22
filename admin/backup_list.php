<html>
<head>
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="expires" content="0">
<meta http-equiv="pragma" content="no-cache">

<link rel="stylesheet" href="css/default.css" type="text/css">
</head>
<body>

<?php

require_once('../config.php');

require_once('admin_panel.php');

$fdir = opendir(CConfig::$backupdir);

echo "<ul class=\"lstFiles\">";

while ( $tmpName = readdir($fdir) ) {
	if ( $tmpName != '.' && $tmpName != '..' ) {
		echo "<li><a href=\"$backupdir/$tmpName\" > $tmpName </a> <a href=\"backup_rename_file.php?f=$tmpName\" > <img src=\"images/edit.png\" height=\"20\" /> </a>"; 
	}
}

echo "</ul>";
?>

</body>

</html>