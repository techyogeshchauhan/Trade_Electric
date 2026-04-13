-- Insert meter data for 15-minute intervals from 9 AM to 5 PM
-- This will create 32 records (8 hours * 4 intervals per hour)

-- Clear existing data (optional - remove if you want to keep existing data)
-- TRUNCATE TABLE meter_data;

-- Insert data for user_id = 7 (change this to your seller's user_id)
SET @user_id = 7;
SET @date = CURDATE(); -- Current date

-- 9:00 AM to 10:00 AM
INSERT INTO meter_data (user_id, date, time_block, generated_units, self_consumption, surplus_units, created_at) VALUES
(@user_id, @date, '09:00-09:15', 2.50, 0.95, 1.55, NOW()),
(@user_id, @date, '09:15-09:30', 2.75, 1.05, 1.70, NOW()),
(@user_id, @date, '09:30-09:45', 3.00, 1.15, 1.85, NOW()),
(@user_id, @date, '09:45-10:00', 3.25, 1.25, 2.00, NOW()),

-- 10:00 AM to 11:00 AM
(@user_id, @date, '10:00-10:15', 3.75, 1.40, 2.35, NOW()),
(@user_id, @date, '10:15-10:30', 4.00, 1.50, 2.50, NOW()),
(@user_id, @date, '10:30-10:45', 4.25, 1.60, 2.65, NOW()),
(@user_id, @date, '10:45-11:00', 4.50, 1.70, 2.80, NOW()),

-- 11:00 AM to 12:00 PM
(@user_id, @date, '11:00-11:15', 4.70, 1.75, 2.95, NOW()),
(@user_id, @date, '11:15-11:30', 4.90, 1.85, 3.05, NOW()),
(@user_id, @date, '11:30-11:45', 5.10, 1.95, 3.15, NOW()),
(@user_id, @date, '11:45-12:00', 5.30, 2.00, 3.30, NOW()),

-- 12:00 PM to 1:00 PM
(@user_id, @date, '12:00-12:15', 5.50, 2.10, 3.40, NOW()),
(@user_id, @date, '12:15-12:30', 5.65, 2.15, 3.50, NOW()),
(@user_id, @date, '12:30-12:45', 5.75, 2.20, 3.55, NOW()),
(@user_id, @date, '12:45-13:00', 5.85, 2.25, 3.60, NOW()),

-- 1:00 PM to 2:00 PM
(@user_id, @date, '13:00-13:15', 5.90, 2.25, 3.65, NOW()),
(@user_id, @date, '13:15-13:30', 6.00, 2.30, 3.70, NOW()),
(@user_id, @date, '13:30-13:45', 6.10, 2.35, 3.75, NOW()),
(@user_id, @date, '13:45-14:00', 6.20, 2.40, 3.80, NOW()),

-- 2:00 PM to 3:00 PM (PEAK TIME)
(@user_id, @date, '14:00-14:15', 6.25, 2.40, 3.85, NOW()),
(@user_id, @date, '14:15-14:30', 6.30, 2.45, 3.85, NOW()),
(@user_id, @date, '14:30-14:45', 6.25, 2.40, 3.85, NOW()),
(@user_id, @date, '14:45-15:00', 6.20, 2.35, 3.85, NOW()),

-- 3:00 PM to 4:00 PM
(@user_id, @date, '15:00-15:15', 5.65, 2.15, 3.50, NOW()),
(@user_id, @date, '15:15-15:30', 5.40, 2.05, 3.35, NOW()),
(@user_id, @date, '15:30-15:45', 5.15, 1.95, 3.20, NOW()),
(@user_id, @date, '15:45-16:00', 4.90, 1.85, 3.05, NOW()),

-- 4:00 PM to 5:00 PM
(@user_id, @date, '16:00-16:15', 4.40, 1.65, 2.75, NOW()),
(@user_id, @date, '16:15-16:30', 4.00, 1.50, 2.50, NOW()),
(@user_id, @date, '16:30-16:45', 3.50, 1.30, 2.20, NOW()),
(@user_id, @date, '16:45-17:00', 3.00, 1.15, 1.85, NOW());

-- Verify the inserted data
SELECT * FROM meter_data WHERE user_id = @user_id AND date = @date ORDER BY time_block;
