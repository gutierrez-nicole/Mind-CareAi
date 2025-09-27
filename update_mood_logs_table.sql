-- Update mood_logs table to match the required structure
ALTER TABLE `mood_logs` 
ADD COLUMN `session_id` VARCHAR(255) DEFAULT NULL AFTER `detected_at`,
ADD COLUMN `duration` INT DEFAULT NULL AFTER `session_id`;

-- Update the table structure to match requirements:
-- id | user_id | emotion | detected_at | session_id | duration