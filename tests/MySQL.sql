DROP DATABASE IF EXISTS `test`;
CREATE DATABASE `test`;
USE `test`;
CREATE TABLE `test_integers` (`tinyint` TINYINT NOT NULL,
                                     `smallint` SMALLINT NOT NULL,
                                     `mediumint` MEDIUMINT NOT NULL,
                                     `int` INT NOT NULL,
                                     `bigint` BIGINT NOT NULL,
                                     `tinyint_unsigned` TINYINT UNSIGNED NOT NULL,
                                     `smallint_unsigned` SMALLINT UNSIGNED NOT NULL,
                                     `mediumint_unsigned` MEDIUMINT UNSIGNED NOT NULL,
                                     `int_unsigned` INT UNSIGNED NOT NULL,
                                     `bigint_unsigned` BIGINT UNSIGNED NOT NULL,
                                     `int_null` INT NULL,
                                     `int_unsigned_null` INT UNSIGNED NULL,
                                     `int_default_null` INT NULL DEFAULT NULL,
                                     `int_default_12345` INT NOT NULL DEFAULT '12345') ENGINE=InnoDB ROW_FORMAT=COMPRESSED;