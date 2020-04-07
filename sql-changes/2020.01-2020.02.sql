use testdb;

CREATE TABLE aplan_freeze (
	idFreeze INT NOT NULL AUTO_INCREMENT,
	user INT NOT NULL,
	freezedate DATE NOT NULL,
	PRIMARY KEY(idFreeze)
);
