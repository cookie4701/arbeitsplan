<?php
include_once 'config.php';
include_once 'database.class.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

//phpinfo();

if ( PHP_VERSION_ID < 50600 ) {
	iconv_set_encoding("internal_encoding", "UTF-8");
	iconv_set_encoding("output_encoding", "UTF-8");
	iconv_set_encoding("input_encoding", "UTF-8");
	
}

else {
	ini_set('default_charset', 'UTF-8');
}

setlocale(LC_TIME, 'de_DE.utf8');

class Helper {

	/**
	 * 
	 * DatabaseConnection object where the connection is stored in
	 * @var DataBaseConnection $dbx
	 */
	private $dbx; 
	
	//! Parameterless constructor
	
	public function __construct () {
		
		try {
                $this->dbx = new DatabaseConnection(
                        CConfig::$dbhost,
                        CConfig::$dbuser,
                        CConfig::$dbpass,
                        CConfig::$dbname);

                $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
                $this->dbx->getDatabaseConnection()->set_charset("utf8");
		}
		
		catch (Exception $ex) {
			echo "<p>Error: $ex </p>";
		}
		
	}
	
	public function __destruct () {
		try {
			$this->dbx = null;
		}
		
		catch (Exception $ex) {
			echo "<p>Error on destruction...</p>";
		}
	}
	
	//! Get number of workareas
	
	public function getNumberWorkareas() {
                return CConfig::$max_rank_workfields;
	}
	
	
	//! Get work times for one day (from / to).
	
	/**
	 *  @param $userid ID The id of the user
	 *  @param $day Day of date
	 *  @param $month Month of date
	 *  @param $year Year of date
	 *  
         *  @return array: Times from and to, if no records are 
         *          found a NULL is returned
	 */
	
