-- Migration script for password reset functionality
-- Add reset token fields to users table

USE sroiv5;

ALTER TABLE `users` 
ADD COLUMN `reset_token` VARCHAR(255) NULL COMMENT 'โทเค็นสำหรับรีเซ็ตรหัสผ่าน' AFTER `email_verified`,
ADD COLUMN `reset_token_expires` DATETIME NULL COMMENT 'วันหมดอายุโทเค็น' AFTER `reset_token`;

-- Add index for faster token lookup
CREATE INDEX idx_reset_token ON users(reset_token);
CREATE INDEX idx_reset_token_expires ON users(reset_token_expires);