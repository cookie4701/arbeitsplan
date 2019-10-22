<html>
<head>
<link rel="stylesheet" href="css/default.css" type="text/css">
</head>
<body>

<?php

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

require_once('../config.php');

require_once('admin_panel.php');
$fname = date("Y-m-d_His");
$fname = CConfig::$backupdir . "/" . $fname . "_backup_aplan.sql";

system("/usr/bin/mysqldump -u". CConfig::$dbuser . " -p" . CConfig::$dbpass . " -h " . CConfig::$dbhost . " " . CConfig::$dbname . " > ".dirname(__FILE__)."/$fname", $fp);
if ($fp==0) echo "Daten exportiert"; else echo "Es ist ein Fehler aufgetreten";
?> 

</body>

</html>