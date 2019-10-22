<?php

require_once('login.class.php');
require_once('helper.class.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set("memory_limit","100M");

header ('Content-Type:text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<link rel="stylesheet" type="text/css" href="default.css">
<title>Wochenreport</title>
</head>

<body>

<?php
	
$log = new CLogin();
$helper = new Helper();

$userid = $log->getIdUser();

if ( !isset( $_POST['week']) ) {

	echo "<p>Falscher Parameter wurde &uuml;bergeben</p>";
	die;
}

$week = $_POST["week"];
$holliday = 0;
$vacation = 0;

if ( $week < 1 || $week > 53 ) {
	echo "<p>Fehlerhafter Wert f&uuml; den Parameter</p>";
	die;
}

$minutesToDoWeek = 0;
$minutesDoneWeek = 0;

$sdate = $helper->CalendarWeekStartDate($week, $helper->getUserStartYear($log->getIdUser()) );

$day = substr($sdate, 8, 2);
$month = substr($sdate,5,2);
$year = substr($sdate,0,4);

$ret = $helper->getWorkWeek($log->getIdUser(), $day, $month, $year);

echo "<p class=\"repName\">" . $log->getDisplayName() . ", Woche: $week </p>";

echo "<table class=\"repday\">";
for ($a = 0; $a < count($ret['workday']); $a++ ) {
	
	$minutesDoneOnDay = 0;
	
	if ( $ret['workday'][$a]['holliday']['hollidayid'] == 1 )
	{
		$minutesToDoWeek += $ret['workday'][$a]['workhourstodo']['minutes'];
		$minutesToDoWeek += $ret['workday'][$a]['workhourstodo']['hours'] * 60;
	}
	
	
	echo "<tr class=\"reprow\" >\n";
	
	echo "<td class=\"reprow2\">" . $ret['workday'][$a]['date'] . "<br>";
	echo "<table class=\"repTableWorkFromTo\">\n";
	echo "<tr><td>Von</td><td>Bis</td></tr>";
	for ( $b = 0; $b < count($ret['workday'][$a]['times']['from']); $b++ ) {
		echo "<tr>\n";
		echo "  <td>" . $ret['workday'][$a]['times']['from'][$b] . "</td>\n";
		echo "  <td>" . $ret['workday'][$a]['times']['to'][$b] . "</td>\n";
		echo "</tr>\n";
		
		$tempTimeWorked = $helper->TimeSubtract($ret['workday'][$a]['times']['from'][$b], $ret['workday'][$a]['times']['to'][$b]);
		$arrTempTime = explode(":", $tempTimeWorked);
		
		$minutesDoneOnDay += $arrTempTime[0] * 60;
		$minutesDoneOnDay += $arrTempTime[1];
		
		
	}
	$minutesDoneWeek += $minutesDoneOnDay;
	echo "</table>\n";
	
	$hoursDoneOnDay = (int) ($minutesDoneOnDay / 60);
	$minutesDoneOnDay = $minutesDoneOnDay - ($hoursDoneOnDay * 60);
	
	echo "<p>Total: $hoursDoneOnDay:$minutesDoneOnDay</p>";
	
	$km = 0;
	for ($b = 0; $b < count($ret['workday'][$a]['caractions']['km']); $b++ ) {
		$km += $ret['workday'][$a]['caractions']['km'][$b];
	}
	echo "<p>Gefahrene KM: $km </p>\n";
	echo "</td>\n ";
	
	echo "<td class=\"reprowWa\">\n";
	echo "<table><tr> <td>";
	$counterDisplay = 0;
	for ($b = 0; $b < count($ret['workareas']); $b++ ) {
	
		for ($c = 0; $c < count($ret['workday'][$a]['workdoneinareas']['id']); $c++ ) {
			if ($ret['workday'][$a]['workdoneinareas']['id'][$c] == $ret['workareas'][$b][0] ) {
				$t = $ret['workday'][$a]['workdoneinareas']['time'][$c];
				$t = substr($t, 0, 5);
				if ( strcmp($t, "00:00") == 0 ) {
					
				}
				else {
					if ( ($counterDisplay + 1) % 5 == 0 ) {
						echo "</td> <td>";
					}
					
					$counterDisplay++;
					echo "<p>" . $ret['workareas'][$b][2] . ": $t </p>" ;
				}
				
			}
			
			
		}
		
	}
	echo "</td></tr></table>";
	echo "</td>";
	
	echo "<td class=\"reprow\">\n";
	
	if ($ret['workday'][$a]['holliday']['hollidayid'] == 2 ) {
		echo "<p>Urlaub</p>";
		$holliday++;
	}
	
	if ($ret['workday'][$a]['holliday']['hollidayid'] == 3 ) {
		echo "<p>Feiertag: " . $ret['workday'][$a]['holliday']['hollidaytext'] . "</p>";
		$vacation++;
	}
	
	if ($ret['workday'][$a]['holliday']['hollidayid'] == 4 ) {
		echo "<p>Krankheit</p>";
	}
	
	if ($ret['workday'][$a]['holliday']['hollidayid'] == 5 ) {
		echo "<p>Sonstiges: " . $ret['workday'][$a]['holliday']['hollidaytext'] . "</p>";
	}
	echo "<p>" . str_replace("\n", "<br>", $ret['workday'][$a]['comment']) . "</p";
	echo "</td>";
	
	echo "</tr>";
}
echo "<tr><td colspan=\"4\">";

// ****** calculations *********
$hoursToDoWeek = 0;
while ( $minutesToDoWeek >= 60 ) {
	$minutesToDoWeek -= 60;
	$hoursToDoWeek++;
}

$workToDo = $hoursToDoWeek . ":" . $minutesToDoWeek;


echo "<table>";
echo "<tr>";
echo "<td>Alte Überstunden: </td><td>" . $ret['overhoursbeforeweek'] . "</td><td> Verbleibende Urlaubstage: </td><td>" . $ret['hollidaynow'] . "</td>";
echo "</tr>";

echo "<tr>";
echo "<td>Soll (diese Woche): </td><td>" . $workToDo . "</td><td>Verbleibende Feiertage: </td><td>" . $ret['vacationnow'] . "  </td>";
echo "</tr>";

$hoursWorkedWeek = (int) ($minutesDoneWeek / 60);
$minutesDoneWeek = $minutesDoneWeek - ($hoursWorkedWeek * 60);
echo "<tr>";
echo "<td>Geleistet (diese Woche):</td><td>$hoursWorkedWeek:$minutesDoneWeek</td> <td>Dieses Jahr bereits gefahrene Kilometer: </td> <td>". $ret['kmtotal'] . "</td>";
echo "</tr>";


echo "<tr>";
echo "<td>Neue Überstunden: </td><td>" . $ret['overhours'] . "</td><td></td><td></td>";
echo "</tr>";
echo "</table>";

echo "</td> </tr>";
echo "</table>"

?>

</body>

</html>
