SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `order` (`id`, `order`, `depth`, `parent`, `name`) VALUES
(1,	1,	0,	NULL,	'Root'),
(2,	2,	1,	1,	'BranchA'),
(3,	3,	1,	1,	'BranchB'),
(4,	6,	1,	1,	'BranchC'),
(5,	13,	1,	1,	'BranchD'),
(6,	4,	2,	3,	'BranchBA'),
(7,	5,	3,	6,	'BranchBAA'),
(8,	7,	2,	4,	'BranchCA'),
(9,	10,	2,	4,	'BranchCB'),
(10,	8,	3,	8,	'BranchCAA'),
(11,	9,	3,	8,	'BranchCAB'),
(12,	11,	3,	9,	'BranchCBA'),
(13,	12,	3,	9,	'BranchCBB');
