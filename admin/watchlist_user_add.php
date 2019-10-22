<!html>

<?php 
	include_once '../config.php';
	
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	
	header ('Content-Type:text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<head>

</head>

<body>
<form action="watchlist_user_add.php" method="POST">

<input type="text" name="username"> <br>
<input type="text" name="orgacode"> <br>
<input type="submit">

</form>

<?php

class CPostParameters {
    private $username;
    private $orgacode;

    public function __construct__() {
        $this->username = "";
        $this->orgacode = "";
    }

    public function setUsername($uname) {
        $this->username = $uname;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setOrgacode($code) {
        $this->orgacode = $code;
    }

    public function getOrgacode() {
        return $this->orgacode;
    }
};

function retrievePostParameters() {
    $PostParameters = new CPostParameters();
    if ( isset($_POST["username"]) ) $PostParameters->setUsername( $_POST["username"]);
    if ( isset($_POST["orgacode"]) ) $PostParameters->setOrgacode( $_POST["orgacode"]);
    return $PostParameters;
}

function checkPostParameters($PostParameters) {

    if ( ! get_class($PostParameters) == "CPostParameters" ) throw new Exception("Wrong class");
    if ( $PostParameters->getUsername() == "" ) throw new Exception("No username posted");
    if ( $PostParameters->getOrgacode() == "" ) throw new Exception("No orgacode posted");
}

function openDatabaseConnection() {
    $dbConnection = new mysqli(CConfig::$dbhost, CConfig::$dbuser, CConfig::$dbpass, CConfig::$dbname);
    return $dbConnection;
}

function initStatement($dbConnection) {
    $stmt = $dbConnection->stmt_init();
    if ( ! get_class($stmt) == "mysqli_stmt" ) throw new Exception("Mysqli error: could not initialize statement!"); 
    return $stmt;
}

function getUserId($dbConnection, $username) {
    $userid = -1;
    $stmt = initStatement($dbConnection);
    $stmt->prepare("SELECT id FROM " . CConfig::$db_tbl_prefix . "users WHERE uname = ? LIMIT 0,1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($userid);
    if (! $stmt->fetch() ) throw new Exception("User not found"); 
    $stmt->close();
    return $userid;
}

function insertUserwatchlist($dbConnection, $userid, $orgacode) {
    $stmt = initStatement($dbConnection);
    $stmt->prepare("INSERT INTO " . CConfig::$db_tbl_prefix . "userwatchlist (iduserwatch, orgacode) VALUES (?,?)");
    $stmt->bind_param("is", $userid, $orgacode);
    if (! $stmt->execute() ) throw new Exception("INSERT failed!");
    $stmt->close();
}

function updateUserwatchlist($dbConnection, $userWatchListId, $orgacode) {
    $stmt = initStatement($dbConnection);
    $stmt->prepare("UPDATE " . CConfig::$db_tbl_prefix . "userwatchlist SET orgacode = ? WHERE id = ?");
    $stmt->bind_param("si", $orgacode, $userWatchListId);
    if ( ! $stmt->execute() ) throw new Exception("UPDATE failed!");
    $stmt->close();
}

function getUserWatchListId($dbConnection, $userid) {
    $stmt = initStatement($dbConnection);
    $stmt->prepare("SELECT id FROM " . CConfig::$db_tbl_prefix . "userwatchlist WHERE iduserwatch = ? LIMIT 0,1");
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $stmt->bind_result($ret);
    if (! $stmt->fetch() ) {
        $stmt->close();
        return -1;
    } else {
        $stmt->close();
        return $ret;
    }
}

// Main

try {
    $PostParameters = retrievePostParameters();
    checkPostParameters($PostParameters);
    
    $dbConnection = openDatabaseConnection();
    
    $userid = getUserId($dbConnection, $PostParameters->getUsername() );
    
    $userWatchListId = getUserWatchListId($dbConnection, $userid);
    
    if ( $userWatchListId < 0 ) { // $userid is not in user watchlist, insert it
        insertUserwatchlist($dbConnection, $userid, $PostParameters->getOrgacode() );
    } else {
        updateUserwatchlist($dbConnection, $userWatchListId, $PostParameters->getOrgacode() );
    }
    
} 

catch ( Exception $e ) {
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
</body>


