-- Backup script to run before medical records integration
-- This will save the current structure of the tables we're about to modify

-- Backup medicalrecords table
CREATE TABLE IF NOT EXISTS medicalrecords_backup LIKE medicalrecords;
INSERT INTO medicalrecords_backup SELECT * FROM medicalrecords;

-- Backup billing records table (check which one exists)
SET @table_exists = 0;
SELECT COUNT(*) INTO @table_exists FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'billingrecords';

DROP PROCEDURE IF EXISTS backup_billing_table;
DELIMITER //
CREATE PROCEDURE backup_billing_table()
BEGIN
    IF @table_exists > 0 THEN
        -- Backup billingrecords if it exists
        CREATE TABLE IF NOT EXISTS billingrecords_backup LIKE billingrecords;
        INSERT INTO billingrecords_backup SELECT * FROM billingrecords;
    ELSE
        -- Check if billing_records exists
        SET @billing_exists = 0;
        SELECT COUNT(*) INTO @billing_exists FROM information_schema.tables 
        WHERE table_schema = DATABASE() AND table_name = 'billing_records';
        
        IF @billing_exists > 0 THEN
            -- Backup billing_records if it exists
            CREATE TABLE IF NOT EXISTS billing_records_backup LIKE billing_records;
            INSERT INTO billing_records_backup SELECT * FROM billing_records;
        END IF;
    END IF;
END //
DELIMITER ;

CALL backup_billing_table();
DROP PROCEDURE IF EXISTS backup_billing_table;

-- Backup appointments table
CREATE TABLE IF NOT EXISTS appointments_backup LIKE appointments;
INSERT INTO appointments_backup SELECT * FROM appointments;

-- Backup successful message
SELECT 'Backup completed successfully. Tables medicalrecords_backup, appointments_backup, and billing table backups created.' AS message; 