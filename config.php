<?php

class CConfig {
	public static $dbuser = 'dbuser'; // empty(getenv('MYSQL_USER')) ? 'dbuser' : getenv('MYSQL_USER');
	public static $dbpass = 'dbpass'; // (empty(getenv('MYSQL_PASSWORD'))) ? 'dbuser' : getenv('MYSQL_PASSWORD');
	public static $dbhost = 'dbhost'; // (empty(getenv('MYSQL_HOST'))) ? 'dbuser' : getenv('MYSQL_HOST');
	public static $dbname = 'dbname'; // (empty(getenv('MYSQL_DB'))) ? 'dbuser' : getenv('MYSQL_DB');
	public static $jwtsecret = 'secret'; // (empty(getenv('JWTSECRET'))) ? 'dbuser' : getenv('JWTSECRET'); // Must be 12 characters in length, contain upper and lower case letters, a number, and a special character `*&!@%^#$``
	public static $db_tbl_prefix = "aplan_";
        public static $max_rank_workfields = 24;

	/* Adminpanel options */
	public static $backupdir = "backup";
	public static $displayUsers = 20;
};

?>
