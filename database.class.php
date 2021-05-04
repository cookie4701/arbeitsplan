<?php

//! Class to work with a mysql database server.

/*! Should be used with PHP 5.3.0 and above.
 * 
 */

class DatabaseConnection {

	/*! Hostname of mysql database server
	 * 
	 * @var string $strServer
	 */
	private $strServer;
	
	/**
	 * 
	 * Databasename
	 * @var string $strDatabase
	 */
	private $strDatabase;
	
	/**
	 * 
	 * Username to use for connection
	 * @var string $strUser
	 */
	private $strUser;
	
	/**
	 * 
	 * Password to use for connection
	 * @var string $strPassword
	 */
	private $strPassword;
	
	/**
	 * 
	 * Database Connection object
	 * @var mysqli $dbConnection
	 */
	private $dbConnection;
	
	//! Constructor
	
	/*!
	 * Takes 4 parameters and tries to establish a connection to a mysql server via mysqli
	 * 
	 * \param sServer The host to connect to.
	 * \param sUser Username
	 * \param sPass Password
	 * \param sDatabase Use this database
	 * 
	 * \throws Exception If one of the parameters is not set or if it's empty.
	 */
	public function __construct($sServer, $sUser, $sPass, $sDatabase) {
		
		if ( !isset($sServer) ) {
			throw new Exception("No databaseserver specified");
		}
		
		if ( !isset($sUser) ) {
			throw new Exception("No username specified");
		}
		
		if ( !isset($sPass) ) {
			throw new Exception("No password specified");
		}
		
		if ( !isset($sDatabase) ) {
			throw new Exception("No database specified");
		}
		
		
		// set some values
		$this->strServer = $sServer;
		$this->strDatabase = $sDatabase;
		$this->strUser = $sUser;
		$this->strPassword = $sPass;
		
		// open the connection
		$this->dbConnection = new mysqli( $this->strServer, $this->strUser, $this->strPassword, $this->strDatabase);
		
		if ( ! $this->dbConnection ) {
			throw new Exception("Unable to connect to the database");
		}
	}
	
	//! If the connection to the server is ok this function will return true. If it's not ok, then it will return false.
	public function isConnected() {
		if ( $this->dbConnection ) return true;
		
		return false;
	}
	
	//! The destructor closes the database connection.
	
	public function __destruct() {
		if ( $this->dbConnection ) {
			$this->dbConnection->close();
		}
	}
	
	//! Get login credentials.
	
	public function getConnectionInfo() {
		$resval = "Username: " . $this->strUser . " Database: " . $this->strDatabase . " Host: " . $this->strServer;
		return $resval;
	}
	
	/*!
	 * \param strQuery Query to execute 
	 *
	 * \return result of the query (for SELECT queries use prepared statements)
	 */
	public function ExecuteSQL($strQuery) {
		$res = $this->dbConnection->query($strQuery);
		
		return $res;
	}
	
	/*!
	 * Use this function to get the database connection object. It can be used to prepare a statement for example (see below).
	 * @code
	 *  $dbconnection = new DatabaseConnection("host", "user", "pass", "databasename");
	 *  $stmt = $dbconnection->getDatabaseConnection->stmt_init();
	 * @endcode
	 * \return The database connection
	 */
	public function getDatabaseConnection() {
		return $this->dbConnection;
	}
	
	
}

?>
