<?php
include_once 'config.php';
include_once 'database.class.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

error_reporting(0);
ini_set('display_errors', 0);

//phpinfo();

if (PHP_VERSION_ID < 50600) {
    iconv_set_encoding("internal_encoding", "UTF-8");
    iconv_set_encoding("output_encoding", "UTF-8");
    iconv_set_encoding("input_encoding", "UTF-8");

} else {
    ini_set('default_charset', 'UTF-8');
}

setlocale(LC_TIME, 'de_DE.utf8');


class Helper
{

    /**
     *
     * DatabaseConnection object where the connection is stored in
     * @var DataBaseConnection $dbx
     */
    private $dbx;

    //! Parameterless constructor

    public function __construct()
    {

        try {
		CConfig::$dbuser = (empty(getenv('MYSQL_USER'))) ? CConfig::$dbuser : getenv('MYSQL_USER');
		CConfig::$dbhost = (empty(getenv('MYSQL_HOST'))) ? CConfig::$dbhost : getenv('MYSQL_HOST');
		CConfig::$dbpass = (empty(getenv('MYSQL_PASSWORD'))) ? CConfig::$dbpass : getenv('MYSQL_PASSWORD');
		CConfig::$dbname = (empty(getenv('MYSQL_DB'))) ? CConfig::$dbname : getenv('MYSQL_DB');

		$this->dbx = new DatabaseConnection(
			CConfig::$dbhost,
			CConfig::$dbuser,
			CConfig::$dbpass,
			CConfig::$dbname);

            $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
            $this->dbx->getDatabaseConnection()->set_charset("utf8");
        } catch (Exception $ex) {
            echo "<p>Error: $ex </p>";
        }

    }

    public function __destruct()
    {
        try {
            $this->dbx = null;
        } catch (Exception $ex) {
            echo "<p>Error on destruction...</p>";
        }
    }

    //! Get number of workareas

    public function getNumberWorkareas()
    {
        return CConfig::$max_rank_workfields;
    }


    //! Get work times for one day (from / to).

    /**
     * @param $userid ID The id of the user
     * @param $day Day of date
     * @param $month Month of date
     * @param $year Year of date
     *
     * @return array: Times from and to, if no records are
     *          found a NULL is returned
     */

    public function getTimes($userid, $day, $month, $year)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $times = array();
        $times['from'] = array();
        $times['to'] = array();

        $buildDate = $year . "-" . $month . "-" . $day;
        $sql = "SELECT timefrom, timeto, FROM ";
        $sql .= CConfig::$db_tbl_prefix;
        $sql .= "timefromto WHERE user_id=? AND ";
        $sql .= "dateofday=? ORDER BY timefrom";
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("is", $userid, $buildDate);
            $stmt->execute();
            $stmt->bind_result($fr, $to);

            while ($stmt->fetch()) {
                $times['from'][] = substr($fr, 0, 5);
                $times['to'][] = substr($to, 0, 5);
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
    public function getWork($userid, $day, $month, $year)
    {
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

        if ($stmt->prepare($sql)) {
            $stmt->bind_param("is", $userid, $buildDate);
            $stmt->execute();
            $stmt->bind_result(
                $tempDescription, $tempId, $tempHours);
            while ($stmt->fetch()) {
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

    public function getHollidayState($userid, $day, $month, $year)
    {
        $holliday = array();
        $holliday['id'] = -1;
        $holliday['hollidayid'] = 1;
        $holliday['hollidaytext'] = "";
        $buildDate = "$year-$month-$day";
        $sql = "SELECT id, holliday_id, holliday_text FROM ";
        $sql .= CConfig::$db_tbl_prefix;
        $sql .= "arbeitstage WHERE user_id=? AND dateofday=? LIMIT 0,1";
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("is", $userid, $buildDate);
            $stmt->execute();
            $stmt->bind_result($id, $hollidayid, $hollidaytext);
            if ($stmt->fetch()) {
                $holliday['id'] = $id;
                $holliday['hollidayid'] = $hollidayid;
                $holliday['hollidaytext'] = $hollidaytext;
            }

            $stmt->close();
        }
        return $holliday;
    }

    public function getDatabaseConnection()
    {
        return $this->dbx;
    }

    function getEditedLastWeek($userid)
    {
        $ret = 1;
        $lastweek = 1;
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT week FROM " . CConfig::$db_tbl_prefix;
        $sql .= "lastweek WHERE foreignid=?";

        if ($stmt->prepare($sql)) {
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->bind_param("i", $lastweek);
            if ($stmt->fetch()) {
                ;
            }
        }
        $stmt->close();
        return $lastweek;
    }

    function setEditedLastWeek($userid, $week)
    {
        $updateid = -1;
        $insupsql = "";

        try {
            $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
            if ($stmt->prepare("SELECT foreignid FROM " . CConfig::$db_tbl_prefix . "lastweek WHERE foreignid=?")) {
                $stmt->bind_param("i", $userid);
                $stmt->execute();
                $stmt->bind_result("i", $updateid);
                $stmt->fetch();
                $stmt - close();
            }

            if ($updateid < 0) {
                $insupsql = "INSERT INTO " . CConfig::$db_tbl_prefix . "lastweek (week, foreignid) VALUES (?,?)";
            } else {
                $insupsql = "UPDATE " . CConfig::$db_tbl_prefix . "lastweek SET week=? WHERE foreignid=?";
            }

            $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
            if ($stmt->prepare($insupsql)) {
                $stmt->bind_param("ii", $week, $userid);
                $stmt->execute();
                $stmt->close();
            }

        } catch (Exception $excp) {
            echo "<p>Problem $excp </p>";
        }

    }

    //! Gets the year for the actual user

    /**
     * @param $userid User ID to look for
     *
     * @return int: year for user, on error 1980 is returned which will be recognized as wrong (hopefully)
     */
    function getYearOfUser($userid)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $year = 1980;
        if ($stmt->prepare("SELECT YEAR(startdate) FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?")) {
            $stmt->bind_param("i", $userid);
            $stmt->execute();
            $stmt->bind_param("s", $year);
            $stmt->fetch();
        }
        return $year;
    }

    function createNewSchedule($userid, $startdate, $enddate, $label)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO " . CConfig::$db_tbl_prefix . "schedules ";
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

    function isRessourceMysqliStatement($res)
    {
        if (get_class($res) !== "mysqli_stmt") {
            return false;
        } else {
            return true;
        }
    }

	// creates response strucutre
	function response($status_code, $message) {
		$response = array();
		$response['status'] = $status_code;
		$response['message'] = $message;
		return $response;
	}

  //! Create new user
function restapi_user_create($data) {
	$response = $this->response(500, "Unkown error");

	// extract values
	$arr = json_decode($data);

	$uname = $arr->username;
	$email = $arr->email;
	$password = $arr->password;
	$startdate = TransformDateToUS(  $arr->startdate );
	$displayname = $arr->displayname;

	// check values
	if ( ! isset($uname) || $uname == "" ) return $this->response(501, "No username given");
	if ( ! isset($email) || $email == "" ) return $this->response(502, "No email address given");
	if ( ! isset($password) || $password == "" ) return $this->response(503, "No password was set");
	if ( ! isset($startdate) || $startdate == "" ) return $this->response(504, "No startdate submitted");
	if ( ! isset($displayname) || $displayname == "" ) return $this->response(505, "No displayname given");

	// Check if user exists
	$sqlSelect = "SELCT id, uname from aplan_users where uname = ?";
	$stmt_select = $this->dbx->getDatabaseConnection()->stmt_init();
	if (! $stmt_select->bind_param("s", $uname) ) return $this->response(506, "Unable to bind param uname");
	if (! $stmt_select->execute() ) return $this->response(507, "Unable to execute statement " . $stmt_select->error );
	if (! $stmt_select->bind_result($dbId, $dbUsername) ) return $this->response(508, "Unable to bind result");

	if ( $stmt_select->fetch() ) return $this->response(509, "User already exists");

	$stmt_select->close();

	// Insert data as new user

	$sql = "INSERT INTO aplan_users (uname, email, reg_date, session_id, password, status, alteueberstunden, feiertage, urlaubstage, kmsatz, startdate, report_year, dname) VALUES (?, ?, NOW(), 0, ?, 1, 0, 0, 0, 0.1234, ?, 2020, ?)";

	$stmt = $this->dbx->getDatabaseConnection()->stmt_init();

	if (! $stmt->prepare($sql)) return $this->response(510, "Error on sql prepapre (insert)"); 

	if (!$stmt->bind_param("sssss", $uname, $email, $password, $startdate, $displayname)) 
		return $this->response(511, "Error on binding parameters (insert)");

	if ( ! $stmt->execute() ) return $this->response(511, "Error on executing query (insert)");

	$stmt->close();
	return $this->response(200, "OK");

}

	//! Get holliay periods for a given user
	function restapi_get_holliday_periods($userid) {
		$sql = "SELECT idHolliday, startdate, enddate, nbrdays, nbrminutes FROM aplan_holliday_setup WHERE userid=? ORDER BY startdate DESC";

		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		$response['status'] = 500;
		$response['text'] = "Unkown error";

		if ( ! $stmt->prepare($sql) ) {
			return $response;
		}

		if (! $stmt->bind_param("i", $userid) ) {
			return $response;
		}

		if ( ! $stmt->execute() ) {
			return $response;
		}

		if ( ! $stmt->bind_result($idHolliday, $startdate, $enddate, $nbrDays, $nbrMinutes) ) {
			return $response;
		}

		$resonse['data'] = array();
		$i = 0;

		while ($stmt->fetch() ) {
			$response['data'][] = array();
			$response['data'][$i]['idHolliday'] = $idHolliday;
			$response['data'][$i]['startdate'] = $startdate;
			$response['data'][$i]['enddate'] = $enddate;
			$response['data'][$i]['nbrdays'] = $nbrDays;
			$response['data'][$i]['nbrminutes'] = $nbrMinutes;
			$i++;
	}

		$stmt->close();
		$response['status'] = 200;
		$response['text'] = "ok";
		return $response;
	}

	//! Get overtime table
	function restapi_get_user_overtime_list($userid) {
		$sql = "SELECT A.idPeriod, A.period_start, A.period_end, A.label, B.time_minutes, ";
		$sql .= "B.idStart FROM `aplan_periods` AS A ";
		$sql .= "LEFT JOIN aplan_periods_start_values AS B ";
		$sql .= "ON A.idPeriod=B.idPeriod ";
		$sql .= "WHERE user=? OR B.idStart IS NULL ";
		$sql .= "ORDER BY A.period_start DESC";

		$response = array();
		$response['status'] = 500;
		$response['text'] = "Unkown error";

		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("i", $userid) &&
			$stmt->execute() &&
			$stmt->bind_result($idPeriod, $start, $end, $label, $minutes, $idStart)
		) {
			$response['status'] = 200;
			$response['text'] = "OK";
			$response['periods'] = array();
			$index = 0;
			while ($stmt->fetch() ) {
				$response['periods'][] = array();
				$response['periods'][$index] = array();
				$response['periods'][$index]['idPeriod'] = $idPeriod;
				$response['periods'][$index]['start'] = $start;
				$response['periods'][$index]['end'] = $end;
				$response['periods'][$index]['label'] = $label;
				$response['periods'][$index]['minutes'] = $minutes;
				$response['periods'][$index]['idStart'] = $idStart;
				$index++;
			}
			$stmt->close();
		} else {
			$response['text'] = "Error " . $stmt->error;
		}

		return $response;
	}


	function restapi_set_user_overtime($data) {
		$id = $data->idStart;

		if ( $id <= 0 ) {
			return $this->insert_overtime_user_period($data);
		} else {
			return $this->update_overtime_user_period($data);
		}
	}

	function update_overtime_user_period($data) {
		$id = $data->idStart;
		$time = $data->minutes;

		$sql = "UPDATE aplan_periods_start_values SET time_minutes = ? WHERE idStart=?";

		$response = array();
		$response['status'] = 500;
		$response['text'] = "NOT OK";
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("ii", $time, $id) &&
			$stmt->execute()
		) {
			$response['status'] = 200;
			$response['text'] = "OK";
			$stmt->close();
		} else {
			$response['text'] = $stmt->error;
		}

		return $response;
	}

	function insert_overtime_user_period($data) {
		$time = $data->minutes;
		$user = $data->idUser;
		$period = $data->idPeriod;

		$sql = "INSERT INTO aplan_periods_start_values (idPeriod, time_minutes, user) VALUES (?,?,?)";

		$response = array();
		$response['status'] = 500;
		$response['text'] = "NOT OK";
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("iii", $period, $time, $user) &&
			$stmt->execute()
		) {
			$response['status'] = 200;
			$response['text'] = "OK";
			$stmt->close();
		} else {
			$response['text'] = $stmt->error;
		}

