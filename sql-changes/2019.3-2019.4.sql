CREATE TABLE IF NOT EXISTS aplan2_schedules (
    idSchedule INT NOT NULL AUTO_INCREMENT,
    userid INT NOT NULL,
    startdate DATE NOT NULL,
    enddate DATE NOT NULL,
    label VARCHAR(100),
    PRIMARY KEY(idSchedule)
);

CREATE TABLE IF NOT EXISTS aplan2_schedule_items (
    idScheduleItem INT NOT NULL AUTO_INCREMENT,
    idSchedule INT NOT NULL,
    dayOfWeek INT NOT NULL,
    time_from VARCHAR(8) NOT NULL,
    time_to VARCHAR(8) NOT NULL,
    PRIMARY KEY(idScheduleItem)
);
