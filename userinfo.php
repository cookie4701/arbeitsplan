<?PHP
	session_start();

iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("input_encoding", "UTF-8");

require_once("config.php");
require_once 'login.class.php';
require_once 'helper.class.php';
require_once 'database.class.php';

$log = new CLogin();
$help = new Helper();

global $dbserver;
global $dbuser;
global $dbpass;
global $dbname;

$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);

if ( ! isset($dbx )) {
	echo "Datenbankproblem";
	die;
}

$dbx->ExecuteSQL("SET NAMES 'utf8'");

$userid = $log->getIdUser();

$dbx->ExecuteSQL("USE $dbname");

  // Variablen auswerten und in die Datenbank eintragen
	$kurz[0] = $_GET["arbeitsbereich_kurz1"];
	$lang[0] = $_GET["arbeitsbereich_lang1"];
	$stunden[0] = $_GET["arbeitsbereich_stunden1"];
	  
	$kurz[1] = $_GET["arbeitsbereich_kurz2"];
	$lang[1] = $_GET["arbeitsbereich_lang2"];
	$stunden[1] = $_GET["arbeitsbereich_stunden2"];
	  
	$kurz[2] = $_GET["arbeitsbereich_kurz3"];
	$lang[2] = $_GET["arbeitsbereich_lang3"];
	$stunden[2] = $_GET["arbeitsbereich_stunden3"];
	  
	$kurz[3] = $_GET["arbeitsbereich_kurz4"];
	$lang[3] = $_GET["arbeitsbereich_lang4"];
	$stunden[3] = $_GET["arbeitsbereich_stunden4"];
	  
	$kurz[4] = $_GET["arbeitsbereich_kurz5"];
	$lang[4] = $_GET["arbeitsbereich_lang5"];
	$stunden[4] = $_GET["arbeitsbereich_stunden5"];
	  
	$kurz[5] = $_GET["arbeitsbereich_kurz6"];
	$lang[5] = $_GET["arbeitsbereich_lang6"];
	$stunden[5] = $_GET["arbeitsbereich_stunden6"];
	  
	$kurz[6] = $_GET["arbeitsbereich_kurz7"];
	$lang[6] = $_GET["arbeitsbereich_lang7"];
	$stunden[6] = $_GET["arbeitsbereich_stunden7"];
	  
	$kurz[7] = $_GET["arbeitsbereich_kurz8"];
	$lang[7] = $_GET["arbeitsbereich_lang8"];
	$stunden[7] = $_GET["arbeitsbereich_stunden8"];
  
	$kurz[8] = $_GET["arbeitsbereich_kurz9"];
	$lang[8] = $_GET["arbeitsbereich_lang9"];
	$stunden[8] = $_GET["arbeitsbereich_stunden9"];
	
	$kurz[9] = $_GET["arbeitsbereich_kurz10"];
	$lang[9] = $_GET["arbeitsbereich_lang10"];
	$stunden[9] = $_GET["arbeitsbereich_stunden10"];
	
	$kurz[10] = $_GET["arbeitsbereich_kurz11"];
	$lang[10] = $_GET["arbeitsbereich_lang11"];
	$stunden[10] = $_GET["arbeitsbereich_stunden11"];
	
	$kurz[11] = $_GET["arbeitsbereich_kurz12"];
	$lang[11] = $_GET["arbeitsbereich_lang12"];
	$stunden[11] = $_GET["arbeitsbereich_stunden12"];
	
	$kurz[12] = $_GET["arbeitsbereich_kurz13"];
	$lang[12] = $_GET["arbeitsbereich_lang13"];
	$stunden[12] = $_GET["arbeitsbereich_stunden13"];
	$kurz[13] = $_GET["arbeitsbereich_kurz14"];
	$lang[13] = $_GET["arbeitsbereich_lang14"];
	$stunden[13] = $_GET["arbeitsbereich_stunden14"];
	$kurz[14] = $_GET["arbeitsbereich_kurz15"];
	$lang[14] = $_GET["arbeitsbereich_lang15"];
	$stunden[14] = $_GET["arbeitsbereich_stunden15"];
	$kurz[15] = $_GET["arbeitsbereich_kurz16"];
	$lang[15] = $_GET["arbeitsbereich_lang16"];
	$stunden[15] = $_GET["arbeitsbereich_stunden16"];
	$kurz[16] = $_GET["arbeitsbereich_kurz17"];
	$lang[16] = $_GET["arbeitsbereich_lang17"];
	$stunden[16] = $_GET["arbeitsbereich_stunden17"];
	$kurz[17] = $_GET["arbeitsbereich_kurz18"];
	$lang[17] = $_GET["arbeitsbereich_lang18"];
	$stunden[17] = $_GET["arbeitsbereich_stunden18"];
	$kurz[18] = $_GET["arbeitsbereich_kurz19"];
	$lang[18] = $_GET["arbeitsbereich_lang19"];
	$stunden[18] = $_GET["arbeitsbereich_stunden19"];
	$kurz[19] = $_GET["arbeitsbereich_kurz20"];
	$lang[19] = $_GET["arbeitsbereich_lang20"];
	$stunden[19] = $_GET["arbeitsbereich_stunden20"];
	$kurz[20] = $_GET["arbeitsbereich_kurz21"];
	$lang[20] = $_GET["arbeitsbereich_lang21"];
	$stunden[20] = $_GET["arbeitsbereich_stunden21"];
	$kurz[21] = $_GET["arbeitsbereich_kurz22"];
	$lang[21] = $_GET["arbeitsbereich_lang22"];
	$stunden[21] = $_GET["arbeitsbereich_stunden22"];
	$kurz[22] = $_GET["arbeitsbereich_kurz23"];
	$lang[22] = $_GET["arbeitsbereich_lang23"];
	$stunden[22] = $_GET["arbeitsbereich_stunden23"];
	$kurz[23] = $_GET["arbeitsbereich_kurz24"];
	$lang[23] = $_GET["arbeitsbereich_lang24"];
	$stunden[23] = $_GET["arbeitsbereich_stunden24"];
	
  
