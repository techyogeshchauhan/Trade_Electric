-- Add columns for Smart Trade flow tracking
ALTER TABLE contracts 
ADD COLUMN IF NOT EXISTS energy_filled TINYINT(1) DEFAULT 0 COMMENT 'Whether energy has been filled in monitor',
ADD COLUMN IF NOT EXISTS trade_status VARCHAR(20) DEFAULT 'to_be_traded' COMMENT 'to_be_traded, fully_sold, completed',
ADD COLUMN IF NOT EXISTS energy_filled_at DATETIME NULL COMMENT 'When energy was filled',
ADD COLUMN IF NOT EXISTS trade_completed_at DATETIME NULL COMMENT 'When trade was completed',
ADD COLUMN IF NOT EXISTS tokens_generated TINYINT(1) DEFAULT 0 COMMENT 'Whether tokens have been generated',
ADD COLUMN IF NOT EXISTS wallet_credited TINYINT(1) DEFAULT 0 COMMENT 'Whether wallet has been credited',
ADD COLUMN IF NOT EXISTS transaction_charge DECIMAL(10,2) DEFAULT 0 COMMENT 'KERC transaction charge (₹0.14/unit)';

-- Update existing contracts to have transaction charge
UPDATE contracts 
SET transaction_charge = units * 0.14 
WHERE transaction_charge = 0;

-- Add index for better performance
CREATE INDEX IF NOT EXISTS idx_contracts_trade_status ON contracts(trade_status);
CREATE INDEX IF NOT EXISTS idx_contracts_energy_filled ON contracts(energy_filled);
CREATE INDEX IF NOT EXISTS idx_contracts_date_time ON contracts(date, time_block);
