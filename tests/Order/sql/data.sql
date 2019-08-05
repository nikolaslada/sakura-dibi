SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `order` (`id`, `order`, `depth`, `parent`, `name`) VALUES
(1,	1,	1,	NULL,	'Root'),
(2,	2,	2,	1,	'BranchA'),
(3,	3,	2,	1,	'BranchB'),
(4,	6,	2,	1,	'BranchC'),
(5,	13,	2,	1,	'BranchD'),
(6,	4,	3,	3,	'BranchBA'),
(7,	5,	4,	6,	'BranchBAA'),
(8,	7,	3,	4,	'BranchCA'),
(9,	10,	3,	4,	'BranchCB'),
(10,	8,	4,	8,	'BranchCAA'),
(11,	9,	4,	8,	'BranchCAB'),
(12,	11,	4,	9,	'BranchCBA'),
(13,	12,	4,	9,	'BranchCBB');
