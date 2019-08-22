SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `traversal`;
CREATE TABLE `traversal` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `left` int(10) unsigned NOT NULL,
  `right` int(10) unsigned NOT NULL,
  `parent` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `left` (`left`),
  UNIQUE KEY `right` (`right`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