		return $response;
	}


	function restapi_delete_workperiod($data) {
		$id = $data->idPeriod;

		$sql = "DELETE FROM aplan_periods WHERE idPeriod=?";

		$response = array();
		$response['status'] = 500;
		$response['text'] = "NOT OK";
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("i", $id) &&
			$stmt->execute()
		) {
			$response['status'] = 200;
			$response['text'] = "OK";
			$stmt->close();
		} else {
			$response['text'] = $stmt->error;
		}

		return $response;
	}

	function restapi_get_workperiods() {

		$sql = "SELECT idPeriod, period_start, period_end, label FROM aplan_periods";
		$sql .= " ORDER BY period_start DESC";

		$response = array();
		$response['status'] = 500;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->execute() &&
			$stmt->bind_result($id, $start, $end, $label)
		) {
			$response['status'] = 200;
			$response['text'] = "OK";
			$response['periods'] = array();
			$index = 0;
			while ($stmt->fetch() ) {
				$response['periods'][] = array();
				$response['periods'][$index] = array();
				$response['periods'][$index]['id'] = $id;
				$response['periods'][$index]['start'] = $start;
				$response['periods'][$index]['end'] = $end;
				$response['periods'][$index]['label'] = $label;
				$index++;
			}
			$stmt->close();
		} else {
			$response['text'] = "Error " . $stmt->error;
		}

		return $response;
	}

	function restapi_create_period($formdata) {
		$label = $formdata->label;
		$startdate = $formdata->startdate;
		$enddate = $formdata->enddate;

		$sql = "INSERT INTO aplan_periods (label, period_start, period_end) VALUES (?,?,?)";

		$response = array();
		$response['status'] = 500;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("sss", $label, $startdate, $enddate) &&
			$stmt->execute()
		) {
			$response['status'] = 200;
			$stmt->close();
		}

		return $response;

	}

	function restapi_get_rides($params) {

		$userid = $params['userid'];
		$startdate = $params['sdate'];
		$enddate = $params['edate'];

		$sql = "SELECT id, day, fromwhere, towhere, km FROM aplan_kilometers";
		$sql .= " WHERE user_id = ? AND day >= ? AND day <= ?  ";
		$sql .= " ORDER BY day";

		$response = array();
		$response['status'] = 500;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("iss", $userid, $startdate, $enddate) &&
			$stmt->execute() &&
			$stmt->bind_result($id, $day, $from, $to, $km)
		) {
			$response['status'] = 200;
			$response['workareas'] = array();
			$index = 0;
			while ($stmt->fetch() ) {
				$response['rides'][] = array();
				$response['rides'][$index]['day'] = $day;
				$response['rides'][$index]['idKm'] = $id;
				$response['rides'][$index]['from'] = $from;
				$response['rides'][$index]['to'] = $to;
				$response['rides'][$index]['km'] = $km;
				$response['rides'][$index]['rate'] = 0.0; // $this->get_rate_for_user_day($userid, $day) ;
				$index++;

			}

			$stmt->close();

			for ($rate_index = 0; $rate_index < count($response['rides']); $rate_index++ ) {
				$response['rides'][$rate_index]['rate'] =
					$this->get_rate_for_user_day($userid,
					$response['rides'][$rate_index]['day'] );
			}

		} else {
			$response['message'] = $this->getDatabaseConnection()->getDatabaseConnection()->error;
		}

		return $response;

	}

	function get_rate_for_user_day($user, $day) {
		$sql = "SELECT val FROM aplan_drive_recompensation WHERE ";
		$sql .= "userid=? AND ? <= enddate AND ? >= startdate";

		$rate = 0.123;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ($stmt->prepare($sql) &&
			$stmt->bind_param("iss", $user, $day, $day) &&
			$stmt->execute() &&
	       		$stmt->bind_result($val) ) {

			if ($stmt->fetch() ) {
				$rate = $val;
			}

			$stmt->close();
		} else {
			return $stmt->error;
		}

		return $rate;
	}

	function restapi_selfstat_workareas($userid, $sdate, $edate) {
		$sql = "SELECT B.rank, B.description, A.hours, B.timecapital FROM aplan_workday as A ";
		$sql .= "LEFT JOIN aplan_workfields AS B ";
		$sql .= "ON A.workfield_id = B.rank ";
		$sql .= "WHERE A.user_id = ? AND A.date >= ? AND A.date <= ? AND B.user=? ";
		$sql .= "AND A.hours > 0 ";
		$sql .= "ORDER BY B.rank";

		$response['code'] = 500;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("issi", $userid, $sdate, $edate, $userid) &&
			$stmt->execute() &&
			$stmt->bind_result($rank, $description, $hours, $capital)
		) {
			$response['code'] = 200;
			$response['workareas'] = array();

			$index = -1;

			$oldrank = -1;
			while ($stmt->fetch() ) {
				if ($oldrank != $rank )   {
					$index++;
					$oldrank = $rank;
					$response['workareas'][$index] = array();
					$response['workareas'][$index]['description'] = $description;
					$response['workareas'][$index]['timecapital'] = $capital;
					$response['workareas'][$index]['times'] = array();
				}
				$response['workareas'][$index]['times'][] = $hours;
			}

			$stmt->close();
		} else {
			$response['message'] = $stmt->error;
			$stmt->close();
		}

		return $response;
	}

	function restapi_moderation_freeze_userinput_list($arrayData) {

		$id = $arrayData->moduserid;
		$sql = "SELECT idFreeze, freezedate FROM aplan_freeze WHERE user=? ORDER BY freezedate DESC";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();

		$response = array();
		$response['message'] = "not ok";

		if ($stmt->prepare($sql) && $stmt->bind_param("i", $id) && $stmt->execute() && $stmt->bind_result($idFreeze, $freezedate) ) {
			$index = 0;
			$response['data'] = array();

			while ($stmt->fetch() ) {
				$response['data'][] = array();
				$response['data'][$index]['idFreeze'] = $idFreeze;
				$response['data'][$index]['freezedate'] = $freezedate;
				$index++;
			}

			$response['message'] = "ok";
			$stmt->close();
		}


		return $response;
	}

	function restapi_moderation_freeze_delete($idToDelete) {
		$response = array();
		$response["message"] = "not ok";
		$sql = "DELETE FROM aplan_freeze WHERE idFreeze=?";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();

		if ($stmt->prepare($sql) &&
			$stmt->bind_param("i", $idToDelete) &&
			$stmt->execute() ) {

			$response["message"] = "ok";
			$stmt->close();
		}

		return $response;
	}

	function restapi_moderation_freeze_userinput($arrayData) {
		$id = $arrayData->moduserid;
		$qdate = $arrayData->qdate;
		$response = array();

		$sql = "INSERT INTO aplan_freeze (user, freezedate) VALUES (?,?)";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();

		if ( $stmt->prepare($sql) &&
			$stmt->bind_param("is", $id, $qdate) &&
			$stmt->execute() &&
			$stmt->close()
		) {
			$response['code'] = 200;
			$response['message'] = "ok";
		} else {
			$response['code'] = 500;
			$response['message'] = "error";
		}

		return $response;
	}

	function restapi_moderation_create_timemodification($data) {
		$userid = $data->moduserid;
		$qdate = $this->TransformDateToUs( $data->moddate );
		$reason = $data->modreason;
		$time = $this->TimeToInt( $data->modtime);
		$typemod = $data->modtype;
		if ( strcmp($typemod, '-') == 0 ) {
			$time *= -1.0;
		}

		$response = array();

		$sql = "INSERT INTO aplan_bonus_times (user, bonus_minutes, bonus_date, short_description) VALUES (?, ?, ?, ?)";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();

		if ($stmt->prepare($sql) && $stmt->bind_param("iiss", $userid, $time, $qdate, $reason) &&
			$stmt->execute() && $stmt->close() ) {

			$response['status'] = "ok";
			$response['code'] = 200;
		} else {
			$response['status'] = "not ok";
			$response['code'] = 500;
			$response['text'] = mysqli_error($this->dbx->getDatabaseConnection()) .  " userid: $userid";
		}

		return $response;

	}



    function restapi_scheduleitems_delete($userid, $data)
    {
        $arrData = json_decode($data);
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "DELETE FROM aplan2_schedule_items WHERE idScheduleItem = ?";

        if (!isset($arrData->idScheduleItem)) {
            return "Need id";
        }

        $idScheduleItem = $arrData->idScheduleItem;

        if (
            $stmt->prepare($sql) &&
            $stmt->bind_param("i", $idScheduleItem) &&
            $stmt->execute() &&
            $stmt->close()
        ) {
            return "ok";
        } else {
            return "not ok";
        }
    }

    function restapi_scheduleitems_update($userid, $data)
    {
        $arrData = json_decode($data);
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan2_schedule_items SET dayofWeek=?, time_from=?, time_to=? WHERE idScheduleItem = ?";
        $idScheduleItem = $arrData->idScheduleItem;
        $time_from = $arrData->timeFrom;
        $time_to = $arrData->timeTo;
        $dayOfWeek = $arrData->dayOfWeek;

        if (
            $stmt->prepare($sql) &&
            $stmt->bind_param("issi", $dayOfWeek, $time_from, $time_to, $idScheduleItem) &&
            $stmt->execute() &&
            $stmt->close()
        ) {
            return "ok";
        } else {
            return "not ok";
        }
    }

    function restapi_scheduleitems_read($userid, $data)
    {

        $arrData = json_decode($data);
        if (!isset($arrData->idSchedule)) {
            return "need idSchedule set in json";
        }

        $idSchedule = $arrData->idSchedule;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idScheduleItem, dayOfWeek, time_from, time_to FROM aplan2_schedule_items ";
        $sql .= "WHERE idSchedule=? ";
        $sql .= "ORDER BY dayOfWeek, time_from";

        if ($stmt->prepare($sql) &&
            $stmt->bind_param("i", $idSchedule) &&
            $stmt->execute() &&
            $stmt->bind_result($idScheduleItem, $dayOfWeek, $timeFrom, $timeTo)) {
            $arrScheduleItems = array();

            while ($stmt->fetch()) {
                $item = array(
                    "idScheduleItem" => $idScheduleItem,
                    "dayOfWeek" => $dayOfWeek,
                    "time_from" => $timeFrom,
                    "time_to" => $timeTo
                );

                $arrScheduleItems[] = $item;
            }

            $stmt->close();
            return $arrScheduleItems;

        }

        return "not ok";

    }

    function restapi_scheduleitems_create($userid, $data)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan2_schedule_items ";
        $sql .= "(idSchedule, dayOfWeek, time_from, time_to) VALUES ";
        $sql .= "(?, ?, ?, ?)";
        $msg = "not ok";

        if (!$this->isRessourceMysqliStatement($stmt)) return $msg;

        $arrData = json_decode($data);

        if (isset($arrData->day)) {
            // just one day to save

        }

        if ($stmt->prepare($sql)) {
            $idSchedule = -1;
            $dayOfWeek = 0;
            $time_from = "07:00";
            $time_to = "12:00";

            if (!$stmt->bind_param("iiss", $idSchedule, $dayOfWeek, $time_from, $time_to)) return $msg;

            if (isset($arrData->idSchedule) && isset($arrData->day) && isset ($arrData->time_from) && isset($arrData->time_to)) {
                $idSchedule = $arrData->idSchedule;
                $time_from = $arrData->time_from;
                $time_to = $arrData->time_to;
                $dayOfWeek = $arrData->day;

                if (!$stmt->execute()) {
                    $stmt->close();
                    return $msg;
                } else {
                    $msg = 'ok';
                    $stmt->close();
                    return $msg;
                }

            } else if (count($arrData) > 0) {

                for ($i = 0; $i < count($arrData->scheduleitems); $i++) {
                    $idSchedule = $arrData->scheduleitems[$i]->idSchedule;
                    $dayOfWeek = $arrData->scheduleitems[$i]->day;
                    $time_from = $arrData->scheduleitems[$i]->time_from;
                    $time_to = $arrData->scheduleitems[$i]->time_to;

                    if (!$stmt->execute()) {
                        $stmt->close();
                        return $msg;
                    } else {
                        $msg = 'ok';
                        $stmt->close();
                        return $msg;
                    }

                }

            } else {
                $msg = 'not ok';
            }


            $msg = "ok";
        }


        return $msg;
    }


    function restapi_schedule_delete($userid, $data)
    {

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "DELETE FROM aplan2_schedules WHERE  ";
        $sql .= "userid = ? AND idSchedule = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        if ($stmt->prepare($sql)) {
            $arrData = json_decode($data);
            $idSchedule = $arrData->idSchedule;

            if ($stmt->bind_param("ii", $userid, $idSchedule) && $stmt->execute()) {
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

    function restapi_schedule_update($userid, $data)
    {

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan2_schedules SET startdate = ?, enddate = ? , label = ? WHERE  ";
        $sql .= "userid = ? AND idSchedule = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        if ($stmt->prepare($sql)) {
            $arrData = json_decode($data);
            $startdate = $arrData->startdate;
            $enddate = $arrData->enddate;
            $label = $arrData->label;
            $idSchedule = $arrData->idSchedule;

            if ($stmt->bind_param("sssii", $startdate, $enddate, $label, $userid, $idSchedule) && $stmt->execute()) {
                
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

    function restapi_schedule_create($userid, $data)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan2_schedules (userid, startdate, enddate, label) ";
        $sql .= "VALUES (?, ?, ?, ?)";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        if ($stmt->prepare($sql)) {
            $arrData = json_decode($data);
            $startdate = $arrData->startdate;
            $enddate = $arrData->enddate;
            $label = $arrData->label;
            if ($stmt->bind_param("isss", $userid, $startdate, $enddate, $label) && $stmt->execute()) {
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

    function restapi_schedule_read($userid)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idSchedule, startdate, enddate, label FROM aplan2_schedules WHERE userid=? ORDER BY startdate DESC";
        $schedules = array();

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("i", $userid) && $stmt->execute()) {
                $stmt->bind_result($idSchedule, $startdate, $enddate, $label);
                while ($stmt->fetch()) {
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

	function restapi_driverecompensation_read($data)
	{
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idDrive, userid, startdate, enddate, val FROM aplan_drive_recompensation WHERE userid = ? ORDER BY startdate DESC";
		$ret = array();
		$ret['message'] = "not ok";
        
		if (get_class($stmt) !== "mysqli_stmt") {
            $ret["message"] = "not a mysqli_stmt";
            return $ret;
        }

		$arrData = json_decode($data);
		if (!isset($arrData->id)) {
			$ret['message'] = "id is missing";
			return $ret;
		}

		$id = $arrData->id;
		
		if (! $stmt->prepare($sql) ) {
			$ret['message'] = "prepare failed";
			return $ret;
		}

		if (! $stmt->bind_param("i", $id ) ) {
			$ret["message"] = "query failed: " . $stmt->error;
			return $ret;
		}
		
		if ($stmt->execute() ) {
			$stmt->bind_result($idDrive, $userid, $startdate, $enddate, $val);
			$ret["message"] = "ok";
			$ret["driverecompensation"] = array();
			while ($stmt->fetch() ) {
				$row = array();
				$row["idDrive"] = $idDrive;
				$row["userid"] = $userid;
				$row["startdate"] = $startdate;
				$row["enddate"] = $enddate;
				$row["value"] = $val;
				$ret["driverecompensation"][] = $row;
			}
			$stmt->close();
			return $ret;
		} else {
			return $ret;
		}
	}

	function restapi_driverecompensation_delete($data)
	{
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "DELETE FROM aplan_drive_recompensation where idDrive = ? and userid = ?";
        
		if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

		$arrData = json_decode($data);
		if (!isset($arrData->id)) return "user id missing";
		if (!isset($arrData->idDrive)) return "idDrive id missing";
		$id = $arrData->id;
		$idDrive = $arrData->idDrive;
		
		if (! $stmt->prepare($sql) ) return "prepare failed";

		if (! $stmt->bind_param("ii", $idDrive, $id ) ) return "query failed: " . $stmt->error;
		
		if ($stmt->execute() ) {
			$stmt->close();
			return "ok";
		} else {
			return "not ok";
		}
	}

	function restapi_driverecompensation_update($data)
	{
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan_drive_recompensation SET ";
		$sql .= "startdate = ?, ";
		$sql .= "enddate = ?, ";
		$sql .= "val = ? ";
		$sql .= "where idDrive = ? and userid = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

		$arrData = json_decode($data);
		if (!isset($arrData->startdate)) return "startdate mssing";
		if (!isset($arrData->enddate)) return "enddate mssing";
		if (!isset($arrData->val)) return "val missing";
		if (!isset($arrData->id)) return "user id missing";
		if (!isset($arrData->idDrive)) return "idDrive id missing";
		$startdate = $arrData->startdate;
		$enddate = $arrData->enddate;
		$val = $arrData->val;
		$id = $arrData->id;
		$idDrive = $arrData->idDrive;

		if (! $stmt->prepare($sql) ) return "prepare failed";

		if (! $stmt->bind_param("ssdii", $startdate, $enddate, $val, $idDrive, $id ) ) return "query failed: " . $stmt->error;

		if ($stmt->execute() ) {
			$stmt->close();
			return "ok";
		} else {
			return "not ok";
		}

	}

	function restapi_driverecompensation_create($data)
	{
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan_drive_recompensation (userid, startdate, enddate, val) ";
        $sql .= "VALUES (?, ?, ?, ?)";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

		$arrData = json_decode($data);
		if (!isset($arrData->startdate)) return "startdate mssing";
		if (!isset($arrData->enddate)) return "enddate mssing";
		if (!isset($arrData->val)) return "val missing";
		if (!isset($arrData->id)) return "user id missing";
		$startdate = $arrData->startdate;
		$enddate = $arrData->enddate;
		$val = $arrData->val;
		$id = $arrData->id;

		if (! $stmt->prepare($sql) ) return "prepare failed";

		if (! $stmt->bind_param("issd", $id, $startdate, $enddate, $val) ) return "query failed: " . $stmt->error;

		if ($stmt->execute() ) {
			$stmt->close();
			return "ok";
		} else {
			return "not ok";
		}

	}

    function restapi_drive_recompensation_create($userid, $data)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan_drive_recompensation (userid, startdate, enddate, val) ";
        $sql .= "VALUES (?, ?, ?, ?)";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        if ($stmt->prepare($sql)) {
            $arrData = json_decode($data);
            if (!isset($arrData->startdate)) return "startdate mssing";
            if (!isset($arrData->enddate)) return "enddate mssing";
            if (!isset($arrData->val)) return "val missing";

            $startdate = $arrData->startdate;
            $enddate = $arrData->enddate;
            $val = $arrData->val;
            if ($stmt->bind_param("issd", $userid, $startdate, $enddate, $val) && $stmt->execute()) {
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

    function restapi_drive_recompensation_read($userid)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idDrive, startdate, enddate, val FROM aplan_drive_recompensation WHERE userid=? ORDER BY startdate";
        $reco = array();

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("i", $userid) && $stmt->execute()) {
                $stmt->bind_result($idDrive, $startdate, $enddate, $val);
                while ($stmt->fetch()) {
                    $item = array(
                        "idDrive" => $idDrive,
                        "startdate" => $startdate,
                        "enddate" => $enddate,
                        "val" => $val
                    );
                    $reco[] = $item;
                }
            }

            $stmt->close();
        }

        return $reco;
    }

    function restapi_drive_recompensation_delete($userid, $data)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $arrData = json_decode($data);
        $msg = "ok";

        if (!isset($arrData->idDrive)) return "need idDrive";

        $idDrive = $arrData->idDrive;

        $sql = "DELETE FROM aplan_drive_recompensation WHERE userid=? AND idDrive = ?";

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("ii", $userid, $idDrive) && $stmt->execute()) {

            } else {
                $msg = $stmt->error;

            }

            $stmt->close();
        }

        return $msg;
    }

    //! Creates a dataset in table aplan_holliday_setup
    //! The parameter needs to have the following fields:
    //!  startdate DATE in format YYYY-MM-DD
    //!  enddate DATE in format YYYY-MM-DD
    //!  days INT Number of days
    //!  minutes INT Number of minutes
    //!  userid INT user id
    function restapi_hollidays_create($arrData)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan_holliday_setup (userid, startdate, enddate, nbrdays, nbrminutes) ";
        $sql .= "VALUES (?, ?, ?, ?, ?)";
	$response = array();
	$response["status"] = 500;

        if (get_class($stmt) !== "mysqli_stmt") {
            $response["status"] = 501;
	    $response["text"] = "Failed ot initialize mysqli stmt";
	    return $response;
        }

        if ($stmt->prepare($sql)) {
            //$arrData = json_decode($data);
            if (!isset($arrData->startdate)) {
		$response["status"] = 510;
		return $response;
	    }

            if (!isset($arrData->enddate)) {
		$response["status"] = 511;
		return $response;
	    }

            if (!isset($arrData->days)) {
		$response["status"] = 512;
		return $response;
	    }

	    if (!isset($arrData->minutes)) {
		$response["status"] = 513;
		return $response;
	    }

	    if (!isset($arrData->userid)) {
		$response["status"] = 514;
		return $response;
	    }

            $startdate = $arrData->startdate;
            $enddate = $arrData->enddate;
            $nbrdays = $arrData->days;
	    $nbrminutes = $arrData->minutes;
	    $userid = $arrData->userid;

            if ($stmt->bind_param("issii", $userid, $startdate, $enddate, $nbrdays, $nbrminutes) && $stmt->execute()) {
                $response["text"] = "ok";
		$response["status"] = 200;
            } else {
                $response["text"] = "not ok " . $stmt->error;
		$response["status"] = 504;
            }
            $stmt->close();
        } else {
            $response["text"] = "prepare failed " . $stmt->error;
            $response["status"] = 505;
        }

        return $response;

    }

    function restapi_hollidays_read($userid)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idHolliday, startdate, enddate, nbrdays, nbrminutes FROM aplan_holliday_setup WHERE userid=? ORDER BY startdate DESC";
        $reco = array();

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("i", $userid) && $stmt->execute()) {
                $stmt->bind_result($idDrive, $startdate, $enddate, $val, $valMinutes);
                while ($stmt->fetch()) {
                    $item = array(
                        "idHolliday" => $idDrive,
                        "startdate" => $startdate,
                        "enddate" => $enddate,
                        "nbrdays" => $val,
			"nbrminutes" => $valMinutes
                    );
                    $reco[] = $item;
                }
            }

            $stmt->close();
        }

        return $reco;
    }

    function restapi_hollidays_delete($arrData)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        //$arrData = json_decode($data);
	$response = array();
	$response["status"] = 500;

        if (!isset($arrData->hollidayid)) {
		$response["status"] = 501;
		return $response;
	}

	if (! isset($arrData->userid) ) {
		$response["status"] = 502;
		return $repsonse;
	}

        $idHolliday = $arrData->hollidayid;
	$userid = $arrData->userid;

        $sql = "DELETE FROM aplan_holliday_setup WHERE userid=? AND idHolliday = ?";

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("ii", $userid, $idHolliday) && $stmt->execute()) {
		$response["status"] = 200;
		$response["text"] = "OK";

            } else {
		$response["status"] = 503;
                $response["text"] = $stmt->error;

            }

            $stmt->close();
        }

        return $response;
    }

    function restapi_vacation_create($arrData)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan_vacation_setup (userid, startdate, enddate, nbrdays, nbrminutes) ";
        $sql .= "VALUES (?, ?, ?, ?, ?)";
	$response["status"] = 200;

        if (get_class($stmt) !== "mysqli_stmt") {
	    $response["status"] = 500;
            return $response;
        }

        if ($stmt->prepare($sql)) {
		if (!isset($arrData->startdate)) {
			$response["status"] = 501;
			return $response;
		}

		if (!isset($arrData->enddate)) {
			$response["status"] = 502;
			return $response;
		}

		if (!isset($arrData->days)) {
			$response["status"] = 503;
			return $response;
		}

		if (!isset($arrData->userid)) {
			$response["status"] = 504;
			return $response;
		}

		if (!isset($arrData->minutes)) {
			$response["status"] = 505;
			return $response;
		}

		$startdate = $arrData->startdate;
		$enddate = $arrData->enddate;
		$nbrdays = $arrData->days;
		$userid = $arrData->userid;
		$nbrminutes = $arrData->minutes;

		if ($stmt->bind_param("issii", $userid, $startdate, $enddate, $nbrdays, $nbrminutes) && $stmt->execute()) {
		    } else {
			$response["status"] = 506;
		    }
		    $stmt->close();
		} else {
		    $response["status"] = 507;
		}

        return $response;

    }

    function restapi_vacation_read($userid)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idVacation, startdate, enddate, nbrdays, nbrminutes FROM aplan_vacation_setup WHERE userid=? ORDER BY startdate";
        $reco = array();
	$reco["status"] = 200;
	$reco["text"] = "OK";

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("i", $userid) && $stmt->execute()) {
                $stmt->bind_result($idVacation, $startdate, $enddate, $val, $valMinutes);

		$reco["data"] = array();

                while ($stmt->fetch()) {
                    $item = array(
                        "idVacation" => $idVacation,
                        "startdate" => $startdate,
                        "enddate" => $enddate,
                        "nbrdays" => $val,
			"nbrminutes" => $valMinutes
                    );
                    $reco["data"][] = $item;
                }
            } else {
		$reco["status"] = 501;
		$reco["text"] = "Not ok";
	    }

            $stmt->close();
        } else {
		$reco["status"] = 500;
		$reco["text"] = "Not ok";
	}

        return $reco;
    }

    function restapi_vacation_delete($arrData)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
	$response = array();
	$response["status"] = 200;

	if (!isset($arrData->idVacation)) {
		$response["status"] = 500;
		return $response;
	}

	if (!isset($arrData->userid)) {
		$response["status"] = 502;
		return $response;
	}

        $idVacation = $arrData->idVacation;
	$userid = $arrData->userid;

        $sql = "DELETE FROM aplan_vacation_setup WHERE userid=? AND idVacation = ?";

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("ii", $userid, $idVacation) && $stmt->execute()) {

            } else {
                $response["status"] = 500;

            }

            $stmt->close();
        } else {
		$response["status"] = 501;
	}

        return $response;
    }

    function restapi_workareas_create($userid, $data)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO aplan_workfields (rank, explanation, description, user, timecapital, is_visible) ";
        $sql .= "VALUES (?, ?, ?, ?, ?, 1)";
        $msg = "not ok";

        $rank = 1000;
        $explanation = '';
        $description = '';
        $timecapital = 0;

        $arrData = json_decode($data);

        if (isset($arrData->rank) && intval($arrData->rank) > 0 && intval($arrData->rank) <= 10000) {
            $rank = intval($arrData->rank);
        }

        if (isset($arrData->timecapital) && intval($arrData->timecapital) > 0 && intval($arrData->timecapital) <= 10000) {
            $timecapital = intval($arrData->timecapital);
        }

        if (isset($arrData->explanation)) {
            $explanation = $arrData->explanation;
        }

        if (isset($arrData->description)) {
            $description = $arrData->description;
        }

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        if ($stmt->prepare($sql)) {


            if ($stmt->bind_param("issii", $rank, $explanation, $description, $userid, $timecapital) && $stmt->execute()) {
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

    function restapi_workareas_update_explanation($userid, $data) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan_workfields SET explanation=? ";
        $sql .= "WHERE user=? AND id = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        $arrData = json_decode($data);

        $idWorkarea = -1;
        $explanation = "";

        if ($stmt->prepare($sql)) {

            if ($stmt->bind_param("sii", $explanation, $userid, $idWorkarea)) {
                $msg = "ok";
            } else {
                $msg = "not ok " . $stmt->error;
            }

        } else {
            $msg = "prepare failed " . $stmt->error;
            $stmt->close();
            return $msg;
        }

        $idWorkarea = $arrData->idWorkarea;
        $explanation = $arrData->explanation;

        if (!$stmt->execute()) {
            $msg = "Mysql error: " . $stmt->error;
            $stmt->close();
            return $msg;
        } else {
            $msg = "ok";
        }

        return $msg;

    }

    function restapi_workareas_update_short($userid, $data) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan_workfields SET description=? ";
        $sql .= "WHERE user=? AND id = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        $arrData = json_decode($data);

        $idWorkarea = -1;
        $short = "";

        if ($stmt->prepare($sql)) {

            if ($stmt->bind_param("sii", $short, $userid, $idWorkarea)) {
                $msg = "ok";
            } else {
                $msg = "not ok " . $stmt->error;
            }

        } else {
            $msg = "prepare failed " . $stmt->error;
            $stmt->close();
            return $msg;
        }

        $idWorkarea = $arrData->idWorkarea;
        $short = $arrData->short;

        if (!$stmt->execute()) {
            $msg = "Mysql error: " . $stmt->error;
            $stmt->close();
            return $msg;
        } else {
            $msg = "ok";
        }

        return $msg;

    }

    function restapi_workareas_update_timecapital($userid, $data) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan_workfields SET timecapital=? ";
        $sql .= "WHERE user=? AND id = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        $arrData = json_decode($data);

        $idWorkarea = -1;
        $time = "";

        if ($stmt->prepare($sql)) {

            if ($stmt->bind_param("iii", $time, $userid, $idWorkarea)) {
                $msg = "ok";
            } else {
                $msg = "not ok " . $stmt->error;
            }

        } else {
            $msg = "prepare failed " . $stmt->error;
            $stmt->close();
            return $msg;
        }

        $idWorkarea = $arrData->idWorkarea;
        $time = $arrData->timecapital;

        if (!$stmt->execute()) {
            $msg = "Mysql error: " . $stmt->error;
            $stmt->close();
            return $msg;
        } else {
            $msg = "ok";
        }

        return $msg;

    }

    function restapi_workareas_update_visible($userid, $data) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan_workfields SET is_visible=? ";
        $sql .= "WHERE user=? AND id = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }

        $arrData = json_decode($data);

        $idWorkarea = -1;
        $visible = 0;

        if ($stmt->prepare($sql)) {

            if ($stmt->bind_param("iii", $visible, $userid, $idWorkarea)) {
                $msg = "ok";
            } else {
                $msg = "not ok " . $stmt->error;
            }

        } else {
            $msg = "prepare failed " . $stmt->error;
            $stmt->close();
            return $msg;
        }

        $idWorkarea = $arrData->idWorkarea;
	if ($arrData->visible == 1 )
	{
		$visible = 1;
		$msg .= "visible $visible";
	}
	else
	{
		$visible = 0;
		$msg .= "visible $visible";
	}

        if (!$stmt->execute()) {
            $msg = "Mysql error: " . $stmt->error;
            $stmt->close();
            return $msg;
        } else {
            $msg = "ok";
        }

        return $msg;

    }

