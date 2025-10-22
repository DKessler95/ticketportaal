-- Add Sales and Facilitair departments
-- Run this in phpMyAdmin or MySQL client

INSERT INTO departments (name, description, is_active, created_at) 
VALUES 
    ('Sales', 'Sales afdeling', 1, NOW()),
    ('Facilitair', 'Facilitaire diensten', 1, NOW())
ON DUPLICATE KEY UPDATE 
    name = VALUES(name);
