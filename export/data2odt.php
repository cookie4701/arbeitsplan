<?php
/*
 * data2odt.php
 * 
 * This file contains all code requiered to put exported data into an ODT.
 * 
 * (C) by PaKu 2017 <cookie4rent@gmail.com>
 */

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../helper.class.php');


if ( PHP_VERSION_ID < 50600 ) {
	iconv_set_encoding("internal_encoding", "UTF-8");
	iconv_set_encoding("output_encoding", "UTF-8");
	iconv_set_encoding("input_encoding", "UTF-8");
	
}

else {
	ini_set('default_charset', 'UTF-8');
}

setlocale(LC_TIME, 'de_DE.utf8');


include '../phpodt/phpodt.php';

$odt = ODT::getInstance();

if ( ! isset($_POST["userdata"]) ) {
	$p_nodata = new Paragraph();
	$p_nodata->addText('Es gab keine Daten zum verarbeiten');
	$odt->output("nodata.odt");
	echo "<p>Es gab keine Daten zum verarbeiten!</p>";
	die;
}

$name = new Paragraph();
$name->addText('Name: ' . $_POST['userdata']['displayname']);

$startdatum = new Paragraph();
$startdatum->addText('Startdatum: ' . $_POST['userdata']['startdate']);

$nbrdays = new Paragraph();
$nbrdays->addText('Anzahl Tage übermittelt: ' . count($_POST['userdata']['workdays']) );


//$p = new Paragraph();

//$p->addText('hello');

// write output
//$dd = getcwd();
$odt->output("hello.odt");

echo "<a href='hello.odt'>Download</a>";

?>
