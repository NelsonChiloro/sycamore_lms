-- Install after 20261022_late_charge_framework.sql. No balances are changed here.
CREATE TABLE IF NOT EXISTS loan_penalty_reconstructions (
 id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 loan_id INT NOT NULL,
 cutoff_on DATE NOT NULL,
 input_hash CHAR(64) NOT NULL,
 audit_json LONGTEXT NOT NULL,
 applied_at DATETIME NOT NULL,
 KEY loan_history (loan_id, applied_at)
) ENGINE=InnoDB;
