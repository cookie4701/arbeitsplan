<?php

include_once 'helper.class.php';

class CWorkday {
	private $help;
	
	private $userId;
	
	private $date;
	private $day;
	private $month;
	private $year;
	
	private $times;
	
	private $work;
	
	private $holliday;
	private $hollidayOptions;
	
	private $dbx;
	
	//! Constructor with parameters
	
	/*!
	 * 
	 * Constructor that takes some parameters (see below)
	 * 
	 *  \param userid The user id the record belongs to
	 *  \param pday The day of the data record
	 *  \param pmonth The month of the data record
	 *  \param pyear The year of the data record
	 */
	public function __construct ($userid, $pday , $pmonth, $pyear) {
		$this->userId = $userId;
		
		$this->day = $pday;
		$this->month = $pmonth;
		$this->year = $pyear;
		$this->help = new Helper();
		
		$this->times = $this->help->getTimes($userid, $pday, $pmonth, $pyear);
		$this->work = $this->help->getWork($userid, $pday, $pmonth, $pyear);
		$this->holliday = $this->help->getHollidayState($userid, $pday, $pmonth, $pyear);
		
	}
	
	//! Constructor without parameters
	
	/*!
	 * Constructor without parameters. Normally this is used for new records.
	 */
	
	public function __construct () {
		$this->userId = -1;
		$this->day = "01";
		$this->month = "01";
		$this->year = "1900";
		
		$this->help = new Helper();
	}
	
	//! Gets all data for re-use in javascript 
	
	/*!
	 * Detail: this method collects all data needed for the website to display.
	 *
	 */
	public function getJsonString() {
		
	}
	
	//! Collects data from a JSON string and assigns the values to the class.

	/*!
	 * Afterwards the data of the object may be saved back to the database.
	 * 
	 * \param jsonstring JSON String that contains all data to be saved
	 */
	public function saveJsonString($jsonstring) {
		
	}
}

?>