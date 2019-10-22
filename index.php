<?PHP
	
	require_once("config.php");	
	require_once 'login.class.php';
	
	session_start();
	
	header ('Content-Type:text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>

<link href="default.css" rel="stylesheet" type="text/css">

<title>Arbeitsplan Jugendbüro - Version 2.0 </title>

<script type="text/javascript">


function openKm() {
	closePasswd();
	document.getElementById('kmboxshow').style.display = "inline";
}

function closeKm() {
	document.getElementById('kmboxshow').style.display = "none";
}

function openPasswd() {
	closeKm();
	document.getElementById('passwd').style.display = "inline";
}

function closePasswd() {
	document.getElementById('passwd').style.display = "none";
}

function openReportpartial() {
	document.getElementById('reportpartial').style.display = "inline";
}

function closeReportpartial() {
	document.getElementById('reportpartial').style.display = "none";
}

function chkPasswd() {
	if ( document.getElementById('password').value == document.getElementById('repass') ) {
		return true;
	}
	else {
		return false;
	}
}

</script>

</head>

<body>


<?php
$log = new CLogin();


?>
<div class="menu"> <h2> Hauptmenü </h2>
<ul>
<li class="menuentry"><a href="userinfogui.php" target="__BLANK" > Benutzerinformationen </a></li>

<li class="menuentry"> <a href="aplan_week.php" target="__BLANK"> Arbeitsplan V2.0 </a> </li>


<li class="menuentry"> <a href="report_yearly.php" target="__BLANK"> Jahresbericht</a> </li>

<li class="menuentry" onClick="javascript: openReportpartial(); "> Teilbericht </a> </li>

<li class="menuentry" onclick="javascript: openKm();" > Kilometerabrechnung</li>


<li class="menuentry"> <a href="report_km_finance.php" target="__BLANK">KM Abrechnung Finanzamt</a> </li>

<li class="menuentry" onClick="javascript: openPasswd();"> Passwort ändern </li>

<li class="menuentry"> <?php echo $log->printLogoutLink(); ?> </li> 
</ul>

</div>

<div id="kmboxshow" class="kmboxshow">
<form action="km.php" method="POST" target="__BLANK" id="kmboxdisplay">
Ab Woche <input type="text" name="datefrom" size="3" > bis  <input type="text" name="dateto" size="3" >  
<input type="submit" value="KM Abrechnung" />
</form>
<input type="button" value="Schliessen" onClick="javascript: closeKm();" >
</div>

<div id="passwd" class="passwd">
<form action="passwd.php" method="POST" onsubmit="return chkPasswd()">
<table>
<tr> <td>Neues Passwort: </td> <td> <input type="password" name="password" ></td> </tr>
<tr> <td>Bestätigung Passwort:</td><td> <input type="password" name="repass"> </td> </tr>
</table>
<input type="submit">
</form>
<input type="button" value="Schliessen" onClick="javascript: closePasswd();" >
</div>

<div id="reportpartial" class="reportpartial"> 
<?php 
$index = 0;
$max = 24;
for ($index = 0; $index < $max; $index++ ) {
	echo "<form method=\"post\" action=\"report_partial.php\" target=\"__BLANK\"> <input type=\"hidden\" name=\"workrank\" value=\"$index\"> <input type=\"submit\" value=\"" . ($index+1) . "\"> </form>";
}	
?>
</div>


<script type="text/javascript">closeKm(); closePasswd(); closeReportpartial(); </script>


</body>

</html>
