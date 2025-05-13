-- Add appointment_id column to medicalrecords table if it doesn't exist
ALTER TABLE medicalrecords
ADD COLUMN IF NOT EXISTS appointment_id INT NULL,
ADD CONSTRAINT fk_medicalrecords_appointments
FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
ON DELETE SET NULL;

-- Create index on appointment_id for faster lookups
CREATE INDEX IF NOT EXISTS idx_medicalrecords_appointment_id ON medicalrecords(appointment_id);

-- Add appointment_id column to billing_records table if it doesn't exist
-- Note: It appears this column may already exist in your schema, but adding this for completeness
ALTER TABLE billing_records
MODIFY COLUMN appointment_id INT NULL,
ADD CONSTRAINT fk_billing_records_appointments
FOREIGN KEY (appointment_id) REFERENCES appointments(appointment_id)
ON DELETE SET NULL;

-- Create index on appointment_id for faster lookups
CREATE INDEX IF NOT EXISTS idx_billing_records_appointment_id ON billing_records(appointment_id); 