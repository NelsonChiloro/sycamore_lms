# Late charge framework deployment

## Straight-line principal and financed-fee correction

Upload `application/models/Loan_model.php` and use the existing restructure
approval flow for affected loans. The straight-line creation, edit and rerun
generators now split actual disbursed principal from financed processing fees.
Installment component totals and the sum of all installments match the existing
contract-total calculation, with rounding distributed in cents. No SQL migration
is required. This correction preserves the existing contractual charge calculation;
it does not change the product's processing-fee policy.

For the MWK800,000 / eight-term example, the existing contract total is
MWK1,004,807.69: principal totals MWK800,000 and fees total MWK204,807.69.
The old schedule incorrectly classified MWK1,000,000 as principal and omitted
MWK4,807.69 from installment amounts. After restructure, those fees appear in
the admin/processing-fee component, which is paid before interest and principal.
Principal balance rows still repeat when no later principal payment exists.
`tests/straight_schedule_components_test.php` checks exact component rounding;
the integration suite exercises the actual edit generator and principal balances.

1. Back up the live database and the changed PHP files.
2. Pause repayments, reversals, restructuring and the existing late-charge job during deployment.
3. Import `database/20261022_late_charge_framework.sql` into the live database.
   It creates three InnoDB tables. Re-running it does not reset the policy start date.
4. Upload:
   - `application/libraries/Loan_charge_policy.php` (new)
   - `application/models/Late_charges_model.php`
   - `application/models/Payement_schedules_model.php`
   - `application/models/Group_batch_model.php`
   - `application/controllers/Loan.php`
   - `application/controllers/Late_charges.php`
5. Run the late-charge calculation once and configure it to run daily in
   `Africa/Blantyre` time (set `CRON_TZ=Africa/Blantyre` where supported), for example:
   `5 0 * * * cd /path/to/application && /usr/bin/php index.php Late_charges calculate_late_charges`
   Use the application's real root and PHP executable. Check the JSON result for `success`.
   The existing token-protected `Late_charges/cron_calculate` endpoint is also supported;
   it requires a nonempty configured `cron_secret_token`. Disable duplicate old jobs.
6. Check an overdue loan and a repayment, then resume normal use.

## Rules

- Start on the installation date in Africa/Blantyre (UTC+2). Existing unpaid penalties
  become the opening penalty balance; historical penalties are not recalculated.
- Rate: 10% / 30 per calendar day, using full precision until the balance is rounded
  to cents. MWK100,000 therefore accrues MWK333.33 on its first chargeable day and
  MWK10,000 over 30 chargeable days if its outstanding amount does not change.
- The due date and the following two days are free. First charge: due date + 3.
  Each installment has its own grace period and a maximum of 360 chargeable days.
- Each unpaid eligible installment accrues on `amount - paid_amount`; summing them
  charges the combined outstanding arrears without charging twice or compounding
  penalties. A new installment joins the penalty base after its own grace period.
- Payments settle installments by payment number. Within each installment pay
  penalties, loan cover, admin fees, interest, then principal. Only the excess
  after clearing that whole installment can reach the next installment.
- Historical payments retain their previous proportional component allocation.
  New allocations are recorded in `loan_payment_components`. Penalty payments also
  appear as transaction type 2; contract payments remain type 3.
- Principal balances use actual allocated principal. Penalties never change principal,
  scheduled interest, or the contract total. A loan cannot close with unpaid penalties.
- Payment dates before the most recent accrual calculation are rejected; they must
  be reviewed through reversal/reconciliation rather than silently rewriting history.
  Future payment dates and payments exceeding all obligations are also rejected.
- Restructure replays valid contract payments against rebuilt schedules and retains
  the separate penalty balance. Reversal restores the reversed penalty allocation.
  Reversals do not retrospectively recalculate already-posted historical daily accrual.

## Verification

