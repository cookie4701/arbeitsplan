<?php
iconv_set_encoding("internal_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("input_encoding", "UTF-8");

ob_start();

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

include_once('login.class.php');
include_once('helper.class.php');
include_once('gui.class.php');



$log = new CLogin();
$helper = new Helper();
header ('Content-Type:text/html; charset=UTF-8');
?>
<!doctype html>

<?php
//<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
?>

<html>


<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<meta charset="utf-8">

<title>Arbeitsplan - &Uuml;bersicht - Eingabe Daten</title>

<link href="default.css" rel="stylesheet" type="text/css">

<style>


</style>

<script type="text/javascript">

var selectedWeek = 1;
var useryear = <?php echo $helper->getUserStartYear($log->getIdUser());  ?> ;
var xmlhttp2;
var datenow;
var workareasNbr = <?php echo $helper->getNumberWorkareas(); ?>;
var caractionCounter = [];
var workweek = null;

for (var i=0; i < 7; i++) caractionCounter[i] = 0;

//--------------------------------------------------------------------------
//AJAX
//--------------------------------------------------------------------------

//XML-HTTP-Request
//--------------------------------------------------------------------------
function get_new_xmlhttprequest()
{
	var xmlhttp = null;

	if (window.XMLHttpRequest)
	{
		// code for Mozilla, etc.
		//--------------------------------------------------------------------------
		xmlhttp = new XMLHttpRequest();
	}
	else if (window.ActiveXObject)
	{
		// code for IE
		//--------------------------------------------------------------------------
		xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}

	return xmlhttp;
}
//--------------------------------------------------------------------------
function send_httprequest(inMethod,inURL,inAsync,inParams,inCallback)
{
	var xmlhttp = get_new_xmlhttprequest();

	// Callback-Function setzen
	//--------------------------------------------------------------------------
	if (inCallback != null)
	{
		xmlhttp.onreadystatechange = inCallback;
	}

	// Verbindung öffnen
	//--------------------------------------------------------------------------
	xmlhttp.open(inMethod, inURL, inAsync);

	// Parameter
	//--------------------------------------------------------------------------
	if(inParams != null)
	{
		xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset=UTF-8;');
	}

	// Anfrage senden
	//--------------------------------------------------------------------------
	xmlhttp.send(inParams);

	return xmlhttp;
}
//--------------------------------------------------------------------------

function calcChanges(dd) {
	var minutesGlobal;
	var hoursGlobal;

	var minutesWorkareas = 0;

	minutesGlobal = hoursGlobal = 0;

	for (var i = 1; i <= 4; i++ ) {
		var elFrom = document.getElementById(dd + 'workfrom' + i);
		var elTo = document.getElementById(dd + 'workto' + i);

		var valuesFrom = elFrom.value.split(":");
		var valuesTo = elTo.value.split(":");

		var tempHours   = 0;
		var tempMinutes = 0;

		if ( valuesFrom.length == 2 && valuesTo.length == 2) {
			if ( parseInt( valuesFrom[1],10 ) > parseInt( valuesTo[1],10) ) {
				tempMinutes = 60 + parseInt(valuesTo[1],10) - parseInt(valuesFrom[1],10);
				tempHours   = parseInt(valuesTo[0],10) - parseInt(valuesFrom[0],10);
				tempHours--;
				//document.getElementById('lastsave').value += ' ' + parseInt(valuesFrom[0]);
			}
			else {
				tempMinutes = parseInt(valuesTo[1],10) - parseInt(valuesFrom[1],10);
				tempHours   = parseInt(valuesTo[0],10) - parseInt(valuesFrom[0],10);
				//document.getElementById('lastsave').value += ' ' + parseInt( valuesFrom[0],10);
			}
			minutesGlobal += tempMinutes;
			hoursGlobal += tempHours;

			while ( minutesGlobal >= 60 ) {
				hoursGlobal++;
				minutesGlobal -= 60;
			};
		}
	}

	// calc workareas
	for ( var k = 1; k <= 24; k++ ) {
		var elWork = document.getElementById(dd + 'wa' + k);
		var tempWorkareaTime = elWork.value.split(":");
		//alert ( tempWorkareaTime.length );
		if ( tempWorkareaTime.length == 2) {
			var temp = parseInt(tempWorkareaTime[0],10);
			var tMinutes = (temp * 60) + parseInt(tempWorkareaTime[1],10);
			//alert( temp + ' ' + temp*60 );
			minutesWorkareas += tMinutes;
		}
	}
	var elz = document.getElementById(dd + 'displayworkhours');

	//alert( minutesWorkareas + ' ' + minutesGlobal );
	//document.getElementById('lastsave').value += ' ' + minutesWorkareas + ' ' + (minutesGlobal + (hoursGlobal*60));
	if ( minutesWorkareas != (minutesGlobal + (hoursGlobal*60)) ) {
		elz.className = "descriptionWorkareaNotOk";
	}

	else {
		elz.className = "descriptionWorkareaOk";
	}

	elz.value = hoursGlobal + ':' + minutesGlobal;
}

function loadactions() {
	getStartDate(selectedWeek);
}

function getStartDate(week) {

	document.getElementById('lastsave').value = 'Lade Daten...';

	var uri = "json.php?appcode=2";
	var params = "week=" + week;
	var callback = new Function("","arrivingData();");

	xmlhttp2 = send_httprequest("post", uri, true, params, callback);

}


function arrivingData() {

	if ( xmlhttp2) {
		if (xmlhttp2.readyState == 4) {
			if ( xmlhttp2.status == 200 ) {
				var empfang = xmlhttp2.responseText;
				xmlhttp2 = null;
				if ( empfang != "" ) {
					//alert(empfang);
					if ( workweek != null ) workweek.length = 0;
					eval('var ret = ' + empfang );
					workweek = ret;
					//alert(workweek);
					//HoursToBeDoneRefresh();
					RefreshGrid();
					document.getElementById('lastsave').value = '';
				}
				else {
					alert('nix');
				}
			}
		}
	}
}

function getData() {
	var uri = "json.php?appcode=6";
	var params = "workday=" + datenow.getDay() + "&workmonth=" + datenow.getMonth()+1 + "&workyear=" + datenow.getFullYear();
	var callback = new Function("", "arrivingDataWeek();");
	xmlhttp2 = send_httprequest("post", uri, true, params, callback);
}

function arrivingDataWeek() {
	if ( xmlhttp2) {
		if (xmlhttp2.readyState == 4) {
			if ( xmlhttp2.status == 200 ) {
				var empfang = xmlhttp2.responseText;
				xmlhttp2 = null;
				if ( empfang != "" ) {
					//alert(empfang);
				}
				else {
					alert('nix');
				}
			}
		}
	}
}

function refreshDates() {

	var elementz = document.getElementsByClassName('daydate');
	var tempDate = datenow;
	var i;
	for ( i = 0; i < elementz.length; i++ ) {
		elementz[i].innerHTML = Weekday( tempDate.getDay() ) + ", " + tempDate.getDate() + "." + (tempDate.getMonth()+1) + "." + tempDate.getFullYear() ;
		//alert(tempDate.getDate(), tempDate.getMonth()+1, tempDate.getFullYear(), (i*4)+1);
		//loadWorkFromTo(tempDate.getFullYear(), tempDate.getMonth()+1,  tempDate.getDate(),(i*4)+1 );
		tempDate = new Date( tempDate.setDate( tempDate.getDate() + 1 ) );
		//@TODO: add code for reloading work done maybe save before
	}

	var el = document.getElementById('weekuserinfo');
	el.innerHTML = (datenow.getDate()) + "." + (datenow.getMonth()+1) + "." + datenow.getFullYear();

	//loadWorkFromTo();
}

function Weekday(nbr) {
	switch (nbr) {
		case 0: return "Montag";
		case 1: return "Dienstag";
		case 2: return "Mittwoch";
		case 3: return "Donnerstag";
		case 4: return "Freitag";
		case 5: return "Samstag";
		case 6: return "Sonntag";
		default: return "xxx";
	}
}

function clickWeek( i) {
	selectedWeek = i;
	var lbl;

	document.getElementById('lastsave').value = '';

	for (var k = 1; k <= 53; k++ ) {
		lbl = 'week' + k;
		var el = document.getElementById(lbl);
		if ( k == selectedWeek ) {
			el.style.backgroundColor = '#0099FF';
		}
		else {
			el.style.backgroundColor = '#FFFFCC';
		}
	}
	// @TODO: add code to change the form below
	getStartDate(selectedWeek);

	//getData();


}

function calcNewOverHours() {
	var timeOld = document.getElementById('resold').value;
	var timeNew = document.getElementById('resweek').value;
	var hours = 0;
	var minutes = 0;


	// Old overhours to minutes
	var idx = timeOld.indexOf('-');
	if ( idx == -1 ) { // positive value

		var arrTime = timeOld.split(':');
		if ( arrTime.length == 2 ) {
			minutes += parseInt( arrTime[1], 10) + 60*parseInt(arrTime[0], 10);
		}
	} else if ( idx == 0 ) { // negative value
		timeOld = timeOld.substr(1);
		var arrTime = timeOld.split(':');
		if ( arrTime.length == 2 ) {
			minutes -= parseInt( arrTime[1], 10) + 60*parseInt(arrTime[0], 10);

		}
	}


	idx = timeNew.indexOf('-');
	if ( idx == -1 ) {
		var arrTime = timeNew.split(':');
		if ( arrTime.length == 2 ) {
			minutes += parseInt( arrTime[1], 10) + 60*parseInt(arrTime[0],10);
		}
	} else if ( idx == 0 ) {
		timeNew = timeNew.substr(1);
		var arrTime = timeNew.split(':');
		if ( arrTime.length == 2 ) {
			minutes -= parseInt( arrTime[1], 10) + 60*parseInt(arrTime[0],10);
		}
	}

	var sign = "";
	var minuteDisplay;
	if ( minutes < 0 ) {
		sign = "-";
		minutes *= -1;
	}

	while ( minutes >= 60 ) {
		hours++;
		minutes -= 60;
	}

	if ( isNaN( minutes) ) {
		minuteDisplay = "00";
	} else if ( minutes >= 0 && minutes < 10 ) {
		minuteDisplay = "0" + minutes;
	}
	else {
		minuteDisplay = minutes;
	}

	document.getElementById('resnew').value = sign + hours + ':' + minuteDisplay;

}

function GUIchanges() {
	HoursToBeDoneRefresh();
	HoursDoneRefresh();
	calcResultWeek();
	calcNewOverHours();

	if ( document.getElementById('mohollidaybox') != null ) calcDaysOffTakenThisWeek();

	checkKmBoxes();

}

function HoursToBeDoneRefresh() {
	var hours = 0;
	var minutes = 0;
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	for (var i=0; i < 7; i++ ) {
		//workweek['workday'][i]['times']['to'][k]
		if ( document.getElementById(days[i] + 'hollidaybox').value == 1 ) {
			var hoursTemp = workweek['workday'][i]['workhourstodo']['hours'];
			var minutesTemp = workweek['workday'][i]['workhourstodo']['minutes'];
			hours += hoursTemp;
			minutes += minutesTemp;

		}
	}

	while ( minutes >= 60 ) {
		minutes = minutes - 60;
		hours++;
	}

	//alert('minutes: ' + minutes + ' hours:' + hours);
	var el = document.getElementById('hourstodoweek');
	if ( minutes < 10 ) el.value = hours + ':0' + minutes;
	else el.value = hours + ':' + minutes;

}

function HoursDoneRefresh() {
	var hoursTotal = 0;
	var minutesTotal = 0;
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");

	for (var i = 0; i < 7; i++ ) {
		var el = document.getElementById( days[i] + 'displayworkhours' );
		var val = el.value;
		var arrWorkDone = val.split(':');
		if ( arrWorkDone.length == 2 ) {
			hoursTotal += parseInt(arrWorkDone[0], 10);
			minutesTotal += parseInt(arrWorkDone[1],10);
		}

	}

	while ( minutesTotal >= 60 ) {
		hoursTotal++;
		minutesTotal -= 60;
	}

	var el = document.getElementById('hoursdoneweek');
	if ( minutesTotal < 10 ) {
		el.value = hoursTotal + ':0' + minutesTotal;
	}
	else {
		el.value = hoursTotal + ':' + minutesTotal;
	}

}

function calcResultWeek() {
	var elWorkToBeDone = document.getElementById('hourstodoweek');
	var elWorkDone = document.getElementById('hoursdoneweek');
	var arrWorkToBeDone = elWorkToBeDone.value.split(':');
	var arrWorkDone = elWorkDone.value.split(':');

	var hours = 0;
	var minutes = 0;
	var boolOver = 1;

	if ( arrWorkToBeDone.length == 2 && arrWorkDone.length == 2 ) {
		// overhours
		if ( parseInt(arrWorkDone[0],10) > parseInt(arrWorkToBeDone[0],10) ) {
			hours = parseInt(arrWorkDone[0],10) - parseInt(arrWorkToBeDone[0],10);
			minutes = parseInt(arrWorkDone[1],10) - parseInt(arrWorkToBeDone[1],10);
			if ( minutes < 0 ) {
				minutes += 60;
				hours--;
			}
		}

		else if (parseInt(arrWorkDone[0],10) == parseInt(arrWorkToBeDone[0],10) ) {
			if ( parseInt(arrWorkDone[1],10) >= parseInt(arrWorkToBeDone[1],10) ) {
				minutes = parseInt(arrWorkDone[1],10) - parseInt(arrWorkToBeDone[1],10);
			}
			else {
				boolOver = 0;
				minutes = parseInt(arrWorkToBeDone[1],10) - parseInt(arrWorkDone[1],10);
			}
		} else if ( parseInt(arrWorkDone[0],10) < parseInt(arrWorkToBeDone[0],10) ) {
			boolOver = 0;
			hours = parseInt(arrWorkToBeDone[0],10) - parseInt(arrWorkDone[0],10);
			minutes = parseInt(arrWorkToBeDone[1],10) - parseInt(arrWorkDone[1],10);
			if ( minutes < 0 ) {
				hours--;
				minutes += 60;
			}
		}

		var el = document.getElementById('resweek');

		if ( boolOver == 1 ) {
			if ( minutes >= 10 ) el.value = hours + ':' + minutes;
			else el.value = hours + ':0' + minutes;
		}
		else {
			if (minutes >= 10) el.value = '-' + hours + ':' + minutes;
			else el.value = '-' + hours + ':0' + minutes;
		}

	}
	else {
		var el = document.getElementById('resweek');
		el.value = arrWorkToBeDone.length + arrWorkDone.length + " Error calculating workhours";
	}

}

function calcDaysOffTakenThisWeek() {
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	var hollidays = 0;
	var vacationdays = 0;

	for (var i=0 ; i<7 ; i++ ) {
		if ( document.getElementById(days[i] + 'hollidaybox').value == 2 ) hollidays++;
		if ( document.getElementById(days[i] + 'hollidaybox').value == 3 ) vacationdays++;
	}
	document.getElementById('hollidayweek').value = hollidays;
	document.getElementById('vacationweek').value = vacationdays;

	hollidays = document.getElementById('remainhollidaybefore').value - hollidays;
	vacationdays = document.getElementById('vacationbefore').value - vacationdays;

	document.getElementById('hollidayafter').value = hollidays;
	document.getElementById('vacationafter').value = vacationdays;
}

function doPlusKm(caller) {
	var el = document.getElementById(caller + 'cartable');
	var row = document.createElement('tr');
	var c1 = document.createElement('td');
	var c2 = document.createElement('td');
	var c3 = document.createElement('td');
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");



	for (var i = 0; i < days.length; i++ ) {

		if ( days[i] == caller ) {
			caractionCounter[i]++;
			c1.innerHTML = "<input name=\"kmfrom[]\" id=\""+caller+"kmfrom" +caractionCounter[i] + "\">";
			c2.innerHTML = "<input name=\"kmto[]\" id=\""+caller+"kmto" +caractionCounter[i] + "\">";
			c3.innerHTML = "<input name=\"km[]\" id=\""+caller+"km" +caractionCounter[i] + "\">";

			row.appendChild(c1);
			row.appendChild(c2);
			row.appendChild(c3);
			el.appendChild(row);

		}
	}
	checkKmBoxes();


}

function doMinusKm(caller) {

	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	for (var i=0; i < days.length; i++ ) {
		if ( days[i] == caller ) {
			if (caractionCounter[i] > 0 ) {
				var el = document.getElementById(caller + 'cartable');
				var rem = el.children[caractionCounter[i]];
				el.removeChild(rem);
				caractionCounter[i]--;
			}
		}
	}
	checkKmBoxes();
}

function checkKmBoxes() {
	// disable buttons
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	for ( var i = 0; i < days.length; i++ ) {
		if ( caractionCounter[i] <= 0 ) document.getElementById(days[i] + 'minus').disabled = true;
		else document.getElementById(days[i] + 'minus').disabled = false;

		if ( caractionCounter[i] >= 5 ) document.getElementById(days[i] + 'plus').disabled = true;
		else document.getElementById(days[i] + 'plus').disabled = false;
	}
}

function applyLoadedCaractions() {
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	for (var i=0; i < days.length; i++ ) {
		if ( workweek['workday'][i]['caractions']['from'] != null ) {
			var nbr = workweek['workday'][i]['caractions']['from'].length;
			for (var k=1; k <= nbr; k++ ) {
				doPlusKm(days[i]);
				//alert(days[i] + 'kmfrom' + k);
				document.getElementById(days[i] + 'kmfrom' + k).value =
					workweek['workday'][i]['caractions']['from'][k-1];
				document.getElementById(days[i] + 'kmto' + k).value =
					workweek['workday'][i]['caractions']['to'][k-1];
				document.getElementById(days[i] + 'km' + k).value =
					workweek['workday'][i]['caractions']['km'][k-1];
			}
		}

	}
}

function RefreshGrid() {
	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	var bufferElementA, bufferElementB;

	for (var i = 0; i < days.length; i++ ) {
		// remove all km
		var t = caractionCounter[i];
		for (var q = 0; q < t; q++ ) {
				doMinusKm(days[i]);
		}

		for ( var k = 1; k <= 4; k++ ) {
			bufferElementA = document.getElementById( days[i] + 'workfrom' + k );
			bufferElementB = document.getElementById( days[i] + 'workto' + k);

			if ( workweek['workday'][i]['times']['from'][k-1] != null ) {
				if ( bufferElementA != null ) bufferElementA.value = workweek['workday'][i]['times']['from'][k-1];
			}

			else {
				if ( bufferElementA != null ) bufferElementA.value = "";
			}

			if ( workweek['workday'][i]['times']['to'][k-1] != null ) {
				if ( bufferElementB != null ) bufferElementB.value = workweek['workday'][i]['times']['to'][k-1];
			}

			else {
				if ( bufferElementB != null ) bufferElementB.value = "";
			}
		}

		// holliday
		bufferElementA = document.getElementById( days[i] + 'hollidaybox');
		bufferElementA.value = workweek['workday'][i]['holliday']['hollidayid'];

		bufferElementA = document.getElementById( days[i] + 'hollidaydescription');
		bufferElementA.value = workweek['workday'][i]['holliday']['hollidaytext'];

		//alert(workweek['workday'][i]['holliday']['hollidayid']);

		// get workarea descriptions
		for (k = 0; k < workareasNbr; k++ ) {
			bufferElementA = document.getElementById( days[i] + 'walbl' + (k+1) );
			if (bufferElementA != null ) {
				if ( workweek['workareas'][k][2] != null ) bufferElementA.innerHTML = workweek['workareas'][k][2];
				else { bufferElementA.innerHTML = "a"; }
			}

			// get work hours done already
			bufferElementA = document.getElementById(days[i] + 'wa' + (k+1) );
			if (bufferElementA != null ) {
				if (workweek['workday'][i]['workdoneinareas'] != null) if ( workweek['workday'][i]['workdoneinareas']['time'] != null ) {

					 if (workweek['workday'][i]['workdoneinareas']['time'][k] != null) bufferElementA.value = workweek['workday'][i]['workdoneinareas']['time'][k].substring(0,5);
					 else bufferElementA.value = "00:00";
				}
				else { bufferElementA.value = "00:00"; }
			}
		}

		document.getElementById('remainhollidaybefore').value = workweek['remainholliday'];
		document.getElementById('vacationbefore').value = workweek['remainvacation'];

		calcChanges(days[i]);

		// get date
		bufferElementA = document.getElementById( 'daydate' + (i+1));
		if ( bufferElementA != null ) bufferElementA.innerHTML = Weekday(i) + ', ' + workweek['workday'][i]['date'];

		// get day comment
		bufferElementA = document.getElementById( days[i] + 'comment' );
		if ( bufferElementA != null ) {
			if (workweek['workday'][i]['comment'] != null ) {
				bufferElementA.value = workweek['workday'][i]['comment'];
			}
		}

	}

	HoursToBeDoneRefresh();
	HoursDoneRefresh();
	calcResultWeek();

	//overhours until last week
	document.getElementById('resold').value = workweek['overhoursbeforeweek'];

	calcNewOverHours();
	calcDaysOffTakenThisWeek();

	applyLoadedCaractions();
	checkKmBoxes();
}

function doSaveAll() {
	document.getElementById('btnSaveAll').disabled = 'disabled';

	var days = new Array("mo", "tu", "we", "th", "fr", "sa", "su");
	var arrData = [];

	document.getElementById('lastsave').value = "Speichere Daten...";

	for (var i=0; i < days.length; i++ ) {
		var arrDataKm = new Array();
		var arrDataKmFrom = new Array();
		var arrDataKmTo = new Array();
		var q = 1;

		while ( document.getElementById(days[i] + 'kmfrom' + q) != null ) {
			if ( document.getElementById(days[i] + 'kmto' + q) != null && document.getElementById(days[i] + 'km' + q) != null ) {
				arrDataKm.push( document.getElementById(days[i] + 'km' + q).value );
				arrDataKmFrom.push( document.getElementById(days[i] + 'kmfrom' + q).value );
				arrDataKmTo.push( document.getElementById(days[i] + 'kmto' + q).value );
			}
			q++;
		}

		var workareas = new Array();
		q = 1;
		while (document.getElementById(days[i] + 'wa' + q) != null ) {
			workareas.push(document.getElementById(days[i] + 'wa' + q).value);
			q++;
		}

		var comment = document.getElementById(days[i] + 'comment').value.replace(/&/g, "&amp;"); //&amp;

		arrData[i] = {
				"mdate" : workweek['workday'][i]['date'],
				"from1" : document.getElementById(days[i] + 'workfrom1').value,
				"from2" : document.getElementById(days[i] + 'workfrom2').value,
				"from3" : document.getElementById(days[i] + 'workfrom3').value,
				"from4" : document.getElementById(days[i] + 'workfrom4').value,
				"to1" : document.getElementById(days[i] + 'workto1').value,
				"to2" : document.getElementById(days[i] + 'workto2').value,
				"to3" : document.getElementById(days[i] + 'workto3').value,
				"to4" : document.getElementById(days[i] + 'workto4').value,
				"wa" : workareas,
				"commentday" : comment, //document.getElementById(days[i] + 'comment').value,
				"holliday" : document.getElementById(days[i] + 'hollidaybox').value,
				"hollidaytext" : document.getElementById(days[i] + 'hollidaydescription').value,
				"km" : arrDataKm,
				"kmfrom" : arrDataKmFrom,
				"kmto" : arrDataKmTo
		};
	}
	//et property of non-object in /customers/f/c/e/easternlife.be/httpd.www/aplan_newskin/postdata.php on line 25Notice: T
	var strpost = JSON.stringify(arrData);
	//strpost = encodeURIComponent(strpost);
	var uri = "postdata.php";
	var params = "pdata=" + strpost;
	var callback = new Function("","datasended();");

	xmlhttp2 = send_httprequest("post", uri, true, params, callback);

}

function datasended() {
	if ( xmlhttp2) {
		if (xmlhttp2.readyState == 4) {
			if ( xmlhttp2.status == 200 ) {
				var empfang = xmlhttp2.responseText;
				xmlhttp2 = null;
				if ( empfang != "" ) {
					document.getElementById('lastsave').value = empfang;
					document.getElementById('btnSaveAll').disabled = false;
				}
				else {
					alert('nix');
				}
			}
			else if ( xmlhttp2.status == 503 ) {
				alert('Serverproblem!!! Daten wurden nicht gespeichert! Bitte nochmal probieren');
				document.getElementById('lastsave').value = 'Bitte nochmal speichern!';
				document.getElementById('btnSaveAll').disabled = false;
			} 
			else if ( xmlhttp2.status >= 500 && xmlhttp2.status < 600) {
				alert('Serverproblem!!! Daten wurden nicht gespeichert! Bitte nochmal probieren');
				document.getElementById('lastsave').value = 'Bitte nochmal speichern!';
				document.getElementById('btnSaveAll').disabled = false;
			} 
		}
	}

	//
}

function charTransform(sObj) {
	var ret = sObj.replace(/�/g, "\u00dc");
	ret = ret.replace(/�/g, "\u00fc");
	return ret;
}

function doPrintForm() {
	var parameter = { 'week' : selectedWeek };
	newWindowWithPostParam("report_weekly.php", parameter);
}

function newWindowWithPostParam(link, params)
{

//neues Formelement anlegen
var form = document.createElement("form");
//Methode Post festlegen
form.setAttribute("method", "POST");
//URL für den Post
form.setAttribute("action", link);
//Name des neuen Fenster, kann auch weggelassen werden
form.setAttribute("target", "NEUES FENSTER");
//Für jeden übergebenen Parameter wird ein Input-Feld angelegt
for (var param in params) {

if (params.hasOwnProperty(param)) {
var input= document.createElement('input');
input.type = 'hidden';
input.name = param;
input.value = params[param];
form.appendChild(input);
}

}
//Formelement anhängen
document.body.appendChild(form);
//Formular per Javascript abschicken
form.submit();
//Formelement wieder entfernen
document.body.removeChild(form);

}

function getWeekStartDate(we) {
	//useryear
	if ( useryear < 2000 || useryear > 2050 ) return null;

	var n = new Date(useryear, 0,1);
	var work = we-1;
	work = work * 7; // days
	work = work * 1000; // milliseconds
	work = work * 60 * 60; // hours
	work = work * 24; // one day

	var addmsecs = 0;

	if ( n.getDay() == 0 ) addmsecs = 1000 * 60 * 60 * 24 * 6 * (-1);

	if ( n.getDay() > 1 ) addmsecs = 1000 * 60 * 60 * 24 * (n.getDay()-1) * (-1);

	work = work + n.getMilliseconds() + addmsecs;

	n.setMilliseconds(work);

	return n.getDate() + "." + (n.getMonth()+1) + "." + n.getFullYear();

}

</script>

<script src="http://code.jquery.com/jquery-latest.js"></script>

<script type="text/javascript">

var lastsaveold;

$("document").ready( function() {

	$("li.weekli").mouseenter( function() {
		lastsaveold = $("#lastsave").val();
		$("#lastsave").val( getWeekStartDate( $(this).text() ) );

	});

	$("li.weekli").mouseleave( function() {
		$("#lastsave").val(lastsaveold);
		//$("#lastsave").val( getWeekStartDate( $(this).text() ) );

	});


});

</script>
</head>

<body onload="javascript:loadactions();" >

<?php //phpinfo(); ?>

<!-- general information like name, week number, a button to submit the plan to the boss, ... -->
<div class="general" id="generalinfo">
<span class="ginfo"> <?php echo $log->getDisplayName(); ?>, Arbeitswoche: <span id="weekuserinfo"> </span> <br />
<ul class="weeklist" id="mainweeklist">
<?php
for ( $index = 1; $index <= 53; $index++ ) {
	echo "<li id=\"week$index\" class=\"weekli\" onClick=\"javascript:clickWeek($index);\" > $index </li>";
}
?>
</ul>
<br>
Letzte Speicherung <input type="text" id="lastsave"> <input id="btnSaveAll" type="button" value="Speichern" onClick="javascript: doSaveAll();"> <input type="button" value="Druckansicht" onClick="javascript: doPrintForm();"> </span>

</div>
<!-- end generalinfo -->


<!--  Begin workdays -->
<?php
$days = array("mo", "tu", "we", "th", "fr", "sa", "su");
$obj = new CGuiGenerator();
for ( $arrNbr = 0; $arrNbr < count($days); $arrNbr++ ) {
	$obj->setDayDate($arrNbr+1);
	$obj->setPrefix($days[$arrNbr]);
	echo $obj->generateAll();
}
?>




<div class="sep"></div>

<!-- beginn bottom display, overhours, holliday, ... -->
<div class="bottomdisplay">
<div class="overhours">
<table class="tbloverhours" id="tbloverhours1">
<tr>
	<td class="tbloverhourscol1" >Zu leistende Stunden:</td>
	<td class="tbloverhourscol2"><input type="text" name="hourstodoweek" id="hourstodoweek" value="" > </td>
	<td> </td>
	<td>Verbleibende Urlaubstage vor dieser Woche:</td>
	<td><input type="text" name="holliday" id="remainhollidaybefore" value=""> </td>
</tr>
<tr>
	<td class="tbloverhourscol1" >Geleistete Stunden:</td>
	<td class="tbloverhourscol2"><input type="text" name="hoursdoneweek" id="hoursdoneweek" value=""> </td>
	<td> </td>
	<td>Verbleibende Feiertage vor dieser Woche: </td>
	<td><input type="text" name="vacationbefore" id="vacationbefore" value=""> </td>
</tr>
<tr>
	<td class="tbloverhourscol1" >Ergebnis Woche:</td>
	<td class="tbloverhourscol1"> <input type="text" name="resweek" id="resweek" value=""></td>
	<td class="tbloverhourscol1"> </td>
	<td class="tbloverhourscol1"> Urlaubstage diese Woche genommen:</td>
	<td class="tbloverhourscol1"> <input type="text" id="hollidayweek"> </td>
</tr>
<tr>
	<td class="tbloverhourscol1" >Alte Überstunden:</td>
	<td class="tbloverhourscol1"> <input type="text" name="resweek" id="resold" value=""> </td>
	<td class="tbloverhourscol1"> </td>
	<td class="tbloverhourscol1"> Feiertage diese Woche genommen:</td>
	<td class="tbloverhourscol1"> <input type="text" id='vacationweek' ></td>
</tr>
<tr>
	<td class="tbloverhourscol1" >Neue Überstunden:</td>
	<td class="tbloverhourscol1"> <input type="text" name="resweek" id="resnew" value=""> </td>
	<td class="tbloverhourscol1"> </td>
	<td class="tbloverhourscol1"> Verbleibende Urlaubstage:</td>
	<td class="tbloverhourscol1"> <input type="text" id="hollidayafter"></td>
</tr>
<tr>
	<td class="tbloverhourscol1" ></td>
	<td class="tbloverhourscol1"> </td>
	<td class="tbloverhourscol1"> </td>
	<td class="tbloverhourscol1"> Verbleibende Feiertage: </td>
	<td class="tbloverhourscol1"> <input type="text" id="vacationafter"> </td>
</tr>

</table>
</div>
<!-- end div overhours -->

<div class="holliday"> </div>

<div class="bankholliday"> </div>

</div>
<!-- end div bottomdisplay -->

</body>

</html>

<?php
ob_end_flush();

?>
