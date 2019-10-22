<?php

/*
 * desktop.php
 * 
 * This file contains all code requiered to export a complete year of a user on
 * the desktop side.
 * 
 * (C) by PaKu 2017 <cookie4rent@gmail.com>
 */

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
?>

<head>

<script src="https://code.jquery.com/jquery-latest.js"></script>

</head>

<?php

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

// init helper object if needed
$helper = new Helper();

?>

<body>

<div id="step1">
	<button id="btnGetUsers">Benutzerliste aktualisieren</button> <select id="users"> </select> <button id="btnClickUser">Diesen Benutzer exportieren</button> 
</div>

<div id="step2"> 
<button id="btnCancel">Abbrechen</button> <button id="btnCollectYearData">Daten für das Jahr laden (ab dem Startdatum) </button>

</div>

<div id="msg"> </div>

<script>
var userdata;

$(document).ready(function() {
	resetSteps();
	// Schaltfläche um alle Benutzer zu laden
	$("#btnGetUsers").click( function() {
		
		$("#users")
			.find('option')
			.remove()
			.end()
		;
		
		var myData = 'foo';
		
		var jqxhr = 
			$.post('extractor.php?app=1', {dd : myData}, null, 'JSON');
			
		jqxhr.done(function(data) {
				// check if both parts of the array have the same length
				if ( data.id.length != data.uname.length ) {
					alert('The database didn\` provide data in the requiered format');
					return;
				}
				
				for (var i=0; i < data.id.length; i++ ) {
					$('#users').append($('<option>', { value : data.id[i], text : data.uname[i] }));
				}
				
				$('#msg').append('<p>Benutzerliste geladen</p>');
				
		});
			
		jqxhr.fail(function(data) {
				// failure function
				alert('Something went wrong  ' + data);
		});
	});
	
	// Schaltfläche um den Extraktionsprozess zu starten
	$("#btnClickUser").click(function() {
		// reset existing data
		userdata = undefined;
	
		
		
		$('#msg').append('<p>Lese allgemeine Benutzerdaten aus...</p>');
		
		var queryid = $("#users").val();
		userdata = {
			'userid' : queryid, 
			'displayname' : null, 
			'startdate' : null,
			'oldovertime' : null,
			'hollidays' : null,
			'vacationdays' : null,
			'normalworkdays' : [7],
			'workarea_short' : [24],
			'workarea_long' : [24],
			'workarea_limits' : [24],
			'workdays': null
		};
		
		//userdata['userid'] = queryid;
		userdata['workdays'] = {'day' : [] };
		
		// first operation, get user's startdate
		var jqxhr = $.post('extractor.php?app=2', {uid : queryid}, null, 'JSON');
		
		// when request is done
		jqxhr.done(function(data) {
			userdata['startdate'] = data.startdate;
			userdata['oldovertime'] = data.oldovertime;
			userdata['hollidays'] = data.hollidays;
			userdata['vacationdays'] = data.vacationdays;
			userdata['normalworkdays'] = [];
			for (var i=0; i < 7; i++ ) userdata['normalworkdays'][i] = data.normalworkdays[i];
			
			userdata['workarea_short'] = [];
			userdata['workarea_long'] = [];
			userdata['workarea_limits'] = [];
			
			for (var k=0; k < 24; k++ ) {
				userdata['workarea_short'][k] = data.workarea_short[k];
				userdata['workarea_long'][k] = data.workarea_long[k];
				userdata['workarea_limits'][k] = data.workarea_limits[k];
			}
			
			userdata['displayname'] = data.displayname;
			$('#msg').append('<p>Auslesen fertig, bereit für nächsten Schritt</p>');
			
			$('#step1').hide();
			$('#step2').show();
		});
		
		// when reques failed
		jqxhr.fail(function(data) {
			$('#msg').append('<p>Fehler beim Auslesen, bitte erneut versuchen</p>');
			return;
		});
		
	});
	
	// Schaltfläche um in Schritt 2 abzubrechen
	$('#btnCancel').click(function() {
			resetSteps();
	});
	
	// Schaltfläche um den Extraktionsprozess für das gesammte Jahr zu beginnen
	$('#btnCollectYearData').click( function() {
		// get startdate
		if (userdata == undefined ) {
			$('#msg').append('<p>Bitte abbrechen, Daten wurden vorher nicht korrekt geladen</p>');
			alert('xx');
			return;
		}
		
		var year = userdata['startdate'].substring(0,4);
		var month = userdata['startdate'].substring(5,7);
		var day = userdata['startdate'].substring(8,10);
		
		var startdate = new Date(year, month-1, day, 0, 0,0,0 );
		
		// get enddate for loop
		var enddate = new Date(year, 11, 31, 0,0,0,0);
		
		var tempdate = new Date(startdate);
		var nbrRetries = 3;
		var day = 0;
		allRequestSent = 0;
		do {
			var ok = 0;
			
			ok = extractUserDay(tempdate.getYear(), tempdate.getMonth(), tempdate.getDay(), day );
			
			// increment date by one day
			tempdate.setDate(tempdate.getDate() + 1);
			day++;
			
		} while (tempdate.getTime() <= enddate.getTime()) ;
		
		sendDataToOdt();
	});
	
});