`php tests/late_charge_policy_test.php` tests policy arithmetic without a database.
`tests/late_charge_integration_test.php` uses only a disposable database named
`charge_framework_test`, default port 3407 (override with `CHARGE_TEST_PORT`). It
creates test-only schema and rolls back all loan fixtures. Do not upload tests as
public endpoints. The existing imported local application database is not changed
by these tests.

## Historical reconstruction (one-time backfill)

### Repair and restructure one loan

Upload the updated `application/controllers/Loan.php`,
and `application/models/Late_charges_model.php`.
The existing framework, historical-penalty and bulk-date-repair migrations must
already be installed; no additional migration is required for this option.

Use the existing `/Loan/restructure` form, recommendation and approval process.
Repair runs automatically in the backend at approval, including for requests
created before this update. No new controls or submission fields are required.
Missing dates are recovered only from matching recorded account credits;
reference dates are not automatically inferred by this workflow.
The earlier optional repair controls were removed and both forms restored.
If those form changes were already uploaded, upload the restored
`application/views/loan/edit_loan.php` and `application/views/loan/edit_loan_group.php`.

Approval rechecks the original schedules, repairs permitted missing dates, and
replays all valid receipts by effective date. Penalties accrue before each dated
receipt, and that receipt settles each installment's penalty and contract before
the next installment. Remaining penalties accrue through today before generating
the replacement schedule. The final schedule
replays valid payments and retains the separate penalty debt. All these writes,
date-repair audits, historical snapshots and completion marker share the outer
restructure transaction. A failed generator or repair rolls them back. A failed
repair request returns to recommendation status for review and reapproval.
Completed repair requests cannot be executed again.

Existing receipts are reallocated during restructure, as authorised. Their
combined cash value stays unchanged; contract payments may decrease because part
of the same cash now settles penalties. Repayment allocation rows are reclassified
as type 2 (penalties) or type 3 (contract/credit), and component records are rebuilt.
Original repayment IDs are retained, unused allocation rows become zero, and new
rows are inserted when a receipt needs more allocation rows. The actual account
credit/receipt is unchanged. The audit retains original transaction rows, original
component allocations and schedule values. Suspected duplicate cash receipts are
not deleted or guessed away. Repeated replay conserves cash without double charging.
Unresolved dates, payments without repayment history and previous material edits
without original schedule versions remain blocked.

For the installment-order correction upload `application/libraries/Loan_charge_policy.php`,
`application/models/Late_charges_model.php`, `application/models/Payement_schedules_model.php`,
and `application/views/loan/_loan_statement_page.php`. No additional SQL is needed.
Use the existing restructure approval flow to reallocate existing loans; ordinary
page viewing does not rewrite their receipt allocations. The shared PDF template
now shows installment late charges, total unpaid penalties and total outstanding.

The original migration still starts ordinary accrual on deployment. To reconstruct
the unpaid charges from earlier charging windows:

1. Back up the database. Import `database/20261022_historical_penalties.sql`.
2. Upload the updated `Loan_charge_policy.php`, `Late_charges_model.php`, and
   `Late_charges.php` to their existing application folders.
3. While logged in as SUPER ADMIN, open
   `/Late_charges/reconstruct_history/SCL20231019405` (or any loan number).
   This previews the historical charges and recorded penalty receipts per installment.
   Press **Apply historical reconstruction**, then return to the loan schedule.
4. For batches, use the server CLI:
   `php index.php Late_charges reconstruct_all_history preview 0 100`
   and `php index.php Late_charges reconstruct_all_history apply 0 100`.
   Continue with `next_after_id` in place of `0` until `processed` is zero.
   Each loan is atomic; errors are listed under `needs_review` and are not applied.

Historical reconstruction uses each original due date + two grace days, with at
most 360 charging days. Each dated type-3 repayment reduces its recorded
installment's outstanding amount after that day's charge. Reversed references are
treated as voided from the history, matching the existing repair convention;
reversal timing is not inferred from server timestamps. Type-2 penalty receipts
are deducted separately. Contract amounts and paid amounts are not changed.

