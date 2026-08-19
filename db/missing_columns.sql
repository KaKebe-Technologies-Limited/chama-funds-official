-- ============================================================
-- ChamaFunds – missing_columns.sql
-- Run this once on the live database to add any columns that
-- were added after the initial deployment but are missing from
-- an older schema dump.
-- Requires MariaDB 10.0+ / MySQL 8.0.29+ for "ADD COLUMN IF NOT
-- EXISTS" — safe to run multiple times.
-- ============================================================

-- Marks a donation as entered manually by an admin (cash, bank
-- transfer, etc.) rather than a real mobile-money/card payment.
-- See api/donations.php action=admin_add_manual.
ALTER TABLE `donations`
  ADD COLUMN IF NOT EXISTS `added_by_admin_id` INT(11) NULL DEFAULT NULL AFTER `donor_id`,
  ADD INDEX IF NOT EXISTS `idx_donations_added_by_admin` (`added_by_admin_id`);

-- ioTec charges ChamaFunds a flat disbursement fee to actually send a
-- withdrawal payout to mobile money — separate from ChamaFunds' own
-- platform fee. iotec_fee stores that amount per withdrawal (see
-- calculateIotecPayoutFee() in includes/iotec_functions.php); receipt_path
-- stores the generated PDF receipt filename once a withdrawal completes
-- (see includes/receipt_pdf.php). net_amount is redefined so it actually
-- subtracts the ioTec fee too, not just the platform fee.
ALTER TABLE `withdrawals`
  ADD COLUMN IF NOT EXISTS `iotec_fee` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `fee_amount`,
  ADD COLUMN IF NOT EXISTS `receipt_path` VARCHAR(255) NULL DEFAULT NULL AFTER `transaction_reference`;

ALTER TABLE `withdrawals`
  MODIFY COLUMN `net_amount` DECIMAL(15,2)
  GENERATED ALWAYS AS (`gross_amount` - (`gross_amount` * (`fee_percentage` / 100)) - `iotec_fee`) STORED;