$startdatum = $_GET["startdatum"];
$kmsatz = $_GET["kmsatz"];
$alteueberstunden = $_GET["alteueberstunden"];
$feiertage = $_GET["feiertage"];
$urlaubstage = $_GET["urlaubstage"];

$wtag[0] = str_replace( "," ,  ".",  $_GET["wochentag1"]); // 1
$wtag[1] = str_replace( "," ,  ".",  $_GET["wochentag2"]);
$wtag[2] = str_replace( "," ,  ".",  $_GET["wochentag3"]);
$wtag[3] = str_replace( "," ,  ".",  $_GET["wochentag4"]);
$wtag[4] = str_replace( "," ,  ".",  $_GET["wochentag5"]); // 5
$wtag[5] = str_replace( "," ,  ".",  $_GET["wochentag6"]);
$wtag[6] = str_replace( "," ,  ".",  $_GET["wochentag7"]);

// check if all workdays are already inserted into database
$k = -1;
$tsql = "SELECT id FROM " . $db_tbl_prefix . "workhours WHERE user=? AND workday=?";
$arrBoolUpdate = array(); // array if field needs to be updated (1) or inserted (0), multiple statements are not allowed with php mysqli
$stmt = $dbx->getDatabaseConnection()->stmt_init();
if ( $stmt->prepare($tsql) ) {
	$stmt->bind_param("id", $userid, $k);
	for ( $k = 1; $k <= 7; $k++ ) {
		if ( $stmt->execute() ) {
			if ($stmt->fetch() ) {
				$arrBoolUpdate[] = 1;
			}
			else {
				$arrBoolUpdate[] = 0;
			}
		}
	}
	$stmt->close();
}

for ($b = 0; $b < count($arrBoolUpdate); $b++ ) {
	if ( $arrBoolUpdate[$b] == 0 ) {
		// insert
		$c = $b + 1;
		$isql = "INSERT INTO " . $db_tbl_prefix . "workhours (user, hours, workday) VALUES ($userid," . $wtag[$b] . ", $c)";
		$dbx->ExecuteSQL($isql);
	}
	
	if ( $arrBoolUpdate[$b] == 1 ) {
		$usql = "UPDATE " . $db_tbl_prefix . "workhours SET hours=" . $wtag[$b] . " WHERE user=$userid AND workday=" . ($b + 1);
		$dbx->ExecuteSQL($usql);
	}
}
  
  for ( $i = 0; $i < 24; $i++ )
  {
  	if ( empty($stunden[$i]) ) $stunden[$i] = 0;
  	
  	$sqlSelect = "SELECT id FROM " . CConfig::$db_tbl_prefix . "workfields WHERE user=$userid AND rank=$i";
  	$look = $dbx->ExecuteSQL($sqlSelect);

  	if ( $look->num_rows > 0 ) {
  		$sqlUpdate = "UPDATE "
  		. $db_tbl_prefix
  		. "workfields SET description='" . $kurz[$i] . "', explanation='" . $lang[$i] . "', timecapital=" . $stunden[$i] . " WHERE user=$userid AND rank=$i";
  		
  		$dbx->getDatabaseConnection()->query($sqlUpdate);
  		
  	}
  	
  	else {
  		$sqlInsert = "INSERT INTO " . CConfig::$db_tbl_prefix . "workfields (rank, description, explanation, user, timecapital) VALUES ($i, '" . $kurz[$i] . "', '" . $lang[$i] . "', $userid, " . $stunden[$i] . ")";
  		$dbx->getDatabaseConnection()->query($sqlInsert);
  	}
 
	//$rr = mysqli_affected_rows();
	// echo mysql_error() . "<br>";
	
	//if (  $rr == 0 ) $dbx->getDatabaseConnection()->query($sqlInsert);
		
  }
  
if ( empty($alteueberstunden) ) $alteueberstunden = 0;
if ( empty($feiertage) ) $feiertage = 0;
if ( empty($urlaubstage) ) $urlaubstage = 0;
if ( empty($kmsatz) ) $kmsatz = 0;

$kmsatz = str_replace( ",", ".",  $kmsatz);

$sqlUpdateUserInfo = "UPDATE " . $db_tbl_prefix . "users SET " .
	"alteueberstunden=$alteueberstunden, " .
	"feiertage=$feiertage, " . 
	"urlaubstage=$urlaubstage, " .
	"kmsatz=$kmsatz " .
	" WHERE id=$userid";
	
$dbx->ExecuteSQL($sqlUpdateUserInfo);

//yecho "<br> $sqlUpdateUserInfo <br> " . mysql_error() . "<br>";
  
  // tpl_userinfo.php ausgeben plus Erfolgsmeldung
  
?>

<script type='text/javascript'>
	document.location.href = 'userinfogui.php';
</script>