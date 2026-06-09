-- Portfolio Dashboard Database Schema
-- Run this script once to create the required tables.
-- Tables are prefixed with pd_ (portfolio dashboard).

CREATE TABLE IF NOT EXISTS `pd_snapshots` (
  `id`               INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_time`    DATETIME     NOT NULL,
  `as_of_date`       DATE         NOT NULL,
  `active_loans`     INT          NOT NULL DEFAULT 0,
  `total_principal`  DECIMAL(20,2) NOT NULL DEFAULT 0,
  `total_outstanding` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `total_arrears`    DECIMAL(20,2) NOT NULL DEFAULT 0,
  `loans_in_arrears` INT          NOT NULL DEFAULT 0,
  `par30`            DECIMAL(8,4) NOT NULL DEFAULT 0,
  `par90`            DECIMAL(8,4) NOT NULL DEFAULT 0,
  `collection_rate`  DECIMAL(8,4) NOT NULL DEFAULT 0,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_snapshot_time` (`snapshot_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pd_branches` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_id`     INT          NOT NULL,
  `branch_name`     VARCHAR(255) NOT NULL,
  `loans_count`     INT          NOT NULL DEFAULT 0,
  `outstanding`     DECIMAL(20,2) NOT NULL DEFAULT 0,
  `arrears`         DECIMAL(20,2) NOT NULL DEFAULT 0,
  `par30`           DECIMAL(8,4) NOT NULL DEFAULT 0,
  `par90`           DECIMAL(8,4) NOT NULL DEFAULT 0,
  `collection_rate` DECIMAL(8,4) NOT NULL DEFAULT 0,
  INDEX `idx_snapshot` (`snapshot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pd_products` (
  `id`            INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_id`   INT          NOT NULL,
  `product_name`  VARCHAR(200) NOT NULL,
  `loans_count`   INT          NOT NULL DEFAULT 0,
  `outstanding_km` DECIMAL(20,4) NOT NULL DEFAULT 0,
  `arrears_km`    DECIMAL(20,4) NOT NULL DEFAULT 0,
  `par_rate`      DECIMAL(8,4) NOT NULL DEFAULT 0,
  INDEX `idx_snapshot` (`snapshot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pd_officers` (
  `id`              INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_id`     INT          NOT NULL,
  `officer_name`    VARCHAR(200) NOT NULL,
  `loans_count`     INT          NOT NULL DEFAULT 0,
  `outstanding`     DECIMAL(20,2) NOT NULL DEFAULT 0,
  `arrears`         DECIMAL(20,2) NOT NULL DEFAULT 0,
  `par_rate`        DECIMAL(8,4) NOT NULL DEFAULT 0,
  `collection_rate` DECIMAL(8,4) NOT NULL DEFAULT 0,
  INDEX `idx_snapshot` (`snapshot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pd_trend` (
  `id`             INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_id`    INT          NOT NULL,
  `month_year`     VARCHAR(7)   NOT NULL,
  `loan_count`     INT          NOT NULL DEFAULT 0,
  `principal_km`   DECIMAL(20,4) NOT NULL DEFAULT 0,
  INDEX `idx_snapshot` (`snapshot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pd_aging` (
  `id`                 INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_id`        INT          NOT NULL,
  `bucket`             VARCHAR(20)  NOT NULL,
  `loan_count`         INT          NOT NULL DEFAULT 0,
  `arrears_km`         DECIMAL(20,4) NOT NULL DEFAULT 0,
  `outstanding_balance` DECIMAL(20,2) NOT NULL DEFAULT 0,
  INDEX `idx_snapshot` (`snapshot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pd_loans` (
  `id`                INT          AUTO_INCREMENT PRIMARY KEY,
  `snapshot_id`       INT          NOT NULL,
  `loan_id`           INT          NOT NULL,
  `customer_name`     VARCHAR(500),
  `loan_number`       VARCHAR(200),
  `product_name`      VARCHAR(200),
  `branch_name`       VARCHAR(255),
  `officer_name`      VARCHAR(200),
  `principal`         DECIMAL(20,2) NOT NULL DEFAULT 0,
  `outstanding`       DECIMAL(20,2) NOT NULL DEFAULT 0,
  `arrears`           DECIMAL(20,2) NOT NULL DEFAULT 0,
  `days_arrears`      INT          NOT NULL DEFAULT 0,
  `collection_rate`   DECIMAL(8,4) NOT NULL DEFAULT 0,
  `loan_status`       VARCHAR(50),
  `disbursement_date` DATE,
  `interest_rate`     DOUBLE       NOT NULL DEFAULT 0,
  `period`            VARCHAR(50),
  `installment_amount` DECIMAL(20,2) NOT NULL DEFAULT 0,
  `total_expected`    DECIMAL(20,2) NOT NULL DEFAULT 0,
  INDEX `idx_snapshot` (`snapshot_id`),
  INDEX `idx_loan_id`  (`loan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
