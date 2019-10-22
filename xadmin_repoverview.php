<?php 
	include_once 'helper.class.php';
	
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	header ('Content-Type:text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>

</head>

<body>


<?php 
	$help = new Helper();
	
	// init database
	$help->getDatabaseConnection()->getDatabaseConnection()->query("CREATE TABLE IF NOT EXISTS " . CConfig::$db_tbl_prefix . "userwatchlist  (id INT NOT NULL AUTO_INCREMENT, iduserwatch INT NOT NULL, PRIMARY KEY (id) ) ENGINE = MYISAM"); 

	
?>

<table>
<?php

$stmt = $help->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
if ( $stmt->prepare("SELECT B.dname, A.iduserwatch FROM " . CConfig::$db_tbl_prefix  . "userwatchlist AS A LEFT JOIN aplan_users AS B ON A.iduserwatch = B.id GROUP BY A.iduserwatch") ) {
	$stmt->execute();
	$stmt->bind_result($tmpDisplayname, $tmpId);
	while ( $stmt->fetch() ) {
		echo "<tr><td>$tmpDisplayname</td>";
		for ($i = 1; $i < 54; $i++ ) echo "<td><a href=\"admin_report.php?uid=$tmpId&week=$i\">$i</a></td>";
		echo "</tr>";
	}
	//echo "<tr><td>"
	$stmt->close();
}

?>

</table>
</body>

</html>