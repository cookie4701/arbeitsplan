<?PHP
	error_reporting(E_ALL);
	ini_set('display_errors', 1);

	require_once("config.php");
	require_once 'database.class.php';
	require_once 'login.class.php';
	require_once 'helper.class.php';

	session_start();

	header ('Content-Type:text/html; charset=UTF-8');


?>

<html>

<head>
<link href="default.css" rel="stylesheet" type="text/css">

<script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
<script type="text/javascript">


function zaehlearbeitsbereiche() {
	var std = 0;
	var min = 0;
	var i;
	var max = <?php echo $max_rank_workfields; ?>;

	for (i = 0; i <  max; i++ ) {
		var timevar = document.getElementsByName("arbeitsbereich_stunden"+i);

		if (typeof timevar[0] != "undefined") {

			var k = parseInt(timevar[0].value);

			if ( !isNaN(k) ) std += k;
		}

	}

	document.uinfo.stundentotal.value = std;
	BerechneProzent();

}

function BerechneProzent() {
	var total = document.uinfo.stundentotal.value;
	var p = 0;
	var i;
	var max = <?php echo $max_rank_workfields; ?>;

	for ( i=0; i <= max; i++ )
	{
		var timevar = document.getElementsByName("arbeitsbereich_stunden"+i);
		if ( typeof timevar[0] != "undefined" ) {
			var k = parseInt(timevar[0].value)
			if ( !isNaN(k) )
			{
				var res = k * 100 / total;
				res = res.toFixed(2);
				res = res + " %";
				var t = document.getElementsByName( "proz" + i);
				if ( typeof t[0] != "undefined" ) t[0].value = res;
			}
		}
	}
}

function chkBox() {
	var i;
	var k;

	for ( i=0; i < document.uinfo.urlaubstage.value.length; i++ ) {
		if ( document.uinfo.urlaubstage.value.charAt(i) < "0" ||
			document.uinfo.urlaubstage.value.charAt(i) > "9" ) {
			alert("Urlaubstage ist keine g�ltige Zahl. Bitte �berpr�fen sie die Eingabe");
			return false;
		}
	}

	// ---------------------------------------------------------------------------------------------------------------------------------
	// �berpr�fe Eingabefelder in einer Schleife. Kann einfach kopiert werden und in anderen Formularen wiederverwendet werden
	var boxen = new Array(window.document.uinfo.wochentag1, window.document.uinfo.wochentag2, window.document.uinfo.wochentag3,
	window.document.uinfo.wochentag4, window.document.uinfo.wochentag5 );

	for ( k = 0; k < boxen.length; k++ ) {

		for (i=0; i < boxen[k].value.length ; i++ ) {

			if ( (boxen[k].value.charAt(i) < "0" ||
				boxen[k].value.charAt(i) > "9" )&& boxen[k].value.charAt(i) != "," ) {

				alert( "Eingabe f�r Wochentag " + boxen[k].name + " fehlerhaft." );
				return false;
			}
		}

	}

	// by Pascal Kuck, 2010, Jugendb�ro - pascal.kuck@jugendbuero.be
	// ---------------------------------------------------------------------------------------------------------------------------------

	boxen2 = new Array(window.document.uinfo.arbeitsbereich_stunden1, window.document.uinfo.arbeitsbereich_stunden2,
		window.document.uinfo.arbeitsbereich_stunden3, window.document.uinfo.arbeitsbereich_stunden4,
		window.document.uinfo.arbeitsbereich_stunden5, window.document.uinfo.arbeitsbereich_stunden6,
		window.document.uinfo.arbeitsbereich_stunden7, window.document.uinfo.arbeitsbereich_stunden8,
		window.document.uinfo.arbeitsbereich_stunden9, window.document.uinfo.arbeitsbereich_stunden10,
		window.document.uinfo.arbeitsbereich_stunden11, window.document.uinfo.arbeitsbereich_stunden12,
		window.document.uinfo.arbeitsbereich_stunden13, window.document.uinfo.arbeitsbereich_stunden14,
		window.document.uinfo.arbeitsbereich_stunden15, window.document.uinfo.arbeitsbereich_stunden16,
		window.document.uinfo.arbeitsbereich_stunden17, window.document.uinfo.arbeitsbereich_stunden18,
		window.document.uinfo.arbeitsbereich_stunden19, window.document.uinfo.arbeitsbereich_stunden20,
		window.document.uinfo.arbeitsbereich_stunden21, window.document.uinfo.arbeitsbereich_stunden22,
		window.document.uinfo.arbeitsbereich_stunden23, window.document.uinfo.arbeitsbereich_stunden24 );

	for ( k = 0; k < boxen2.length; k++ ) {

		for (i=0; i < boxen2[k].value.length ; i++ ) {

			if ( boxen2[k].value.charAt(i) < "0" ||
				boxen2[k].value.charAt(i) > "9"  ) {

				alert( "Eingabe für Stunden im Arbeitsbereich " + (k+1) + " ist fehlerhaft!" );
				return false;
			}
		}

	}

	return true;
}

