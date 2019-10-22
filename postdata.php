<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once('login.class.php');
include_once('helper.class.php');
include_once('gui.class.php');

if ( PHP_VERSION_ID < 50600 ) {
	iconv_set_encoding("internal_encoding", "UTF-8");
	iconv_set_encoding("output_encoding", "UTF-8");
	iconv_set_encoding("input_encoding", "UTF-8");
	
}

else {
	ini_set('default_charset', 'UTF-8');
}

$log = new CLogin();
$helper = new Helper();

$user = $log->getIdUser();

if ( isset($_POST["pdata"]) ) {
	$content = $_POST["pdata"];
	$content = json_decode($content);
	
	if ( count($content) == 0 ) echo "error no data";
	
	for ( $i = 0; $i < count($content); $i++ ) {
		$workdate = $content[$i]->mdate;
		$workday = substr($workdate, 0, 2);
		$workmonth = substr($workdate, 3, 2);
		$workyear = substr($workdate, 6,4);
		$workdbdate = $workyear . "-" . $workmonth . "-" . $workday;
		//echo $workdbdate;
		
		// check if all variables are present, if not abort operation
		if ( !isset($content[$i]->from1) ) {
			echo "Error!"; return;
		}
		if ( !isset($content[$i]->from2) ) {
			echo "Error!"; return;
		}
		if ( !isset($content[$i]->from3) ) {
			echo "Error!"; return;
		}
		if ( !isset($content[$i]->from4) ) {
			echo "Error!"; return;
		}
		$workfrom = array();
		
		if ( strcmp($content[$i]->from1, "") == 0) $workfrom[] = "00:00:00";
		else $workfrom[] = $content[$i]->from1 . ":00";
		
		if ( strcmp($content[$i]->from2, "") == 0) $workfrom[] = "00:00:00";
		else $workfrom[] = $content[$i]->from2 . ":00";
		
		if ( strcmp($content[$i]->from3, "") == 0) $workfrom[] = "00:00:00";
		else $workfrom[] = $content[$i]->from3 . ":00";
		
		if ( strcmp($content[$i]->from4, "") == 0) $workfrom[] = "00:00:00";
		else $workfrom[] = $content[$i]->from4 . ":00";
		
		if ( !isset($content[$i]->to1) ) {
			echo "Error!"; return;
		}
		if ( !isset($content[$i]->to2) ) {
			echo "Error!"; return;
		}
		if ( !isset($content[$i]->to3) ) {
			echo "Error!"; return;
		}
		if ( !isset($content[$i]->to4) ) {
			echo "Error!"; return;
		}
		
		$workto = array();
		if ( strcmp($content[$i]->to1, "") == 0) $workto[] = "00:00:00";
		else $workto[] = $content[$i]->to1 . ":00";
		
		if ( strcmp($content[$i]->to2, "") == 0) $workto[] = "00:00:00";
		else $workto[] = $content[$i]->to2 . ":00";
		
		if ( strcmp($content[$i]->to3, "") == 0) $workto[] = "00:00:00";
		else $workto[] = $content[$i]->to3 . ":00";
		
		if ( strcmp($content[$i]->to4, "") == 0) $workto[] = "00:00:00";
		else $workto[] = $content[$i]->to4 . ":00";
		
		// workareas
		if ( count($content[$i]->wa) != 24 ) {
			echo "Error workareas number mismatch!";
			return;
		}
		
		$workareas = array();
		for ( $q = 0; $q < count($content[$i]->wa); $q++ ) {
			$workareas[] = $content[$i]->wa[$q];
		} 
		
		// day comment aka daydescription
		if ( !isset($content[$i]->commentday) ) {
			echo "Error daydescription";
			return;
		}
		$workdaydescription = $helper->CharactersToEnteties( $content[$i]->commentday );
		
		// holliday id
		if ( !isset($content[$i]->holliday ) ) {
			echo "error holliday id";
			return;
		}
		$workdayhollidayid = $content[$i]->holliday;
		
		// holliday comment
		if ( !isset($content[$i]->hollidaytext ) ) {
			echo "error holliday text";
			return;
		}
		$workdayhollidaytext = $content[$i]->hollidaytext;
		
		$workkm = array();
		$workkmfrom = array();
		$workkmto = array();
		if ( isset($content[$i]->km ) ) {
			for ($a=0; $a < count($content[$i]->km); $a++ ) {
				$workkm[] = $content[$i]->km[$a];
			}
		}
		
		if ( isset($content[$i]->kmfrom ) ) {
			for ($a=0; $a < count($content[$i]->kmfrom); $a++ ) {
				$workkmfrom[] =$content[$i]->kmfrom[$a];
			}
		}
		
		if ( isset($content[$i]->kmto ) ) {
			for ($a=0; $a < count($content[$i]->kmto); $a++ ) {
				$workkmto[] =$content[$i]->kmto[$a];
			}
		}
		
		if ( count($workkm) != count($workkmto)  ) {
			$workkm = $workkmfrom = $workkmto = null;
		}
		
		$helper->getDatabaseConnection()->getDatabaseConnection()->query("SET NAMES 'utf8'");
		
		// all data gathered, now start updating / inserting to database
		
		// timefrom timeto
		// first remove entries
		$sql = "DELETE FROM " . CConfig::$db_tbl_prefix . "timefromto WHERE user_id=? AND dateofday=?";
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("is", $user, $workdbdate);
			if ( $stmt->execute() ) {
				//echo "ok";
			}
			else {
				echo "not ok";
			}
		}
		else {
			echo "x";
		}
		
		$stmt->close();
		
		// insert values
		$tmpDateFrom = "00:00:00";
		$tmpDateTo = "00:00:00";
		$sql = "INSERT INTO " . CConfig::$db_tbl_prefix . "timefromto (timefrom, timeto, dateofday, user_id) VALUES (?,?,?,?)";
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("sssi", $tmpDateFrom, $tmpDateTo, $workdbdate, $user);
			for ($a = 0; $a < count($workfrom); $a++ ) {
				$tmpDateFrom = $workfrom[$a];
				$tmpDateTo   = $workto[$a];
				if ( strcmp($tmpDateFrom, "00:00:00") == 0 && strcmp($tmpDateTo, "00:00:00") == 0 ) {
						
				}
				else {
					if ( ! $stmt->execute() ) {
						echo "Error timefromto insert database";
						return;
					}
				}
			}
		}
		$stmt->close();
		
		// workareas, check if entry is there and needs to be updated - only update if there is new data (no overwriting of 05:00:00 with 05:00:00 - most of the time it's the case) 
		// @TODO: improve speed here please - done partialy
		$myTempDate = $workdbdate . " 00:00:00";
		$arrWorkareasTweak                 = array();
		$arrWorkareasTweak['updateinsert'] = array(); // 1 update 2 insert 3 no action
		$arrWorkareasTweak['iddatabase']   = array();
		$arrWorkareasTweak['time']         = array(); // Used time in format hh:mm
		
		for ($a = 0; $a < count($workareas); $a++ ) {
			$arrWorkareasTweak['updateinsert'][] = 2;
			$arrWorkareasTweak['iddatabase'][] = -1;
			$arrWorkareasTweak['time'][] = $workareas[$a] . ":00";
		}
		
		$a = 0;
		// run loop to collect data and write it to a table
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		
		if ( ! isset($stmt) ) {
			echo "Database error: " . $helper->getDatabaseConnection()->getDatabaseConnection()->error;
			die;
		}
		
		if ( $stmt->prepare("SELECT hours, id, workfield_id FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=? AND date=? ORDER BY workfield_id")) {
			$stmt->bind_param("is", $user, $myTempDate);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpHours, $tmpId, $tmpWorkfield);  // $tmpWorkfield should be equal to $a in loops
				while ( $stmt->fetch() ) {
					//echo "tmpWorkfield: $tmpWorkfield ";
					if ( $tmpWorkfield >= 0 && $tmpWorkfield < 24 ) {
						$arrWorkareasTweak['iddatabase'][$tmpWorkfield] = $tmpId;
						if ( strcmp($arrWorkareasTweak['time'][$tmpWorkfield], $tmpHours) == 0 ) {
							$arrWorkareasTweak['updateinsert'][$tmpWorkfield] = 3;
							//echo "no action ";
						} else {
							$arrWorkareasTweak['updateinsert'][$tmpWorkfield] = 1;
							//echo "update ";
						}
					}
				}
			}
		}
		
		$stmt->close();
		
		// run update loop
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ($stmt->prepare("UPDATE " . CConfig::$db_tbl_prefix . "workday SET hours=? WHERE id=?") ) {
			$tmpHours = 0;
			$tmpId = 0;
			$stmt->bind_param("si", $tmpHours, $tmpId );
			
			for ($a = 0; $a < count($arrWorkareasTweak['iddatabase']); $a++ ) {
					$tmpHours = $arrWorkareasTweak['time'][$a];
					$tmpId = $arrWorkareasTweak['iddatabase'][$a];
					if ($tmpId > 0 && $arrWorkareasTweak['updateinsert'][$a] == 1 ) {
						//echo "update executed iddatebase: " . $arrWorkareasTweak['iddatabase'][$a] . " time: " . $arrWorkareasTweak['time'][$a] . "updateinstert: " . $arrWorkareasTweak['updateinsert'][$a] ;
						//echo "u";
						$stmt->execute();
					} else {
						//echo "no update! iddatebase: " . $arrWorkareasTweak['iddatabase'][$a] . " time: " . $arrWorkareasTweak['time'][$a] . "updateinstert: " . $arrWorkareasTweak['updateinsert'][$a] ;
					}
				}
		} else {
			echo "problem with update loop: " . $helper->getDatabaseConnection()->getDatabaseConnection()->error;
		}
		$stmt->close();
		
		// run insert loop
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ($stmt->prepare("INSERT INTO " . CConfig::$db_tbl_prefix . "workday (hours, workfield_id, date, user_id) VALUES (?,?,?,?)") ) {
			$tmpWorkareaHours = "";
			$user_id = $log->getIdUser();
			$a = 0;
			$stmt->bind_param("sisi", $tmpWorkareaHours, $a, $myTempDate, $user_id);
			
			for ($a = 0; $a < count($arrWorkareasTweak['iddatabase']); $a++ ) {
				if ( $arrWorkareasTweak['iddatabase'][$a] < 0 && $arrWorkareasTweak['updateinsert'][$a] == 2 ) {
					$tmpWorkareaHours = $arrWorkareasTweak['time'][$a];
					//echo "insert executed! " . $arrWorkareasTweak['iddatabase'][$a] . " time: " . $arrWorkareasTweak['time'][$a] . "updateinstert: " . $arrWorkareasTweak['updateinsert'][$a] ;
					//echo "i";
					$stmt->execute();
				} else {
					//echo "no insert!" . $arrWorkareasTweak['iddatabase'][$a] . " time: " . $arrWorkareasTweak['time'][$a] . "updateinstert: " . $arrWorkareasTweak['updateinsert'][$a] ;
				}
			}
		} else {
			echo "problem with insert loop: " . $helper->getDatabaseConnection()->getDatabaseConnection()->error;
		}
		$stmt->close();		
		// end workareas
		
		// day comment
		$rowsUpdated = 0;
		
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ($stmt->prepare("SELECT id FROM " . CConfig::$db_tbl_prefix . "daydescriptions WHERE user_id=? AND workday=?") ) {
			$stmt->bind_param("is", $user, $workdbdate );
			$stmt->execute();
			$stmt->bind_result($tmpA);
			if ( $stmt->fetch() ) {
				// row needs to be updated
				$rowsUpdated = 1;
			}
			else {
				// row needs to be inserted
				$rowsUpdated = 2;
			}
			
		}
		$stmt->close();
		
		if ( $rowsUpdated == 1) {
			$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
			if ($stmt->prepare("UPDATE " . CConfig::$db_tbl_prefix . "daydescriptions SET description=? WHERE user_id=? AND workday=?") ) {
				$descr = $workdaydescription;
				$stmt->bind_param("sis", $descr, $user, $workdbdate );
				$stmt->execute();
				$rowsUpdated = $stmt->affected_rows;
			}
			$stmt->close();
		}
		
		
		// insert if rowsUpdated is null
		if ( $rowsUpdated == 2) {
			$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
			if ($stmt->prepare("INSERT INTO " . CConfig::$db_tbl_prefix . "daydescriptions (user_id, workday, description) VALUES (?,?,?)") ) {
				$descr = $workdaydescription;
				$stmt->bind_param("iss",  $user, $workdbdate, $descr );
				$stmt->execute();
				$rowsUpdated = $stmt->affected_rows;
			}
			$stmt->close();
		}
		
		// holliday and hollidaytext
		$id = -1;
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT id FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND dateofday=?") ) {
			$stmt->bind_param("is", $user, $myTempDate);
			$stmt->execute();
			$stmt->bind_result($id);
			if ( $stmt->fetch() ) {
				$bUpdate = 1;
			}
			else {
				$bUpdate = 2;
			}
		}
		$stmt->close();
		
		if ( $bUpdate == 1 ) {
			$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
			if ($stmt->prepare("UPDATE " . CConfig::$db_tbl_prefix . "arbeitstage SET holliday_id=? , holliday_text=? WHERE id=?") ) {
				$stmt->bind_param("isi", $workdayhollidayid, $workdayhollidaytext, $id );
				$stmt->execute();
			}
			$stmt->close();
		}
		

		if ( $bUpdate == 2) {
			$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
			if ( $stmt->prepare("INSERT INTO " . CConfig::$db_tbl_prefix . "arbeitstage (user_id, dateofday, holliday_id, holliday_text) VALUES (?,?,?,?)") ) {
				$stmt->bind_param("isis", $user, $workdbdate, $workdayhollidayid, $workdayhollidaytext );
				$stmt->execute();
				$rowsUpdated = $stmt->affected_rows;
			}
			$stmt->close();
		}
		
		// delete KM for that day
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if ($stmt->prepare("DELETE FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=? AND day=?") ) {
			$stmt->bind_param("is", $user, $workdbdate);
			$stmt->execute();
		}
		$stmt->close();
		
		// insert KM for that day
		$stmt = $helper->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		$tmpDbDate = $workdbdate . " 00:00:00";
		$km = 0;
		$from = "";
		$to = "";
		
		if ($stmt->prepare("INSERT INTO " . CConfig::$db_tbl_prefix . "kilometers (user_id, day, km, fromwhere, towhere) VALUES (?,?,?,?,?)") ) {
			$stmt->bind_param("isiss", $user, $tmpDbDate, $km, $from, $to);
			for ($a = 0; $a < count($content[$i]->kmfrom); $a++) {
				$from = $content[$i]->kmfrom[$a];
				//$from = mb_convert_encoding($from, "UTF-8");
				$to = $content[$i]->kmto[$a];
				$km = $content[$i]->km[$a];
				$stmt->execute();
			}

			$stmt->close();
		}
	}
	
	// success message
	date_default_timezone_set('Europe/Brussels');
	echo date("H:i d.m.Y");
	
}
else {
	echo "Error while saving";
}
?>
