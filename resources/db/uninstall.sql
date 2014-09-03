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

--
-- Disable foreign key checks so we can delete tables in any order.
--
SET FOREIGN_KEY_CHECKS = 0;

--
-- Gets all table names from the current database, or null if there
-- are no tables.
--
SET @tables = NULL;
SELECT GROUP_CONCAT(table_name) INTO @tables
  FROM information_schema.tables
  WHERE table_schema = DATABASE();

--
-- Create a no-op statement, SELECT 1, if there are no tables to delete,
-- or construct the DROP TABLE statement.
--
SET @tables = IF(@tables IS NULL, 'SELECT 1', CONCAT('DROP TABLE ', @tables));

--
-- Prepare, execute, and deallocate the statement.
--
PREPARE stmt FROM @tables;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

--
-- Reenable foreign key checks.
--
SET FOREIGN_KEY_CHECKS = 1;
