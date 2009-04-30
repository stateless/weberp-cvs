CREATE TABLE `woserialnos` (
	`wo` INT NOT NULL ,
	`stockid` VARCHAR( 20 ) NOT NULL ,
	`serialno` VARCHAR( 30 ) NOT NULL ,
	`qualitytext` TEXT NOT NULL,
	 PRIMARY KEY (`wo`,`stockid`,`serialno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO config (confname, confvalue) VALUES ('AutoCreateWOs',1);
INSERT INTO config (confname, confvalue) VALUES ('DefaultFactoryLocation','MEL');
INSERT INTO config (confname, confvalue) VALUES ('FactoryManagerEmail','manager@company.com');

ALTER TABLE `stockmaster` ADD `nextserialno` VARCHAR( 30 ) NOT NULL DEFAULT '0';
