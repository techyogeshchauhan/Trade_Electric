# KERC P2P Solar Energy Transaction Regulations 2024 - Compliance Implementation

## Overview
This P2P Energy Trading Platform is compliant with **KERC (Karnataka Electricity Regulatory Commission) Implementation of Peer to Peer Solar Energy Transaction Regulations, 2024** (Notification No. KERC/S/03/1/560, dated 06.08.2024).

## Key Compliance Features

### 1. **Transaction Charges**
- **KERC Mandated**: 14 paise (₹0.14) per unit
- **Implementation**: Automatically calculated and added to all transactions
- **Reference**: Section 8(h) of KERC Regulations

### 2. **Platform Technology**
- **Blockchain/Technology Based**: Platform uses blockchain or other technology for recording P2P transactions
- **Immutable Ledger**: All transactions are recorded with transaction hashes
- **Reference**: Section 2(d) - Blockchain definition

### 3. **Participant Types**
- **P2P Prosumer**: Sellers with rooftop solar generating excess energy
- **P2P Consumer**: Buyers purchasing solar energy from prosumers
- **Reference**: Sections 2(j) and 2(k)

### 4. **Eligibility Criteria** (Section 3)
- Registered domestic consumers with SRTPV
- Net metering or gross metering arrangement
- Multiple consumers and prosumers can engage in P2P transactions

### 5. **Pricing Mechanisms** (Section 4)
The platform supports three pricing methods:
1. **Highest Price by Buyer**: Cleared price = buyer's maximum offer
2. **Lowest Price by Seller**: Cleared price = seller's minimum ask
3. **Average Price**: Cleared price = (buyer price + seller price) / 2

### 6. **Smart Meter Requirement** (Section 7)
- ToD (Time of Day) compliant energy meter required
- Post-paid smart meter installation mandatory
- MDM (Meter Data Management) integration for billing

### 7. **Time Block System**
- **15-minute intervals**: Energy transactions in 15-min blocks
- **96 blocks per day**: Complete 24-hour coverage
- **Schedule Submission**:
  - Day ahead: By 17:00 hours of (n-1)th day
  - Intraday: At least 4 time blocks before

### 8. **Energy Accounting & Settlement** (Section 8)
- Billing cycle synced with Distribution Licensee
- Time block-wise meter data fetching
- Mutually agreed transaction price
- Green Energy Open Access charges applicable

### 9. **Charges Breakdown**
```
Total Payment = Energy Cost + Transaction Charge + Platform Fee + Utility Fee

Where:
- Energy Cost = Units × Price per Unit
- Transaction Charge = Units × ₹0.14 (KERC mandated)
- Platform Fee = Units × ₹0.25 (Service Provider)
- Utility Fee = Units × ₹0.02 (Green Energy Open Access)
```

### 10. **Registration Process** (Section 6)
- P2P participants register with Distribution Licensee
- Distribution Licensee communicates to Service Provider
- System compatibility check within 15 days
- Registration on P2P platform within 15 days of acceptance

### 11. **Data Privacy & Security** (Section 5.a.iv)
- Data privacy maintained by Distribution Licensee and Service Provider
- Cyber security protocols implemented
- Secure transaction recording

### 12. **Electrical Safety** (Section 5.a.iii)
- P2P exchange doesn't compromise electrical safety
- Grid-connected SRTPV plants as per KERC regulations
- CEA standards compliance

## Database Schema Updates

### New Columns Added:
```sql
-- contracts table
ALTER TABLE contracts ADD COLUMN transaction_charge DECIMAL(10,2) DEFAULT 0.00;

-- trades table  
ALTER TABLE trades ADD COLUMN transaction_charge DECIMAL(10,2) DEFAULT 0.00;
```

### Run Migration:
```bash
mysql -u root -p energy_trading < database_scripts/add_kerc_compliance_columns.sql
```

## API Changes

### Updated Files:
1. `api/create_contract.php` - Added transaction_charge calculation
2. `api/confirm_contract.php` - Include transaction_charge in trades
3. `api/create_contract_v2.php` - Updated charge structure
4. `api/create_contract_simple.php` - Updated charge structure

### Charge Calculation Example:
```php
// KERC Compliant Charges
$total_amount = $units * $price_per_unit;
$transaction_charge = $units * 0.14;  // KERC mandated
$platform_fee = $units * 0.25;        // Service Provider
$utility_fee = $units * 0.02;         // Green Energy Open Access
```

## Frontend Updates

### Terminology Changes:
- "Seller" → "Prosumer" (in relevant contexts)
- "Buyer" → "Consumer" (in relevant contexts)
- "Trade" → "Smart Trade"

### Display Updates:
- Transaction charges shown separately in billing
- KERC compliance notice on registration
- Smart meter requirement mentioned

## Regulatory References

### Key KERC Documents:
1. **Main Regulation**: KERC (Implementation of Peer to Peer Solar Energy Transaction) Regulations, 2024
2. **Related**: KERC (Implementation of Solar Rooftop Photovoltaic Power Plants) Regulations, 2016
3. **Charges**: KERC (Terms and Conditions for Green Energy Open Access) Regulations, 2022

### Commission Details:
**Karnataka Electricity Regulatory Commission**
- Address: No. 16, C-1, Miller Tank Bund Road, Miller Tank Bed Area, Vasanthanagara, Bengaluru, Karnataka - 560 052
- Notification: No. KERC/S/03/1/560
- Date: 06.08.2024

## Compliance Checklist

- [x] Transaction charges (14 paise/unit) implemented
- [x] Blockchain/technology-based platform
- [x] 15-minute time block system
- [x] Smart meter requirement documented
- [x] Three pricing mechanisms supported
- [x] Green Energy Open Access charges
- [x] Platform fee structure
- [x] Participant registration system
- [x] Data privacy & security measures
- [x] Settlement mechanism with Distribution Licensee

## Future Enhancements

1. **MDM Integration**: Direct integration with Distribution Licensee's MDM system
2. **Automated Schedule Submission**: Auto-submit schedules as per KERC timelines
3. **Real-time Meter Data**: Live meter reading integration
4. **KERC Reporting**: Automated compliance reports for Commission
5. **Penalty Calculation**: Auto-calculate penalties for under/over injection/drawal

## Support & Documentation

For queries related to KERC compliance:
- Email: info@karnataka-energy-trading.com
- Phone: +91-XXXX-XXXXXX
- KERC Website: https://kerc.karnataka.gov.in

---

**Last Updated**: December 2024
**Regulation Version**: KERC P2P Regulations 2024
**Platform Version**: 1.0.0
