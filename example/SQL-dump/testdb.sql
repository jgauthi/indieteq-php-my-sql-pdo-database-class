SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `persons`;
CREATE TABLE `persons` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `Firstname` varchar(32) DEFAULT NULL,
  `Lastname` varchar(32) DEFAULT NULL,
  `Sex` char(1) DEFAULT NULL,
  `Age` tinyint(3) DEFAULT NULL,
  `last_update` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;

INSERT INTO `persons` (Id, Firstname, Lastname, Sex, Age)
     VALUES (1, 'John', 'Doe', 'M', 19),
            (2, 'Bob', 'Black', 'M', 40),
            (3, 'Zoe', 'Chan', 'F', 21),
            (4, 'Sekito', 'Khan', 'M', 19),
            (5, 'Kader', 'Khan', 'M', 56)
;


DROP TABLE IF EXISTS `variable`;
CREATE TABLE `variable` (
    `name` varchar(100) NOT NULL,
    `value` text,
    `serialize` tinyint UNSIGNED NOT NULL DEFAULT '0',
    `dateUpdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Diverses variables et options pour l''application';

INSERT INTO `variable` (`name`, `value`, `serialize`) VALUES  ('timestamp', '1495113365', 0);
