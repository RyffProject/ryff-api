--
-- Uninstall Database
-- ==================
--
-- Disables foreign key checks, drops all tables, and
-- reenables foreign key checks.
--
-- Ryff API <http://www.github.com/rfotino/ryff-api>
-- Released under the Apache License 2.0.
--

SET FOREIGN_KEY_CHECKS = 0; 
SET @tables = NULL;
SELECT GROUP_CONCAT(table_name) INTO @tables
  FROM information_schema.tables 
  WHERE table_schema = DATABASE();

SET @tables = CONCAT('DROP TABLE ', @tables);
PREPARE stmt FROM @tables;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
SET FOREIGN_KEY_CHECKS = 1;
