<?php

function TimeToInt($tvar)
{
    $el = explode(":", $tvar ) ;
    if (count($el) != 2) return 0;
    $result = intval($el[0]) * 60;
    $result += intval($el[1]);
    return $result;
}

$tests = ["11:00", "7:36", "7:15", "6:15"];
$total = 0;

for ($i = 0; $i < count($tests); $i++ ) {
  echo TimeToInt($tests[$i]) . "<br />";
  $total += TimeToInt($tests[$i]);
}

echo "total: $total";
