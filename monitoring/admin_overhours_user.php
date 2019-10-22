<!html>

<?php 
    include_once '../helper.class.php';
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

?>

<head>

</head>

<body>

<?php

    function toMinutes($timeval) {
        $timeParts = explode(":", $timeval);
        if (count($timeParts) != 2 ) return 0;
        $timeres = intval($timeParts[0]) * 60 + intval($timeParts[1]);
        return $timeres;
    }

    function toTimeString($minuteval) {
        $sign = "";
        if ( $minuteval < 0 ) {
            $minuteval *= -1.0;
            $sign = "-";
        }

        $hours = intval($minuteval / 60);
        $minutes = $minuteval - $hours * 60;
        $strResult = "$hours:";
        if ( $minutes < 10 ) $strResult .= "0";
        $strResult .= $minutes;
        return $sign . $strResult;
    }

    function fromStringToTime($mdate) {
        $year = intval(substr($mdate, 0,4),10);
        $month = intval(substr($mdate, 5,2),10);
        $day = intval(substr($mdate,8,2),10);

        $mtime = mktime(0,0,0, $month, $day, $year);

        return $mtime;
    }
    function compareDates($date1, $date2) {
        $mtime1 = fromStringToTime($date1);
        $mtime2 = fromStringToTime($date2);
        if ($mtime1 < $mtime2 ) return -1;
        else if ($mtime1 == $mtime2 ) return 0;
        else return 1;
    }

    function addDays($date1, $nbrDays) {
        $mtime = fromStringToTime($date1);
        $restime = mktime(0,0,0,date("m", $mtime), date("d", $mtime) + $nbrDays,date("Y", $mtime) );
        return date("Y-m-d", $restime); 
    }

    function getDayNumber($mdate) {
        $nameDay = date("l", $mdate);
        $nbrDay = -1;
        if ( $nameDay == "Sunday") $nbrDay = 7;
        if ( $nameDay == "Monday") $nbrDay = 1;
        if ( $nameDay == "Tuesday") $nbrDay = 2;
        if ( $nameDay == "Wednesday") $nbrDay = 3;
        if ( $nameDay == "Thursday") $nbrDay = 4;
        if ( $nameDay == "Friday") $nbrDay = 5;
        if ( $nameDay == "Saturday") $nbrDay = 6;

        return $nbrDay;
    }

    function getGermanNameDay($dayNumber) {
        switch ( $dayNumber ) {
            case 1: return "Montag";
            case 2: return "Dienstag";
            case 3: return "Mittwoch";
            case 4: return "Donnerstag";
            case 5: return "Freitag";
            case 6: return "Samstag";
            case 7: return "Sonntag";
            default: return "Tag nicht gefunden";
        }
    }

    if ( ! isset($_GET["userid"]) ) {
        echo "Need user id";
        die;
    }

    if ( ! isset($_GET["period_start"]) ) {
        echo "Need start of period";
        die;
    }

    if ( ! isset($_GET["period_end"]) ) {
        echo "Need end of period";
        die;
    }

    $userid = $_GET["userid"];
    $pStart = $_GET["period_start"];
    $pEnd = $_GET["period_end"];

    $helper = new Helper();
    $pStart = $helper->TransformDateToUs($pStart);
    $pEnd = $helper->TransformDateToUs($pEnd);

    if ( compareDates($pStart, $pEnd) >= 0 ) {
        echo "Startdate is ahead of enddate!";
        die;
    }
    
    $tempDate = $pStart;
    $counterOverminutes = 0;
    $counterWeek = 0;
    // begin table
    echo "<table>";
    echo "<tr><td>Datum</td><td>Zu leistende Stunden</td><td>Geleistete Stunden</td><td>Tagesergebnis</td><td>Summe der Überstunden</td><td>Wochenergebnis</td></tr>";
    while ( compareDates($tempDate, $pEnd) <= 0 ) {
        $mtime = fromStringToTime($tempDate);
        $workday = $helper->getWorkDay($userid,
           date("d", $mtime),
           date("m", $mtime),
           date("Y", $mtime),
           getDayNumber($mtime)
       );

        $timeDone = 0;
        for ($a = 0; $a < count($workday['times']['from']); $a++ ) {
            $tempTimeTo = $workday['times']['to'][$a];
            $tempTimeFrom = $workday['times']['from'][$a];
            $timeDone += toMinutes($tempTimeTo) - toMinutes($tempTimeFrom);
        }

        $displayTimeDone = toTimeString($timeDone);

        if ( $workday['holliday']['hollidayid'] == 1 ) {
            $timeToDo = toMinutes($workday["workhourstodo"]["hours"] . ":" . $workday["workhourstodo"]["minutes"]);
        } else {
            $timeToDo = 0;
        }

        $displayTimeToDo = toTimeString($timeToDo); 

        $dayResult = $timeDone - $timeToDo;
        $displayDayResult = toTimeString($dayResult);


        $counterOverminutes += $dayResult;
        $displayCounterOverminutes = toTimeString($counterOverminutes);

        $color = "black";
        $border = "none";
        $counterWeek += $dayResult;
        if ( getDayNumber($mtime) == 7 ) {
            $displayCounterWeek = toTimeString($counterWeek);
            $border = "4px solid black";
            if ( $counterWeek >= 120 ) {
                $color = "red";
            }
        } else {
            $displayCounterWeek = "";
        }

        $weekday = getGermanNameDay( getDayNumber($mtime) );

        echo "<tr>";
        echo "<td style=\"color:$color;border-bottom:$border;\">" . date("d", $mtime) . "." . date("m", $mtime) . "." . date("Y",$mtime) . " ($weekday) </td>";
        echo "<td style=\"color:$color;border-bottom:$border;\">$displayTimeToDo</td>";
        echo "<td style=\"color:$color;border-bottom:$border;\">$displayTimeDone</td>";
        echo "<td style=\"color:$color;border-bottom:$border;\">$displayDayResult</td>";
        echo "<td style=\"color:$color;border-bottom:$border;\">$displayCounterOverminutes</td>";
        echo "<td style=\"color:$color;border-bottom:$border;\">$displayCounterWeek</td>";
        echo "</tr>";
        $tempDate = addDays($tempDate, 1);

        if ( getDayNumber($mtime) == 7 ) {
            $counterWeek = 0;
        }
    }

    echo "</table>";