function data_contains_visible_field($arrData) {

	if (is_array( $arrData ) ) {
		// is array
		if ( array_key_exists('visible', $arrData[0]) ) return true;
	}
	else {
		// no array
		if ( array_key_exists('visible', $arrData) ) return true;
	}

	return false;
}

    function restapi_workareas_update($userid, $data)
    {
	$arrData = json_decode($data);

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE aplan_workfields SET rank=?, explanation=?, description=?, timecapital=? ";
	if ( data_contains_visible_field($arrData) ) $sql .= ", is_visible=? ";
        $sql .= "WHERE user=? AND id = ?";
        $msg = "not ok";

        if (get_class($stmt) !== "mysqli_stmt") {
            $msg = "not a mysqli_stmt";
            return $msg;
        }



        // default values
        $rank = 1000;
        $explanation = '';
        $description = '';
        $timecapital = 0;
        $idWorkarea = -1;
	$visible = 0;

        if ($stmt->prepare($sql)) {

		$str_param_list = "issiii";
		if (data_contains_visible_field($arrData) ) {
			$str_param_list .= "i";
			if ($stmt->bind_param($str_param_list, $rank, $explanation, $description, $timecapital, $userid, $idWorkarea, $visible)) {
				$msg = "ok";
			} else {
				$msg = "not ok " . $stmt->error;
			}
		} else {

		    if ($stmt->bind_param($str_param_list, $rank, $explanation, $description, $timecapital, $userid, $idWorkarea)) {
			$msg = "ok";
		    } else {
			$msg = "not ok " . $stmt->error;
		    }
		}

        } else {
            $msg = "prepare failed " . $stmt->error;
            $stmt->close();
            return $msg;
        }


        for ($i = 0; $i < count($arrData); $i++) {

            if (isset($arrData[$i]->rank)) {
                $rank = intval($arrData[$i]->rank);
            }

            if (isset($arrData[$i]->timecapital) && intval($arrData[$i]->timecapital) >= 0 && intval($arrData[$i]->timecapital) <= 10000) {
                $timecapital = intval($arrData[$i]->timecapital);
		if ($timecapital == 0) $timecapital = 1;
            }

            if (isset($arrData[$i]->explanation)) {
                $explanation = $arrData[$i]->explanation;
            }

            if (isset($arrData[$i]->description)) {
                $description = $arrData[$i]->description;
            }

            if (isset($arrData[$i]->idWorkarea)) {
                $idWorkarea = $arrData[$i]->idWorkarea;
            }
	    if (isset($arrData[$i]->visible)) {
                $visible = $arrData[$i]->visible;
            }

            if (!$stmt->execute()) {
                $msg = "Mysql error: " . $stmt->error;
                $stmt->close();
                return $msg;
            }

        }

        $stmt->close();
        return $msg;

    }

    function restapi_workareas_read($userid)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT id, rank, description, explanation, timecapital, is_visible FROM aplan_workfields WHERE user=? ORDER BY rank";
        $reco = array();

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("i", $userid) && $stmt->execute()) {

                $stmt->bind_result($id,
                    $rank,
                    $description,
                    $explanation,
                    $timecapital,
                    $visible);

                while ($stmt->fetch()) {
                    $item = array(
                        "idWorkarea" => $id,
                        "rank" => $rank,
                        "description" => $description,
                        "explanation" => $explanation,
                        "timecapital" => $timecapital,
                        "visible" => $visible
                    );
                    $reco[] = $item;
                }
            }

            $stmt->close();
        }

        return $reco;
    }

    function restapi_monitor_get_userinfo($userid) {
      $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
      $sql = "SELECT dname FROM aplan_users WHERE id = ?";
      $msg = array();
      if ($stmt->prepare($sql)) {
          if ($stmt->bind_param("i", $userid) && $stmt->execute()) {
              $stmt->bind_result($displayname);
              if ( $stmt->fetch() ) {
                $msg['displayname'] = $displayname;
                $msg['id'] = $userid;
                $msg['status'] = 'found';


              } else {
                $msg['status'] = 'not found';

              }
          } else {
              $msg = $stmt->error;
          }

          $stmt->close();
      } else {
        $msg["status"] = 'not found';
      }

      return $msg;
    }

    function restapi_workareas_delete($userid, $data)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $arrData = json_decode($data);
        $msg = "ok";

        if (!isset($arrData->idWorkarea)) return "need idWorkarea";

        $idWorkarea = $arrData->idWorkarea;

        $sql = "DELETE FROM aplan_workfields WHERE user=? AND id = ?";

        if ($stmt->prepare($sql)) {
            if ($stmt->bind_param("ii", $userid, $idWorkarea) && $stmt->execute()) {

            } else {
                $msg = $stmt->error;

            }

            $stmt->close();
        }

        return $msg;
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
    function getOverhoursUntilDate($user, $day, $month, $year)
    {
        $userStartYear = $this->getUserStartYear($user);
        $endDate = mktime(0, 0, 0, $month, $day, $year);
        $workDoneMinutes = 0;
        $workToBeDoneMinutes = 0;
        $workDaysOffMinutes = 0;
        $workLastYear = 0.0;
        $startDate = mktime(0, 0, 0, 1, 1, 2010);

        // Get startdate for user
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT startdate, alteueberstunden FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?")) {
            $stmt->bind_param("i", $user);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpStartDate, $tmpHoursLastYear);
                if ($stmt->fetch()) {
                    $workLastYear = (double)$tmpHoursLastYear * 60.0;
                    $tmpStartDate = substr($tmpStartDate, 0, 10);

                    $arrStartDate = explode("-", $tmpStartDate, 3);
                    if (count($arrStartDate) == 3) {
                        $startDate = mktime(0, 0, 0, $arrStartDate[1], $arrStartDate[2], $arrStartDate[0]);
                        $stmt->close();
                    } else {
                        return 0;
                    }
                } else {
                    return 0;
                }
            }
        }

        // Load table with work done by user within $startDate and $endDate
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id = ? AND date < ? AND date >= ?")) {
            $dbStartDate = date("Y-m-d", $startDate);
            $dbEndDate = date("Y-m-d", $endDate);
            $stmt->bind_param("iss", $user, $dbEndDate, $dbStartDate);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpHoursWorked);
                while ($stmt->fetch()) {
                    $arrTime = explode(":", $tmpHoursWorked);
                    if (count($arrTime) == 3) {
                        $workDoneMinutes += (int)$arrTime[1] + (int)($arrTime[0] * 60);
                    }
                }
            }
        }


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

            $tmpDate = mktime(0, 0, 0, date("m", $tmpDate), date("d", $tmpDate) + 1, date("Y", $tmpDate));
            $index += 1;
        }

        $sql = "SELECT time_from, time_to ";
        $sql .= "FROM " . CConfig::$db_tbl_prefix . "schedules AS A ";
        $sql .= "LEFT JOIN " . CConfig::$db_tbl_prefix . "schedule_items AS B ";
        $sql .= "ON A.idSchedule = B.idSchedule ";
        $sql .= "WHERE A.userid=? AND A.startdate <= ? AND A.enddate > ? AND B.dayOfWeek= ? ";

        if ($stmt->prepare()) {
            if ($stmt->bind_param("issi", $user, $pWorkToDoPerDayDate, $pWorkToDoPerDayDate, $pWorkday)) {
                for ($i = 0; $i < count($tblWorkToDoPerDay); $i++) {
                    $pWorkday = $tblWorkToDoPerDay[$i]['workday'];
                    $pWorkToDoPerDayDate = $tblWorkToDoPerDay[$i]['date'];
                    if ($stmt->execute()) {
                        $stmt->bind_result($from, $to);
                        $nbrTimes = 0;
                        $minutes = 0;
                        while ($stmt->fetch()) {
                            $tblWorkToDoPerDay[$index]['timesSchedule'][] = array();
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

        // Table with hollidays and days-off taken
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT dateofday FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id = ? AND dateofday >= ? AND dateofday < ? AND holliday_id != 1 ")) {
            $dbStartDate = date("Y-m-d", $startDate);
            $dbEndDate = date("Y-m-d", $endDate);
            $stmt->bind_param("iss", $user, $dbStartDate, $dbEndDate);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpDateOfDay);
                while ($stmt->fetch()) {
                    $TempDateParts = substr($tmpDateOfDay, 0, 10);
                    $arrTempDateParts = explode("-", $TempDateParts);
                    if (count($arrTempDateParts) == 3) {
                        $tDate = mktime(0, 0, 0, $arrTempDateParts[1], $arrTempDateParts[2], $arrTempDateParts[0]);
                        //$nameDay = date("l", $tDate);
                        $nbrDay = $this->calcDayOfWeek($tDate);
                        // get entry in $tblWorkToDoPerDay[], change status to 'not work' and set 'work to do for this day' = 0
                        //if ( $nbrDay >= 0 && $nbrDay < 8 ) $workDaysOffMinutes += $tblWorkday[$nbrDay];
                        $indexDay = 0;
                        do {
                            if ($tblWorkToDoPerDay[$indexDay] == $tDate) {
                                $tblWorkToDoPerDay[$indexDay]['timeToDoMinutes'] = 0;
                                $indexDay = count($tblWorkToDoPerDay);
                            }
                            $indexDay += 1;
                        } while ($indexDay < count($tblWorkToDoPerDay));
                    }
                }
            }
        }

        $workToBeDoneMinutes = 0;
        for ($i = 0; $i < count($tblWorkToDoPerDay); $i++) {
            $workToBeDoneMinutes += $tblWorkToDoPerDay[$i]['timeToDoMinutes'];
        }

        $resMinutes = $workDoneMinutes - $workToBeDoneMinutes + $workLastYear;

	// get all bonus times between start and enddate
    	$dbStartDate = date("Y-m-d", $startDate);
        $dbEndDate = date("Y-m-d", $endDate);
	$sql = "SELECT bonus_minutes FROM aplan_bonus_times WHERE user=? AND bonus_date >= ? AND bonus_date <= ?";
	$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
	if (
		$stmt->prepare($sql) &&
		$stmt->bind_param("iss", $use , $dbStartDate, $dbEndDate ) &&
		$stmt->execute() &&
		$stmt->bind_result($mins)
	) {

		while ($stmt->fetch() ) {
			$resMinutes += $mins;
		}

		$stmt->close();
	 }
        return $resMinutes;
    }

    function TimeToInt($tvar)
    {
        $el = explode(":", $tvar ) ;
        if (count($el) != 2) return 0;
        $result = intval($el[0]) * 60;
        $result += intval($el[1]);
        return $result;
    }

    function calcDayOfWeek($tDate)
    {
        $timestamp = $this->getMktimeFromString($tDate);
        $nameDay = date("w", $timestamp);
        $nbrDay = -1;

        if ($nameDay == 0 ) {
            $nbrDay = 6;
        } else {
            $nbrDay = $nameDay - 1;
        }
        /*
        if ($nameDay == "Sunday") $nbrDay = 6;
        if ($nameDay == "Monday") $nbrDay = 0;
        if ($nameDay == "Tuesday") $nbrDay = 1;
        if ($nameDay == "Wednesday") $nbrDay = 2;
        if ($nameDay == "Thursday") $nbrDay = 3;
        if ($nameDay == "Friday") $nbrDay = 4;
        if ($nameDay == "Saturday") $nbrDay = 5;
        */

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
    function getOverhoursUntilDateOld($user, $day, $month, $year)
    {
        // mktime( [int $Stunde [, int $Minute [, int $Sekunde [, int $Monat [, int $Tag [, int $Jahr [, int $is_dst]]]]]]] )
        $userStartYear = $this->getUserStartYear($user);
        $endDate = mktime(0, 0, 0, $month, $day, $year);
        $runningDay = 1;
        // need to get user startdate
        //$tempDate = mktime(0,0,0,1,$runningDay, $userStartYear);
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT startdate FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?")) {
            $stmt->bind_param("i", $user);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpStartDate);
                if ($stmt->fetch()) {
                    $tmpStartDate = substr($tmpStartDate, 0, 10);

                    //$arrStartDate = preg_split("/-/", $tmpStartDate);
                    $arrStartDate = explode("-", $tmpStartDate, 3);
                    if (count($arrStartDate) == 3) {
                        $tempDate = mktime(0, 0, 0, $arrStartDate[1], $arrStartDate[2], $arrStartDate[0]);
                        $stmt->close();
                    } else {
                        return 0;
                    }
                } else {
                    return 0;
                }
            }
        }
        $minutes = 0;

        // get overhours last year
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT alteueberstunden FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?")) {
            $stmt->bind_param("i", $user);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpHoursLastYear);
                if ($stmt->fetch()) $minutes += $tmpHoursLastYear * 60;
            }
            $stmt->close();
        }

        if ($endDate <= $tempDate) {
            return $minutes;
        }

        do {
            $workDoneMinutes = 0;
            // get work done on that date
            $searchdate = date("Y-m-d", $tempDate);
            $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
            if ($stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=? AND date=?")) {
                $stmt->bind_param("is", $user, $searchdate);
                if ($stmt->execute()) {
                    $stmt->bind_result($tmpHours);
                    while ($stmt->fetch()) {
                        //$arrWork = preg_split('/:/', $tmpHours, -1, PREG_SPLIT_NO_EMPTY);
                        $arrWork = explode(":", $tmpHours);
                        if (count($arrWork) == 3) {
                            $workDoneMinutes += (int)$arrWork[1] + (int)($arrWork[0] * 60);
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
            if ($nameDay == "Sunday") $nbrDay = 7;
            if ($nameDay == "Monday") $nbrDay = 1;
            if ($nameDay == "Tuesday") $nbrDay = 2;
            if ($nameDay == "Wednesday") $nbrDay = 3;
            if ($nameDay == "Thursday") $nbrDay = 4;
            if ($nameDay == "Friday") $nbrDay = 5;
            if ($nameDay == "Saturday") $nbrDay = 6;

            $ttt = -1.0;
            if ($nbrDay < 6) {
                $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
                if ($stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix . "workhours WHERE user=? AND workday=?")) {
                    $stmt->bind_param("ii", $user, $nbrDay);
                    if ($stmt->execute()) {
                        $stmt->bind_result($tmpWorkhours);
                        if ($stmt->fetch()) {
                            $ttt = $tmpWorkhours;
                            $minutesToDo = 60.0 * (double)$tmpWorkhours;
                        }
                    }
                    $stmt->close();
                }
            }

            // get holliday state, 1 is normal work (so count hours to be done) everything else is a day off, so no hours have to be done
            $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
            if ($stmt->prepare("SELECT holliday_id FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND dateofday=?")) {
                $searchdate = $searchdate . " 00:00:00";
                $stmt->bind_param("is", $user, $searchdate);
                if ($stmt->execute()) {
                    $stmt->bind_result($tmpHolliday);
                    if ($stmt->fetch()) {
                        if ($tmpHolliday == 1) {
                            // do nothing
                        } else {
                            $minutesToDo = 0;
                        }
                    }
                }
                $stmt->close();
            } else $stmt->close();

            // calc changes - if there was more to time to be spent on work, a negative value will be the result
            $minutes += $workDoneMinutes - $minutesToDo;
            //if ( date("Y-m-d", $tempDate) == '2012-01-04' ) return $ttt . " " . $workDoneMinutes ." / " . $minutesToDo;

            // increment date
            $tempDate = mktime(0, 0, 0, date("m", $tempDate), date("d", $tempDate) + 1, date("Y", $tempDate));
        } while ($tempDate < $endDate);

        return $minutes;
    }

    function createIfNotExistsDaydescription($user, $date)
    {
        if (!$this->existDaydescription($user, $date)) {
            $this->insertDaydescription($user, $date);
        }
    }

    function existDaydescription($user, $date)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT id FROM aplan_daydescriptions WHERE user_id=? AND workday=?");

        if ($stmt->error !== "") {
            throw new Error($stmt->error);
            return false;
        }

        $stmt->bind_param("is", $user, $date);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->bind_result($id);

        if (!$stmt->fetch()) {
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }

    function insertDaydescription($user, $date)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("INSERT INTO aplan_daydescriptions (user_id, workday, description) VALUES (?,?,'')");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("is", $user, $date);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;

    }

    function createIfNotExistsWorkday($user, $date)
    {
        $missingWorkareas = $this->existWorkday($user, $date);

        if ($missingWorkareas !== false) {
            $this->insertWorkday($user, $date, $missingWorkareas);
        } else {
            echo "false";
        }

    }

    function existWorkday($user, $date)
    {
        $missingWorkareas = array();
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        //$stmt->prepare("SELECT id FROM aplan_workday WHERE user_id=? AND date=? AND workfield_id = ?");
        $stmt->prepare("SELECT rank FROM aplan_workfields WHERE user = ? AND aplan_workfields.rank NOT IN (SELECT workfield_id FROM aplan_workday WHERE date=? AND user_id=?)");
        if ($stmt->error) {
            echo $stmt->error;
        }
        $stmt->bind_param("isi",$user,$date, $user );
        $stmt->execute();
        $stmt->bind_result($waMissing);
        while ($stmt->fetch()) {
            $missingWorkareas[] = $waMissing;
        }
        $stmt->close();
        return $missingWorkareas;

        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("isi", $user, $date, $wa);
        $stmt->bind_result($id);

        for ($i = 0; $i < count($definedWorkareas); $i++) {
            $wa = $definedWorkareas[$i];

            if (!$stmt->execute()) {
                $stmt->close();
                return false;
            }

            if (! $stmt->affected_rows == 0 ) {
                $missingWorkareas[] = $wa;
            }
        }
        $stmt->close();
        return $missingWorkareas;
    }

    function insertWorkday($user, $date, $missingWorkareas)
    {
        if (count($missingWorkareas) == 0 ) return;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();

        // put count($ranks) records into table aplan_workday with user and date
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("INSERT INTO aplan_workday (user_id, date, workfield_id, hours) VALUES (?,?, ?, '00:00')");
        $rk = 0;
        $stmt->bind_param("isi", $user, $date, $rk);

        for ($i = 0; $i < count($missingWorkareas); $i++) {
            $rk = $missingWorkareas[$i];
            $stmt->execute();
        }

        $stmt->close();


    }

    function createIfNotExistsArbeitstage($userid, $date ) {
        if ( ! $this->existArbeitstag($userid, $date) ) {
            $this->createArbeitstag($userid, $date);
        }
    }

    function createArbeitstag($userid, $date) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("INSERT INTO aplan_arbeitstage (user_id, dateofday, holliday_id, holliday_text) VALUES (?,?,1, '')");
        if ($stmt->error) {
            echo $stmt->error;
        }
        $stmt->bind_param("is",$userid,$date );
        $stmt->execute();
        $stmt->close();
    }

    function existArbeitstag($userid, $date) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT id FROM aplan_arbeitstage WHERE user_id = ? AND dateofday=?");
        if ($stmt->error) {
            echo $stmt->error;
        }
        $stmt->bind_param("is",$userid,$date );
        $stmt->execute();
        $stmt->bind_result($id);

        if ($stmt->fetch()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }

    }

    function getWorkdayId($userid, $date) {
        $id = -1;
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT id FROM aplan_arbeitstage WHERE user_id = ? AND dateofday=?");
        if ($stmt->error) {
            echo $stmt->error;
        }
        $stmt->bind_param("is",$userid,$date );
        $stmt->execute();
        $stmt->bind_result($id);

        $stmt->fetch();
        return $id;
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
    function restapi_helper_getInfoDay($id, $date)
    {
            // check if day exists in tables aplan_daydescrptions and aplan_workday
            // if not, create the day by inserting empty datasets
        $this->createIfNotExistsDaydescription($id, $date);
        $this->createIfNotExistsWorkday($id, $date);
        $this->createIfNotExistsArbeitstage($id, $date);

        $dayInfo = array();
        $dayInfo['dateOfDay'] = $date;

        $dayInfo['id'] = $this->getWorkdayId($id, $date);

        $dayInfo['schedule'] = $this->getSchedule($id, $date);

        $dayInfo['inputblocked'] = $this->restapi_isBlocked($id, $date);

        $dayInfo['bonus'] = $this->restapi_get_bonus_time($id, $date);

        //@test: get from to workhours
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT timefrom, timeto, id FROM " . CConfig::$db_tbl_prefix . "timefromto WHERE user_id=? AND dateofday=? ORDER BY timefrom");

        if ($stmt->error !== "") {
            echo $stmt->error;
            die;
        }
        $stmt->bind_param("is", $id, $date);
        $stmt->execute();
        $tfrom = "";
        $tto = "";
        $stmt->bind_result($tfrom, $tto, $timeId);
        $idxtimefromto = 0;

        $dayInfo['worktime'] = array();

        while ($stmt->fetch()) {
            $dayInfo['worktime'][] = array();
            $dayInfo['worktime'][$idxtimefromto]['id'] = $timeId;
            $dayInfo['worktime'][$idxtimefromto]['from'] = substr($tfrom, 0, 5);
            $dayInfo['worktime'][$idxtimefromto]['to'] = substr($tto, 0, 5);
            $idxtimefromto++;
        }
        $stmt->close();

        //@test: get workareas
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT A.hours AS hours, B.rank, A.id, B.description FROM " . CConfig::$db_tbl_prefix . "workday AS A LEFT JOIN " . CConfig::$db_tbl_prefix . "workfields AS B ON A.workfield_id=B.rank WHERE A.user_id=? AND A.date=? AND B.user = ? AND B.is_visible = 1 ORDER BY rank");
        $stmt->bind_param("isi", $id, $date, $id);
        $hours = "";
        $rank = "";
        $stmt->execute();
        echo $stmt->error;
        $stmt->bind_result($hours, $rank, $workdayId, $descriptive);

        $idxWorkdone = 0;

        while ($stmt->fetch()) {
            $dayInfo['workdone'][] = array();
            $dayInfo['workdone'][$idxWorkdone]['hours'] = substr($hours, 0, 5);
            $dayInfo['workdone'][$idxWorkdone]['rank'] = $rank;
            $dayInfo['workdone'][$idxWorkdone]['descriptive'] = $descriptive;
            $idxWorkdone++;
        }
        $stmt->close();

        //@test: get comment
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT description FROM " . CConfig::$db_tbl_prefix . "daydescriptions WHERE user_id=? AND workday=? LIMIT 0,1");
        $stmt->bind_param("is", $id, $date);
        $stmt->execute();
        $descr = "";
        $stmt->bind_result($descr);
        $stmt->fetch();
        $dayInfo['comment'] = $descr;
        $stmt->close();

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT id, holliday_id, holliday_text FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND dateofday=? LIMIT 0,1");
        $stmt->bind_param("is", $id, $date);

        $idArbeitstag = 0;
        $hollid = "";
        $holltext = "";

        if ($stmt->execute()) {
            $stmt->bind_result($idArbeitstag, $hollid, $holltext);
            $stmt->fetch();
        }

        $stmt->close();

        if ($hollid != "") {
            $dayInfo['hollidayStatus'] = array();
            $dayInfo['hollidayStatus']['id'] = $idArbeitstag;
            $dayInfo['hollidayStatus']['hollidayId'] = $hollid;
            $dayInfo['hollidayStatus']['hollidayText'] = $holltext;

        } else {
            $dayInfo['hollidayStatus'] = array();
            $dayInfo['hollidayStatus']['id'] = -1;
            $dayInfo['hollidayStatus']['hollidayId'] = 1;
            $dayInfo['hollidayStatus']['hollidayText'] = "";

        }

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT id, km, fromwhere, towhere FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=? AND day=?");
        $stmt->bind_param("is", $id, $date);

        $dayInfo['travel'] = array();

        if ($stmt->execute()) {
            $stmt->bind_result($idKm, $km, $kmFrom, $kmTo);
            $i = 0;
            while ($stmt->fetch()) {
                $dayInfo['travel'][] = array();
                $dayInfo['travel'][$i]['kmFrom'] = $kmFrom;
                $dayInfo['travel'][$i]['kmTo'] = $kmTo;
                $dayInfo['travel'][$i]['km'] = $km;
                $dayInfo['travel'][$i]['id'] = $idKm;
                $i++;
            }

            $stmt->close();
        }

        // if date is monday, we need some additional data - before
        $dateparts = explode("-", $date);

        if ( count($dateparts) < 3 ) return $dayInfo;

        $ndate = mktime(0, 0, 0, $dateparts[1], $dateparts[2], $dateparts[0] );

	$params = array();
	$params['id'] = $id;
	$params['date'] = $date;

        if ( date('N', $ndate) == 1) {
            $dayInfo['timeAccount'] = $this->calcTimeAccount($id, $date);
            $dayInfo['remainVavationBeforeDate'] = $this->calcVacationRemainBeforeDate($id, $date);
	    $dayInfo['remainVacationTimeBeforeDate'] = $this->calcVacationTimeRemainBeforeDate($params);
            $dayInfo['vacationPeriod'] = $this->getVacationPeriod($id, $date);
            $dayInfo['remainHollidayBeforeDate'] = $this->calcHollidayRemainBeforeDate($id, $date);
            $dayInfo['hollidayPeriod'] = $this->getHollidayPeriod($id, $date);
	    $params['hollidayperiod'] = $dayInfo['hollidayPeriod'];
	    $dayInfo['remainHollidayTimeBeforeDate'] = $this->calcHollidayTimeRemainBeforeDate($params);
        }

        return $dayInfo;

    }


	function calcVacationTimeRemainBeforeDate($params) {
		$user = $params["id"];
		$date = $params["date"];

		$totalTime = $this->getVacationTimeTotal($user, $date);

		$startDateVacationPeriod = $this->getVacationPeriodStart($user, $date);

		$timeTaken = $this->getVacationTimeTakenInPeriod($user, $startDateVacationPeriod, $date);

		$result = $totalTime - $timeTaken;

		return $result;
	}

	function calcHollidayTimeRemainBeforeDate($params) {
		$user = $params["id"];
		$date = $params["date"];

		$totalTime = $this->getHollidayTimeTotal($user, $date);
		$startDateHollidayperiod = $this->getHollidayPeriodStart($user, $date);
		$timeTaken = $this->getHollidayTimeTakenInPeriod($user, $startDateHollidayperiod, $date);
		$result = $totalTime - $timeTaken;
		return $result;
	}


	function restapi_get_bonus_time($id, $date) {
		$bonusTimes = array();
		$bonusIndex = 0;

		$sql = "SELECT idBonus, bonus_minutes, short_description FROM aplan_bonus_times WHERE user=? AND bonus_date = ?";
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if (
			$stmt->prepare($sql) &&
			$stmt->bind_param("is", $id, $date) &&
			$stmt->execute() &&
			$stmt->bind_result($id, $minutes, $description)
		) {
			while ($stmt->fetch() ) {
				$bonusTimes[] = array();
				$bonusTimes[$bonusIndex]['id'] = $id;
				$bonusTimes[$bonusIndex]['minutes'] = $minutes;
				$bonusTimes[$bonusIndex]['description'] = $description;
				$bonusIndex++;
			}
			$stmt->close();
			return $bonusTimes;

		} else {
			return $bonusTimes;
		}
	}

	function restapi_isBlocked($id, $date) {
		$sql = "SELECT idFreeze FROM aplan_freeze WHERE user=? AND freezedate >= ?";
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if (
			$stmt->prepare($sql) &&
			$stmt->bind_param("is", $id, $date) &&
			$stmt->execute() &&
			$stmt->bind_result($idFreeze) &&
			$stmt->fetch()
		) {
			$stmt->close();
			return true;

		} else {
			return false;
		}
	}

    function restapi_getCurrentStatus($id, $date) {
        $dayInfo = array();

        $dayInfo['timeAccount'] = $this->calcTimeAccount($id, $date);
        $dayInfo['remainVavationBeforeDate'] = $this->calcVacationRemainBeforeDate($id, $date);
        $dayInfo['vacationPeriod'] = $this->getVacationPeriod($id, $date);
        $dayInfo['remainHollidayBeforeDate'] = $this->calcHollidayRemainBeforeDate($id, $date);
        $dayInfo['hollidayPeriod'] = $this->getHollidayPeriod($id, $date);

        return $dayInfo;
    }

    function getSchedule($user, $date) {
        $dow = date('N', $this->getMktimeFromString($date));

        if ( $dow == 0 ) {
            $dow = 6;
        } else {
            $dow--;
        }

        //$dayOfWeek =
        $sql = "SELECT A.label, B.time_from, B.time_to, B.idScheduleItem ";
        $sql .= "FROM aplan2_schedules AS A ";
        $sql .= "INNER JOIN aplan2_schedule_items AS B ON A.idSchedule=B.idSchedule ";
        $sql .= "WHERE userid=? AND startdate <= ? AND enddate >= ? AND dayOfWeek=? ";
        $sql .= "ORDER BY B.time_from";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $arrData = array();
        if ( ! $stmt->bind_param("issi", $user, $date, $date, $dow) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($label, $from, $to, $iditem)
         ) {
            $index = 0;
            while ($stmt->fetch() ) {
                $arrData[] = array();
                $arrData[$index]['label'] = $label;
                $arrData[$index]['timeFrom'] = $from;
                $arrData[$index]['timeTo'] = $to;
		$arrData[$index]['iditem'] = $iditem;
                $index++;
            }

        }

        $stmt->close();
        return $arrData;

    }

    function getHollidayPeriod($user, $date) {
        $startHollidayPeriodDate = $this->getHollidayStartDatePeriod($user, $date);
        $endHollidayPeriodDate = $this->getHollidayEndDatePeriod($user, $date);
        $data = array();
        $data['hollidayStart'] = $startHollidayPeriodDate;
        $data['hollidayEnd'] = $endHollidayPeriodDate;
        return $data;
    }

    function getVacationPeriod($user, $date) {
        $startDateVactionPeriod = $this->getVacationPeriodStart($user, $date);
        $endDateVacationPeriod = $this->getVacationPeriodEnd($user, $date);
        $data = array();
        $data['vacationStart'] = $startDateVactionPeriod;
        $data['vacationEnd'] = $endDateVacationPeriod;
        return $data;
    }

    function calcHollidayRemainBeforeDate($user, $date) {
        // get total number hollidays for period
        $totalNumberHollidays = $this->getHollidaysTotal($user, $date);

        // get startdate from holliday setup table
        $startHollidayTable = $this->getHollidayStartDatePeriod($user, $date);

        // get number of days already taken before $date
        $nbrDaysTakenBeforeDate = $this->getHollidaysTakenBetween($user, $startHollidayTable, $date);

        // do the math
        $result = $totalNumberHollidays - $nbrDaysTakenBeforeDate;

        return $result;
    }

    function getHollidaysTakenBetween($user, $startDate, $endDate) {
        $sql = "SELECT COUNT(id) FROM aplan_arbeitstage WHERE user_id=? AND dateofday >= ? AND dateofday < ? AND holliday_id=2";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $nbrDaysTaken = 0;
        if ( ! $stmt->bind_param("iss", $user, $startDate, $endDate) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($nbrDaysTaken) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $nbrDaysTaken;
    }

    function getHollidayEndDatePeriod($user, $date) {
        $sql = "SELECT enddate FROM aplan_holliday_setup WHERE userid=? AND startdate <= ? AND enddate > ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $endDate = "2010-01-01";
        if ( ! $stmt->bind_param("iss", $user, $date, $date) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($endDate) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $endDate;
    }

    function getHollidayStartDatePeriod($user, $date) {
        $sql = "SELECT startdate FROM aplan_holliday_setup WHERE userid=? AND startdate <= ? AND enddate > ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $startDate = "2010-01-01";
        if ( ! $stmt->bind_param("iss", $user, $date, $date) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($startDate) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $startDate;
    }

    function getHollidaysTotal($user, $date) {
        $sql = "SELECT nbrdays FROM aplan_holliday_setup WHERE userid=? AND startdate <= ? AND enddate > ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $nbrDaysTotal = 0;
        if ( ! $stmt->bind_param("iss", $user, $date, $date) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($nbrDaysTotal) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $nbrDaysTotal;
    }

    function calcVacationRemainBeforeDate($user, $date) {

        // get vacation period and number of allocated days where date is in (can only be one, due to constraint: no overlapping periods!)
        $totalDays = $this->getVacationDaysTotal($user, $date);

        // get startdate from vacation table
        $startDateVactionPeriod = $this->getVacationPeriodStart($user, $date);
        // get all vacation days taken in this period
        $daysTaken = $this->getVacationDaysTakenInPeriod($user, $startDateVactionPeriod, $date);

        // do the math!
        $result = $totalDays - $daysTaken;

        // return the result!
        return $result;
    }

    function getVacationDaysTakenInPeriod($user, $startDate, $endDate) {
        $sql = "SELECT COUNT(id) FROM aplan_arbeitstage WHERE user_id=? AND dateofday >= ? AND dateofday < ? AND holliday_id=3";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $nbrDaysTaken = 0;
        if ( ! $stmt->bind_param("iss", $user, $startDate, $endDate) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($nbrDaysTaken) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $nbrDaysTaken;
    }

    function getVacationPeriodEnd($user, $date) {
        $sql = "SELECT enddate FROM aplan_vacation_setup WHERE userid=? AND enddate > ? AND startdate <= ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $endDate = "2010-01-01";
        if ( ! $stmt->bind_param("iss", $user, $date, $date) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($endDate) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $endDate;
    }

    function getHollidayPeriodStart($user, $date) {
	$sql = "SELECT startdate FROM aplan_holliday_setup WHERE userid=? AND enddate > ? AND startdate <=?";
	$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
	if (! $stmt->prepare($sql)) return 0;
	$startDate = "2010-01-01";
	if (! $stmt->bind_param("iss", $user, $date, $date) ) return 0;
        if (
            $stmt->execute() &&
            $stmt->bind_result($startDate) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
	return $startDate;
    }

    function getVacationPeriodStart($user, $date) {
        $sql = "SELECT startdate FROM aplan_vacation_setup WHERE userid=? AND enddate > ? AND startdate <= ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $startDate = "2010-01-01";
        if ( ! $stmt->bind_param("iss", $user, $date, $date) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($startDate) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $startDate;
    }


	//
	function getTableDaysWithHollidayId($user, $start, $end, $hollidayId) {
		$sql = "SELECT dateofday FROM aplan_arbeitstage WHERE holliday_id=? AND dateofday >= ? AND dateofday < ? AND user_id=?";
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		$table = array();
		if ( ! $stmt->prepare($sql) ) return $table;

		if ( ! $stmt->bind_param("issi", $hollidayId, $start, $end, $user) ) return $table;

		if ( ! $stmt->execute() ) return $table;

		if ( ! $stmt->bind_result($dateofday) ) return $table;

		while ($stmt->fetch() ) {
			$table[] = $dateofday;
		}

		$stmt->close();
		return $table;
	}

	//! Returns an ID for the applicable schedule (according to refdate)
	function getScheduleId($user, $refdate) {
		$refId = -1;
		$sql = "SELECT idSchedule FROM aplan2_schedules WHERE userid = ? AND startdate <= ? AND enddate >= ?";

		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if (! $stmt->prepare($sql) ) return $refId;
		if (! $stmt->bind_param("iss", $user, $refdate, $refdate) ) return $refId;
		if (! $stmt->execute() ) return $refId;
		if (! $stmt->bind_result($refId) ) return $refId;
		$stmt->fetch();
		$stmt->close();
		return $refId;
	}

	// Retrieves the work to for a given schedule
	function getTableWorkToDo($idSchedule) {
		// prepare table
		$table = array();
		for ($i = 0; $i < 7; $i++ ) {
			$table[] = 0; // table indices are the days 0..6
		}
		$sql = "SELECT dayOfWeek, time_from, time_to FROM aplan2_schedule_items WHERE idSchedule = ? ORDER BY dayOfWeek";

		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if (! $stmt->prepare($sql) ) return $table;
		if (! $stmt->bind_param("i", $idSchedule) ) return $table;
		if (! $stmt->execute() ) return $table;
		if (! $stmt->bind_result($dow, $from, $to) ) return $table;
		while ($stmt->fetch() ) {
			if ($dow >= 0 && $dow <= 6) {
				$table[$dow] += $this->myTimeToInt($to);
				$table[$dow] -= $this->myTimeToInt($from);
			}
		}

		$stmt->close();
		return $table;

	}

	function getTimeTakenInPeriodByType($user, $start, $end, $typeFree) {
		$workTable = array();

		// get table with days where vacationtime has been chosen
		$workTable["days"] = $this->getTableDaysWithHollidayId($user, $start, $end, $typeFree);

		// get ID of applicable workplan
		$idSchedule = $this->getScheduleId($user, $end);

		if ($idSchedule < 0 ) return 0;

		// load work to do table
		$workTable["todo"] = $this->getTableWorkToDo($idSchedule);

		if ( count($workTable["todo"]) != 7 ) return 0;

		// run through the 'days' and transform it to the day of week and add the
		// time to do to the result
		$result = 0;
		for ($i = 0; $i < count($workTable["days"]); $i++ ) {
			$tempDate = strtotime($workTable["days"][$i]);
			$dow = date ("w", $tempDate);
			if ($dow == 0) $dow = 6;
			else {
				$dow--;
			}
			$result += $workTable["todo"][$dow];
		}
		return $result;

	}
	function getVacationTimeTakenInPeriod($user, $start, $end) {
		$result = $this->getTimeTakenInPeriodByType($user, $start, $end, 7);
		return $result;
	}

	function getHollidayTimeTakenInPeriod($user, $start, $end) {
		$result = $this->getTimeTakenInPeriodByType($user, $start, $end, 6);
		return $result;
	}

	function getVacationTimeTotal($user, $date) {
		$sql = "SELECT nbrminutes FROM aplan_vacation_setup WHERE userid=? AND enddate > ? AND startdate <= ?";
		$totalTime = 0;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if (! $stmt->prepare($sql) ) return 0;
		if (! $stmt->bind_param("iss", $user, $date, $date) ) return 0;
		if ( $stmt->execute() && $stmt->bind_result($totalTime) && $stmt->fetch() ) {

		} else {
			return 0;
		}

		$stmt->close();
		return $totalTime;
	}

	function getHollidayTimeTotal($user, $date) {
		$sql = "SELECT nbrminutes FROM aplan_holliday_setup WHERE userid=? AND enddate > ? AND startdate <= ?";
		$totalTime = 0;
		$stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
		if (! $stmt->prepare($sql) ) return 0;
		if (! $stmt->bind_param("iss", $user, $date, $date) ) return 0;
		if ( $stmt->execute() && $stmt->bind_result($totalTime) && $stmt->fetch() ) {

		} else {
			return 0;
		}

		$stmt->close();
		return $totalTime;
	}

    function getVacationDaysTotal($user, $date) {
        $sql = "SELECT nbrdays FROM aplan_vacation_setup WHERE userid=? AND enddate > ? AND startdate <= ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        if (! $stmt->prepare($sql)) return 0;
        $totalDays = 0;
        if ( ! $stmt->bind_param("iss", $user, $date, $date) ) return 0;

        if (
            $stmt->execute() &&
            $stmt->bind_result($totalDays) &&
            $stmt->fetch()
        ) {

        }

        $stmt->close();
        return $totalDays;
    }

    function getPeriodStart($user) {
        $sql = "SELECT startdate FROM aplan_users WHERE id=?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        $sdate = "2010-01-01";

        if (! $stmt->prepare($sql)) return 0;

        if ( ! $stmt->bind_param("i", $user) ) return 0;

        if ( $stmt->execute() && $stmt->bind_result($sdate) && $stmt->fetch() ) {

        }

        $stmt->close();
        return $sdate;

    }

    function getPeriodStartWithDate($user, $date) {
        $sdate = $this->getPeriodStart($user);
        $sql = "SELECT MAX(A.period_start) FROM aplan_periods AS A ";
        $sql .= "LEFT JOIN aplan_periods_start_values AS B ";
        $sql .= "ON A.idPeriod = B.idPeriod ";
        //$sql .= " WHERE B.user=? AND A.period_start <= ? AND A.period_end >= ?";
        $sql .= " WHERE B.user=? AND A.period_start <= ?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();
        //$sdate = "2010-01-01";

        if (! $stmt->prepare($sql)) return 0;

        if ( ! $stmt->bind_param("is", $user, $date) ) return 0;

        if ( $stmt->execute() && $stmt->bind_result($sdate) && $stmt->fetch() ) {

        }

        $stmt->close();
        return $sdate;
    }

    function getMktimeFromString($datestring) {
        $arrDate = explode("-", $datestring );

        if ( count($arrDate) < 3 ) return time();

        $dtObject = mktime(0,0, 0, $arrDate[1], $arrDate[2], $arrDate[0]);

        return $dtObject;
    }

    function calcTimeAccount($user, $date) {
        // get startdate (YYYY-MM-DD)
        $arrStartdate = explode("-", substr($this->getPeriodStartWithDate($user, $date), 0, 10) );
        if ( count($arrStartdate) < 3 ) return 0;

        $arrEnddate = explode("-", $date);

        if (count($arrEnddate) < 3 ) return 0;

        $dateTemp = mktime(0,0,0,$arrStartdate[1], $arrStartdate[2], $arrStartdate[0]);
	$startDate = $dateTemp;
        $dateEnd = mktime(0,0,0, $arrEnddate[1], $arrEnddate[2], $arrEnddate[0]);

        // make array from startdate to date, return if array length is zero (returns 0)
        $data = array();
        $index = 0;
        while ( $dateTemp < $dateEnd) {

            $data[] = array();
            $data[$index]['date'] = date("Y-m-d", $dateTemp);
            $data[$index]['workToDo'] = 0;
            $data[$index]['workDone'] = 0;
            $data[$index]['holliday'] = 1; // normal work day

            $index++;
            $dateTemp = mktime(0,0,0, date('m', $dateTemp), date('d', $dateTemp) + 1, date('Y', $dateTemp));
        }

        // get work to do for each day (from $startdate to $date) - from schedules
        // SELECT b.time_from, b.time_to FROM aplan2_schedule_items AS B LEFT JOIN aplan2_schedules AS A on B.idSchedule = A.idSchedule WHERE
        // A.userid = ? AND A.startdate <= ? AND A.enddate >= ?
        $sql = "SELECT B.time_from, B.time_to FROM aplan2_schedule_items AS B INNER JOIN aplan2_schedules AS A on B.idSchedule = A.idSchedule WHERE ";
        $sql .= "A.userid = ? AND A.startdate <= ? AND A.enddate >= ? AND dayOfWeek=? GROUP BY time_from";
        $pDate = "";
        $dow = "";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

        if (! $stmt->prepare($sql)) return $stmt->error;

        if ( ! $stmt->bind_param("issi", $user, $pDate, $pDate, $dow) ) return 0;


        for ($index = 0; $index < count($data); $index++ ) {
            $pDate = $data[$index]['date'];
            $dow = $this->calcDayOfWeek($pDate);
            $stmt->execute();
            $stmt->bind_result($f, $t);
            while ($stmt->fetch() ) {
                $data[$index]['workToDo'] += $this->TimeToInt($t) - $this->TimeToInt($f);
            }
        }
        $stmt->close();

        // get total work done for each day
        $sql = "SELECT hours FROM aplan_workday WHERE user_id=? AND date=?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

        if (! $stmt->prepare($sql)) return 0;

        if ( ! $stmt->bind_param("is", $user,$pDate) ) return 0;

        for ($index = 0; $index < count($data); $index++ ) {
            $pDate = $data[$index]['date'];

            $stmt->execute();
            $stmt->bind_result($h);
            while ($stmt->fetch() ) {
                $data[$index]['workDone'] += $this->TimeToInt(substr($h,0,5 ));
            }
        }
        $stmt->close();

        // get holliday status for each day
        $sql = "SELECT holliday_id FROM aplan_arbeitstage WHERE user_id=? AND dateofday=?";
        $stmt = $this->getDatabaseConnection()->getDatabaseConnection()->stmt_init();

        if (! $stmt->prepare($sql)) return 0;

        if ( ! $stmt->bind_param("is", $user,$pDate) ) return 0;

        $result = 0;
        for ($index = 0; $index < count($data); $index++ ) {
            $pDate = $data[$index]['date'];
            $stmt->execute();
            $stmt->bind_result($h);
            while ($stmt->fetch() ) {
                $result += $data[$index]['workDone'];

                if ($h == 1 ) {
                    $result -= $data[$index]['workToDo'];
                }

            }
        }
        $stmt->close();

	// retrieve bonus / subtraction times
	$strStartDate = date("Y-m-d", $startDate);
	$strEndDate = date("Y-m-d", $dateEnd);
	$sql = "SELECT bonus_minutes FROM aplan_bonus_times WHERE user=? AND bonus_date >= ? AND bonus_date < ?";
	$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
	if ($stmt->prepare($sql) &&
		$stmt->bind_param("iss", $user, $strStartDate, $strEndDate) &&
		$stmt->execute() && $stmt->bind_result($bonus) ) {

		while($stmt->fetch() ) {
			$result += $bonus;
		}

		$stmt->close();
	}


        return $result;
    }

    function insertDrivePairForUser($user, $dateOfDay) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("INSERT INTO aplan_kilometers (user_id, fromwhere, towhere, day, km) VALUES (?,'', '', ?, 0)");
        if ($stmt->error !== "") {
            throw new Error($stmt->error);
            return false;
        }

        $stmt->bind_param("is", $user, $dateOfDay);

        if (!$stmt->execute()) {
            throw new Error( $stmt->error );
            $stmt->close();
            return false;
        }

        $insertId = $stmt->insert_id;

        $stmt->close();
        return $insertId;
    }

    function insertTimePairForUser($user, $dateOfDay) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("INSERT INTO aplan_timefromto (user_id, timefrom, timeto, dateofday) VALUES (?,'00:00', '00:00', ?)");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("is", $user, $dateOfDay);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $insertId = $stmt->insert_id;

        $stmt->close();
        return $insertId;
    }

    function getDateOfDayForUser($user, $id) {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("SELECT dateofday FROM aplan_arbeitstage WHERE user_id=? AND id=?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("ii", $user, $id);

        if (! $stmt->execute() ) {
            throw new Error("Unable to fetch dataset");
        }
        $dateOfDay = "";
        $stmt->bind_result($dateOfDay);

        if (! $stmt->fetch() ) {
            throw new Error("Record not found user: $user and id: $id " . $stmt->error);
        }

        $stmt->close();
        return $dateOfDay;
    }

    function restapi_drivepair_delete($user, $pData) {
        $data = json_decode($pData);

        if (! isset($data->driveId)) {
            return false;
        }

        $idToDelete = $data->driveId;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("DELETE FROM aplan_kilometers WHERE user_id = ? AND id = ?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("is", $user, $idToDelete);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }


    function restapi_drivepair_create($user, $pData) {
        $data = json_decode($pData);
        if ( ! isset($data->workdayId)) {
            return "no data provided";
        }

        // get dateofday for id provided
        $dateOfDay = $this->getDateOfDayForUser($user, $data->workdayId);
        $insertedId = $this->insertDrivePairForUser($user, $dateOfDay);

        $travel = array();
        $travel["id"] = $insertedId;
        $travel["kmFrom"] = "";
        $travel["kmTo"] = "";
        $travel["km"] = 0;

        return $travel;
    }

    function restapi_timepair_create($user, $pData) {
        $data = json_decode($pData);
        if ( ! isset($data->workdayId)) {
            return "no data provided";
        }

        // get dateofday for id provided
        $dateOfDay = $this->getDateOfDayForUser($user, $data->workdayId);

        // insert empty record '00:00' (twice)
        $insertedId = $this->insertTimePairForUser($user, $dateOfDay);

        $worktime = array();
        $worktime['id'] = $insertedId;
        $worktime['from'] = '00:00';
        $worktime['to'] = '00:00';

        return $worktime;

    }

    function restapi_timepair_delete($user, $pData) {
        $data = json_decode($pData);

        if (! isset($data->idToDelete)) {
            return "no id to delete";
        }

        $idToDelete = $data->idToDelete;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("DELETE FROM aplan_timefromto WHERE user_id = ? AND id = ?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("is", $user, $idToDelete);

        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }

    function restapi_workday_update($user, $pData) {
        $data = json_decode($pData);

        if (! isset($data->dateOfDay) ) {
            throw new Error('Need dateOfDay');
        }
        $dateOfDay = $this->TransformDateToUS($data->dateOfDay);

        // save time from to
        if ( isset($data->worktime) && count($data->worktime) > 0) {
            $this->updateTimeFromTo($user, $data->worktime);
        }

        // save work done in areas
        if ( isset($data->workdone) && count($data->workdone) > 0) {
            $this->updateWorkDone($user, $dateOfDay, $data->workdone);
        }

        // save holliday status, hollidaytext
        if ( isset($data->hollidayStatus) ) {
            $this->updateHollidayStatus($user, $dateOfDay, $data->hollidayStatus);
        }

        // save day comment
        if ( isset($data->comment) ) {
             $this->updateDayComment($user, $dateOfDay, $data->comment);

        } else {
            echo "no comment";
        }

        // save travel
        if ( isset($data->travel) && count($data->travel) > 0) {
            $this->updateTravel($user, $data->travel);
        }
    }

    function updateTimeFromTo($user, $arrWorktime)
    {
        if (! is_array($arrWorktime) ) return;

        $from = "";
        $to = "";
        $id = -1;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("UPDATE aplan_timefromto SET timefrom=?, timeto=? WHERE id=? AND user_id=?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("ssis", $from, $to, $id, $user);

       for ($i = 0; $i < count($arrWorktime); $i++) {
                $from = $arrWorktime[$i]->from;
                $to = $arrWorktime[$i]->to;
                $id = $arrWorktime[$i]->id;

                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Error("Unable to update worktimes");
                }
            }


        $stmt->close();
        return true;

    }

    function updateWorkDone($user, $date, $arrWorkfields) {
        $rank = -1;
        $hours = "00:00";

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("UPDATE aplan_workday SET hours=? WHERE user_id=? AND date = ? AND workfield_id = ?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("sisi", $hours, $user, $date, $rank);

        for ($i = 0; $i < count($arrWorkfields) ; $i++) {
            $rank  = $arrWorkfields[$i]->rank;
            $hours = $arrWorkfields[$i]->hours;

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Error("Unable to update workfields");
            }
        }

        $stmt->close();
        return true;

    }

    function updateHollidayStatus($user, $date, $dataset) {

        if (! isset($dataset->hollidayId)) return;

        if (! isset($dataset->hollidayText)) return;

        $id = $dataset->hollidayId;
        $text = $dataset->hollidayText;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("UPDATE aplan_arbeitstage SET holliday_id=?, holliday_text=? WHERE user_id=? AND dateofday = ?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("isis", $id, $text, $user, $date);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Error("Unable to update holliday status");
        }

        $stmt->close();
        return true;

    }

    function updateDayComment($user, $date, $comment) {
        if (! isset($comment)) return false;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("UPDATE aplan_daydescriptions SET description=? WHERE user_id=? AND workday = ?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("sis", $comment, $user, $date);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Error("Unable to update daydescription");
        }

        $stmt->close();
        return true;
    }

    function updateTravel($user, $arrTravelData) {
        $from = "";
        $to = "";
        $id = -1;
        $km = 0;

        if (! isset($arrTravelData)) return;

        if ( count($arrTravelData) <= 0 ) return;
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $stmt->prepare("UPDATE aplan_kilometers SET km=?, fromwhere=?, towhere=? WHERE user_id=? AND id = ?");
        if ($stmt->error !== "") {
            return false;
        }

        $stmt->bind_param("issii", $km, $from, $to, $user, $id);

        for ($i = 0; $i < count($arrTravelData); $i++ ) {
            $from = $arrTravelData[$i]->kmFrom;
            $to = $arrTravelData[$i]->kmTo;
            $id = $arrTravelData[$i]->id;
            $km = $arrTravelData[$i]->km;

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Error("Unable to update travel");
            }
        }

        $stmt->close();
        return true;

    }
    /**
     *
     * Gives back the work to be done on a day
     * @param int $userid
     * @param int $workday Workday, 1:monday, 5: friday
     * @return array ['hours']['minutes']
     */

    function getWorkToDo($userid, $workday)
    {
        $hours = 0;
        $minutes = 0;
        $tempHours = 0.0;

        if ($workday > 7) {
            $ret = array();
            $ret['minutes'] = 0;
            $ret['hours'] = 0;
            return $ret;
        }

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT hours FROM " . CConfig::$db_tbl_prefix . "workhours WHERE user=? AND workday=?")) {
            $stmt->bind_param("ii", $userid, $workday);
            if ($stmt->execute()) {
                $stmt->bind_result($tempHours);
                if ($stmt->fetch()) {
                    $hours = $this->getIntpart($tempHours);
                    $minutes = $this->getDecpart($tempHours) * 60.0;

                } else {

                }
            } else {

            }

            $ret = array();
            $ret['minutes'] = round($minutes, 2);
            $ret['hours'] = $hours;
            return $ret;
        } else {
            $ret = array();
            $ret['minutes'] = 0;
            $ret['hours'] = 0;
            return $ret;
        }
    }

    function getIntpart($nbr)
    {
        $ret = intval($nbr, 10);
        return $ret;
    }

    function getDecpart($nbr)
    {
        $ret = $nbr;
        $ret -= $this->getIntpart($nbr);
        return $ret;
    }


    function GetHollidayDescription($userid, $mdate)
    {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;

        $dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);

        $dateMySql = TransformDateToUS($mdate);

        $ssql = "SELECT holliday_text FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=$userid AND dateofday='$dateMySql'";
        //return $res;

        $res = $dbx->ExecuteSql($ssql);

        if ($res->num_rows > 0) {
            if ($ff = $res->fetch_row()) {
                return $ff[0];
            }
        }

        return "";
    }

    function getUserStartYear($userid)
    {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;

        $dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
        $ssql = "SELECT YEAR(startdate) FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
        $res = $dbx->ExecuteSql($ssql);
        if ($res) {
            if ($ff = $res->fetch_row()) {
                return $ff[0];
            }
        }

        return "Jahr nicht gefunden";
    }

    function getDisplayName($userid)
    {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;

        $dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);
        $dbx->ExecuteSql("SET NAMES utf8");
        //$dateMySql = TransformDateToUS($mdate);
        $ssql = "SELECT dname FROM " . CConfig::$db_tbl_prefix . "users WHERE id=$userid";
        $res = $dbx->ExecuteSql($ssql);
        if ($res) {
            if ($ff = $res->fetch_row()) {
                return $ff[0];
            }
        }

        return "Benutzername nicht gefunden";
    }

    function GetKilometers($userid, $mdate)
    {
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

        while ($ff = $res->fetch_row()) {
            $ret_val .= "kmdazu();\n";
            $ret_val .= "document.getElementById('kmvon$counter').value = '" . $ff[1] . "'; \n";
            $ret_val .= "document.getElementById('kmbis$counter').value = '" . $ff[2] . "'; \n";
            $ret_val .= "document.getElementById('kmanzahl$counter').value = '" . $ff[0] . "'; \n";

            $counter++;
        }

        $ret_val .= "</script>";

        return $ret_val;
    }

    // B - A
    function TimeSubtract($clockA, $clockB)
    {
        $cA = explode(":", $clockA);
        $cB = explode(":", $clockB);

        if ($cA[1] > $cB[1]) {
            $cA[0]++;
            $cB[1] += 60; // one hour
        }

        $r_minute = $cB[1] - $cA[1];
        $r_hour = $cB[0] - $cA[0];

        return $r_hour . ":" . $r_minute;

    }

    function TimeSum($clockA, $clockB)
    {
        $cA = explode(":", $clockA);
        $cB = explode(":", $clockB);

        $r_minute = $cA[1] + $cB[1];
        $r_hour = $cA[0] + $cB[0];
        while ($r_minute >= 60) {
            $r_minute -= 60;
            $r_hour++;
        }

        return $r_hour . ":" . $r_minute;
    }

    function TimeCompare($clockA, $clockB)
    {
        $cA = explode(":", $clockA);
        $cB = explode(":", $clockB);

        if ($cA[0] < $cB[0]) {
            return -1;
        }

        if ($cA[0] > $cB[0]) {
            return 1;
        }

        if ($cA[1] < $cB[1]) {
            return -1;
        }

        if ($cA[1] > $cB[1]) {
            return 1;
        }

        return 0;
    }

    function DateToDay($mdate)
    {
        $dt = new DateTime($mdate);

        if (!$dt) {
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

    function DayToNumber($mday)
    {
        $ret_val = $mday;
        $ret_val = str_replace("Montag", "1", $ret_val);
        $ret_val = str_replace("Dienstag", "2", $ret_val);
        $ret_val = str_replace("Mittwoch", "3", $ret_val);
        $ret_val = str_replace("Donnerstag", "4", $ret_val);
        $ret_val = str_replace("Freitag", "5", $ret_val);

        return $ret_val;
    }

    function ConvertFloatToTime($mfloat)
    {
        $intpart = (int)$mfloat;

        $decpart = $mfloat - $intpart;

        if ($decpart < 10) {
            $decpart *= 10;
        }
        $decpart = (60 * $decpart) / 100;

        if ($decpart < 10) {
            $decpart *= 10;
        }

        if ($decpart == 0) {
            $decpart = "00";
        }

        $ret_val = $intpart . ":" . $decpart;
        return $ret_val;
    }

    function ConvertTimeToFloat($mtime)
    {
        $timeparts = explode(":", $mtime);
        $ret_val = $timeparts[0];
        $ret_val += ($timeparts[1] / 3) * 5;

        return $ret_val;
    }

    function GetDayStatusAll()
    {
        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;

        $dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);

        $ssql = "SELECT id, beschreibung, typ FROM " . CConfig::$db_tbl_prefix . "holliday";

        $resquery = $dbx->ExecuteSQL($ssql);

        $ret_val = array();
        $a = 0;

        while ($gg = $resquery->fetch_row()) {

            $ret_val[$a][] = $gg[0];
            $ret_val[$a][] = $gg[1];
            $ret_val[$a][] = $gg[2];

            $a++;
        }

        return $ret_val;
    }

    function GetTimeFromTo($userid, $date)
    {
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

        while ($ff = $res->fetch_row()) {
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

    function CalendarWeekStartDate($week, $year)
    {

        date_default_timezone_set("Europe/Brussels");

        $firstofyear = mktime(0, 0, 0, 1, 1, $year);

        $firstday = date("D", $firstofyear);

        $firstday = str_replace("Mon", "1", $firstday);
        $firstday = str_replace("Tue", "2", $firstday);
        $firstday = str_replace("Wed", "3", $firstday);
        $firstday = str_replace("Thu", "4", $firstday);
        $firstday = str_replace("Fri", "5", $firstday);
        $firstday = str_replace("Sat", "6", $firstday);
        $firstday = str_replace("Sun", "7", $firstday);


        $firstday--;

        $startday = mktime(0, 0, 0, date("m", $firstofyear), date("d", $firstofyear) - $firstday, date("y", $firstofyear));

        if ($week == 1) {
            return date("Y-m-d", $startday);
        }

        $myweek = 1;

        do {
            $startday = mktime(0, 0, 0, date("m", $startday), date("d", $startday) + 7, date("y", $startday));

            $myweek++;


        } while ($week != $myweek);

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

    public function getCarActions($user, $day, $month, $year)
    {
        $mdate = mktime(0, 0, 0, $month, $day, $year);
        $mdate = date("Y-m-d", $mdate);

        // prepare datastructure
        $ret = array();
        $retFrom = array();
        $retTo = array();
        $retKm = array();

        $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT fromwhere, towhere, km FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=? AND day=? ORDER BY id ASC")) {
            $stmt->bind_param("is", $user, $mdate);
            $stmt->execute();
            $stmt->bind_result($tmpFrom, $tmpTo, $tmpKm);
            while ($stmt->fetch()) {
                //$retFrom[] = utf8_decode( $tmpFrom );
                //$retTo[]   = utf8_decode( $tmpTo );
                $retFrom[] = $tmpFrom;
                $retTo[] = $tmpTo;
                $retKm[] = $tmpKm;
                //echo "$tmpFrom $tmpTo $tmpKm";
            }

            $ret['from'] = $retFrom;
            $ret['to'] = $retTo;
            $ret['km'] = $retKm;

            $stmt->close();
            return $ret;
        } else {
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

    public function getWorkDay($user, $day, $month, $year, $workday)
    {
        $mdate = mktime(0, 0, 0, $month, $day, $year);
        //$mdate = date("Y.m.d", $mdate);

        //prepare datastructure
        $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");

        $ret = array();
        $ret['caractions'] = $this->getCarActions($user, $day, $month, $year);

        $ret['times'] = $this->getTimes($user, $day, $month, $year);

        $ret['workdoneinareas'] = $this->getWorkDoneInAreas($user, $day, $month, $year);

        $ret['holliday'] = $this->getHollidayState($user, $day, $month, $year);

        $ret['workhourstodo'] = $this->getWorkToDo($user, $workday);

        $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
        $tcomment = $this->getDayComment($user, $day, $month, $year);
        if ($tcomment == "") $tcomment = " ";
        $tcomment = str_replace("<br>", "\n", $tcomment);
        $ret['comment'] = utf8_decode($tcomment);
        $ret['date'] = date("d.m.Y", $mdate);

        // give back datastucture
        return $ret;
    }

    public function getRemainHolliday($user, $day, $month, $year)
    {
        $nbr = 0;
        $dd = $year . "-" . $month . "-" . $day . " 00:00:00";
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT urlaubstage FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?")) {
            $stmt->bind_param("i", $user);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpHolliday);
                if ($stmt->fetch()) $nbr = $tmpHolliday;
            }
            $stmt->close();
        }

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT COUNT(user_id) FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND holliday_id=2 AND dateofday<?")) {
            $stmt->bind_param("is", $user, $dd);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpTaken);
                if ($stmt->fetch()) {
                    $nbr -= (int)$tmpTaken;
                }
            }
            $stmt->close();
        }
        return $nbr;
    }

    public function getRemainVacation($user, $day, $month, $year)
    {
        $nbr = 0;
        $dd = $year . "-" . $month . "-" . $day . " 00:00:00";
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT feiertage FROM " . CConfig::$db_tbl_prefix . "users WHERE id=?")) {
            $stmt->bind_param("i", $user);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpHolliday);
                if ($stmt->fetch()) $nbr = $tmpHolliday;
            }
            $stmt->close();
        }

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT COUNT(user_id) FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=? AND holliday_id=3 AND dateofday<?")) {
            $stmt->bind_param("is", $user, $dd);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpTaken);
                if ($stmt->fetch()) {
                    $nbr -= (int)$tmpTaken;
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

    public function getWorkWeek($user, $day, $month, $year)
    {
        $mdate = mktime(0, 0, 0, $month, $day, $year);
        $tempDate = $mdate; // = date("Y-m-d", $mdate);

        $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");

        $ret = array();
        $ret['workday'] = array();

        for ($i = 0; $i < 7; $i++) {
            $ret['workday'][] = $this->getWorkDay($user, date("d", $tempDate), date("m", $tempDate), date("Y", $tempDate), ($i + 1));
            $tempDate = mktime(0, 0, 0, date("m", $tempDate), date("d", $tempDate) + 1, date("Y", $tempDate));
        }

        // workareas
        $ret['workareas'] = $this->GetWorkfieldsAll($user);

        // over hours
        $tmpOverminutes = $this->getOverhoursUntilDate($user, $day, $month, $year);

        if ($tmpOverminutes >= 0) {
            $tmpOverhours = 0;
            while ($tmpOverminutes >= 60) {
                $tmpOverminutes -= 60;
                $tmpOverhours++;
            }
            if ($tmpOverminutes < 10) $ret['overhoursbeforeweek'] = $tmpOverhours . ":0" . $tmpOverminutes;
            else $ret['overhoursbeforeweek'] = $tmpOverhours . ":" . $tmpOverminutes;
        } else {
            $tmpOverhours = 0;
            $tmpOverminutes *= -1.0;
            while ($tmpOverminutes >= 60) {
                $tmpOverminutes -= 60;
                $tmpOverhours++;
            }
            if ($tmpOverminutes < 10) $ret['overhoursbeforeweek'] = "-" . $tmpOverhours . ":0" . $tmpOverminutes;
            else $ret['overhoursbeforeweek'] = "-" . $tmpOverhours . ":" . $tmpOverminutes;
        }

        $ret['remainholliday'] = $this->getRemainHolliday($user, $day, $month, $year);

        $ret['remainvacation'] = $this->getRemainVacation($user, $day, $month, $year);

        $tmpDate = mktime(0, 0, 0, $month, $day + 7, $year);

        $ret['hollidaynow'] = $this->getRemainHolliday($user, date("d", $tmpDate), date("m", $tmpDate), date("Y", $tmpDate));
        $ret['vacationnow'] = $this->getRemainVacation($user, date("d", $tmpDate), date("m", $tmpDate), date("Y", $tmpDate));
        $tmpOverminutesnow = $this->getOverhoursUntilDate($user, date("d", $tmpDate), date("m", $tmpDate), date("Y", $tmpDate));
        if ($tmpOverminutesnow >= 0) {
            $tmpOverhoursnow = 0;
            while ($tmpOverminutesnow >= 60) {
                $tmpOverminutesnow -= 60;
                $tmpOverhoursnow++;
            }
            if ($tmpOverminutesnow < 10) $ret['overhours'] = $tmpOverhoursnow . ":0" . $tmpOverminutesnow;
            else $ret['overhours'] = $tmpOverhoursnow . ":" . $tmpOverminutesnow;
        } else {
            $tmpOverhoursnow = 0;
            $tmpOverminutesnow *= -1.0;
            while ($tmpOverminutesnow >= 60) {
                $tmpOverminutesnow -= 60;
                $tmpOverhoursnow++;
            }
            if ($tmpOverminutesnow < 10) $ret['overhours'] = "-" . $tmpOverhoursnow . ":0" . $tmpOverminutesnow;
            else $ret['overhours'] = "-" . $tmpOverhoursnow . ":" . $tmpOverminutesnow;
        }

        // Kilometers
        $kmEndDate = mktime(0, 0, 0, $month, $day + 6, $year);
        $kmEndDate = date("Y-m-d", $kmEndDate);
        $kmStartDate = mktime(0, 0, 0, 1, 1, $year);
        $kmStartDate = date("Y-m-d", $kmStartDate);

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();

        if ($stmt->prepare("SELECT SUM(km) FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id = ? AND day >= ? AND day < ?")) {
            $stmt->bind_param("iss", $user, $kmStartDate, $kmEndDate);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpKmTotal);
                if ($stmt->fetch()) {
                    $ret['kmtotal'] = $tmpKmTotal;
                } else {
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
    public function getDayComment($user, $day, $month, $year)
    {
        $mdate = mktime(0, 0, 0, $month, $day, $year);
        $mdate = date("Y-m-d", $mdate);

        $ret = "";

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT description FROM " . CConfig::$db_tbl_prefix . "daydescriptions WHERE user_id=? AND workday=?")) {
            $stmt->bind_param("is", $user, $mdate);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpDescription);
                if ($stmt->fetch()) {
                    $ret = $tmpDescription;
                    $stmt->close();
                    $ret = utf8_encode($ret);
                    return $ret;
                } else {
                    $ret = "";
                    $stmt->close();
                    return $ret;
                }
                $stmt->close();
            }
        }

        return "";
    }

    function GetHollidayStateForUser($userid, $date)
    {

        $dbserver = CConfig::$dbhost;
        $dbuser = CConfig::$dbuser;
        $dbpass = CConfig::$dbpass;
        $dbname = CConfig::$dbname;

        $dbx = new DatabaseConnection($dbserver, $dbuser, $dbpass, $dbname);

        $dateMySql = TransformDateToUS($date);
        $ssql = "SELECT holliday_id FROM " . CConfig::$db_tbl_prefix . "arbeitstage WHERE user_id=$userid AND dateofday='$dateMySql'";

        $myres = $dbx->ExecuteSql($ssql);

        if ($gg = $myres->fetch_row()) {
            return $gg[0];
        } else {
            return 1; // default
        }
    }

    function GetDescription($userid, $mdate)
    {
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
        } else {
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

    function getWorkDoneInAreas($userid, $day, $month, $year)
    {

        $max_rank_workfields = CConfig::$max_rank_workfields;

        $mdate = mktime(0, 0, 0, $month, $day, $year);
        $mdate = date("Y-m-d", $mdate);

        $ret = array();
        $ret['id'] = array();
        $ret['time'] = array();

        $tmpId = -1;
        $tmpHours = "00:00";

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT workfield_id, hours FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=? AND date=? ORDER BY workfield_id";
        //"SELECT workfield_id, hours FROM " . CConfig::$db_tbl_prefix . "workday WHERE user_id=? AND date=? ORDER BY workfield_id"
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("is", $userid, $mdate);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpId, $tmpHours);
                $i = 0;
                if ($stmt->fetch()) {
                    for ($i; $i < $max_rank_workfields; $i++) {
                        if ($i == $tmpId) {
                            $ret['id'][] = $tmpId;
                            $ret['time'][] = $tmpHours;
                            if ($stmt->fetch()) {

                            } else {
                                $tmpId = -1;
                            }
                        } else {
                            $ret['id'][] = -1;
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

    function HTMLSelectHolliday($userid, $date)
    {
        $TableHolliday = GetDayStatusAll();
        $iHolliday = count($TableHolliday);
        $iHollidayIdSelected = GetHollidayStateForUser($userid, $date);

        $ret_val = "";
        $ret_val .= "<select name=\"urlaub\">";

        for ($a = 0; $a < $iHolliday; $a++) {

            if ($TableHolliday[$a][0] == $iHollidayIdSelected) {
                $ret_val .= "<option value=\"" . $TableHolliday[$a][0] . "\" selected> " . $TableHolliday[$a][1] . "</option>";
            } else {
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
    function GetWorkfieldsAll($user)
    {

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

        if ($stmt->prepare($sqlSelect)) {
            $stmt->bind_param("i", $user);

            $stmt->execute();
            //echo "<p>rows: " . $stmt->num_rows . "</p>";
            $stmt->bind_result($tmpRank, $tmpExplanation, $tmpDescription, $tmpUser, $tmpTimecapital);
            if (!$stmt->fetch()) {
            }

            for ($i = 0; $i < $max_rank_workfields; $i++) {

                if ($tmpRank == $i) {
                    $ranked[$i][0] = $tmpRank;
                    //$ranked[$i][1] = utf8_encode( $tmpExplanation );
                    //$ranked[$i][1] = utf8_decode($tmpExplanation);
                    $ranked[$i][1] = $tmpExplanation;
                    //$ranked[$i][2] = utf8_decode($tmpDescription);
                    $ranked[$i][2] = $tmpDescription;
                    $ranked[$i][3] = $tmpUser;
                    $ranked[$i][4] = $tmpTimecapital;
                    if (0 == $stmt->fetch()) {
                        $tmpRank = -1;
                        //echo "<p>$tmpRank</p>";
                    } else {

                    }
                } else {
                    $ranked[$i][0] = $i;
                    $ranked[$i][1] = "";
                    $ranked[$i][2] = "";
                    $ranked[$i][3] = "";
                    $ranked[$i][4] = "0";
                }
                //echo "<p> " . $stmt->error . "$tmpRank</p>";
            }

            $stmt->close();
        } else {
            echo "error!!!";
        }
        echo $this->dbx->getDatabaseConnection()->error;

        return $ranked;

    }

    function GetUserInfo($uid)
    {
        $retArray = array();

        $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
        $this->dbx->getDatabaseConnection()->query("USE " . CConfig::$dbname);

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();

        $sqlSelect = "SELECT id, uname, email, reg_date, session_id, password, status, alteueberstunden, feiertage, urlaubstage, kmsatz, startdate, dname, report_year FROM " . CConfig::$db_tbl_prefix . "users WHERE id=? LIMIT 1"; // $uid

        if (!$stmt->prepare($sqlSelect)) {
            echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
            die;
        }

        if (!$stmt->bind_param("i", $uid)) {
            echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
            die;
        }

        if (!$stmt->execute()) {
            echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
            die;
        }

        if (!$stmt->bind_result($tmpId, $tmpUname, $tmpEmail, $tmpRegdate, $tmpSessionid, $tmpPassword, $tmpStatus, $tmpAlteueberstunden, $tmpFeiertage, $tmpUrlaubstage, $tmpKmsatz, $tmpStartdate, $tmpDname, $tmpReportYear)) {
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

        if ($stmt->fetch()) {
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


    function GetUserWorkDays($uid)
    {

        $this->dbx->getDatabaseConnection()->query("SET NAMES 'utf8'");
        $this->dbx->getDatabaseConnection()->query("USE " . CConfig::$dbname);
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sqlSelect = "SELECT hours, workday FROM " . CConfig::$db_tbl_prefix . "workhours WHERE user LIKE ? ORDER BY workday"; // $uid
        if (!$stmt->prepare($sqlSelect)) {
            echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
            die;
        }

        if (!$stmt->bind_param("i", $uid)) {
            echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
            die;
        }

        if (!$stmt->execute()) {
            echo "<p>Error: " . $this->dbx->getDatabaseConnection()->error . "</p>";
            die;
        }

        $stmt->bind_result($day, $hours);

        $i = 0;
        $retarray = array();

        while ($row = $stmt->fetch()) {
            $retarray[$i] = array();
            $retarray[$i][1] = $day; //$row[1];	      // workday
            $retarray[$i][0] = (double)$hours; //(double) $row[0];       // hours
            $i += 1;
        }

        //$i = 0;

        while ($i < 7) {
            $retarray[$i][0] = $i + 1;
            $retarray[$i][1] = 0.0;
            $i++;
        }

        $stmt->close();
        return $retarray;
    }


    function TransformUserToId($username)
    {
        include_once(__DIR__ . "/includes/db_connect.php");

        //$db_conn = mysql_connect($dbserver, $dbuser, $dbpass);

        if (!$db) {
            die("Keine Verbindung zur Datenbank m&ouml;glich");
        }

        $db->query("USE " . CConfig::$dbname);

        //$sqlSelect = "SELECT * FROM " . CConfig::$db_tbl_prefix . "users WHERE uname LIKE '" . $username . "' LIMIT 1";
        $sqlSelect = "SELECT id FROM " . CConfig::$db_tbl_prefix . "users WHERE uname LIKE ? LIMIT 1";
        $stmt = $db->stmt_init();

        if (!isset($stmt)) {
            echo "<p>Unable to connect to database, class helper, member TransformUserToId</p>";
            die;
        }

        if (!$stmt->prepare($sqlSelect)) {
            echo "<p>Unable to prepare statement, class helper, member TransformUserToId</p>";
            die;
        }

        if (!$stmt->bind_param("s", $username)) {
            echo "<p>Unable to bind parameter, class helper, member TransformUserToId</p>";
            die;
        }


        if (!$stmt->execute()) {
            echo "<p>Unable to execute query, class helper, member TransformUserToId</p>";
            echo "<p>" . $db->error . "</p>";
            die;
        }

        $stmt->bind_result($id);

        if ($stmt->fetch()) {
            $stmt->close();
            $db->close();

            return $id;
        } else {
            $stmt->close();
            $db->close();
            return 0;
        }
    }

    function TransformDateToBelgium($inputdate)
    {
        $startdatum = $inputdate;
        $startdatum = substr($startdatum, 0, 10);
        $startjahr = substr($startdatum, 0, 4);
        $startmonat = substr($startdatum, 5, 2);
        $starttag = substr($startdatum, 8, 2);

        $startdatum = $starttag . "." . $startmonat . "." . $startjahr;

        return $startdatum;

    }


    function TransformDateToUS($inputdate)
    {
        $startdatum = $inputdate;
        $startdatum = substr($startdatum, 0, 10);
        $startjahr = substr($startdatum, 6, 4);
        $startmonat = substr($startdatum, 3, 2);
        $starttag = substr($startdatum, 0, 2);

        $startdatum = $startjahr . "-" . $startmonat . "-" . $starttag;

        return $startdatum;

    }

	function mktime_from_us_date($mdate) {
		$year = substr($mdate, 0,4);
		$month = substr($mdate, 5, 2);
		$day = substr($mdate, 8, 2);

		$mtime = mktime(0,0,0,$month, $day, $year);

		return $mtime;
	}

	function mktime_from_be_date($mdate) {

		$year = substr($mdate, 6,4);
		$month = substr($mdate, 3, 2);
		$day = substr($mdate, 0, 2);

		$mtime = mktime(0,0,0,$month, $day, $year);

		return $mtime;
	}

    function EntetiesToCharacters($sObj)
    {
        $ret = str_replace("&uuml;", "\u00fc", $sObj);

        return $ret;
    }

    function CharactersToEnteties($sObj)
    {
        $ret = str_replace("\u00fc", "&uuml;", $sObj);

        return $ret;
    }


    function myTimeToInt($tval) {
	$parts = explode(":", $tval);

	if (count($parts)< 2 ) {
		return 0;
	}

	if (count($parts) > 3 ) {
		return 0;
	}

	$prefix = "";

	if ( strpos( $parts[0], "-") ) {
		$parts[0] = substr($parts[0], 1, strlen($parts[0]) - 1);
		$prefix = "-";
	}

	$r = intval($parts[1], 10);
	$r += intval($parts[0], 10) * 60;

	if ($prefix == "-") $r *= -1;

	return $r;
    }

    function intToTime($tval) {
	$sign = "";
	if ($tval < 0 ) {
		$sign = "-";
		$tval *= -1;
	}
	$h = intval($tval / 60, 10);
	$m = $tval - ($h * 60);
	$superzero = "";
	if ($m < 10 ) $superzero = "0";

	return "$sign$h:$superzero$m";

    }

    public function subtract_times($valA, $valB) {
	$a = $this->myTimeToInt($valA);
	$b = $this->myTimeToInt($valB);

	$r = $a - $b;

	return $this->intToTime($r);
    }

    public function generateKmInvoiceTable($user)
    {
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
        if ($stmt->prepare("SELECT kmweek FROM " . CConfig::$db_tbl_prefix . "kminvoiced WHERE userid=? ORDER BY kmweek LIMIT 0,24")) {
            $stmt->bind_param("i", $user);
            if ($stmt->execute()) {
                $stmt->bind_result($toWeek);
                while ($stmt->fetch()) {
                    $tblWeeks['from'][] = $fromWeek;
                    $tblWeeks['to'][] = $toWeek;
                    $fromWeek = $toWeek + 1;
                    $a++;

                }
            }
            $stmt->close();
        } else {
            echo $stmt->error;
        }


        for ($b = 0; $b < $a; $b++) {
            $tblWeeks['km'][$b] = $this->getKmBetween($user, $tblWeeks['from'][$b], $tblWeeks['to'][$b]);
        }


        for ($b = 0; $b < 24; $b++) {
            if ($b < $a) {
                $ret .= "<tr>";
                $ret .= "<td><input type=\"text\" name=\"kmfrom[]\" size=\"3\" value=\"" . $tblWeeks['from'][$b] . "\"></td>";
                $ret .= "<td><input type=\"text\" name=\"kmto[]\" size=\"3\" value=\"" . $tblWeeks['to'][$b] . "\"></td>";
                $ret .= "<td><input type=\"text\" name=\"km[]\" size=\"4\" value=\"" . $tblWeeks['km'][$b] . "\"></td>";
                $ret .= "</tr>";
            } else {
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

    function getKmBetween($user, $wstart, $wend)
    {
        $km = 0;
        $sdate = $this->CalendarWeekStartDate($wstart, $this->getUserStartYear($user));
        $edate = $this->CalendarWeekStartDate($wend, $this->getUserStartYear($user));
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SELECT SUM(km) FROM " . CConfig::$db_tbl_prefix . "kilometers WHERE user_id=? AND day >= ? AND day <= ?")) {
            $stmt->bind_param("iss", $user, $sdate, $edate);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpkm);
                if ($stmt->fetch()) {
                    $km = $tmpkm;
                }
            }
            $stmt->close();
        }

        return $km;
    }

    function DumpDatabase()
    {
        $tables = array();

        $fp = fopen("dump2.sql", "w");

        if (!$fp) {
            echo "Can't write to file!";
            return;
        }

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        if ($stmt->prepare("SHOW TABLES")) {
            if ($stmt->execute()) {
                $stmt->bind_result($itm);
                while ($stmt->fetch()) {
                    $tables[] = $itm;
                }
            }
            $stmt->close();
        } else {
            echo "xxx";
            return;
        }

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $t = null;
        if ($stmt->prepare("SHOW CREATE TABLE ?")) {
            foreach ($tables as $t) {
                $stmt->bind_param("s", $t);

                if ($stmt->execute()) {
                    $stmt->bind_result($itm);
                    while ($stmt->fetch()) {
                        echo "# Table: $t<br>";
                        fwrite($fp, "# Table: $t\n");
                        fwrite($fp, $itm);
                    }

                    $stmt->close();
                } else {
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

    function getInvoiceSetup($id)
    {
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

        if ($stmt->prepare($sql)) {
            if ($stmt->execute()) {
            } else {
                $ret['msg'] = "could not create table";
                $ret['msgcode'] = 1;
                return $ret;
            }
        } else {
            $ret['msg'] = $stmt->error . " could not create table (2)";
            $ret['msgcode'] = 2;
            return $ret;
        }

        $stmt->close();

        $sql = "SELECT id, idUser, defAddress, defZipAddr, defCityAddr, defNameRecv, defAddressRecv, defZipRecv, defCityRecv, defEmailRecv, defAccHolder, defAccNumber, defPaymentMethod FROM " . CConfig::$db_tbl_prefix;
        $sql .= "invsetup WHERE idUser=?";

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();

        if ($stmt->prepare($sql)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpId, $tmpIdUser, $tmpAddress, $tmpZip, $tmpCity, $tmpRecvName, $tmpRecvAddress, $tmpRecvZip, $tmpRecvCity, $tmpRecvEmail, $tmpAccountHolder, $tmpAccountNumber, $tmpPaymentMethod);
                if ($stmt->fetch()) {
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
                } else {
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
        } else {
            $ret['msg'] = "Error while getting data: " . $stmt->error;
            $ret['msgcode'] = 3;
            return $ret;
        }
    }

    function newInvoiceSetup($userid, $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO " . CConfig::$db_tbl_prefix . "invsetup (idUser, defAddress, defZipAddr, defCityAddr, defNameRecv, defAddressRecv, defZipRecv, defCityRecv, defEmailRecv, defAccHolder, defAccNumber, defPaymentMethod) ";
        $sql .= "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt->prepare($sql)) {
            $stmt->bind_param("issssssssssi", $userid, $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod);

            if ($stmt->execute()) {
                $stmt->close();
                return 0; // success
            } else {
                return 1; // error number 1
            }
        } else {
            //return 2; // error number 2
            return $stmt->error;
        }
    }

    function updateInvoiceSetup($id, $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod)
    {
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "UPDATE " . CConfig::$db_tbl_prefix . "invsetup set defAddress=?, defZipAddr=?, defCityAddr=?, defNameRecv=?, defAddressRecv=?, defZipRecv=?, defCityRecv=?, ";
        $sql .= "defEmailRecv=?, defAccHolder=?, defAccNumber=?, defPaymentMethod=? WHERE id=?";

        if ($stmt->prepare($sql)) {
            $stmt->bind_param("ssssssssssii", $address, $zip, $city, $recvName, $recvAddress, $recvZip, $recvCity, $recvEmail, $accountHolder, $accountNumber, $paymentMethod, $id);
            if ($stmt->execute()) {
                $stmt->close();
                return 0; // success
            } else {
                return 3;
            }
        } else {
            return 4;
        }
    }

    // Creates a new invoice with todays date
    //
    // Parameter:
    // $id ID User

    // Return value
    // idInvoice
    function createNewInvoice($id)
    {
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

        if ($stmt->prepare($sql)) {
            if ($stmt->execute()) {
            } else {
                $ret['msg'] = "could not create table";
                $ret['msgcode'] = 1;
                return $ret;
            }
        } else {
            $ret['msg'] = $stmt->error . " could not create table (2)";
            $ret['msgcode'] = 2;
            return $ret;
        }

        $stmt->close();

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "INSERT INTO " . CConfig::$db_tbl_prefix . "invoices (idUser, invoicedate, invoicelabel) VALUES (?, NOW(), 'neue Rechnung ohne Label')";
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
            } else {
                return -1;
            }
        } else {
            return -2;
        }

        $stmt->close();
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT MAX(idInvoice) FROM " . CConfig::$db_tbl_prefix . "invoices WHERE idUser=?";
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpMaxId);
                if ($stmt->fetch()) {
                    $stmt->close();
                    return $tmpMaxId;
                } else {
                    return -3;
                }
            } else {
                return -4;
            }
        } else {
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
    function listInvoices($id)
    {
        $ret = array();
        //$ret['invoices'] = array();
        $i = 0;

        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idInvoice, invoicedate, invoicelabel FROM " . CConfig::$db_tbl_prefix . "invoices WHERE idUser=? ORDER BY invoicedate";
        if ($stmt->prepare($sql)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpIdInvoice, $tmpDateInvoice, $tmpLabelInvoice);
                while ($stmt->fetch()) {
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
    function detailInvoice($id)
    {
        $ret = array();
        $stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT idInvoice, invoicedate, invoicelabel, invFromName, invFromFirstname, invFromAddress, invFromZip, invFromCity, invToName, invToAddress, invToZip, invToCity ";
        $sql .= "invAccountHolder, invAccountNumber, invPaymentMethod FROM " . CConfig::$db_tbl_prefix . "invoices WHERE idInvoice=? LIMIT 0,1";
        if ($stmt->prepare($sql)) {
            $stmt_ > bind_param("i", $id);
            if ($stmt->execute()) {
                $stmt->bind_result($tmpIdInvoice, $tmpDateInvoice, $tmpLabelInvoice, $tmpFromNameInvoice, $tmpFromFirsnameInvoice,
                    $tmpFromAddressInvoice, $tmpFromZipInvoice, $tmpFromCityInvoice, $tmpToNameInvoice, $tmpToAddressInvoice, $tmpToZipInvoice, $tmpToCityInvoice,
                    $tmpAccountHolderInvoice, $tmpAccountNumberInvoice, $tmpPaymentMethodInvoice
                );

                if ($stmt->fetch()) {
                    $ret['idInvoice'] = $tmpIdInvoice;
                    $ret['Fromname'] = $tmpFromNameInvoice;
                    $ret['FromFirstname'] = $tmpFromFirstnameInvoice;
                    $ret['FromAddress'] = $tmpFromAddressInvoice;
                    $ret['FromCity'] = $tmpFromCityInvoice;
                    $ret['FromZip'] = $tmpFromZipInvoice;
                    $ret['ToName'] = $tmpToNameInvoice;
                    $ret['ToAddress'] = $tmpToAddressInvoice;
                    $ret['ToZip'] = $tmpToZipInvoice;
                    $ret['ToCity'] = $tmpToCityInvoice;
                    $ret['InvDate'] = $tmpDateInvoice;
                    $ret['InvNumber'] = $tmpLabelInvoice;
                    $ret['accHolder'] = $tmpAccountHolderInvoice;
                    $ret['accNumber'] = $tmpAccountNumberInvoice;
                    $ret['PaymentMethod'] = $tmpPaymentMethodInvoice;

                } else {
                    $ret['idInvoice'] = -1;
                    $ret['Fromname'] = "";
                    $ret['FromFirstname'] = "";
                    $ret['FromAddress'] = "";
                    $ret['FromCity'] = "";
                    $ret['FromZip'] = "";
                    $ret['ToName'] = "";
                    $ret['ToAddress'] = "";
                    $ret['ToZip'] = "";
                    $ret['ToCity'] = "";
                    $ret['InvDate'] = "";
                    $ret['InvNumber'] = "";
                    $ret['accHolder'] = "";
                    $ret['accNumber'] = "";
                    $ret['PaymentMethod'] = "";
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
    public function getWorkDoneYear($userid, $workrank)
    {
        $ret = array();
        $ret['msg'] = "ok";
        $ret['msgcode'] = 0;
        $index = 0;
        $t_date = "";
        $t_hours = "";
        $t_description = "";

        //$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
        $sql = "SELECT A.date, A.hours, B.description FROM " . CConfig::$db_tbl_prefix . "workday AS A LEFT JOIN " . CConfig::$db_tbl_prefix . "daydescriptions AS B ON A.date = B.workday WHERE A.user_id=B.user_id AND A.user_id=? AND A.workfield_id=? ORDER BY A.date";

        if (!$stmt = $this->dbx->getDatabaseConnection()->prepare($sql)) {
            $ret['msg'] = "Error: prepare sql statement: $sql *** Mysql error:" . $this->dbx->getDatabaseConnection()->error;
            $ret['msgcode'] = 1;
            return $ret;
        }

        if (!$stmt->bind_param("ii", $userid, $workrank)) {
            $ret['msg'] = "Error: bind sql parameters";
            $ret['msgcode'] = 2;
            return $ret;
        }

        $stmt->bind_result($t_date, $t_hours, $t_description);

        if (!$stmt->execute()) {
            $ret['msg'] = "Error: sql execute failed";
            $ret['msgcode'] = 3;
            return $ret;
        }

        $ret['data'] = array();

        while ($stmt->fetch()) {
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
