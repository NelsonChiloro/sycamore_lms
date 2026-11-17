-- Preserve existing customer states and allow soft deletion without removing history.
ALTER TABLE individual_customers MODIFY COLUMN approval_status
 ENUM('Approved','Not Approved','Rejected','Blacklisted','Archived','Recommended','Deleted')
 NOT NULL DEFAULT 'Not Approved';
