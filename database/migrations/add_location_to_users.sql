-- Add location field to users table
ALTER TABLE users ADD COLUMN location VARCHAR(100) NULL AFTER department_id;

-- Add index for location
ALTER TABLE users ADD INDEX idx_location (location);

-- Update existing users to have default location
UPDATE users SET location = 'Kruit en Kramer' WHERE location IS NULL;
