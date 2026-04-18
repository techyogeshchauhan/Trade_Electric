-- Add new columns to settings table for dynamic configuration
-- Run this script to remove hardcoded values from the system

-- Add default wallet balance
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS default_wallet_balance DECIMAL(10,2) DEFAULT 5000.00 
COMMENT 'Default balance when creating new wallet';

-- Add GESCOM average values
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS gescom_avg_consumption DECIMAL(10,2) DEFAULT 5.00 
COMMENT 'GESCOM average consumption for buyers';

ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS gescom_avg_supply DECIMAL(10,2) DEFAULT 5.00 
COMMENT 'GESCOM average supply for sellers';

-- Add max units per slot
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS max_units_per_slot INT DEFAULT 100 
COMMENT 'Maximum units allowed per time slot';

-- Add listing limits
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS default_listing_limit INT DEFAULT 10 
COMMENT 'Default number of listings to show';

ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS max_listing_limit INT DEFAULT 1000 
COMMENT 'Maximum number of listings allowed';

-- Add logo paths
ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS logo_left VARCHAR(255) DEFAULT '../assets/gescomLogo.png' 
COMMENT 'Path to left logo image';

ALTER TABLE settings 
ADD COLUMN IF NOT EXISTS logo_right VARCHAR(255) DEFAULT '../assets/apcLogo.jpg' 
COMMENT 'Path to right logo image';

-- Insert default values if settings table is empty
INSERT INTO settings (
    utility_charge_buyer, 
    utility_charge_seller, 
    platform_charge,
    default_wallet_balance,
    gescom_avg_consumption,
    gescom_avg_supply,
    max_units_per_slot,
    default_listing_limit,
    max_listing_limit,
    logo_left,
    logo_right
)
SELECT 
    0.02, 
    0.02, 
    2.00,
    5000.00,
    5.00,
    5.00,
    100,
    10,
    1000,
    '../assets/gescomLogo.png',
    '../assets/apcLogo.jpg'
WHERE NOT EXISTS (SELECT 1 FROM settings LIMIT 1);

-- Verify the changes
SELECT * FROM settings;