</script>


</head>

<body>



<?php

$log = new CLogin();
$help = new Helper();

$userid = $log->getIdUser();

$userinfo = $help->GetUserInfo($userid);
$userworkdays = $help->GetUserWorkDays($userid);

// KM Satz , durch . ersetzen
$meinkm = $userinfo[10];
$meinkm = str_replace( "." ,  ",", $meinkm);

$startdatum = $userinfo[11];
$startdatum = substr($startdatum, 0, 10);
$startjahr = substr($startdatum, 0, 4);
$startmonat = substr($startdatum, 5, 2);
$starttag = substr($startdatum, 8, 2);

$startdatum = $starttag . "." . $startmonat . "." . $startjahr;

$alteueberstunden = $userinfo[7];
$urlaubstage = $userinfo[9];
$feiertage = $userinfo[8];

?>


<xxxform action="userinfo.php" method="get" name="uinfo" onsubmit="return chkBox();" >
<div class="benutzerangabendiv">
 <h2 class="benutzerangaben"> Benutzerangaben </h2>

 <table border="0" class="userinfo">
 <tr>
	<td> Name </td>
	<td> <input class="gelbbox" name="benutzername" value="<?php echo $log->getDisplayName(); ?>" type="text" size="20" readonly /> </td>
</tr>

<tr>
	<td> KM Satz </td>
	<td> <input class="gelbbox" name="kmsatz" value="<?php $meinkm=str_replace(",", ".", $meinkm); echo round($meinkm,4); ?>" type="text" size="20" />
</tr>

<tr>
	<td> Startdatum </td>
	<td> <input class="gelbbox" name="startdatum" value="<?php echo $startdatum; ?>" type="text" size="20" readonly />
</tr>

<tr>
	<td> Alte Überstunden </td>
	<td> <input class="gelbbox" name="alteueberstunden" type="text" size="20" value="<?php echo $alteueberstunden; ?>" />
</tr>

<tr>
	<td> Urlaubstage </td>
	<td> <input class="gelbbox" name="urlaubstage" type="text" size="20" value="<?php echo $urlaubstage; ?>"  />
</tr>

<tr>
	<td> Feiertrage </td>
	<td> <input class="gelbbox" name="feiertage" type="text" size="20" value="<?php echo $feiertage; ?>"   />
</tr>

</table>

</div>

<div class="arbeitszeitendiv">
<h2 class="benutzerangaben"> Arbeitszeiten </h2>
<table border="0">
 <tr>
	<td> Wochentag </td>
	<td> Stunden </td>
</tr>
<tr>
	<td> Montag </td>
	<td> <input class="gelbbox" name="wochentag1" value="<?php echo str_replace( "." ,  ",", $userworkdays[0][1]); ?>" type="text" size="10" /> </td>
