# Client Issues - Fixes Summary

## All Issues Addressed (Latest Round)

### 1. Duplicate Group Records (Issue 1) ✓
- **Fix:** Added duplicate check in `create_group_member_loans_act` – skips creating a loan if one already exists for same member + group_id + batch.
- **Location:** `application/controllers/Loan.php`

### 2. Irregular Payment Scheduling (Issue 2) ✓
- **Fix:** Added `_ensure_schedule_date_unique()` in Loan_model – prevents duplicate payment dates when generating schedules. Applied to add_reducing_balance_weekly and add_reducing_balance_biweekly.
- **Location:** `application/models/Loan_model.php`

### 3. Loan Summary Not Updating (Issue 3)
- **Status:** `get_loan_outstanding_balance()` computes from payement_schedules dynamically. If still incorrect, check for caching or payment recording bugs.

### 4. Incorrect Loan Start Date (Issue 6) ✓
- **Fix:** Added `shift_schedules_to_disbursed_date()` – when disbursing (batch or single), schedule dates are shifted to start from disbursed_date.
- **Location:** `application/models/Payement_schedules_model.php`, `application/controllers/Loan.php`

### 5. Amortization Errors After Reversal/Edit (Issue 8) ✓
- **Fix:** (1) Reversal: set paid_amount to 0 (not negative) when fully reversing; (2) Added `recalculate_loan_balances()` and call it after reversal; (3) Added `shift_schedules_to_disbursed_date()` for consistency.
- **Location:** `application/controllers/Loan.php`, `application/models/Payement_schedules_model.php`

### 6. Loan Restructuring Workflow (Issue 10) ✓
- **Fix:** Added "Restructure Loans" button in Group Batch Module; restructure page filters by batch when `?batch=` is passed.
- **Location:** `application/views/loan/group_batch_loans.php`, `application/controllers/Loan.php`, `application/models/Loan_model.php`

### 7. Unintended Group Members Added (Issue 11) ✓
- **Fix:** When creating a new group, `group_id` is empty – `get_all_by_id` with empty group_id could return wrong rows. Now only loads existing members when `group_id` is not empty.
- **Location:** `application/views/groups/groups_form.php`

---

## Previously Implemented Fixes

### 1. Arrears Filtering (Issue 9) ✓
- **Fix:** Corrected duplicate `id="officer_id"` on the Branch select in `application/views/reports/arrears_filter.php` – Branch now uses `id="branch_id"`.
- **Impact:** Ensures filters work correctly when JavaScript or form validation references elements by ID.

### 2. Sunday Payment Dates (Issue 7) ✓
- **Fix:** Added Sunday rollover to Monday in:
  - `add_reducing_balance_weekly` – weekly schedule generation
  - `add_reducing_balance_biweekly` – bi-weekly schedule generation
- **Location:** `application/models/Loan_model.php`
- **Logic:** If a payment due date falls on Sunday (day 7), it is moved to the following Monday.

### 3. Group Membership Visibility (Issue 5) ✓
- **Fix:** Added group membership display to individual customer profiles.
- **Changes:**
  - `Customer_groups_model->get_groups_by_customer($customer_id)` – fetches groups for an individual customer
  - `Individual_customers` controller – loads `Customer_groups_model` and passes `customer_groups` to views
  - `individual_customers_read.php` and `view.php` – show group name and code for customers in groups
- **Display:** "Group Membership" row with group name (code) and date joined, or "None" if no groups.

### 4. Premature Loan Closure (Issue 4) ✓
- **Fix:** Stricter closure logic to avoid closing loans before full repayment.
- **Changes:** Added `_should_close_loan()` in `Payement_schedules_model.php` that:
  - Verifies total schedules paid equals total schedule count
  - Verifies total paid amount ≥ total due amount (with 0.01 tolerance for rounding)
- **Impact:** Loans are closed only when both conditions are met.

### 5. Report Content – Customer + Group Names (Issue 12) ✓
- **Fix:** Arrears report now shows group names for individual customers who belong to groups.
- **Location:** `bulk_report/arrearsReport.js`
- **Logic:** For individual customers, an extra query fetches group membership from `customer_groups` and displays it in the "Customer Group" column.

### 6. Arrears Date Filter – Disbursement Date (Issue 6 partial) ✓
- **Fix:** Arrears report date filter now uses `COALESCE(disbursed_date, loan_date)` instead of only `loan_date`.
- **Location:** `bulk_report/arrearsReport.js`
- **Impact:** Date filtering reflects actual disbursement date when available.

---

## Issues Requiring Further Investigation / Data Fixes

### 1. Duplicate Group Records (Issue 1)
- **Status:** No auto-insert logic found in code. Duplicates likely from data or batch creation.
- **Recommendation:** Run a data audit to find duplicate groups for Zitsamba loans and merge or de-duplicate records. Check `loan` and `groups` tables for duplicate `group_id`/`batch` combinations.

### 2. Irregular Payment Scheduling (Issue 2)
- **Status:** Multiple schedule generation paths exist. Sunday rollover added to reducing balance weekly/biweekly.
- **Recommendation:** Audit existing loans with duplicate/skipped months and consider a migration script to regenerate schedules for affected loans.

### 3. Loan Summary Not Updating (Issue 3)
- **Status:** `get_loan_outstanding_balance()` in `common_queries_helper.php` computes balance from `payement_schedules` (SUM(amount) - SUM(paid_amount)).
- **Recommendation:** If balances still look wrong, check for caching, incorrect `paid_amount` updates, or views using a different source. Verify payment recording updates `payement_schedules.paid_amount` correctly.

### 4. Incorrect Loan Start Date (Issue 6 – full fix)
- **Status:** Schedules are created at loan creation using `loan_date`. `disbursed_date` is set later at disbursement.
- **Recommendation:** To base schedules on disbursement date, either:
  - Regenerate schedules when disbursing (delete existing and recreate with `disbursed_date`), or
  - Defer schedule creation until disbursement and create them then using `disbursed_date`.

### 5. Amortization Errors After Reversal/Edit (Issue 8)
- **Status:** Not yet traced. Reversal and edit flows need review.
- **Recommendation:** Trace `transaction_reversal` and loan edit flows to see how they update schedules and whether they leave inconsistent data.

### 6. Loan Restructuring Workflow (Issue 10)
- **Status:** Restructuring is in a separate module.
- **Recommendation:** Add restructuring actions/UI into the Group Batch Module (e.g. `group_batch_loans` view) so users can restructure without leaving the batch context.

### 7. Unintended Group Members Added (Issue 11)
- **Status:** No code found that auto-adds exactly three members. `add_members` and `update_members` only add members from the provided array.
- **Recommendation:** Check for:
  - Default/placeholder members in the UI
  - Race conditions or double submissions
  - Slow performance causing repeated submissions

---

## Files Modified

| File | Changes |
|------|---------|
| `application/views/reports/arrears_filter.php` | Fixed duplicate id on Branch select |
| `application/models/Loan_model.php` | Sunday rollover in add_reducing_balance_weekly, add_reducing_balance_biweekly |
| `application/models/Customer_groups_model.php` | Added get_groups_by_customer() |
| `application/controllers/Individual_customers.php` | Load Customer_groups_model, pass customer_groups to read/view |
| `application/views/individual_customers/individual_customers_read.php` | Group membership display |
| `application/views/individual_customers/view.php` | Group membership display |
| `application/models/Payement_schedules_model.php` | _should_close_loan(), stricter closure checks |
| `bulk_report/arrearsReport.js` | COALESCE for date filter, group names for individual customers |
