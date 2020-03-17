use testdb;

CREATE TABLE IF NOT EXISTS aplan_drive_recompensation (
    idDrive INT NOT NULL AUTO_INCREMENT,
    userid INT NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    val DOUBLE(4,4),
    PRIMARY KEY(idDrive)
);

