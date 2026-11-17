-- Audit for the one-time bulk historical date repair. No balances are changed.
CREATE TABLE IF NOT EXISTS loan_history_date_repairs (
 id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 transaction_id INT NOT NULL,
 loan_id INT NOT NULL,
 ref VARCHAR(200) NOT NULL,
 old_date VARCHAR(30) NULL,
 new_date DATETIME NOT NULL,
 source VARCHAR(40) NOT NULL,
 repaired_at DATETIME NOT NULL,
 KEY transaction_history (transaction_id),
 KEY loan_history (loan_id)
) ENGINE=InnoDB;
