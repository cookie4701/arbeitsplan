<?php

$dbuser = "docker";
$dbpass = "123456";
$dbserver = "localhost";
$dbname = "testdb";
$db_tbl_prefix = "aplan_";
$max_rank_workfields = 24;

class CConfig {
	public static $dbuser = "docker";
	public static $dbpass = "123456";
	public static $dbhost = "localhost";
	public static $dbname = "testdb";
	public static $db_tbl_prefix = "aplan_";
        public static $max_rank_workfields = 24;
        public static $jwt_secret = ""; // Must be 12 characters in length, contain upper and lower case letters, a number, and a special character `*&!@%^#$``

	/* Adminpanel options */
	public static $backupdir = "backup";
	public static $displayUsers = 20;
};

?>
