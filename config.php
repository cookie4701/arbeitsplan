<?php

class CConfig {
	public static $dbuser = getenv('MYSQL_USER') ?: 'dbuser';
	public static $dbpass = getenv('MYSQL_PASSWORD') ?: 'dbpass';
	public static $dbhost = getenv('MYSQL_HOST') ?: 'localhost';
	public static $dbname = getenv('MYSQL_DB') ?: 'dbname';
	public static $db_tbl_prefix = "aplan_";
        public static $max_rank_workfields = 24;
        public static $jwt_secret = getenv('JWTSECRET') ?: 'NotSoS3cret!'; // Must be 12 characters in length, contain upper and lower case letters, a number, and a special character `*&!@%^#$``

	/* Adminpanel options */
	public static $backupdir = "backup";
	public static $displayUsers = 20;
};

?>