	public function getTimes($userid, $day, $month, $year) {
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$times = array();
		$times['from'] = array();
		$times['to'] = array();
		
                $buildDate = $year . "-" . $month . "-" . $day; 
                $sql = "SELECT timefrom, timeto, FROM ";
                $sql .= CConfig::$db_tbl_prefix;
                $sql .= "timefromto WHERE user_id=? AND ";
                $sql .= "dateofday=? ORDER BY timefrom";
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("is", $userid, $buildDate);
			$stmt->execute();
			$stmt->bind_result($fr, $to);
			
			while ( $stmt->fetch() ) {
				$times['from'][] = substr($fr, 0, 5);
				$times['to'][] = substr($to,0,5);
			}
			
			$stmt->close();
                        return $times;
		} else {
                        return NULL;
		}
	}
	
        //! This member returns all workfields, their corresponding id and
        //! the time done (if any)
	
	/**
	 * @param $userid int ID of user
	 * @param $day Day of date
	 * @param $month Month of date
	 * @param $year Year of date
	 * 
         * @return array: Array with workfields, ids, and time - if no
         *                data is found NULL is returned
	 */
	public function getWork($userid, $day, $month, $year) {
                $work = array();
                $work['dbid'] = array();
                $work['fieldname'] = array();
                $work['done'] = array();
                $buildDate = $year . "-" . $month . "-" . $day;
                $sql = "SELECT A.description, A.id, B.hours FROM ";
                $sql .= CConfig::$db_tbl_prefix;
                $sql .= "workfields AS A LEFT JOIN ";
                $sql . CConfig::$db_tbl_prefix;
                $sql .= "workday AS B ON A.id = B.workfield_id";
                $sql .= " WHERE A.user=? AND B.date=? ORDER BY A.rank";
                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("is", $userid, $buildDate);
			$stmt->execute();
                        $stmt->bind_result(
                                $tempDescription, $tempId, $tempHours);
			while ( $stmt->fetch() ) {
				$work['dbid'][] = $tempId;
				$work['fieldname'][] = $tempDescription;
				$work['done'][] = $tempHours;
			} 
                        $stmt->close();
			return $work;
		} else {
			return NULL;
		}
	}
	
	//! This member returns the hollidaystate for a user on specific day
	
	/**
	* @param $userid int ID of user
	* @param $day Day of date
	* @param $month Month of date
	* @param $year Year of date
	*
        * @return array: Array with id of the entry, id of the holliday
        *                (day off, normal work day, etc), and hollidaytext
        *                (description)
	*/
	
	public function getHollidayState($userid, $day, $month, $year) {
                $holliday = array();
                $holliday['id'] = -1;
                $holliday['hollidayid'] = 1;
                $holliday['hollidaytext'] = "";
		$buildDate = "$year-$month-$day"; 
                $sql = "SELECT id, holliday_id, holliday_text FROM ";
                $sql .= CConfig::$db_tbl_prefix;
                $sql .= "arbeitstage WHERE user_id=? AND dateofday=? LIMIT 0,1";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("is", $userid, $buildDate);
			$stmt->execute();
			$stmt->bind_result($id, $hollidayid, $hollidaytext);
			if ( $stmt->fetch() ) {
				$holliday['id'] = $id;
				$holliday['hollidayid'] = $hollidayid;
				$holliday['hollidaytext'] = $hollidaytext;
			} 
			
			$stmt->close();
		}
		return $holliday;
	}
	
	public function getDatabaseConnection() {
		return $this->dbx;
	}
	
	function getEditedLastWeek ($userid) {
		$ret = 1;
		$lastweek = 1;
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                $sql = "SELECT week FROM " . CConfig::$db_tbl_prefix;
                $sql .= "lastweek WHERE foreignid=?";
                
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("i", $userid);
			$stmt->execute();
			$stmt->bind_param("i", $lastweek);
			if ( $stmt->fetch() ) {
				;
			}
		}
		$stmt->close();
		return $lastweek;
	}
	
	function setEditedLastWeek ($userid, $week) {
		$updateid = -1;
		$insupsql = "";
		
		try {
                        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			if ($stmt->prepare("SELECT foreignid FROM " . CConfig::$db_tbl_prefix  . "lastweek WHERE foreignid=?")) {
				$stmt->bind_param("i", $userid);
				$stmt->execute();
				$stmt->bind_result("i", $updateid);
				$stmt->fetch();
				$stmt-close();
			}
			
			if ( $updateid < 0 ) {
				$insupsql = "INSERT INTO " . CConfig::$db_tbl_prefix  . "lastweek (week, foreignid) VALUES (?,?)";
			}
			else {
				$insupsql = "UPDATE " . CConfig::$db_tbl_prefix  . "lastweek SET week=? WHERE foreignid=?";
			}
			
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			if ( $stmt->prepare($insupsql) ) {
				$stmt->bind_param("ii", $week, $userid);
				$stmt->execute();
				$stmt->close();
			}
			
		}
		
		catch ( Exception $excp ) {
			echo "<p>Problem $excp </p>";
		}
		
	}
	
	//! Gets the year for the actual user
	
	/**
	 * @param $userid User ID to look for
	 * 
	 * @return int: year for user, on error 1980 is returned which will be recognized as wrong (hopefully)
	 */
	function getYearOfUser($userid) {
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$year = 1980;
		if ( $stmt->prepare("SELECT YEAR(startdate) FROM " . CConfig::$db_tbl_prefix  . "users WHERE id=?") ) {
			$stmt->bind_param("i", $userid);
			$stmt->execute();
			$stmt->bind_param("s", $year);
			$stmt->fetch();
		}
		return $year;
	}

	function createNewSchedule($userid, $startdate, $enddate, $label) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO " . CConfig::$db_tbl_prefix  . "schedules ";
        $sql .= "(userid, startdate, enddate, label) VALUES ";
        $sql .= "(?,?,?,?)";

        if ($stmt->prepare($sql) &&
            $stmt->bind_param("isss", $userid, $startdate, $enddate, $label) &&
            $stmt->execute()) {

            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
        }

        function isRessourceMysqliStatement($res) {
                if ( get_class($res) !== "mysqli_stmt" ) {
                        return false;
                } else {
                        return true;
                }
        }

        function restapi_scheduleitems_create($userid, $data) {
                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                $sql = "INSERT INTO aplan2_schedule_items ";
                $sql .= "(idSchedule, dayOfWeek, time_from, time_to) VALUES ";
                $sql .= "(?, ?, ?, ?)";
                $msg = "not ok";

                if ( ! isRessourceMysqliStatement($stmt) ) return $msg;

                $arrData = json_decode($data);

                if ($stmt->prepare($sql) ) {
                        $idSchedule = -1;
                        $dayOfWeek = 0;
                        $time_from = "07:00";
                        $time_to = "12:00";

                        if (! $stmt->bind_param("iiss", $idSchedule, $dayOfWeek, $time_from, $time_to) ) return $msg;

                        for ($i = 0; $i < count($arrData); $i++ ) {
                                $idSchedule = $arrData[$i]->idSchedule;
                                $dayOfWeek = $arrData[$i]->dayOfWeek;
                                $time_from = $arrData[$i]->timeFrom;
                                $time_to = $arrData[$i]->timeTo;

                                if (! $stmt->execute() ) return $msg;

                        }

                        $stmt->close();
                        $msg = "ok";
                }


                return $msg;
        }


        function restapi_schedule_delete($userid, $data) {

                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                $sql = "DELETE FROM aplan2_schedules WHERE  ";
                $sql .= "userid = ? AND idSchedule = ?";
                $msg = "not ok";

                if ( get_class($stmt) !== "mysqli_stmt" ) {
                        $msg = "not a mysqli_stmt";
                        return $msg;
                }

                if ($stmt->prepare($sql) ) {
                        $arrData = json_decode($data);
                        $idSchedule = $arrData->idSchedule;

                        if ($stmt->bind_param("ii", $userid, $idSchedule) && $stmt->execute() ) {
                                $msg = "ok";
                        } else {
                                $msg = "not ok " . $stmt->error;
                        } 
                        $stmt->close();
                } else {
                        $msg = "prepare failed " . $stmt->error;
                }

                return $msg;
        }

        function restapi_schedule_update($userid, $data) {

                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                $sql = "UPDATE aplan2_schedules SET startdate = ?, enddate = ? , label = ? WHERE  ";
                $sql .= "userid = ? AND idSchedule = ?";
                $msg = "not ok";

                if ( get_class($stmt) !== "mysqli_stmt" ) {
                        $msg = "not a mysqli_stmt";
                        return $msg;
                }

                if ($stmt->prepare($sql) ) {
                        $arrData = json_decode($data);
                        $startdate = $arrData->startdate;
                        $enddate = $arrData->enddate;
                        $label = $arrData->label;
                        $idSchedule = $arrData->idSchedule;

                        if ($stmt->bind_param("sssii", $startdate, $enddate, $label, $userid, $idSchedule) && $stmt->execute() ) {
                                $msg = "ok";
                        } else {
                                $msg = "not ok " . $stmt->error;
                        } 
                        $stmt->close();
                } else {
                        $msg = "prepare failed " . $stmt->error;
                }

                return $msg;
        }

        function restapi_schedule_create($userid, $data) {
                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                $sql = "INSERT INTO aplan2_schedules (userid, startdate, enddate, label) ";
                $sql .= "VALUES (?, ?, ?, ?)";
                $msg = "not ok";

                if ( get_class($stmt) !== "mysqli_stmt" ) {
                        $msg = "not a mysqli_stmt";
                        return $msg;
                }

                if ($stmt->prepare($sql) ) {
                        $arrData = json_decode($data);
                        $startdate = $arrData->startdate;
                        $enddate = $arrData->enddate;
                        $label = $arrData->label;
                        if ($stmt->bind_param("isss", $userid, $startdate, $enddate, $label) && $stmt->execute() ) {
                                $msg = "ok";
                        } else {
                                $msg = "not ok " . $stmt->error;
                        } 
                        $stmt->close();
                } else {
                        $msg = "prepare failed " . $stmt->error;
                }

                return $msg;

        }

        function restapi_schedule_read($userid) {
                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                $sql = "SELECT idSchedule, startdate, enddate, label FROM aplan2_schedules WHERE userid=?";
                $schedules = array();

                if ($stmt->prepare($sql) ) {
                        if ($stmt->bind_param("i", $userid) && $stmt->execute() ) {
                                $stmt->bind_result($idSchedule, $startdate, $enddate, $label);
                                while ($stmt->fetch() ) {
                                        $item = array(
                                                "idSchedule" => $idSchedule,
                                                "startdate" => $startdate,
                                                "enddate" => $enddate,
                                                "label" => $label
                                        );
                                        $schedules[] = $item;
                                }
                        }

                        $stmt->close();
                }

                return $schedules;

        }


	
	/**
	 * 
	 * Gets overhours until date for a given user
	 * @param int $user User ID
	 * @param int $day Day
	 * @param int $month Month
	 * @param int $year Year
	 * 
	 * @return Return the number of overhours in MINUTES
	 */
	function getOverhoursUntilDate($user, $day, $month, $year) {
		$userStartYear = $this->getUserStartYear($user);
		$endDate = mktime(0,0,0, $month, $day, $year );
		$workDoneMinutes = 0;
		$workToBeDoneMinutes = 0;
		$workDaysOffMinutes = 0;
		$workLastYear = 0.0;
		$startDate = mktime(0,0,0, 1, 1, 2010);
		
		// Get startdate for user
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT startdate, alteueberstunden FROM " . CConfig::$db_tbl_prefix  . "users WHERE id=?") ) {
			$stmt->bind_param("i", $user);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpStartDate, $tmpHoursLastYear);
				if ( $stmt->fetch() ) {
                    $workLastYear = (double) $tmpHoursLastYear * 60.0;
					$tmpStartDate = substr($tmpStartDate, 0, 10);
					
					$arrStartDate = explode("-", $tmpStartDate, 3);
					if ( count($arrStartDate) == 3 ) {
						$startDate = mktime(0,0,0,$arrStartDate[1],$arrStartDate[2], $arrStartDate[0]);
						$stmt->close();
					}
					else {
						return 0;
					}
				}
				else {
					return 0;
				}
			}
		}
		
		// Load table with work done by user within $startDate and $endDate
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix  . "workday WHERE user_id = ? AND date < ? AND date >= ?") ) {
			$dbStartDate = date("Y-m-d", $startDate);
			$dbEndDate = date("Y-m-d", $endDate);
			$stmt->bind_param("iss", $user, $dbEndDate, $dbStartDate);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpHoursWorked);
				while ( $stmt->fetch() ) {
					$arrTime = explode(":", $tmpHoursWorked);
					if ( count($arrTime) == 3 ) {
						$workDoneMinutes += (int)$arrTime[1] + (int)($arrTime[0]*60);
					}
				}
			}
		}

		//@TODO: TEST+DEBUG-IF-NEEDED: change the following code in a way that it uses schedules and schedule_items

                // Load all schedules that are in the desired range (startdate / enddate)
                $tblWorkToDoPerDay = array();
                $tmpDate = $dbStartDate = date("Y-m-d", $startDate);
                $dbEndDate = date("Y-m-d", $endDate);
                $index = 0;
                while ($tmpDate <= $dbEndDate) {
                    $tblWorkToDoPerDay[] = array();
                    $tblWorkToDoPerDay[$index]['date'] = $tmpDate;
                    $tblWorkToDoPerDay[$index]['timesSchedule'] = array();
                    $tblWorkToDoPerDay[$index]['workday'] = $this->calcDayOfWeek($tmpDate);

                    $tmpDate = mktime(0,0,0, date("m", $tmpDate), date("d", $tmpDate)+1, date("Y", $tmpDate) );
                    $index += 1;
                }

                $sql = "SELECT time_from, time_to ";
                $sql .= "FROM " . CConfig::$db_tbl_prefix  . "schedules AS A ";
                $sql .= "LEFT JOIN " . CConfig::$db_tbl_prefix  . "schedule_items AS B ";
                $sql .= "ON A.idSchedule = B.idSchedule ";
                $sql .= "WHERE A.userid=? AND A.startdate <= ? AND A.enddate > ? AND B.dayOfWeek= ? ";

                if ($stmt->prepare() ) {
                    if ($stmt->bind_param("issi", $user, $pWorkToDoPerDayDate, $pWorkToDoPerDayDate, $pWorkday ) ) {
                        for ($i = 0; $i < count($tblWorkToDoPerDay) ; $i++ ) {
                            $pWorkday = $tblWorkToDoPerDay[$i]['workday'];
                            $pWorkToDoPerDayDate = $tblWorkToDoPerDay[$i]['date'];
                            if ($stmt->execute()) {
                                $stmt->bind_result($from, $to);
                                $nbrTimes = 0;
                                $minutes = 0;
                                while ($stmt->fetch() ) {
                                    $tblWorkToDoPerDay[$index]['timesSchedule'][]= array();
                                    $tblWorkToDoPerDay[$index]['timesSchedule'][$nbrTimes] ['from'] = $from;
                                    $tblWorkToDoPerDay[$index]['timesSchedule'][$nbrTimes] ['to'] = $to;
                                    $minutes += $this->TimeToInt($to);
                                    $minutes -= $this->TimeToInt($from);
                                    $nbrTimes += 1;
                                }
                                $tblWorkToDoPerDay[$index]['timeToDoMinutes'] = $minutes;
                            }
                        }
                        $stmt->close();
                    }
                }

                //@TODO: DEBUG+TEST change the following code in a way that id modifies directly the table generated above
		// Table with hollidays and days-off taken
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT dateofday FROM " . CConfig::$db_tbl_prefix  . "arbeitstage WHERE user_id = ? AND dateofday >= ? AND dateofday < ? AND holliday_id != 1 ") ) {
			$dbStartDate = date("Y-m-d", $startDate);
			$dbEndDate = date("Y-m-d", $endDate);
			$stmt->bind_param("iss", $user, $dbStartDate, $dbEndDate);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpDateOfDay);
				while ( $stmt->fetch() ) {
					$TempDateParts = substr($tmpDateOfDay, 0, 10);
					$arrTempDateParts = explode("-", $TempDateParts);
					if ( count($arrTempDateParts) == 3 ) {
						$tDate = mktime(0,0,0, $arrTempDateParts[1], $arrTempDateParts[2], $arrTempDateParts[0]);
						//$nameDay = date("l", $tDate);
						$nbrDay = $this->calcDayOfWeek($tDate);
						// get entry in $tblWorkToDoPerDay[], change status to 'not work' and set 'work to do for this day' = 0
						//if ( $nbrDay >= 0 && $nbrDay < 8 ) $workDaysOffMinutes += $tblWorkday[$nbrDay];
                        $indexDay = 0;
                        do {
                            if ( $tblWorkToDoPerDay[$indexDay] == $tDate ) {
                                $tblWorkToDoPerDay[$indexDay]['timeToDoMinutes'] = 0;
                                $indexDay = count($tblWorkToDoPerDay);
                            }
                            $indexDay += 1;
                        } while ( $indexDay < count($tblWorkToDoPerDay) );
					}
				}
			}
		}

		$workToBeDoneMinutes = 0;
		for ($i = 0; $i < count($tblWorkToDoPerDay); $i++) {
		    $workToBeDoneMinutes += $tblWorkToDoPerDay[$i]['timeToDoMinutes'];
        }
				
		$resMinutes = $workDoneMinutes - $workToBeDoneMinutes + $workLastYear;
		
		return $resMinutes;
	}

	function TimeToInt($tvar) {
	    $el = explode(":", $tvar);
	    if (count($el) != 2 ) return 0;
	    $result = intval($el[0]) * 60;
	    $result += intval(el[1]);
	    return $result;
    }

	function calcDayOfWeek($tDate) {
        $nameDay = date("l", $tDate);
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
	
	/**
	 * 
	 * Gets overhours until date for a given user
	 * @param int $user User ID
	 * @param int $day Day
	 * @param int $month Month
	 * @param int $year Year
	 * 
	 * @return Return the number of overhours in MINUTES
	 */
	function getOverhoursUntilDateOld($user, $day, $month, $year) {
		// mktime( [int $Stunde [, int $Minute [, int $Sekunde [, int $Monat [, int $Tag [, int $Jahr [, int $is_dst]]]]]]] )
		$userStartYear = $this->getUserStartYear($user);
		$endDate = mktime(0,0,0, $month, $day, $year );
		$runningDay = 1;
		// need to get user startdate
		//$tempDate = mktime(0,0,0,1,$runningDay, $userStartYear);
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT startdate FROM " . CConfig::$db_tbl_prefix  . "users WHERE id=?") ) {
			$stmt->bind_param("i", $user);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpStartDate);
				if ( $stmt->fetch() ) {
					$tmpStartDate = substr($tmpStartDate, 0, 10);
					
					//$arrStartDate = preg_split("/-/", $tmpStartDate);
					$arrStartDate = explode("-", $tmpStartDate, 3);
					if ( count($arrStartDate) == 3 ) {
						$tempDate = mktime(0,0,0,$arrStartDate[1],$arrStartDate[2], $arrStartDate[0]);
						$stmt->close();
					}
					else {
						return 0;
					}
				}
				else {
					return 0;
				}
			}
		}
		$minutes = 0;
		
		// get overhours last year
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT alteueberstunden FROM " . CConfig::$db_tbl_prefix  . "users WHERE id=?") ) {
			$stmt->bind_param("i", $user);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpHoursLastYear);
				if ( $stmt->fetch() ) $minutes += $tmpHoursLastYear * 60;
			}
			$stmt->close();
		}
		
		if ( $endDate <= $tempDate ) {			
			return $minutes;
		}
		
		do {
			$workDoneMinutes = 0;
			// get work done on that date
			$searchdate = date("Y-m-d", $tempDate);
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			if ( $stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix  . "workday WHERE user_id=? AND date=?") ) {
				$stmt->bind_param("is", $user, $searchdate);
				if ($stmt->execute() ) {
					$stmt->bind_result($tmpHours);
					while ($stmt->fetch()) {
						//$arrWork = preg_split('/:/', $tmpHours, -1, PREG_SPLIT_NO_EMPTY);
						$arrWork = explode(":", $tmpHours);
						if (count($arrWork) == 3 ) {
							$workDoneMinutes += (int)$arrWork[1] + (int)($arrWork[0]*60);
						}
					}				
				}
				$stmt->close();
			}
			// get work that needs to be done on that day
			$minutesToDo = 0;
			// need to get what day we are (1,2,3 ... 7 ), days 5 and 6
			$nameDay = date("l", $tempDate);
			$nbrDay = -1;
			if ( $nameDay == "Sunday") $nbrDay = 7;
			if ( $nameDay == "Monday") $nbrDay = 1;
			if ( $nameDay == "Tuesday") $nbrDay = 2;
			if ( $nameDay == "Wednesday") $nbrDay = 3;
			if ( $nameDay == "Thursday") $nbrDay = 4;
			if ( $nameDay == "Friday") $nbrDay = 5;
			if ( $nameDay == "Saturday") $nbrDay = 6;

			$ttt = -1.0;
			if ( $nbrDay < 6) {
				$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
				if ( $stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix  . "workhours WHERE user=? AND workday=?") ) {
					$stmt->bind_param("ii", $user, $nbrDay);
					if ( $stmt->execute() ) {
						$stmt->bind_result($tmpWorkhours);
						if ( $stmt->fetch() ) {
							$ttt = $tmpWorkhours;
							$minutesToDo = 60.0 * (double) $tmpWorkhours;
						}
					}
					$stmt->close();	
				}
			}
			
			// get holliday state, 1 is normal work (so count hours to be done) everything else is a day off, so no hours have to be done
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			if ( $stmt->prepare("SELECT holliday_id FROM " . CConfig::$db_tbl_prefix  . "arbeitstage WHERE user_id=? AND dateofday=?") ) {
				$searchdate = $searchdate . " 00:00:00";
				$stmt->bind_param("is", $user, $searchdate);
				if ( $stmt->execute() ) {
					$stmt->bind_result($tmpHolliday);
					if ( $stmt->fetch() ) {
						if ( $tmpHolliday == 1 ) {
							// do nothing
						}
						else {
							$minutesToDo = 0;
						}
					}
				}
				$stmt->close();
			}
			else $stmt->close();
			
			// calc changes - if there was more to time to be spent on work, a negative value will be the result
			$minutes += $workDoneMinutes - $minutesToDo;
			//if ( date("Y-m-d", $tempDate) == '2012-01-04' ) return $ttt . " " . $workDoneMinutes ." / " . $minutesToDo;
			
			// increment date
			$tempDate = mktime(0,0,0,date("m", $tempDate),date("d", $tempDate)+1, date("Y", $tempDate));
		} while ($tempDate < $endDate );
		
		return $minutes;
	}
	
	/**
	 * getInfoDay
	 * 
	 * @Params:
	 * $id int id of user
	 * $date date (of day)
	 * 
	 * Returns the data that a user ($id) has already saved so far for this date
	 */
	function getInfoDay($id, $date) {
		$dateparts = explode(".", $date);
		$ndate = mktime(0,0,0, date("m", $dateparts[1]) , date("d", $dateparts[0]), date("Y", $dateparts[2] ) );
		
		

		for ($idx = 0; $idx < 7; $idx++ ) {
			
			//@test: get from to workhours
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			$stmt->prepare("SELECT timefrom, timeto FROM " . CConfig::$db_tbl_prefix  . "timefromto WHERE user_id=? AND dayofday=?");
			$tmpdate = mktime(0,0,0, date("m", $dateparts[1]) , date("d", $dateparts[0])+ $idx, date("Y", $dateparts[2] ) );
			$tmpdate = date("Y-m-d", $tmpdate);
			
			$stmt->bind_param("is", $id, $tmpdate);
			$stmt->execute();
			$tfrom = "";
			$tto = "";
			$stmt->bind_result($tfrom, $tto);
			$idxtimefromto = 0;
			
			while ( $stmt->fetch() ) {
				$jsonarray[$idx]['workhoursdone'][$idxtimefromto] = $tfrom;
				$jsonarray[$idx]['workhourstodo'][$idxtimefromto] = $tto;
			}
			$stmt->close();
			
			//@test: get workareas
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			$stmt->prepare("SELECT A.hours AS hours, B.rank as rank FROM " . CConfig::$db_tbl_prefix  . "workday AS A LEFT JOIN " . CConfig::$db_tbl_prefix  . "workfields AS B ON A.workfield_id=B.id WHERE A.user_id=? AND A.date=?");
			$stmt->bind_param("is", $id, $tmpdate);
			$hours = "";
			$rank = "";
			$stmt->execute();
			$stmt->bind_result($hours, $rank);
			
			while ( $stmt->fetch() ) {
				$jsonarray[$idx]['workarea'][$rank] = $hours;
			}
			$stmt->close();
			
			//@test: get comment
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			$stmt->prepare("SELECT description FROM " . CConfig::$db_tbl_prefix  . "daydescriptions WHERE user_id=? AND workday=? LIMIT 0,1");
			$stmt->bind_param("is", $id, $tmpdate);
			$stmt->execute();
			$descr = "";
			$stmt->bind_result($descr);
			
			if ( $stmt->fetch() ) {
				$jsonarray[$idx]['comment'] = $descr;
			}
			else {
				$jsonarray[$idx]['comment'] = "";
			}
			$stmt->close();
			
			//@todo: get holliday
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			$stmt->prepare("SELECT holliday_id, holliday_text FROM ". CConfig::$db_tbl_prefix  . "arbeitstage WHERE user_id=? AND dateofday=? LIMIT 0,1");
			$stmt->bind_param("is", $id, $tmpdate);
			
			$hollid = "";
			$holltext = "";
			$holldescr = "";
			
			if ( $stmt->execute() ) {
				$stmt->bind_result($hollid, $holltext, $holldescr);
				$stmt->fetch();	
			}
			
			if ( $hollid != "") {
				$jsonarray[$idx]['hollidayid'] = $hollid;
				$jsonarray[$idx]['hollidaytext'] = $holltext;
				$jsonarray[$idx]['hollidaydescr'] = $holldescr;
			}
			else {
				$jsonarray[$idx]['hollidayid'] = "1";
				$jsonarray[$idx]['hollidaytext'] = "";
				$jsonarray[$idx]['hollidaydescr'] = "";
			}
			$jsonarray[$idx]['holliday'];
			$jsonarray[$idx]['hollidaytext'];
			
			
			//@todo: get kilometers
			for ( $d = 0; $d < $travelsdone; $d++ ) {
				$jsonarray[idx]['kmfrom'];
				$jsonarray[idx]['kmto'];
				$jsonarray[idx]['kmdone'];
			}
		}
		
		return json_encode($jsonarary);
		
	}
	
	/**
	 * 
	 * Gives back the work to be done on a day
	 * @param int $userid
	 * @param int $workday Workday, 1:monday, 5: friday
	 * @return array ['hours']['minutes']
	 */
	
	function getWorkToDo($userid, $workday) {
		$hours = 0;
		$minutes = 0;
		$tempHours = 0.0;
		
		if ( $workday > 7 ) {
			$ret = array();
			$ret['minutes'] = 0;
			$ret['hours'] = 0;
			return $ret;
		}
		 
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ($stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix . "workhours WHERE user=? AND workday=?") ) {
			$stmt->bind_param("ii", $userid, $workday);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tempHours);
				if ( $stmt->fetch() ) {
					$hours = $this->getIntpart($tempHours);
					$minutes = $this->getDecpart($tempHours) * 60.0;
					
				} 
				else {
					
				}
			}
			else {
				
			}
			
			$ret = array();
			$ret['minutes'] = round($minutes,2);
			$ret['hours'] = $hours;
			return $ret;
		}
		else {
			$ret = array();
			$ret['minutes'] = 0;
			$ret['hours'] = 0;
			return $ret;
		}
	}
	
	function getIntpart($nbr) {
		$ret = intval($nbr, 10);
		return $ret;
	}
	
	function getDecpart($nbr) {
		$ret = $nbr;
		$ret -= $this->getIntpart($nbr);
		return $ret;
	}
	

	function GetHollidayDescription($userid, $mdate) {
		$dbserver = CConfig::$dbhost;
		$dbuser = CConfig::$dbuser;
		$dbpass = CConfig::$dbpass;
		$dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
	
		$dateMySql = TransformDateToUS($mdate);
	
		$ssql = "SELECT holliday_text FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=$userid AND dateofday='$dateMySql'";
		//return $res;
	
		$res = $dbx->ExecuteSql($ssql);
	
		if ( $res->num_rows > 0) {
			if ( $ff = $res->fetch_row() ) {
				return $ff[0];
			}
		}
	
		return "";
	}
	
	function getUserStartYear($userid) {
		$dbserver = CConfig::$dbhost;
		$dbuser = CConfig::$dbuser;
		$dbpass = CConfig::$dbpass;
		$dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
		$ssql = "SELECT YEAR(startdate) FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
		$res = $dbx->ExecuteSql($ssql);
		if ( $res ) {
			if ( $ff = $res->fetch_row() ) {
				return $ff[0];
			}
		}
	
		return "Jahr nicht gefunden";
	}
	
	function getDisplayName($userid) {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
		$dbx->ExecuteSql("SET NAMES utf8");
		//$dateMySql = TransformDateToUS($mdate);
		$ssql = "SELECT dname FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
		$res = $dbx->ExecuteSql($ssql);
		if ( $res ) {
			if ( $ff = $res->fetch_row() ) {
				return $ff[0];
			}
		}
	
		return "Benutzername nicht gefunden";
	}
	function GetKilometers($userid, $mdate) {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
		$dateMySql = TransformDateToUS($mdate);
		$ssql = "SELECT km, fromwhere, towhere FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=$userid AND day='$dateMySql' ORDER BY id";
	
		$res = $dbx->ExecuteSql($ssql);
		$ret_val = "<script>";
		$counter = 0;
	
		while ($ff = $res->fetch_row() ) {
			$ret_val .= "kmdazu();\n";
			$ret_val .= "document.getElementById('kmvon$counter').value = '". $ff[1] . "'; \n";
			$ret_val .= "document.getElementById('kmbis$counter').value = '". $ff[2] . "'; \n";
			$ret_val .= "document.getElementById('kmanzahl$counter').value = '". $ff[0] . "'; \n";
	
			$counter++;
		}
	
		$ret_val .="</script>";
	
		return $ret_val;
	}
	// B - A
	function TimeSubtract($clockA, $clockB) {
		$cA = explode(":", $clockA);
		$cB = explode(":", $clockB);
	
		if ( $cA[1] > $cB[1] ) {
			$cA[0]++;
			$cB[1] += 60; // one hour
		}
	
		$r_minute = $cB[1] - $cA[1];
		$r_hour = $cB[0] - $cA[0];
	
		return $r_hour . ":" . $r_minute;
	
	}
	
	function TimeSum($clockA, $clockB) {
		$cA = explode(":", $clockA);
		$cB = explode(":", $clockB);
	
		$r_minute = $cA[1] + $cB[1];
		$r_hour = $cA[0] + $cB[0];
		while ( $r_minute >= 60 ) {
			$r_minute -= 60;
			$r_hour++;
		}
	
		return $r_hour . ":" . $r_minute;
	}
	
	function TimeCompare($clockA, $clockB) {
		$cA = explode(":", $clockA);
		$cB = explode(":", $clockB);
	
		if ( $cA[0] < $cB[0] ) {
			return -1;
		}
	
		if ( $cA[0] > $cB[0] ) {
			return 1;
		}
	
		if ( $cA[1] < $cB[1] ) {
			return -1;
		}
	
		if ( $cA[1] > $cB[1] ) {
			return 1;
		}
	
		return 0;
	}
	
	function DateToDay($mdate) {
		$dt = new DateTime($mdate);
	
		if ( ! $dt ) {
			return "Unbekannt";
		}
	
		$ret_val = $dt->format("D");;
	
		$ret_val = str_replace("Mon", "Montag", $ret_val);
		$ret_val = str_replace("Tue", "Dienstag", $ret_val);
		$ret_val = str_replace("Wed", "Mittwoch", $ret_val);
		$ret_val = str_replace("Thu", "Donnerstag", $ret_val);
		$ret_val = str_replace("Fri", "Freitag", $ret_val);
		$ret_val = str_replace("Sat", "Samstag", $ret_val);
		$ret_val = str_replace("Sun", "Sonntag", $ret_val);
		return $ret_val;
	}
	
	function DayToNumber($mday) {
		$ret_val = $mday;
		$ret_val = str_replace("Montag", "1", $ret_val);
		$ret_val = str_replace("Dienstag", "2", $ret_val);
		$ret_val = str_replace("Mittwoch", "3", $ret_val);
		$ret_val = str_replace("Donnerstag", "4", $ret_val);
		$ret_val = str_replace("Freitag", "5", $ret_val);
	
		return $ret_val;
	}
	
	function ConvertFloatToTime($mfloat) {
		$intpart = (int) $mfloat;
	
		$decpart = $mfloat - $intpart;
	
		if ( $decpart < 10 ) {
			$decpart *= 10;
		}
		$decpart = (60 * $decpart) / 100;
	
		if ( $decpart < 10 ) {
			$decpart *= 10;
		}
	
		if ( $decpart == 0 ) {
			$decpart = "00";
		}
	
		$ret_val = $intpart . ":" . $decpart;
		return $ret_val;
	}
	
	function ConvertTimeToFloat($mtime) {
		$timeparts = explode(":", $mtime);
		$ret_val = $timeparts[0];
		$ret_val += ($timeparts[1] / 3) * 5;
	
		return $ret_val;
	}
	
	function GetDayStatusAll() {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
	
		$ssql = "SELECT id, beschreibung, typ FROM " . CConfig::$db_tbl_prefix . "holliday";
	
		$resquery = $dbx->ExecuteSQL($ssql);
	
		$ret_val = array();
		$a = 0;
	
		while ( $gg = $resquery->fetch_row() ) {
	
			$ret_val[$a][] = $gg[0];
			$ret_val[$a][] = $gg[1];
			$ret_val[$a][] = $gg[2];
	
			$a++;
		}
	
		return $ret_val;
	}
	
	function GetTimeFromTo($userid, $date) {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
	
		$dateMySql = TransformDateToUS($date);
		$ssql = "SELECT timefrom, timeto FROM " . CConfig::$db_tbl_prefix . "timefromto WHERE user_id=$userid AND dateofday='$dateMySql' ORDER BY timefrom";
		$res = $dbx->ExecuteSql($ssql);
		$ret_val = array();
		$a = 0;
	
		while ( $ff = $res->fetch_row() ) {
			$ret_val[$a][0] = substr($ff[0], 0, 5);
			$ret_val[$a][1] = substr($ff[1], 0, 5);
			$a++;
		}
	
		return $ret_val;
	}
	
	//! Gives the start date for a given week in a given year
	
	/**
	 * 
	 * @param int $week Week to look for
	 * @param int $year Year to look for
	 * @return string Formatted date string (Y-m-D)
	 */
	
	function CalendarWeekStartDate($week, $year) {
	
		date_default_timezone_set ( "Europe/Brussels" );
	
		$firstofyear = mktime(0,0,0, 1,1, $year);
	
		$firstday = date("D", $firstofyear);
	
		$firstday = str_replace("Mon", "1", $firstday);
		$firstday = str_replace("Tue", "2", $firstday);
		$firstday = str_replace("Wed", "3", $firstday);
		$firstday = str_replace("Thu", "4", $firstday);
		$firstday = str_replace("Fri", "5", $firstday);
		$firstday = str_replace("Sat", "6", $firstday);
		$firstday = str_replace("Sun", "7", $firstday);
	
	
		$firstday--;
	
		$startday = mktime(0,0,0, date("m", $firstofyear), date("d", $firstofyear) - $firstday, date("y", $firstofyear) );
	
		if ( $week == 1 ) {
			return date("Y-m-d", $startday);
		}
	
		$myweek = 1;
	
		do {
			$startday = mktime(0,0,0, date("m", $startday), date("d", $startday) + 7, date("y", $startday) );
	
			$myweek++;
	
	
		} while ( $week != $myweek );
	
		return date("Y-m-d", $startday);
	}
	
	//! Get all actions a person made with his / her car on a given day
	
	/**
	 * 
	 * @param int $user User ID
	 * @param int $day Day
	 * @param int $month Month
	 * @param int $year Year
	 * @return multitype  | Array with all caractions done on a day by a person OR null if no actions are done
	 */
	
	public function getCarActions($user, $day, $month, $year) {
		$mdate = mktime(0,0,0, $month, $day, $year);
		$mdate = date("Y-m-d", $mdate);
		
		// prepare datastructure
		$ret = array();
		$retFrom = array();
		$retTo = array();
		$retKm = array();
		
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ($stmt->prepare("SELECT fromwhere, towhere, km FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=? AND day=? ORDER BY id ASC") ) {
			$stmt->bind_param("is", $user, $mdate);
			$stmt->execute();
			$stmt->bind_result($tmpFrom, $tmpTo, $tmpKm);
			while ( $stmt->fetch() ) {
				//$retFrom[] = utf8_decode( $tmpFrom );
				//$retTo[]   = utf8_decode( $tmpTo );
				$retFrom[] = $tmpFrom ;
				$retTo[]   = $tmpTo;
				$retKm[]   = $tmpKm;
				//echo "$tmpFrom $tmpTo $tmpKm";
			}
			
			$ret['from'] = $retFrom;
			$ret['to']   = $retTo;
			$ret['km'] = $retKm;
			
			$stmt->close();
			return $ret;
		}
		
		else {
			return "";
		}
		
	}
	
	/**
	 * 
	 * Retrieves all information from the database for a given user and workday.
	 * @param int $user
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return array All information available at the moment
	 */
	
	public function getWorkDay($user, $day, $month, $year, $workday) {
		$mdate = mktime(0,0,0, $month, $day, $year);
		//$mdate = date("Y.m.d", $mdate);
		
		//prepare datastructure
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		
		$ret = array();
		$ret['caractions'] = $this->getCarActions($user, $day, $month, $year);
		
		$ret['times'] = $this->getTimes($user, $day, $month, $year);
		
		$ret['workdoneinareas'] = $this->getWorkDoneInAreas($user, $day, $month, $year);
		
		$ret['holliday'] =  $this->getHollidayState($user, $day, $month, $year);
		
		$ret['workhourstodo'] = $this->getWorkToDo($user, $workday);
		
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		$tcomment = $this->getDayComment($user, $day, $month, $year);
		if ($tcomment == "" ) $tcomment = " ";
		$tcomment = str_replace("<br>", "\n", $tcomment);
		$ret['comment'] = utf8_decode( $tcomment );
		$ret['date']    = date("d.m.Y", $mdate);
		
		// give back datastucture
		return $ret;
	}
	
	public function getRemainHolliday($user, $day, $month, $year) {
		$nbr = 0;
		$dd = $year . "-" . $month . "-" . $day . " 00:00:00"; 
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT urlaubstage FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?") ) {
			$stmt->bind_param("i", $user);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpHolliday);
				if ( $stmt->fetch() ) $nbr = $tmpHolliday;
			}
			$stmt->close();
		}
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT COUNT(user_id) FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND holliday_id=2 AND dateofday<?") ) {
			$stmt->bind_param("is", $user, $dd);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpTaken);
				if ($stmt->fetch() ) {
					$nbr -= (int) $tmpTaken;
				}
			}
			$stmt->close();
		}
		return $nbr;
	}
	
	public function getRemainVacation($user, $day, $month, $year) {
		$nbr = 0;
		$dd = $year . "-" . $month . "-" . $day . " 00:00:00";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT feiertage FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?") ) {
			$stmt->bind_param("i", $user);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpHolliday);
				if ( $stmt->fetch() ) $nbr = $tmpHolliday;
			}
			$stmt->close();
		}
	
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT COUNT(user_id) FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND holliday_id=3 AND dateofday<?") ) {
			$stmt->bind_param("is", $user, $dd);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpTaken);
				if ($stmt->fetch() ) {
					$nbr -= (int) $tmpTaken;
				}
			}
			$stmt->close();
		}
		return $nbr;
	}
	
	//! Returns an array with 7 days (workweek), startdate of the week, week number etc.
	
	/**
	 * 
	 * @param int $user
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * 
	 * @return array 7 days, startdate, weeknumber, etc.
	 */
	
	public function getWorkWeek($user, $day, $month, $year) {
		$mdate = mktime(0,0,0, $month, $day, $year);
		$tempDate = $mdate; // = date("Y-m-d", $mdate);
		
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		
		$ret = array();
		$ret['workday'] = array();

		for ( $i = 0; $i < 7; $i++) {
			$ret['workday'][] = $this->getWorkDay($user, date("d", $tempDate), date("m", $tempDate), date("Y", $tempDate), ($i+1) );
			$tempDate = mktime(0,0,0,date("m", $tempDate), date("d", $tempDate)+1, date("Y", $tempDate));
		}
		
		// workareas
		$ret['workareas'] = $this->GetWorkfieldsAll($user);
		
		// over hours
		$tmpOverminutes = $this->getOverhoursUntilDate($user, $day, $month, $year);
		
		if ($tmpOverminutes >= 0) {
			$tmpOverhours = 0;
			while ( $tmpOverminutes >= 60) {
				$tmpOverminutes -= 60;
				$tmpOverhours++;
			}
			if ( $tmpOverminutes < 10 )	$ret['overhoursbeforeweek'] = $tmpOverhours . ":0" . $tmpOverminutes;
			else $ret['overhoursbeforeweek'] = $tmpOverhours . ":" . $tmpOverminutes;
		}
		else {
			$tmpOverhours = 0;
			$tmpOverminutes *= -1.0;
			while ( $tmpOverminutes >= 60) {
				$tmpOverminutes -= 60;
				$tmpOverhours++;
			}
			if ($tmpOverminutes < 10 ) $ret['overhoursbeforeweek'] = "-" . $tmpOverhours . ":0" . $tmpOverminutes;
			else $ret['overhoursbeforeweek'] = "-" . $tmpOverhours . ":" . $tmpOverminutes;
		}
		
		$ret['remainholliday'] = $this->getRemainHolliday($user, $day, $month, $year);
		
		$ret['remainvacation'] = $this->getRemainVacation($user, $day, $month, $year);
		
		$tmpDate = mktime(0,0,0, $month, $day+7, $year);
		
		$ret['hollidaynow'] = $this->getRemainHolliday($user, date("d", $tmpDate), date("m", $tmpDate) , date("Y", $tmpDate) );
		$ret['vacationnow'] = $this->getRemainVacation($user, date("d", $tmpDate), date("m", $tmpDate) , date("Y", $tmpDate) );
		$tmpOverminutesnow = $this->getOverhoursUntilDate($user, date("d", $tmpDate), date("m", $tmpDate) , date("Y", $tmpDate) );
		if ($tmpOverminutesnow >= 0) {
			$tmpOverhoursnow = 0;
			while ( $tmpOverminutesnow >= 60) {
				$tmpOverminutesnow -= 60;
				$tmpOverhoursnow++;
			}
			if ( $tmpOverminutesnow < 10 )	$ret['overhours'] = $tmpOverhoursnow . ":0" . $tmpOverminutesnow;
			else $ret['overhours'] = $tmpOverhoursnow . ":" . $tmpOverminutesnow;
		}
		else {
			$tmpOverhoursnow = 0;
			$tmpOverminutesnow *= -1.0;
			while ( $tmpOverminutesnow >= 60) {
				$tmpOverminutesnow -= 60;
				$tmpOverhoursnow++;
			}
			if ($tmpOverminutesnow < 10 ) $ret['overhours'] = "-" . $tmpOverhoursnow . ":0" . $tmpOverminutesnow;
			else $ret['overhours'] = "-" . $tmpOverhoursnow . ":" . $tmpOverminutesnow;
		}
		
		// Kilometers
		$kmEndDate = mktime(0,0,0,$month, $day+6, $year);
		$kmEndDate = date("Y-m-d", $kmEndDate);
		$kmStartDate = mktime(0,0,0, 1, 1, $year);
		$kmStartDate = date("Y-m-d", $kmStartDate);
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		
		if ( $stmt->prepare("SELECT SUM(km) FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id = ? AND day >= ? AND day < ?") ) {
			$stmt->bind_param("iss", $user, $kmStartDate, $kmEndDate);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpKmTotal);
				if ( $stmt->fetch() ) {
					$ret['kmtotal'] = $tmpKmTotal;
				}
				else {
					$ret['kmtotal'] = 0;
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * 
	 * Gets the description for a given day of a given user
	 * @param int $user
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 */
	public function getDayComment($user, $day, $month, $year) {
		$mdate = mktime(0,0,0, $month, $day, $year);
		$mdate = date("Y-m-d", $mdate);
		
		$ret = "";
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT description FROM " . CConfig::$db_tbl_prefix . "daydescriptions WHERE user_id=? AND workday=?")) {
			$stmt->bind_param("is", $user, $mdate);
			if ($stmt->execute() ) {
				$stmt->bind_result($tmpDescription);
				if ( $stmt->fetch() ) {
					$ret = $tmpDescription;
					$stmt->close();
					$ret = utf8_encode($ret);
					return $ret;
				}
				else {
					$ret = "";
					$stmt->close();
					return $ret;
				}
				$stmt->close();
			}
		}
		
		return "";
	}
	
	function GetHollidayStateForUser($userid, $date) {

        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
	
		$dateMySql = TransformDateToUS($date);
		$ssql = "SELECT holliday_id FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=$userid AND dateofday='$dateMySql'";
	
		$myres = $dbx->ExecuteSql($ssql);
	
		if ( $gg = $myres->fetch_row() ) {
			return $gg[0];
		}
		else {
			return 1; // default
		}
	}
	
	function GetDescription($userid, $mdate) {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;
		$max_rank_workfields = CConfig::$max_rank_workfields;
	
	
		$dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
	
		$MyDate = TransformDateToUS($mdate);
	
		$ssql = "SELECT description FROM " . CConfig::$db_tbl_prefix . "daydescriptions WHERE user_id=$userid AND workday='$MyDate'";
		//echo "<p>$ssql</p>";
		//return $ssql;
		$res = $dbx->ExecuteSql($ssql);
	
		if ($res) {
			$ff = $res->fetch_row();
			return $ff[0];
		}
	
		else {
			return "";
		}
	
	}
	
	/**
	 * 
	 * Gets all work done on a day out of the different workareas 
	 * @param int $userid
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return array Array with all work done on the given day for the given user
	 */
	
	function getWorkDoneInAreas($userid, $day, $month, $year) {
	
		$max_rank_workfields = CConfig::$max_rank_workfields;
		
		$mdate = mktime(0,0,0, $month, $day, $year);
		$mdate = date("Y-m-d", $mdate);
		
		$ret = array();
		$ret['id'] = array();
		$ret['time'] = array();
		
		$tmpId = -1;
		$tmpHours = "00:00";
	
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "SELECT workfield_id, hours FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=? AND date=? ORDER BY workfield_id";
		//"SELECT workfield_id, hours FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=? AND date=? ORDER BY workfield_id"
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("is", $userid, $mdate);
			if ( $stmt->execute() )
			{
				$stmt->bind_result($tmpId, $tmpHours);
				$i = 0;
				if ( $stmt->fetch() ) {
					for ( $i ; $i < $max_rank_workfields; $i++ ) {
						if ( $i == $tmpId ) {
							$ret['id'][]   = $tmpId;
							$ret['time'][] = $tmpHours;
							if ( $stmt->fetch() ) {
								
							}
							else {
								$tmpId = -1;
							}
						}
						else {
							$ret['id'][]   = -1;
							$ret['time'][] = "00:00";
						}
					}
				}	
				
				
				$stmt->close();
				return $ret;
			}
			$stmt->close();
		}
		
		return null;
		
		//return 0;
	}
	
	function HTMLSelectHolliday($userid, $date) {
		$TableHolliday = GetDayStatusAll();
		$iHolliday = count($TableHolliday);
		$iHollidayIdSelected = GetHollidayStateForUser($userid, $date);
	
		$ret_val = "";
		$ret_val .= "<select name=\"urlaub\">";
	
		for ( $a = 0; $a < $iHolliday; $a++ ) {
	
			if ( $TableHolliday[$a][0] == $iHollidayIdSelected ) {
				$ret_val .= "<option value=\"" . $TableHolliday[$a][0] . "\" selected> " . $TableHolliday[$a][1] . "</option>";
			}
			else {
				$ret_val .= "<option value=\"" . $TableHolliday[$a][0] . "\" > " . $TableHolliday[$a][1] . "</option>";
			}
		}
	
		$ret_val .= "</select>";
		return $ret_val;
	}
	
	//! Get all workfields for a given user
	
	/**
	 * @param int $user ID to search for
	 * @return array All the workfields, even if they are empty
	 *
	 */
	function GetWorkfieldsAll($user) {
		
		$max_rank_workfields = CConfig::$max_rank_workfields;
		$ranked = array();
		$tmpRank = -1;
		$tmpExplanation = "";
		$tmpDescription = "";
		$tmpUser = -1;
		$tmpTimecapital = "";
		
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		
		$sqlSelect = "SELECT rank, explanation, description, user, timecapital FROM " . CConfig::$db_tbl_prefix . "workfields WHERE user=? ORDER BY rank";
		
		if ( $stmt->prepare($sqlSelect) ) {
			$stmt->bind_param("i", $user);
			
			$stmt->execute();
			//echo "<p>rows: " . $stmt->num_rows . "</p>";
			$stmt->bind_result($tmpRank, $tmpExplanation, $tmpDescription, $tmpUser, $tmpTimecapital);
			if ( !$stmt->fetch() ) {
			}
			
			for ( $i = 0; $i < $max_rank_workfields; $i++ ) {
	
				if ( $tmpRank == $i ) {
					$ranked[$i][0] = $tmpRank;
					//$ranked[$i][1] = utf8_encode( $tmpExplanation );
					//$ranked[$i][1] = utf8_decode($tmpExplanation);
					$ranked[$i][1] = $tmpExplanation;
					//$ranked[$i][2] = utf8_decode($tmpDescription);
					$ranked[$i][2] = $tmpDescription;
					$ranked[$i][3] = $tmpUser;
					$ranked[$i][4] = $tmpTimecapital;
					if (0 == $stmt->fetch() ) {
						$tmpRank = -1;
						//echo "<p>$tmpRank</p>";
					}
					else {
						
					}
				}
		
				else {
					$ranked[$i][0] = $i;
					$ranked[$i][1] = "";
					$ranked[$i][2] = "";
					$ranked[$i][3] = "";
					$ranked[$i][4] = "0";
				}
				//echo "<p> " . $stmt->error . "$tmpRank</p>";
			}
			
			$stmt->close();
		}
		else {
			echo "error!!!";
		}
		echo $this->dbx->getDatabaseConnection()->error;
		
		return $ranked;
	
	}
	
	function GetUserInfo($uid) {
		$retArray = array();
		
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		$this->dbx->getDatabaseConnection()->query("USE " . CConfig::$dbname);
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		
		$sqlSelect = "SELECT id, uname, email, reg_date, session_id, password, status, alteueberstunden, feiertage, urlaubstage, kmsatz, startdate, dname, report_year FROM " . CConfig::$db_tbl_prefix . "users WHERE id=? LIMIT 1"; // $uid
		
		if (! $stmt->prepare($sqlSelect) ) {
			echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
			die;
		}
		
		if ( ! $stmt->bind_param("i", $uid) ) {
			echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
			die;
		}
		
		if ( ! $stmt->execute() ) {
			echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
			die;
		}
		
		if ( ! $stmt->bind_result($tmpId, $tmpUname, $tmpEmail, $tmpRegdate, $tmpSessionid, $tmpPassword, $tmpStatus, $tmpAlteueberstunden, $tmpFeiertage, $tmpUrlaubstage, $tmpKmsatz, $tmpStartdate, $tmpDname, $tmpReportYear) ) {
			die;
		}
		
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";
		$retArray[] = "";

		if ( $stmt->fetch() ) {
			$retArray[0] = $tmpId;
			$retArray[1] = $tmpUname;
			$retArray[2] = $tmpEmail;
			$retArray[3] = $tmpRegdate;
			$retArray[4] = $tmpSessionid;
			$retArray[5] = $tmpPassword;
			$retArray[6] = $tmpStatus;
			$retArray[7] = $tmpAlteueberstunden;
			$retArray[8] = $tmpFeiertage;
			$retArray[9] = $tmpUrlaubstage;
			$retArray[10] = $tmpKmsatz;
			$retArray[11] = $tmpStartdate;
			$retArray[12] = $tmpDname;
			$retArray[13] = $tmpReportYear;
		}
		$stmt->close();
		return $retArray;
	}

	
	function GetUserWorkDays($uid) {
	
		$this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
		$this->dbx->getDatabaseConnection()->query("USE " . CConfig::$dbname);
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sqlSelect = "SELECT hours, workday FROM " . CConfig::$db_tbl_prefix . "workhours WHERE user LIKE ? ORDER BY workday"; // $uid
		if (! $stmt->prepare($sqlSelect) ) {
			echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
			die;
		}
		
		if ( ! $stmt->bind_param("i", $uid) ) {
			echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
			die;
		}
		
		if ( ! $stmt->execute() ) {
			echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
			die;
		}
		
		$stmt->bind_result($day, $hours);
		
		$i = 0;
		$retarray = array();
		
		while ( $row = $stmt->fetch() ) {
			$retarray[$i] = array();
			$retarray[$i][1] = $day; //$row[1];	      // workday
			$retarray[$i][0] = (double) $hours; //(double) $row[0];       // hours
			$i += 1;
		}
		
		//$i = 0;
		
		while ( $i < 7 )
		{
			$retarray[$i][0] = $i+1;
			$retarray[$i][1] = 0.0;
			$i++;
		}
		
		$stmt->close();
		return $retarray;
	}
	
	
	
	function TransformUserToId($username) {
		include_once(__DIR__ . "/includes/db_connect.php");

		//$db_conn = mysql_connect($dbserver, $dbuser, $dbpass);
	
		if ( ! $db ) {
			die("Keine Verbindung zur Datenbank m&ouml;glich");
		}
	
		$db->query("USE " . CConfig::$dbname);
	
		//$sqlSelect = "SELECT * FROM " . CConfig::$db_tbl_prefix . "users WHERE uname LIKE '" . $username . "' LIMIT 1";
		$sqlSelect = "SELECT id FROM " . CConfig::$db_tbl_prefix . "users WHERE uname LIKE ? LIMIT 1";
		$stmt = $db->stmt_init();

		if ( ! isset($stmt) ) {
			echo "<p>Unable to connect to database, class helper, member TransformUserToId</p>";
			die;
		}

		if ( ! $stmt->prepare($sqlSelect) ) {
			echo "<p>Unable to prepare statement, class helper, member TransformUserToId</p>";
			die;
		}	

		if ( ! $stmt->bind_param("s", $username) ) {
			echo "<p>Unable to bind parameter, class helper, member TransformUserToId</p>";
			die;
		}


		if ( ! $stmt->execute() ) {
			echo "<p>Unable to execute query, class helper, member TransformUserToId</p>";
			echo "<p>" . $db->error . "</p>";
			die;
		}

		$stmt->bind_result($id);
		
		if ( $stmt->fetch() ) { 
			$stmt->close();
			$db->close();

			return $id;
		}
	
		else {
			$stmt->close();
			$db->close();
			return 0;
		}
	}
	
	function TransformDateToBelgium($inputdate) {
		$startdatum = $inputdate;
		$startdatum = substr($startdatum, 0, 10);
		$startjahr = substr($startdatum, 0, 4);
		$startmonat = substr($startdatum, 5, 2);
		$starttag = substr($startdatum, 8, 2);
	
		$startdatum = $starttag . "." . $startmonat . "." . $startjahr;
	
		return $startdatum;
	
	}
	
	function TransformDateToUS($inputdate) {
		$startdatum = $inputdate;
		$startdatum = substr($startdatum, 0, 10);
		$startjahr = substr($startdatum, 6, 4);
		$startmonat = substr($startdatum, 3, 2);
		$starttag = substr($startdatum, 0, 2);
	
		$startdatum = $startjahr . "-" . $startmonat . "-" . $starttag;
	
		return $startdatum;
	
	}
	
	function EntetiesToCharacters($sObj) {
		$ret = str_replace("&uuml;", "\u00fc", $sObj);
		
		return $ret;
	}
	
	function CharactersToEnteties($sObj) {
		$ret = str_replace("\u00fc", "&uuml;", $sObj);
		
		return $ret;
	}
	
	public function generateKmInvoiceTable($user) {
		$this->dbx->ExecuteSQL("CREATE TABLE IF NOT EXISTS " . CConfig::$db_tbl_prefix . "kminvoiced  ( \n 
		id     int(10) unsigned NOT NULL AUTO_INCREMENT, \n
		userid   int(11) unsigned NOT NULL, \n
		kmweek int(11) unsigned default NULL, \n
		PRIMARY KEY(id) ) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8 ;");
		
		echo $this->dbx->getDatabaseConnection()->error . "<br>";
		$a = 0;
		$fromWeek = 1;
		$toWeek = 53;
		$tblWeeks = array();
		$tblWeeks['from'] = array();
		$tblWeeks['to'] = array();
		$tblWeeks['km'] = array();
		
		$ret = "<table class=\"kminvoice\">";
		$ret .= "<tr><td>Woche von</td><td>Woche bis</td><td>KM</td></tr>";
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT kmweek FROM " . CConfig::$db_tbl_prefix . "kminvoiced WHERE userid=? ORDER BY kmweek LIMIT 0,24")) {
			$stmt->bind_param("i", $user);
			if ( $stmt->execute() ) {
				$stmt->bind_result($toWeek);
				while ( $stmt->fetch() ) {
					$tblWeeks['from'][] = $fromWeek;
					$tblWeeks['to'][] = $toWeek;
					$fromWeek = $toWeek + 1;
					$a++;
					
				}
			}
			$stmt->close();
		}
		else {
			echo $stmt->error;
		}
		
		
		for ($b = 0; $b < $a; $b++ ) {
			$tblWeeks['km'][$b] = $this->getKmBetween($user,$tblWeeks['from'][$b], $tblWeeks['to'][$b] );
		}
		
		
		for ($b = 0; $b < 24; $b++ ) {
			if ( $b < $a ) {
				$ret .= "<tr>";
				$ret .= "<td><input type=\"text\" name=\"kmfrom[]\" size=\"3\" value=\"" . $tblWeeks['from'][$b] . "\"></td>";
				$ret .= "<td><input type=\"text\" name=\"kmto[]\" size=\"3\" value=\"" .   $tblWeeks['to'][$b] . "\"></td>";
				$ret .= "<td><input type=\"text\" name=\"km[]\" size=\"4\" value=\"" .     $tblWeeks['km'][$b] . "\"></td>";
				$ret .= "</tr>";
			}
			else {
				$ret .= "<tr>";
				$ret .= "<td><input type=\"text\" name=\"kmfrom[]\" size=\"3\"></td>";
				$ret .= "<td><input type=\"text\" name=\"kmto[]\" size=\"3\"></td>";
				$ret .= "<td><input type=\"text\" name=\"km[]\" size=\"4\"></td>";
				$ret .= "</tr>";
			}
			
		}
		
		$ret .= "</table>";
		$ret .= $this->dbx->getDatabaseConnection()->error;
		return $ret;
		
	}
	
	function getKmBetween($user, $wstart, $wend) {
		$km = 0;
		$sdate = $this->CalendarWeekStartDate($wstart, $this->getUserStartYear($user) );
		$edate = $this->CalendarWeekStartDate($wend, $this->getUserStartYear($user) );
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT SUM(km) FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=? AND day >= ? AND day <= ?") ) {
			$stmt->bind_param("iss", $user, $sdate, $edate);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpkm);
				if ( $stmt->fetch() ) {
					$km = $tmpkm;
				}
			}
			$stmt->close();
		}

		return $km;
	}
	
	function DumpDatabase() {
		$tables = array();
		
		$fp = fopen("dump2.sql", "w");
		
		if ( !$fp ) {
			echo "Can't write to file!";
			return;
		}
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SHOW TABLES") ) {
			if ( $stmt->execute() ) {
				$stmt->bind_result($itm);
				while ( $stmt->fetch() ) {
					$tables[] = $itm;
				}
			}
			$stmt->close();
		}
		
		else {
			echo "xxx";
			return;
		}
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$t = null;
		if ( $stmt->prepare("SHOW CREATE TABLE ?") ) {
			foreach ( $tables as $t) {
				$stmt->bind_param("s", $t);
			
				if ( $stmt->execute() ) {
					$stmt->bind_result($itm);
					while ( $stmt->fetch() ) {
						echo "# Table: $t<br>";
						fwrite($fp, "# Table: $t\n");
						fwrite($fp, $itm);
					}
					
					$stmt->close();
				}
				
				else {
					echo "yyy";
					return;
				}
			}
			
		}
		
		fclose($fp);	
	}
	
	//! Get the invoice setup data
	
	/**
	 * @param int $user ID to search for
	 * @return array All default fields
	 *
	 */
	 
	function getInvoiceSetup($id) {
		$ret = array();
		
		$ret['msg'] = "ok";
		$ret['msgcode'] = 0;
		
		// check if table exists, if not create it
		$sql = "CREATE TABLE IF NOT EXISTS " . CConfig::$db_tbl_prefix . "invsetup (id BIGINT(20) NOT NULL auto_increment, \n";
		$sql .= "idUser BIGINT(20) NOT NULL, \n";
		$sql .= "defAddress VARCHAR(200), \n";
		$sql .= "defZipAddr VARCHAR(10), \n";
		$sql .= "defCityAddr VARCHAR(40), \n";
		$sql .= "defNameRecv VARCHAR(200), \n";
		$sql .= "defAddressRecv VARCHAR(200), \n";
		$sql .= "defZipRecv VARCHAR(10), \n";
		$sql .= "defCityRecv VARCHAR(40), \n";
		$sql .= "defEmailRecv VARCHAR(250), \n";
		$sql .= "defAccHolder VARCHAR(200), \n";
		$sql .= "defAccNumber VARCHAR(50), \n";
		$sql .= "defPaymentMethod TINYINT(1), \n";
		$sql .= "PRIMARY KEY (id)";
		$sql .= ") ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		
		if ( $stmt->prepare($sql) ) {
			if ( $stmt->execute() ) {
			}
			else {
				$ret['msg'] = "could not create table";
				$ret['msgcode'] = 1;
				return $ret;
			}
		}
		else {
			$ret['msg'] = $stmt->error . " could not create table (2)";
			$ret['msgcode'] = 2;
			return $ret;
		}
		
		$stmt->close();
		
		$sql = "SELECT id, idUser, defAddress, defZipAddr, defCityAddr, defNameRecv, defAddressRecv, defZipRecv, defCityRecv, defEmailRecv, defAccHolder, defAccNumber, defPaymentMethod FROM " . CConfig::$db_tbl_prefix;
		$sql .= "invsetup WHERE idUser=?";
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("i", $id);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpId, $tmpIdUser, $tmpAddress, $tmpZip, $tmpCity, $tmpRecvName, $tmpRecvAddress, $tmpRecvZip, $tmpRecvCity, $tmpRecvEmail, $tmpAccountHolder, $tmpAccountNumber, $tmpPaymentMethod);
				if ( $stmt->fetch() ) {
					$ret['id'] = $tmpId;
					$ret['idUser'] = $tmpIdUser;
					$ret['address'] = $tmpAddress;
					$ret['zip'] = $tmpZip;
					$ret['city'] = $tmpCity;
					$ret['recvName'] = $tmpRecvName;
					$ret['recvAddress'] = $tmpRecvAddress;
					$ret['recvZip'] = $tmpRecvZip;
					$ret['recCity'] = $tmpRecvCity;
					$ret['recEmail'] = $tmpRecvEmail;
					$ret['accountHolder'] = $tmpAccountHolder;
					$ret['accountNumber'] = $tmpAccountNumber;
					$ret['paymentMethod'] = $tmpPaymentMethod;
				}
				else {
					$ret['id'] = -1;
					$ret['idUser'] = $id;
					$ret['address'] = "";
					$ret['zip'] = "1000";
					$ret['city'] = "";
					$ret['recvName'] = "";
					$ret['recvAddress'] = "";
					$ret['recvZip'] = "";
					$ret['recCity'] = "";
					$ret['recEmail'] = "";
					$ret['accountHolder'] = "";
					$ret['accountNumber'] = "";
					$ret['paymentMethod'] = 1; // pay cash
				}
				
				$stmt->close();
				return $ret;
			}
		}
		else {
			$ret['msg'] = "Error while getting data: " . $stmt->error;
			$ret['msgcode'] = 3;
			return $ret;
		}
	}
	
	function newInvoiceSetup($userid, $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod) {
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "INSERT INTO " . CConfig::$db_tbl_prefix . "invsetup (idUser, defAddress, defZipAddr, defCityAddr, defNameRecv, defAddressRecv, defZipRecv, defCityRecv, defEmailRecv, defAccHolder, defAccNumber, defPaymentMethod) ";
		$sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		if ( $stmt->prepare($sql) ){
			$stmt->bind_param("issssssssssi", $userid, $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod);
			
			if ( $stmt->execute() ) {
				$stmt->close();
				return 0; // success
			}
			
			else {
				return 1; // error number 1
			}
		}
		
		else {
			//return 2; // error number 2
			return $stmt->error;
		}
	}
	
	function updateInvoiceSetup($id, $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod) {
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "UPDATE " . CConfig::$db_tbl_prefix . "invsetup set defAddress=?, defZipAddr=?, defCityAddr=?, defNameRecv=?, defAddressRecv=?, defZipRecv=?, defCityRecv=?, ";
		$sql .= "defEmailRecv=?, defAccHolder=?, defAccNumber=?, defPaymentMethod=? WHERE id=?";
		
		if ($stmt->prepare($sql)) {
			$stmt->bind_param("ssssssssssii", $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod, $id);
			if ( $stmt->execute() ) {
				$stmt->close();
				return 0; // success
			}
			else {
				return 3;
			}
		}
		else {
			return 4;
		}
	}
	
	// Creates a new invoice with todays date
	// 
	// Parameter:
	// $id ID User
	
	// Return value
	// idInvoice
	function createNewInvoice($id) {
		// Create the table if it does not exist
		$sql = "CREATE TABLE IF NOT EXISTS " . CConfig::$db_tbl_prefix . "invoices (idInvoice BIGINT(20) NOT NULL auto_increment, \n";
		$sql .= "idUser BIGINT(20) NOT NULL, \n";
		$sql .= "invoicedate DATE, \n";
		$sql .= "invoicelabel VARCHAR(200), \n";
		$sql .= "invFromName VARCHAR(50), \n";
		$sql .= "invFromFirstName VARCHAR(50), \n";
		$sql .= "invFromAddress VARCHAR(200), \n";
		$sql .= "invFromZip VARCHAR(10), \n";
		$sql .= "invFromCity VARCHAR(200), \n";
		$sql .= "invToName VARCHAR(200), \n";
		$sql .= "invToAddress VARCHAR(200), \n";
		$sql .= "invToZip VARCHAR(200), \n";
		$sql .= "invToCity VARCHAR(200), \n";
		$sql .= "invAccountNumber VARCHAR(200), \n";
		$sql .= "invAccountHolder VARCHAR(200), \n";
		$sql .= "invPaymentMethod TINYINT(1), \n";
		$sql .= "PRIMARY KEY (idInvoice)";
		$sql .= ") ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		
		if ( $stmt->prepare($sql) ) {
			if ( $stmt->execute() ) {
			}
			else {
				$ret['msg'] = "could not create table";
				$ret['msgcode'] = 1;
				return $ret;
			}
		}
		else {
			$ret['msg'] = $stmt->error . " could not create table (2)";
			$ret['msgcode'] = 2;
			return $ret;
		}
		
		$stmt->close();
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "INSERT INTO " . CConfig::$db_tbl_prefix . "invoices (idUser, invoicedate, invoicelabel) VALUES (?, NOW(), 'neue Rechnung ohne Label')";
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("i", $id);
			if ( $stmt->execute() ) {
			}
			
			else {
				return -1;
			}
		}
		
		else {
			return -2;
		}
		
		$stmt->close();
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "SELECT MAX(idInvoice) FROM " . CConfig::$db_tbl_prefix . "invoices WHERE idUser=?";
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("i", $id);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpMaxId);
				if ( $stmt->fetch() ) {
					$stmt->close();
					return $tmpMaxId;
				}
				else {
					return -3;
				}
			}
			else {
				return -4;
			}
		}
		else {
			return -5;
		}
		$stmt->close();
		return -6; // should never reach here
		
	}
	
	// Build list with all invoices from user
	// Parameter:
	// $id User
	
	// Return value
	// Array with all invoices: id, date, invoicelabel
	function listInvoices($id) {
		$ret = array();
		//$ret['invoices'] = array();
		$i = 0;
		
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "SELECT idInvoice, invoicedate, invoicelabel FROM " . CConfig::$db_tbl_prefix . "invoices WHERE idUser=? ORDER BY invoicedate";
		if ( $stmt->prepare($sql) ) {
			$stmt->bind_param("i", $id);
			if ( $stmt->execute() ) {
				$stmt->bind_result($tmpIdInvoice, $tmpDateInvoice, $tmpLabelInvoice);
				while ( $stmt->fetch() ) {
					$ret[$i]['idInvoice'] = $tmpIdInvoice;
					$ret[$i]['dateInvoice'] = $tmpDateInvoice;
					$ret[$i]['labelInvoice'] = $tmpLabelInvoice;
					$i++;
					//$ret['invoices'][] = $t;
				}
			}
		}
		
		return $ret;
	}
	
	// Build list with information about one invoice
	// Parameter:
	// $id Invoice ID
	
	// Return value
	// Array with following structure:
	//  idInvoice
	//  Fromname
	//  FromFirstname
	//  FromAddress
	//  FromCity
	//  FromZip
	//  ToName
	//  ToAddress
	//  ToZip
	//  ToCity
	//  InvDate
	//  InvNumber
	//  accHolder
	//  accNumber
	function detailInvoice($id) {
		$ret = array();
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "SELECT idInvoice, invoicedate, invoicelabel, invFromName, invFromFirstname, invFromAddress, invFromZip, invFromCity, invToName, invToAddress, invToZip, invToCity ";
		$sql .= "invAccountHolder, invAccountNumber, invPaymentMethod FROM " . CConfig::$db_tbl_prefix . "invoices WHERE idInvoice=? LIMIT 0,1";
		if ( $stmt->prepare($sql) ) {
			$stmt_>bind_param("i", $id);
			if ($stmt->execute() ) {
				$stmt->bind_result( $tmpIdInvoice, $tmpDateInvoice, $tmpLabelInvoice, $tmpFromNameInvoice, $tmpFromFirsnameInvoice,
					$tmpFromAddressInvoice, $tmpFromZipInvoice, $tmpFromCityInvoice, $tmpToNameInvoice, $tmpToAddressInvoice, $tmpToZipInvoice, $tmpToCityInvoice, 
					$tmpAccountHolderInvoice, $tmpAccountNumberInvoice, $tmpPaymentMethodInvoice
				);
				
				if ( $stmt->fetch() ) {
					$ret['idInvoice']       = $tmpIdInvoice;
					$ret['Fromname']        = $tmpFromNameInvoice;
					$ret['FromFirstname']   = $tmpFromFirstnameInvoice;
					$ret['FromAddress']     = $tmpFromAddressInvoice;
					$ret['FromCity']        = $tmpFromCityInvoice;
					$ret['FromZip']         = $tmpFromZipInvoice;
					$ret['ToName']          = $tmpToNameInvoice;
					$ret['ToAddress']       = $tmpToAddressInvoice;
					$ret['ToZip']           = $tmpToZipInvoice;
					$ret['ToCity']          = $tmpToCityInvoice;
					$ret['InvDate']         = $tmpDateInvoice;
					$ret['InvNumber']       = $tmpLabelInvoice;
					$ret['accHolder']       = $tmpAccountHolderInvoice;
					$ret['accNumber']       = $tmpAccountNumberInvoice;
					$ret['PaymentMethod']   = $tmpPaymentMethodInvoice;
					
				}
				
				else {
					$ret['idInvoice']       = -1;
					$ret['Fromname']        = "";
					$ret['FromFirstname']   = "";
					$ret['FromAddress']     = "";
					$ret['FromCity']        = "";
					$ret['FromZip']         = "";
					$ret['ToName']          = "";
					$ret['ToAddress']       = "";
					$ret['ToZip']           = "";
					$ret['ToCity']          = "";
					$ret['InvDate']         = "";
					$ret['InvNumber']       = "";
					$ret['accHolder']       = "";
					$ret['accNumber']       = "";
					$ret['PaymentMethod']   = "";
				}
			}
		}
	}
	
	//! This memberfunction selects all work of a field for a given user and returns it in an array of (date, time, description)
	
	/**
	 * 
	 * @param int $userid User ID
	 * @param int $workrank Workfield to extract
	 * @return multitype  | Array with all days worked on the workfield
	 */
	public function getWorkDoneYear($userid, $workrank) {
		$ret = array();
		$ret['msg'] = "ok";
		$ret['msgcode'] = 0;
		$index = 0;
		$t_date = "";
		$t_hours = "";
		$t_description = "";
		
		//$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$sql = "SELECT A.date, A.hours, B.description FROM " . CConfig::$db_tbl_prefix . "workday AS A LEFT JOIN " . CConfig::$db_tbl_prefix . "daydescriptions AS B ON A.date = B.workday WHERE A.user_id=B.user_id AND A.user_id=? AND A.workfield_id=? ORDER BY A.date";
		
		if (!$stmt = $this->dbx->getDatabaseConnection()->prepare($sql) ) {
			$ret['msg'] = "Error: prepare sql statement: $sql *** Mysql error:" . $this->dbx->getDatabaseConnection()->error;
			$ret['msgcode'] = 1;
			return $ret;
		}
		
		if ( !$stmt->bind_param("ii", $userid, $workrank) ) {
			$ret['msg'] = "Error: bind sql parameters";
			$ret['msgcode'] = 2;
			return $ret;
		}
		
		$stmt->bind_result($t_date, $t_hours, $t_description);
		
		if ( ! $stmt->execute() ) {
			$ret['msg'] = "Error: sql execute failed";
			$ret['msgcode'] = 3;
			return $ret;
		}
		
		$ret['data'] = array();
		
		while ( $stmt->fetch() ) {
			$ret['data'][] = array();
			$ret['data'][$index]['mdate'] = $t_date;
			$ret['data'][$index]['mtime'] = $t_hours;
			$ret['data'][$index]['mdescription'] = $t_description;
			
			$index++;
		}
		
		$stmt->close();
		
		return $ret;
	}
}