</tr>

<tr>
	<td> Dienstag </td>
	<td> <input class="gelbbox" name="wochentag2" value="<?php echo str_replace( "." ,  ",",  $userworkdays[1][1]); ?>" type="text" size="10" /> </td>
</tr>

<tr>
	<td> Mittwoch </td>
	<td> <input class="gelbbox" name="wochentag3" value="<?php echo str_replace( "." ,  ",",  $userworkdays[2][1]); ?>" type="text" size="10" /> </td>
</tr>

<tr>
	<td> Donnerstag </td>
	<td> <input class="gelbbox" name="wochentag4" type="text" size="10" value="<?php echo str_replace( "." ,  ",",  $userworkdays[3][1]); ?>" /> </td>
</tr>

<tr>
	<td> Freitag </td>
	<td> <input class="gelbbox" name="wochentag5" type="text" size="10" value="<?php echo str_replace( "." ,  ",", $userworkdays[4][1]); ?>" /> </td>
</tr>

<tr>
	<td> Samstag </td>
	<td> <input class="gelbbox" name="wochentag6" type="text" size="10" value="<?php echo str_replace( "." ,  ",", $userworkdays[5][1]); ?>" /> </td>
</tr>

<tr>
	<td> Sonntag </td>
	<td> <input class="gelbbox" name="wochentag7" type="text" size="10" value="<?php echo str_replace( "." ,  ",", $userworkdays[6][1]); ?>" /> </td>
</tr>


</table>

</div>

<div id="vueapp"> </div>


<div class="arbeitsbereichediv">
<h2 class="benutzerangaben"> Arbeitsbereiche </h2>
<table border="0">
 <tr>
	<td> Kurzform </td>
	<td> Erklärung </td>
	<td> Stunden für diesen Bereich </td>
</tr>
<tr>
	<td></td>
	<td></td>
	<td>Verteilte Stunden: <input type="text" name="stundentotal" readonly size="5" >


<?php

global $max_rank_workfields;
$bereich = $help->GetWorkfieldsAll($userid);

for ( $i = 0; $i < $max_rank_workfields; $i++ ) {

?>
<tr>
	<td> <input class="gelbbox" name="arbeitsbereich_kurz<?php echo $i+1 ?>"    value="<?php echo $bereich[$i][2]; ?>" size="20" type="text" /> </td>
	<td> <input class="gelbbox" name="arbeitsbereich_lang<?php echo $i+1 ?>"    value="<?php echo $bereich[$i][1]; ?>"size="60" type="text" /> </td>
	<td> <input class="gelbbox" onkeyup="zaehlearbeitsbereiche();" name="arbeitsbereich_stunden<?php echo $i+1 ?>" value="<?php echo $bereich[$i][4]; ?>" size="20" type="text" /> </td>
	<td> <input name="proz<?php echo $i+1 ?>" value="na" size="10" type="text" /> </td>
</tr>
<?php
}
?>



</table>

<template id="schedule-item-add">
    <div class="schedule-item-add">
        Tag: 
        <select v-model="scheduleItem.workday">
            <option value="0">Montag</option>
            <option value="1">Dienstag</option>
            <option value="2">Mittwoch</option>
            <option value="3">Donnerstag</option>
            <option value="4">Freitag</option>
            <option value="5">Samstag</option>
            <option value="6">Sonntag</option>
        </select> Von <input v-model="scheduleItem.from" type="text"> Bis <input type="text" v-model="scheduleItem.to"> <button v-on:"$emit('buttonAddScheduleItem', scheduleItem)">Hinzufügen</button>
    </div>
</template>


<input class="gruenbox" type="submit" value="Speichern" />
</div>
</form>

<script type="text/javascript">
zaehlearbeitsbereiche();

</script>


<script src="javascripts/userinfo-vue.js"></script>

</body>

</html>
