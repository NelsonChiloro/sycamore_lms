# CRB filters

Upload together:

- `application/controllers/Reports.php`
- `application/views/reports/crb.php`
- `bulk_report/index.js`
- `bulk_report/databaseHelpers.js`
- `bulk_report/crbFilters.js` (new)

Restart the existing bulk_report Node service after uploading its files. No SQL
migration is needed. Uploading only the PHP page will not enable filtering in an
older running report service.

The CRB page provides status (all disbursed, active, completely paid up, written
off), product, branch and optional inclusive loan-date range filters. PHP validates
the selections and sends them in the existing report-service request. The Node
service validates them again and uses SQL bindings. The queued report title
includes the selected status. Filenames include the report ID to prevent two
requests generated in the same second overwriting each other's filtered output.

Completely paid up requires individual-customer, disbursed loans, a positive
schedule, no unpaid contract cents in any installment and no outstanding late
charges. It accepts settled ACTIVE/CLOSED/paid-off states, but excludes written-off
and deleted loans. An overpayment on one installment cannot mask another unpaid
installment. A CLOSED flag alone does not qualify a loan as paid up.

Date filters apply to `loan.loan_date`, not the repayment/closure date. The
existing CRB export columns and download flow are retained.

Validation: PHP and JavaScript syntax checks, Node validation tests and actual
MySQL filter-selection tests in the isolated charge_framework_test database passed.
The SQL test covers settled active/closed loans, unpaid one-cent balances,
one-cent late charges, written-off loans, group/customer ID isolation, empty
schedules, overpayment masking, and combined product/branch/date filters.
No production data was changed and the deployed browser/service was not accessed.

After deployment, open Reports/crb, select Completely paid up and generate. Check
the resulting report on the usual report-progress page. Test a product/branch/date
combination against known paid-up loans in the target database.
