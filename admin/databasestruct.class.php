<?php

class CTable {
	private $fields;
	private $tblname;
	private $bPrimaryKey;
	private $primeKey;
	private $engine;
	private $charset;
	private $autoincrement;
	
	public function __construct() {
		$this->fields = array();
		$this->tblname = "dummyname";
		$this->bPrimaryKey = false;
		$this->primeKey = "";
		$this->engine = "MyISAM"; // default engine
		$this->charset = "utf8";
		$this->autoincrement = 1;
	}
	
	public function setAutoincrement($v) {
		$this->autoincrement = $v;
	}
	
	public function setCharset($v) {
		$this->charset = $v;
	}
	
	public function setEngine($v) {
		$this->engine = $v;
	}
	
	public function addField($f) {
		$this->fields[] = $f;
	}
	
	public function setTableName($v) {
		$this->tblname = $v;
	}
	
	public function getCreateCommand() {
		$sql = "CREATE TABLE IF NOT EXISTS " . $this->tblname . " (";
		for ($i = 0; $i < count($this->fields); $i++ ) {
			if ( $i != 0 ) $sql .= ", ";
			sql .= $this->fields[$i];
		}
		
		$sql .= ") ENGINE=" . $this->engine . " DEFAULT CHARSET=" . $this->charset . " ";
		
		if ( $this->bPrimaryKey ) $sql .= "AUTOINCREMENT=" . $this->autoincrement;
		$sql .= ";";
		
		return $sql;
	}
	
};

class CDatabaseStructure {
	private CTable tables;
	
	public function __construct() {
		$this->tables = array();
	}
	
	public function addTable(CTable ctbl) {
		$this->tables[] = ctbl;
	}
};

?>