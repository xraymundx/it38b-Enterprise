-- Direct integration script - No backups, direct changes

-- Check if billingrecords table exists
SET @billingrecords_exists = (SELECT COUNT(*) FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'billingrecords');

-- 1. Add columns to medicalrecords table
ALTER TABLE medicalrecords
ADD COLUMN IF NOT EXISTS appointment_id INT NULL,
ADD COLUMN IF NOT EXISTS prescribed_medications TEXT NULL,
ADD COLUMN IF NOT EXISTS test_results TEXT NULL;

-- 2. Add the index to medicalrecords
SET @index_exists = (SELECT COUNT(*) FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'medicalrecords' AND index_name = 'idx_appointment_id');
SET @sql = IF(@index_exists = 0, 'ALTER TABLE medicalrecords ADD INDEX idx_appointment_id (appointment_id)', 'SELECT "Index already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add foreign key to medicalrecords
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.referential_constraints 
WHERE constraint_schema = DATABASE() AND table_name = 'medicalrecords' AND referenced_table_name = 'appointments');
SET @sql = IF(@fk_exists = 0, 
'ALTER TABLE medicalrecords ADD CONSTRAINT fk_medicalrecords_appointments 
FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL', 
'SELECT "Foreign key already exists" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Update appointment status enum values
SET @column_type = (SELECT column_type FROM information_schema.columns 
WHERE table_schema = DATABASE() AND table_name = 'appointments' AND column_name = 'status');
SET @sql = IF(@column_type != "enum('Requested','Scheduled','Completed','No Show','Cancelled')",
'ALTER TABLE appointments MODIFY COLUMN status ENUM("Requested", "Scheduled", "Completed", "No Show", "Cancelled") DEFAULT "Scheduled"', 
'SELECT "Status column already has correct values" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Add columns to billingrecords
SET @sql = IF(@billingrecords_exists > 0,
'ALTER TABLE billingrecords 
ADD COLUMN IF NOT EXISTS record_id INT NULL,
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL, 
ADD COLUMN IF NOT EXISTS payment_date DATETIME NULL,
ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS notes TEXT NULL', 
'SELECT "Billingrecords table does not exist" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. Add index to billingrecords
SET @index_exists = (SELECT COUNT(*) FROM information_schema.statistics 
WHERE table_schema = DATABASE() AND table_name = 'billingrecords' AND index_name = 'idx_record_id');
SET @sql = IF(@index_exists = 0 AND @billingrecords_exists > 0, 
'ALTER TABLE billingrecords ADD INDEX idx_record_id (record_id)', 
'SELECT "Index already exists or table doesn\'t exist" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 7. Add foreign key to billingrecords
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.referential_constraints 
WHERE constraint_schema = DATABASE() AND table_name = 'billingrecords' AND referenced_table_name = 'medicalrecords');
SET @sql = IF(@fk_exists = 0 AND @billingrecords_exists > 0,
'ALTER TABLE billingrecords ADD CONSTRAINT fk_billingrecords_medicalrecords 
FOREIGN KEY (record_id) REFERENCES medicalrecords(record_id) ON DELETE SET NULL', 
'SELECT "Foreign key already exists or table doesn\'t exist" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 8. Create reporting view
SET @sql = IF(@billingrecords_exists > 0,
'CREATE OR REPLACE VIEW view_appointment_records AS
SELECT 
    a.appointment_id,
    a.appointment_datetime,
    a.status,
    a.reason_for_visit,
    CONCAT(pu.first_name, " ", pu.last_name) AS patient_name,
    CONCAT(du.first_name, " ", du.last_name) AS doctor_name,
    COUNT(DISTINCT m.record_id) AS medical_record_count,
    COUNT(DISTINCT b.bill_id) AS billing_record_count,
    SUM(IFNULL(b.amount, 0)) AS total_billed_amount
FROM 
    appointments a
JOIN patients p ON a.patient_id = p.patient_id
JOIN users pu ON p.user_id = pu.user_id
JOIN doctors d ON a.doctor_id = d.doctor_id
JOIN users du ON d.user_id = du.user_id
LEFT JOIN medicalrecords m ON a.appointment_id = m.appointment_id
LEFT JOIN billingrecords b ON a.appointment_id = b.appointment_id
GROUP BY a.appointment_id',
'SELECT "Cannot create view as billingrecords table does not exist" AS message');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'Direct integration completed. Your database has been updated with the new fields needed for medical records and billing integration.' AS message; 