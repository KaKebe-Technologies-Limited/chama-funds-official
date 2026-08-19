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
