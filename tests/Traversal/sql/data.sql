SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

INSERT INTO `traversal` (`id`, `left`, `right`, `parent`, `name`) VALUES
(1,	1,	26,	NULL,	'Root'),
(2,	2,	3,	1,	'BranchA'),
(3,	4,	9,	1,	'BranchB'),
(4,	10,	23,	1,	'BranchC'),
(5,	24,	25,	1,	'BranchD'),
(6,	5,	8,	3,	'BranchBA'),
(7,	6,	7,	6,	'BranchBAA'),
(8,	11,	16,	4,	'BranchCA'),
(9,	17,	22,	4,	'BranchCB'),
(10,	12,	13,	8,	'BranchCAA'),
(11,	14,	15,	8,	'BranchCAB'),
(12,	18,	19,	9,	'BranchCBA'),
(13,	20,	21,	9,	'BranchCBB');
