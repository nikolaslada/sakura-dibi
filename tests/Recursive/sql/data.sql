SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `recursive` (`id`, `parent`, `name`) VALUES
(1,	NULL,	'Root'),
(2,	1,	'BranchA'),
(3,	1,	'BranchB'),
(4,	1,	'BranchC'),
(5,	1,	'BranchD'),
(6,	3,	'BranchBA'),
(7,	6,	'BranchBAA'),
(8,	4,	'BranchCA'),
(9,	4,	'BranchCB'),
(10,	8,	'BranchCAA'),
(11,	8,	'BranchCAB'),
(12,	9,	'BranchCBA'),
(13,	9,	'BranchCBB');

