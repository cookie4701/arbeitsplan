<?php


class schedule
{
    private $id;
    private $startdate;
    private $enddate;
    private $label;
    /** @var mysqli $dbConnection */
    private $dbConnection;

    public function __construct()
    {
        $this->id = -1;
        $this->startdate = mktime(0,0,0,12,31,1900);
        $this->enddate = mktime(0,0,0,12,31,1900);
        $this->dbConnection = null;
    }

    public function setId($i) {
        $this->id = $i;
    }

    /**
     * @param false|int $startdate
     */
    public function setStartdate($startdate)
    {
        $this->startdate = $startdate;
    }

    /**
     * @param false|int $enddate
     */
    public function setEnddate($enddate)
    {
        $this->enddate = $enddate;
    }

    /**
     * @param mixed $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return false|int
     */
    public function getStartdate()
    {
        return $this->startdate;
    }

    /**
     * @return false|int
     */
    public function getEnddate()
    {
        return $this->enddate;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }



    public function saveToDatabase() {
        if ($this->dbConnection == null) return;

        if ($this->id < 0) {
            $this->insertToDatabase();
        } else {
            $this->updateToDatabase();
        }
    }

    /** @var mysqli $dbx */
    public function setDatabaseConnection($dbx) {
        $this->dbConnection = $dbx;
    }

    private function insertToDatabase() {

        $stmt = $this->dbConnection->stmt_init();
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

    private function updateToDatabase() {

        if ($this->id <= 0 ) {
            return false;
        }

        $stmt = $this->dbConnection->stmt_init();
        $sql = "UPDATE " . CConfig::$db_tbl_prefix  . "schedules ";
        $sql .= "SET startdate=?, enddate=?, label=? ";
        $sql .= "WHERE userid=?";

        if ($stmt->prepare($sql) &&
            $stmt->bind_param("sssi", $startdate, $enddate, $label, $userid) &&
            $stmt->execute()) {

            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }
}