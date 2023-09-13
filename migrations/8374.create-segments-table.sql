CREATE TABLE `segments` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(100) NOT NULL,
  `condition_hash` VARCHAR(32) NOT NULL,
  `script` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `table_name_condition_hash` (`table_name`, `condition_hash`),
  INDEX `condition_hash` (`condition_hash`),
  INDEX `table_name` (`table_name`)
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB
;
