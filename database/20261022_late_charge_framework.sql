-- Run once on the target database before uploading the PHP changes.
-- No historical penalties are recalculated. The policy starts on installation.
CREATE TABLE IF NOT EXISTS loan_charge_policy (
 id INT NOT NULL PRIMARY KEY,
 starts_on DATE NOT NULL
) ENGINE=InnoDB;
INSERT IGNORE INTO loan_charge_policy (id, starts_on) VALUES (1, DATE(UTC_TIMESTAMP() + INTERVAL 2 HOUR));

CREATE TABLE IF NOT EXISTS loan_charge_state (
 loan_id INT NOT NULL,
 payment_number INT NOT NULL,
 paid_cover DECIMAL(18,2) NOT NULL DEFAULT 0,
 paid_admin DECIMAL(18,2) NOT NULL DEFAULT 0,
 paid_interest DECIMAL(18,2) NOT NULL DEFAULT 0,
 paid_principal DECIMAL(18,2) NOT NULL DEFAULT 0,
 penalty_base DECIMAL(18,2) NOT NULL DEFAULT 0,
 penalty_accrued DECIMAL(24,8) NOT NULL DEFAULT 0,
 penalty_paid DECIMAL(18,2) NOT NULL DEFAULT 0,
 accrued_through DATE NOT NULL,
 PRIMARY KEY (loan_id, payment_number)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS loan_payment_components (
 id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 loan_id INT NOT NULL,
 payment_number INT NOT NULL,
 ref VARCHAR(200) NOT NULL,
 component VARCHAR(20) NOT NULL,
 amount DECIMAL(18,2) NOT NULL,
 paid_at DATETIME NOT NULL,
 KEY loan_ref (loan_id, ref),
 KEY loan_component (loan_id, payment_number, component)
) ENGINE=InnoDB;