function resetSteps() {
	userdata = undefined;
	$('#step1').show();
	$('#step2').hide();
	$('#msg').val('');
}

function extractUserDay(y, m, d, runDay) {
	var uid = userdata['userid'];
	var year = y + 1900;
	var month = m + 1;
	var day = d;
	
	jQuery.ajaxSetup({async:false});
	
	var jqxhr = $.post('extractor.php?app=3', { uid : uid, datay : year, datam : month, datad : day }, 'JSON');
	jqxhr.done(function(data) {
		
		if ( data == 'No year') return 0;
		if ( data == 'No month') return 0;
		if ( data == 'No day') return 0;
		
		var obj = jQuery.parseJSON(data);
		
		userdata['workdays'][runDay] = {
			'dateofday' : obj.date,
			'from': null,
			'to' : null,
			'workareas' : [],
			'daydescription' : null,
			'holliday_status' : null,
			'km_from' : [],
			'km_to' : [],
			'km_distance' : []
		};
		
		// get workhours
		if ( obj.times != undefined ) {
			// work time found
			if ( obj.times.from != undefined && obj.times.to != undefined) {
				userdata.workdays[runDay].from = [obj.times.from.length];
				userdata.workdays[runDay].to = [obj.times.to.length];
		
				for (var i = 0; i < obj.times.from.length; i++ ) {
					userdata['workdays'][runDay]['from'][i] = obj.times.from[i];
					userdata['workdays'][runDay]['to'][i] = obj.times.to[i];
				}
			}
		}
		// get workday description
		userdata['workdays'][runDay]['daydescription'] = obj.comment;
		// get holliday status hollidayid
		//$('#msg').append(obj.holliday.hollidayid);
		
		if ( obj.holliday.hollidayid != undefined ) {
			if ( obj.holliday.hollidayid == 1 ) {
				userdata['workdays'][runDay]['holliday_status'] = 'Normaler Arbeitstag';
			} else if ( obj.holliday.hollidayid == 3 ) {
				userdata['workdays'][runDay]['holliday_status'] = 'Feiertag';
			} else if ( obj.holliday.hollidayid == 2 ) {
				userdata['workdays'][runDay]['holliday_status'] = 'Urlaubstag';
			} else if ( obj.holliday.hollidayid == 4 ) {
				userdata['workdays'][runDay]['holliday_status'] = 'Krankheit';
			} else if ( obj.holliday.hollidayid == 5 ) {
				userdata['workdays'][runDay]['holliday_status'] = 'Sonstiges';
			}
		
		} else {
			$('#msg').append(obj.date);
		}
		
		// get workareas
		var n = 0;
		for (var k = 0; k < 24; k++ ) {
			if ( obj.workdoneinareas.time[k] != '00:00:00' &&  obj.workdoneinareas.time[k] != '' && obj.workdoneinareas.time[k] != null ) {
				userdata['workdays'][runDay]['workareas'][n] = { field : k, workdonetime : obj.workdoneinareas.time[k] };
				//userdata['workdays'][runDay]['workareas'][k] = obj.workdoneinareas.time[k];
				n++;
			}
		}
		
		// get km done
		for (var j = 0; j < obj.caractions.from.length; j++ ) {
			userdata['workdays'][runDay]['km_from']     = obj.caractions.from[j];
			userdata['workdays'][runDay]['km_to']       = obj.caractions.to[j];
			userdata['workdays'][runDay]['km_distance'] = obj.caractions.km[j];
		}
		
		
		return 1;
	});
	
	jqxhr.fail(function(data) {
		return 0;
	});
}

function sendDataToOdt() {
	//
	var jqxhr = $.post('data2odt.php', { userdata : userdata }, 'JSON');
	
	jqxhr.done( function(data) {
		$('#msg').append(data);
	});
}

</script>

</body>