Only the period ending the day before deployment is reconstructed. The calculated
unpaid historical charge **replaces** `penalty_base`; post-deployment accrual and
post-deployment penalty payments remain separate. Re-applying does not add the
same historical charge again. Every application stores a before-snapshot and
the calculated report in `loan_penalty_reconstructions`.

Expired charging windows still retain their unpaid historical debt; they do not
restart a new window. A loan with changed historical terms, obsolete installment
references, invalid payment dates, excess penalty receipts, or unreconciled
repayment totals is blocked for review. Original schedule versions or missing
source transactions must be recovered first. The code does not guess historical
payment dates, waivers, or prior loan terms from today's balance.

### Missing historical payment dates

Some legacy repayment rows contain `0000-00-00 00:00:00`. Reconstruction now lists
the transaction ID, reference, original date, and the server's business date in
its error. A missing/invalid repayment date may be recovered from a matching
loan-account credit's `system_time`, only when the reference and loan account
match exactly and all matching credits agree on one valid effective date.
Recovered dates are shown in the preview and stored in the audit; source
repayment rows are not edited. A future date is still blocked.

Dates embedded in `GF...`/`TR...` references and posting `server_time` are not
used as substitutes for the customer's effective payment date. If the matching
account credit is absent or ambiguous, restore the date from verified source
records before retrying. Repayment-total and installment-reference validation
still runs after dates are resolved.

### Bulk repair for all active loans

Browser option: upload `application/controllers/Late_charges.php` and
`application/views/late_charges/bulk_history.php`, along with the model and SQL
migrations listed below. While logged in as SUPER ADMIN, open:

`https://sycamore-test.chumasoft.com/Late_charges/bulk_history`

Click **Preview all active loans**. Review or download the report, then click
**Apply repairs and historical penalties**. Keep the page open until complete.
The browser automatically processes five loans per request, continues past loans
that need review, and refreshes the CSRF token between requests. Opening the URL
does not save changes. Applying requires an authenticated POST request.
The default date option matches the CLI `reference-date` mode below.

Import `database/20261022_bulk_history_dates.sql`, then upload the updated
`application/models/Late_charges_model.php`, `application/controllers/Late_charges.php`
and new `scripts/repair-historical-penalties.php`.

Run from the project root on the live server:

```sh
php scripts/repair-historical-penalties.php preview reference-date > historical-preview.jsonl
php scripts/repair-historical-penalties.php apply reference-date > historical-results.jsonl
```

Review the preview before the apply command. Both commands automatically process
all ACTIVE loans in batches of 100. Optional third/fourth arguments resume after
a loan ID and change the batch size, e.g. `apply reference-date 5000 100`.
Use `account-only` instead of `reference-date` to forbid inferred dates.

The reference-date mode explicitly permits a **posting-date assumption** for
otherwise unmatched zero/missing dates: legacy `GF.YYYYMMDD.number`,
`ST.YYYYMMDD.number`, and `TR-SnumberYYYYMMDD` references carry the system-generated
posting date. That date is not proof of the customer's effective payment date.
The script labels these as `reference_posting_date_inferred`; approve this
assumption only after reviewing the preview. Verified account effective dates
take priority. Conflicting account dates and future dates are never overwritten.
Invalid encoded dates, or dates before the loan date, remain unresolved.

Preview simulates date repairs and reconstruction, then rolls back all changes.
Apply saves old/new date values and sources in `loan_history_date_repairs`, then
attempts historical reconstruction for each loan. A repayment-total discrepancy
or changed loan terms does not stop other loans: the loan is marked `needs_review`
and no historical charges are applied to it. Successfully recovered dates remain
saved even when reconstruction needs review. The script does **not** delete
suspected duplicate payments or alter contract paid amounts to force a match.
Each JSON line contains a loan result; the last line contains the totals and last
processed loan ID. Re-running does not repeatedly replace already-valid dates or
add the same historical penalties twice. Back up the DB and pause repayments and
the daily charge job while applying the batch.
