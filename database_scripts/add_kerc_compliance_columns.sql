-- KERC P2P Solar Energy Transaction Regulations 2024 Compliance
-- Add transaction_charge column to contracts and trades tables

-- Add transaction_charge to contracts table
ALTER TABLE contracts 
ADD COLUMN IF NOT EXISTS transaction_charge DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'KERC Transaction Charge: 14 paise per unit as per KERC guidelines';

-- Add transaction_charge to trades table
ALTER TABLE trades 
ADD COLUMN IF NOT EXISTS transaction_charge DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'KERC Transaction Charge: 14 paise per unit as per KERC guidelines';

-- Update existing records with transaction charge (14 paise per unit)
UPDATE contracts SET transaction_charge = units * 0.14 WHERE transaction_charge = 0;
UPDATE trades SET transaction_charge = units * 0.14 WHERE transaction_charge = 0;

-- Add comments to existing columns for KERC compliance
ALTER TABLE contracts MODIFY COLUMN platform_fee DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Service Provider fee for P2P platform services';

ALTER TABLE contracts MODIFY COLUMN utility_fee DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Green Energy Open Access charges as per KERC tariff order';

ALTER TABLE trades MODIFY COLUMN platform_fee DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Service Provider fee for P2P platform services';

ALTER TABLE trades MODIFY COLUMN utility_fee DECIMAL(10,2) DEFAULT 0.00 
COMMENT 'Green Energy Open Access charges as per KERC tariff order';
