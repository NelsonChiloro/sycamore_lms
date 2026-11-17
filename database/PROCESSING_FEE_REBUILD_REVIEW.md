# Processing-fee fix and existing restructure review

## Scope and deployment

Production/application databases were not changed. Tests used only the disposable
`charge_framework_test` database on port 3407. No production loan was rebuilt.
No new rebuild route, UI or schema migration was introduced.

Deploy these two application files together after staging validation:

- `application/controllers/Loan.php`
- `application/models/Late_charges_model.php`

For the subsequent contract-payment correction, also deploy
`application/libraries/Loan_charge_policy.php`. If the fee fix is already uploaded,
only the latter library and `Late_charges_model.php` need replacing.

Test changes: `tests/processing_fee_rebuild_test.php` (new) and
`tests/late_charge_integration_test.php` (legacy-deposit corroboration fixtures).

The existing charge framework, historical reconstruction audit and history-date
repair tables/migrations must already be installed. Confirm that the participating
tables use InnoDB. Older installations without the existing framework are not
made compatible by these two files.

## Defects and corrected behavior

### Processing fee

`Loan::cash_transaction()` previously compared Account_model's numeric inserted
row ID with `'success'`. The deposit succeeded, but the transfer back and GF fee
record were skipped. `Account_model::cash_transaction()` is unchanged, preserving
its contract for all other callers.

The existing controller method now locks the loan, locks both account rows, and
posts the fee deposit, transfer back and GF fee record in one transaction. It
checks the numeric deposit result and transaction status after each operation.
Query errors are handled with db_debug disabled within this method and restored
afterward. A failure rolls back all fee ledger rows, balance updates and the fee
record. Individual activation stops on fee failure; batch disbursement already
catches each loan's exception and records the failure without activating it.

References are deterministic: `PF.IN.<loan_id>`, `PF.OUT.<loan_id>` and
`GF.LOAN.<loan_id>`. The GF row is committed with the ledger movements and acts
as the completion marker under the loan lock. A retry returns without reposting.
Fee posting and subsequent schedule activation remain separate stages: if the
fee succeeds but activation fails, an approved-loan retry reuses the marker.
Individual approval rejects an already-disbursed loan before rerunning schedules.

Existing disbursements, historical fee records or unexplained pre-disbursement
ledger movements are blocked for review rather than automatically repaired.
This includes Levi's old MWK 60,000 posting. The fix does not convert it to cash
received, spend it on installments, delete it or overwrite the account balance.

### Existing restructure/rebuild

The existing path remains:

`Loan::create_act_edit()` -> `Late_charges_model::prepare_restructure()` ->
`replay_sequential_repayments()` -> `Loan_model::add_loan_edit()` ->
`Payement_schedules_model::repair_loan_payment_state()`.

The controller's existing outer transaction covers the repair, audit, schedule
generation and final reconciliation. Failed generation rolls the whole change
back. The existing approved-request completion marker prevents execution twice.

Previously replay summed type 2/3 allocations as its cash source, missing genuine
receipts whose allocations were absent or incomplete. It now groups by internal
reference and gives recorded type 1 customer receipts precedence. A qualifying
receipt has a positive amount, a positive installment number and a non-GF
reference. Processing-fee records have installment zero and/or a GF reference.

Receipt cash is not added to allocation cash. Type 2/3 rows with the same ref
are derived rows to rebuild. If a legacy reference lacks a receipt, its summed
allocations require exactly one matching positive `deposit` ledger credit to
the same loan account, with the same amount and a non-conflicting effective date.
Internal ledger rows marked `other` do not qualify. The ledger is never summed
as another source of repayment cash. Reversed references are excluded.

Duplicate internal receipt references, duplicate non-empty customer payment
references, negative adjustments, invalid/future dates, uncorroborated legacy
allocations and paid schedules with no verified cash source stop reconstruction
for review. Historical principal, product and tenure changes still require original
schedule versions. Approved date-only corrections are retroactive under the
confirmed business rule: replay uses the stored corrected schedule from the
beginning, including penalties. Both edit dates must be valid and the current
loan date must match the latest approved correction. The selected policy and
approval IDs/old/corrected dates are retained in the reconstruction audit.

Original type 1 receipt rows retain IDs, refs, external references, amounts and
timestamps. Singular account-ledger rows are not changed. Type 2/3 derived rows
are reallocated/reclassified; their before-images and selected receipt sources
are stored in the existing reconstruction audit. Derived dates follow the source
receipt; missing legacy dates can still be recovered by the existing audited
date-repair workflow. Valid original receipt dates are not rewritten.

Normal new repayments retain the existing penalty-first policy. Historical
contract repair does not retrospectively divert receipts into new penalties:
it preserves separately recorded penalty payments and applies the remaining
receipt cash to contractual installments. Rebuild preserves existing posted
penalties rather than inventing charges from disbursement. Daily accrual after
the saved baseline remains separate and respects the configured policy start.
Repeating against unchanged receipts, terms and business date produces the same
financial totals and no additional positive allocation cash.
Audit rows may grow on repeated applications. Later business dates can accrue
additional legitimate penalties.

Replay now inserts missing charge-state rows directly, then uses the existing
normalization method to refresh the next installment and actual principal
balances. It no longer needs an initial repair from potentially incomplete
allocation rows before receipt replay can run.

### Zero-paid installment status

`Late_charges_model::save_states()` already rounds allocated contract payments
to cents, then sets `NOT PAID` when zero, `PARTIAL PAID` when positive but not
settled, and `PAID` when the contract and penalties are settled. Replay invokes
this existing calculation, clearing stale `PARTIAL PAID`/`YES` flags on zero-paid
rows. No change to the status formula was necessary. A fully paid contract with
unsettled penalties remains incomplete under the existing policy.

