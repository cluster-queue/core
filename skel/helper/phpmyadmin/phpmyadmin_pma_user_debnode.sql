CREATE USER 'pma'@localhost IDENTIFIED BY 'password';
GRANT SELECT, INSERT, DELETE, UPDATE, ALTER ON `phpmyadmin`.* TO 'pma'@localhost;
