CREATE DATABASE testdb;

CREATE USER 'docker'@'localhost' IDENTIFIED BY '123456';

GRANT ALL PRIVILEGES ON testdb.* TO 'docker'@'localhost';

