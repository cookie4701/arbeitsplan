<?php

require_once 'config.php';
require_once 'database.class.php';

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

//! Include this class in your projects to have a login procedure

/**
 * You have just to create an object of this class (see below)
 * @code
 * $mylogin = new CLogin();
 * @endcode
 */
class CLogin {

	private $strUsername;
	private $strPassword;
	private $loggedOn;
	private $dbx;
	private $idUser;
	private $displayName;
	
	public function __construct()
	{
		$this->detectLogout();
		
		$this->idUser = -1;
		$this->loggedOn = 0;
		$this->dbx = new DatabaseConnection(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);
		$this->chkLogin();
		
		if ( $this->loggedOn == 0 ) {
			echo $this->getLoginForm();
			die;
		}
		
		if ( $this->strUsername != "" ) {
			$_COOKIE['username'] = $this->strUsername;

		}
	}
	
	public function getIdUser() {
		return $this->idUser;
	}
	
	public function logout() {
		unset($_COOKIE['username']);
		setcookie('username', $this->strUsername, time() - 7200);
	}
	
	public function detectLogout() {
		if ( !isset($_GET['logout'] ) ) return;
		
		if ( $_GET['logout'] == '1' ) {
			$this->logout();
		}
	}
	
	public function getLogoutLink() {
		$ret = "<a href=\"" . $_SERVER['PHP_SELF'] . "?logout=1\" class=\"logoutlink\" id=\"idlogoutlink\"> Logout </a>";
		return $ret;
	}
	
	public function printLogoutLink() {
		echo $this->getLogoutLink();
	}
	
	public function getDisplayName() {
		return $this->displayName;
	}
	
	public function login($strU, $strP ) 
	{
		$this->dbx->getDatabaseConnection()->set_charset("utf8");
		$id = -1;
		$dname = "";
		$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
		if ( $stmt->prepare("SELECT id, dname FROM " . CConfig::$db_tbl_prefix . "users WHERE password=? AND uname=?") ) {
			$stmt->bind_param("ss", $strP, $strU);
			$stmt->execute();
			
			// login ok
			$stmt->bind_result($id, $dname);
			if ( $stmt->fetch() ) {
				if ( $id != -1 ) $this->idUser = $id;
				$this->strUsername = $strU;
				$this->displayName = htmlentities($dname);
				setcookie('username', $this->strUsername, time() + 3600);
				$this->loggedOn = 1;
			}

			$stmt->close();
		}
		
		else {
			echo "<p>" . $this->dbx->getDatabaseConnection()->error . "</p>";
		}
		
	}
	public function chkLogin()
	{
		if ( !isset( $_COOKIE['username']) ) {
			// need to check if $_POST contains login informations
			if ( isset( $_POST["username"]) && isset($_POST["password"]) ) {
				$this->login($_POST["username"], $_POST["password"]);
			}
			
			else {
				
			}
		}
		
		else {
			$this->setUsername($_COOKIE['username']);
			$tuser = $this->strUsername;
			$this->loggedOn = 1;
			$stmt = $this->dbx->getDatabaseConnection()->stmt_init();
			if ( $stmt->prepare("SELECT id, dname FROM " . CConfig::$db_tbl_prefix . "users WHERE uname=?") ) {
				$stmt->bind_param("s", $tuser);
				$id = -1;
				$dname = "";
				$stmt->execute();
				$stmt->bind_result($id, $dname);
				
				if ($stmt->fetch() ) {
					$this->idUser = $id;
					$this->displayName = $dname;
					$this->loggedOn = 1;
					setcookie('username', $this->strUsername, time() + 3600);
				}
				else {
					$this->idUser = -1;
					$this->displayName = "not logged in";
					$this->loggedOn = 0;
				}
			} 
				
		}
	}
	
	public function setUsername($username)
	{
		$tempstring = htmlspecialchars($username);
		$this->strUsername = $tempstring;
	}
	
	public function getUsername()
	{
		if ( $this->loggedOn == 1) return $this->strUsername;
		
		return "";
	}
	
	public function setPassword($pass)
	{
		if ($this->loggedOn == 1) {
			$tempstring = htmlspecialchars($pass);
			$this->strPassword = $tempstring;
		}
	}
	
	public function getLoginForm() {
		$ret = "<div class=\"loginform\" >";
		$ret .= "<table><tr><td><img src=\"images/logo.png\"></td><td>";
		$ret .= "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">";
		$ret .= "<table><tr><td>Benutzername:</td>";
		$ret .= "<td><input type=\"text\" size=\"24\" maxlength=\"50\" name=\"username\"> </td></tr>";
		$ret .= "<tr><td>Passwort:</td><td> <input type=\"password\" size=\"24\" maxlength=\"50\" name=\"password\"></td></tr>";
		$ret .= "<tr> <td colspan=\"2\"> <input type=\"submit\" value=\"Login\"> </td></tr></table> </form>";
		$ret .= "</td></tr></table></div>";
		
		return $ret;
	}

}

?>
