-- Add review comment field to tickets table
ALTER TABLE `tickets` 
ADD COLUMN `satisfaction_comment` TEXT NULL AFTER `satisfaction_rating`;

-- Add index for better performance when filtering reviewed tickets
ALTER TABLE `tickets`
ADD INDEX `idx_satisfaction_rating` (`satisfaction_rating`);
