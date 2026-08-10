-- ============================================================
-- ChamaFunds – missing_tables.sql
-- Run this once on the live database to create any tables
-- that were added after the initial deployment.
-- Safe to run multiple times (uses CREATE TABLE IF NOT EXISTS)
-- ============================================================

-- Hero slider slides (used on the homepage)
CREATE TABLE IF NOT EXISTS `hero_slides` (
  `slide_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `image_url`  VARCHAR(500) NOT NULL,
  `alt_text`   VARCHAR(255) NOT NULL DEFAULT 'ChamaFunds campaign photo',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_hero_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Multiple images per campaign
CREATE TABLE IF NOT EXISTS `campaign_images` (
  `image_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT UNSIGNED NOT NULL,
  `image_url`   VARCHAR(500) NOT NULL,
  `is_cover`    TINYINT(1)   NOT NULL DEFAULT 0,
  `sort_order`  INT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ci_campaign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional supporting documents per campaign
CREATE TABLE IF NOT EXISTS `campaign_documents` (
  `doc_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `campaign_id` INT UNSIGNED NOT NULL,
  `document_url` VARCHAR(500) NOT NULL,
  `file_name`   VARCHAR(255) NOT NULL DEFAULT '',
  `uploaded_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cd_campaign` (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
