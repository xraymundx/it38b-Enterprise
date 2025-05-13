-- Add backup tables first
CREATE TABLE IF NOT EXISTS medicalrecords_backup AS SELECT * FROM medicalrecords;
CREATE TABLE IF NOT EXISTS appointments_backup AS SELECT * FROM appointments;
SET @billing_table = IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'billingrecords'), 'billingrecords', 'billing_records');

SET @backup_billing = CONCAT('CREATE TABLE IF NOT EXISTS ', @billing_table, '_backup AS SELECT * FROM ', @billing_table);
PREPARE stmt FROM @backup_billing;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Modify medicalrecords table
ALTER TABLE medicalrecords
ADD COLUMN IF NOT EXISTS appointment_id INT NULL,
ADD COLUMN IF NOT EXISTS prescribed_medications TEXT NULL,
ADD COLUMN IF NOT EXISTS test_results TEXT NULL,
ADD INDEX idx_appointment_id (appointment_id),
ADD FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id) ON DELETE SET NULL;

-- Handle billing records table
SET @rename_stmt = IF(@billing_table = 'billingrecords', 'RENAME TABLE billingrecords TO billing_records', 'SELECT 1');
PREPARE stmt FROM @rename_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure billing_records table has all needed columns
ALTER TABLE billing_records
ADD COLUMN IF NOT EXISTS record_id INT NULL,
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS payment_date DATETIME NULL,
ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS notes TEXT NULL,
ADD COLUMN IF NOT EXISTS billing_datetime TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
ADD INDEX idx_appointment_id (appointment_id),
ADD INDEX idx_record_id (record_id),
ADD FOREIGN KEY (record_id) REFERENCES medicalrecords(record_id) ON DELETE SET NULL;

-- Update the appointment status options
ALTER TABLE appointments 
MODIFY COLUMN status ENUM('Requested', 'Scheduled', 'Completed', 'No Show', 'Cancelled') DEFAULT 'Scheduled';

-- Create a simple view for reporting
CREATE OR REPLACE VIEW view_appointment_records AS
SELECT 
    a.appointment_id,
    a.appointment_datetime,
    a.status,
    CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
    CONCAT(du.first_name, ' ', du.last_name) AS doctor_name,
    COUNT(DISTINCT m.record_id) AS medical_record_count,
    COUNT(DISTINCT b.billing_id) AS billing_record_count,
    SUM(b.amount) AS total_billed_amount
FROM 
    appointments a
JOIN patients p ON a.patient_id = p.patient_id
JOIN users pu ON p.user_id = pu.user_id
JOIN doctors d ON a.doctor_id = d.doctor_id
JOIN users du ON d.user_id = du.user_id
LEFT JOIN medicalrecords m ON a.appointment_id = m.appointment_id
LEFT JOIN billing_records b ON a.appointment_id = b.appointment_id
GROUP BY a.appointment_id; 