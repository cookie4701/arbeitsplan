use testdb;

CREATE TABLE IF NOT EXISTS aplan_holliday_setup (
    idHolliday INT NOT NULL AUTO_INCREMENT,
    userid INT NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    nbrdays INT,
    PRIMARY KEY(idHolliday)
);

CREATE TABLE IF NOT EXISTS aplan_vacation_setup (
    idVacation INT NOT NULL AUTO_INCREMENT,
    userid INT NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    nbrdays INT,
    PRIMARY KEY(idVacation)
);

