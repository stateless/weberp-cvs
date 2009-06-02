ALTER TABLE `purchdata` DROP PRIMARY KEY;
ALTER TABLE `purchdata` ADD PRIMARY KEY (`supplierno`,`stockid`, `effectivefrom`); 
