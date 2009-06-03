ALTER TABLE `purchdata` ADD PRIMARY KEY (`supplierno`,`stockid`, `effectivefrom`); 
ALTER TABLE `salesorders` ADD `quotedate` date NOT NULL default '0000-00-00';
ALTER TABLE `salesorders` ADD `confirmeddate` date NOT NULL default '0000-00-00';