## Loan 7622 verification and limits

The supplied five payment dates/amounts total MWK 1,196,466.15. Tests now use the
six supplied schedule dates and actual principal/interest/admin/cover components
with synthetic receipt references. The actual prepare_restructure path and the
post-generator repair both leave MWK 239,293.23 against MWK 1,435,759.38. The
first five installments each have exactly MWK 239,293.23 paid and PAID status;
installment six has zero paid, NOT PAID, and principal balance MWK 212,951.86.
Internal MWK 60,000 ledger cash is excluded, and original receipt rows/ledger
entries remain unchanged.

The earlier deployed replay incorrectly moved MWK 163,592.08 of contract cash
into retrospectively calculated penalties. The corrected path reads original
payment splits and posted penalty balances from the earliest existing rebuild
audit, then regenerates derived allocations without treating the prior bad
reallocation as genuine penalty payment. A test emulates that prior corruption
and verifies recovery. Existing genuine penalty receipts/balances are tested
separately and remain preserved. Do not delete reconstruction audits: recovery
depends on their before-images; without them the original split needs review.

Rounded components in installments 3 and 4 differ from their installment totals
by one cent. Loan_charge_policy now reconciles at most two cents in a financed
fee component while preserving principal. This prevents 239,293.22/239,293.24
allocations and false partial statuses. Larger discrepancies still stop repair.

The actual loan's penalty-adjusted outcome cannot be certified without its newer
receipt refs, exact timestamps, installment/component history and adjustments.
The supplied demo results confirm edit 2066 changed 11 May to 24 April 2026,
approved 15 June, with principal/product/tenure unchanged. The user confirmed
that the corrected schedule applies retroactively to the entire loan, including
penalties under the applicable policy, without newly diverting prior contract
cash or backdating a new policy. This date-only change passes the safeguard when the stored loan
date is 24 April; other material historical changes remain blocked. No approval
history is deleted or rewritten to obtain this result.

Reconstructing repayments does not correct the separate internal MWK 60,000
ledger balance. Any historical accounting correction requires its own reviewed,
auditable posting after confirming the original fee's missing return leg.

## Read-only newer-database checks

Run these SELECTs in phpMyAdmin before staging a preview with current data.

```sql
-- Confirm transaction support before relying on rollback/row locks.
SELECT TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'live_sycamore_copy'
  AND TABLE_NAME IN ('loan','account','transaction','transactions',
    'payement_schedules','loan_charge_state','loan_payment_components',
    'loan_penalty_reconstructions','loan_history_date_repairs','approval_edits');

-- Original receipts and derived allocations: inspect separately, never sum all.
SELECT transaction_id, transaction_type, ref, amount, payment_number,
       payment_reference, payment_type, date_stamp, description
FROM live_sycamore_copy.transactions
WHERE loan_id = '7622'
ORDER BY date_stamp, transaction_id;

-- Current schedules/components and stale flags.
SELECT * FROM live_sycamore_copy.payement_schedules
WHERE loan_id = 7622 ORDER BY payment_number, id;

-- Review material historical edits, including date changes.
SELECT approval_edits_id, state, type, old_info, new_info
FROM live_sycamore_copy.approval_edits
WHERE id = '7622' AND type = 'Loan edit'
ORDER BY approval_edits_id;

-- Internal fee and actual account movements remain separate evidence.
SELECT id, account_number, transaction_id, credit, debit, balance,
       transaction_type, reversed, system_time, server_time
FROM live_sycamore_copy.`transaction`
WHERE account_number = 'SCL202604298864'
   OR transaction_id = 'TR-S549220260511'
ORDER BY server_time, id;
```

## Validation

PHP syntax checks passed for both application files and both changed test files.
All six test files passed (the new test includes the existing integration suite):

- `processing_fee_rebuild_test.php`: numeric return handling; four balancing fee
  rows and unchanged net account balances; retry without duplicates; injected
  deposit, transfer and fee-record database failures roll back; historical partial
  fee blocks; five supplied payments counted once despite stale/missing derived
  allocations; receipt/ledger preservation; preview rollback; repeatable monetary
  totals/allocation counts; zero-paid status; ambiguous/duplicate-history blocks.
  Also covers an approved 11 May -> 24 April correction, a subsequent unchanged
  approved edit, correction audit metadata and rejection of a stored loan date
  that does not match the approved correction.
- `late_charge_integration_test.php`: real schedule generator, charges,
  reversals, repair/restructure, payoff, history audit, date recovery, pagination,
  admin/POST guards, preview and generator-failure rollback.
- `late_charge_policy_test.php`: accrual and sequential allocation.
- `straight_schedule_components_test.php`: principal/fees and exact cent totals.
- `loan_statement_late_charges_test.php`: exported penalty/balance presentation.
- `customer_edit_soft_delete_test.php`: existing customer regression checks.

Diff reviewed against pre-task source snapshots. No Account_model change and no
new rebuild system. Live browser disbursement, concurrent-request load testing
and an authoritative production-loan preview were not performed. Stage both
individual and batch disbursement with current schema before deployment.

For an already-completed bad demo restructure, keep the previous approval and
audit history and submit a fresh restructure request after uploading the corrected
files. The completion guard intentionally prevents executing the old request
again. Review its preview/audit totals before applying on production. The demo
was not changed remotely by this coding session, and the separate internal
MWK 60,000 balance is not automatically corrected by the rebuild.
