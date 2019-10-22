<?php

/***************
 * error display
 ***************/

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**********
 * includes
 **********/
require_once '../config.php';
require_once '../database.class.php'; 
require_once('../helper.class.php');
require_once('../fpdf/fpdf.php');

/*******************
 * config area start
 *******************/

 // font options
 $font = "Times";
 
 $font_size_regular = 12;
 
 $font_size_title = 18;
 $font_style_title = "b";
 
 // doc title
 $doctitle = "Jahresbericht von";
 
 date_default_timezone_set('Europe/Brussels');
 
 
/*****************
 * config area end
 *****************/
/**************************
 * initialize helper object
 **************************/
 $helper = new Helper();
 
/*****************
 * data collecting
 *****************/
 
 // parameter id
 if ( isset($_GET["id"]) ) {
	$id = $_GET["id"];
 }
 else {
	$id = -1;
	echo "<p>error</p>";
	//header ('Content-Type:application/pdf; charset=UTF-8');
	//error_pdf();
	die;
 }

 if ($id > 0 ) $userinfo = $helper->GetUserInfo($id);

 /***********
  * print pdf
  ***********/
 
 // header
 header ('Content-Type:application/pdf; charset=UTF-8');

 // initialize pdf object
 $pdf = new FPDF('P','mm','A4'); // for the moment these settings are default but who knows what will happen in the future...
 
 // add first / title page and set it up
 $pdf->SetFont($font,$font_style_title,$font_size_title);
 $pdf->AddPage();
 $pdf->Cell(40, 100, "$doctitle " . $userinfo["dname"]);
 
 $pdf->Output(); // output document
/*
function error_pdf()
{
	header ('Content-Type:application/pdf; charset=UTF-8');
	pdf = new FPDF('P','mm','A4'); // for the moment these settings are default but who knows what will happen in the future...
	$pdf->AddPage();
	$pdf->SetFont($font,'',$font_size_regular);
	$pdf->Cell(40,10,'No user id');
	$pdf->Output();

}
*/
?>
