# Bulk Report User Manual

## 1. Overview

The `bulk_report` project is a Node.js reporting service for the Sycamore/Finance Realm system. It connects to the MySQL database, generates loan and transaction reports as HTML files, and updates the `reports` database table so users can track progress and open completed reports from the main application.

The project also includes a browser page for bulk loan date correction. This page lets an operator paste loan numbers, run one batch update, and see which loans were updated, unchanged, or failed.

## 2. Main Features

- Generate loan portfolio reports.
- Generate loan portfolio write-off reports.
- Generate loan collection reports.
- Generate upcoming installment reports.
- Generate payment transaction reports.
- Generate tracked transaction reports.
- Generate RBM loan classification reports.
- Generate PAR reports, including detailed portfolio and principal balance variants.
- Generate arrears reports.
- Generate loan deposit reports.
- Generate CRB reports.
- Correct loan repayment schedules in bulk using stored loan dates.
- Generate group loan batch numbers for qualifying group-member loans.

## 3. Requirements

Before using the service, make sure the following are available:

- Node.js installed on the server.
- MySQL/MariaDB running and reachable.
- The Sycamore/Finance Realm database available.
- The required Node packages installed:
  - `express`
  - `mysql2`
  - `moment`

The project already contains `package.json` and `package-lock.json`, so dependencies can be installed from the project folder.

## 4. Database Connection

Database settings are read from environment variables where available. If no environment variables are set, the service uses these defaults:

| Setting | Environment Variable | Default |
| --- | --- | --- |
| Host | `DB_HOST` | `localhost` |
| User | `DB_USER` | `root` |
| Password | `DB_PASSWORD` | empty |
| Database | `DB_NAME` | `financerealm_sycamore_demo` |
| Connection limit | `DB_POOL_LIMIT` | `15` |
| Queue limit | `DB_POOL_QUEUE_LIMIT` | `0` |

If the database name, username, or password is different on the server, set the environment variables before starting the service.

## 5. Starting the Service

Open a terminal in the project folder:

```powershell
cd C:\wamp64\www\public_html\bulk_report
```

Install packages if needed:

```powershell
npm install
```

Start the service:

```powershell
node index.js
```

By default, the service runs on port `4500`.

To use a different port, set `REPORT_PORT` before starting:

```powershell
$env:REPORT_PORT="4600"
node index.js
```

When the service starts successfully, it prints a message similar to:

```text
Server is running on port 4500
```

## 6. Basic Health Check

Open this address in a browser or API client:

```text
http://localhost:4500/
```

A working service returns a short JSON response saying the service is running.

## 7. How Report Generation Works

Most reports follow the same workflow:

1. The main application sends a request to one of the report endpoints.
2. The service immediately returns a `202` response so the user does not have to wait on the browser request.
3. A new row is inserted into the `reports` table with status `in progress`.
4. The report generator reads data from MySQL.
5. The service writes an HTML report file into:

```text
bulk_report\reports
```

6. The `reports` table is updated with:
   - `status = completed`
   - `percentage = 100`
   - `download_link = reports/<generated-file-name>.html`
   - `completed_time`

If an error occurs, the report row is updated with `status = failed` and an error message.

## 8. Available Report Endpoints

All report endpoints use `POST` requests. The common fields are:

| Field | Purpose |
| --- | --- |
| `user` | Name or identifier of the user requesting the report. |
| `user_id` | User ID of the requester. |

### RBM Loan Classification

Endpoint:

```text
POST /generate-report-rbm-classification
```

Useful filters:

| Field | Description |
| --- | --- |
| `branch` | Branch filter. Defaults to `All`. |
| `officer` | Loan officer filter. Defaults to `All`. |
| `product` | Product filter. Defaults to `All`. |
| `base_url` | Base URL used by the generated report where needed. |

### Payment Transactions

Endpoint:

```text
POST /generate-report-transactions
```

Useful filters:

| Field | Description |
| --- | --- |
| `branch` | Branch filter. |
| `transaction_type_id` | Transaction type filter. |
| `loan` | Loan filter. |
| `product` | Product filter. |
| `officer` | Officer filter. |
| `from` | Start date. |
| `to` | End date. |

### Loan Portfolio

Endpoint:

```text
POST /generate-report-portfolio
```

Useful filters:

| Field | Description |
| --- | --- |
| `officer` | Officer filter. Defaults to `All`. |
| `branch` | Branch filter. Defaults to `All`. |
| `branchgp` | Group branch filter. Defaults to `branch` or `All`. |
| `productid` | Product ID filter. Defaults to `All`. |
| `status` | Loan status filter. Defaults to `All`. |
| `date_from` | Start date. |
| `date_to` | End date. |

### Loan Portfolio Write-Off

Endpoint:

```text
POST /generate-report-portfolio-write-off
```

Uses the same filters as the Loan Portfolio report.

### Loan Collections

Endpoint:

```text
POST /generate-report-collections
```

Useful filters:

| Field | Description |
| --- | --- |
| `officer` | Officer ID or filter value. |
| `officer_name` | Officer display name. |
| `branch` | Branch ID or filter value. |
| `branch_name` | Branch display name. |
| `period` | Collection period. |
| `date_from` | Start date. |
| `date_to` | End date. |

### Upcoming Installments

Endpoint:

```text
POST /generate-report-upcoming-installment
```

Useful filters:

| Field | Description |
| --- | --- |
| `branch` | Branch filter. |
| `officer` | Officer filter. |
| `product` | Product filter. |

### CRB Report

Endpoint:

```text
POST /generate-report-crb
```

Useful fields:

| Field | Description |
| --- | --- |
| `report_type` | Report type value stored for tracking. |

### Basic PAR Report

Endpoint:

```text
POST /generate-report-par
```

Useful filters:

| Field | Description |
| --- | --- |
| `officer` | Officer filter. |
| `product` | Product filter. |
| `report_type` | Report type value stored for tracking. |

### Enhanced PAR Detailed Portfolio

Endpoint:

```text
POST /generate-report-par-v2
```

Useful filters:

| Field | Description |
| --- | --- |
| `officer` | Officer filter. |
| `product` | Product filter. |
| `branch` | Branch filter. |
| `date_from` | Start date. |
| `date_to` | End date. |

### PAR Detailed Portfolio

Endpoint:

```text
POST /generate-report-par-detailed-portfolio
```

Useful filters:

| Field | Description |
| --- | --- |
| `officer` or `loan_officer` | Officer filter. |
| `product` or `loan_product` | Product filter. |
| `branch` | Branch filter. |
| `status` | Loan status filter. Defaults to `ACTIVE`. |
| `customer_type` | Customer type filter. |
| `date_from` or `from_date` | Start date. |
| `date_to` or `to_date` | End date. |

### PAR Principal Balance

Endpoint:

```text
POST /generate-report-par-principal-balance
```

Useful filters:

| Field | Description |
| --- | --- |
| `officer` or `loan_officer` | Officer filter. |
| `product` or `loan_product` | Product filter. |
| `branch` | Branch filter. |
| `date_from` or `from_date` | Start date. |
| `date_to` or `to_date` | End date. |

### Arrears Report

Endpoint:

```text
POST /generate-report-arrears
```

Useful filters:

| Field | Description |
| --- | --- |
| `start_date` | Start date. |
| `end_date` | End date. |
| `officer_id` | Officer ID. |
| `officer_name` | Officer display name. Defaults to `All Officers`. |
| `branch_id` | Branch ID. |
| `branch_name` | Branch display name. Defaults to `All Branches`. |

### Loan Deposits

Endpoint:

```text
POST /generate-report-loan-deposits
```

Useful filters:

| Field | Description |
| --- | --- |
| `from` | Start date. |
| `to` | End date. |

### Tracked Transactions

Endpoint:

```text
POST /generate-report-track-transactions
```

Useful filters:

| Field | Description |
| --- | --- |
| `from` | Start date. |
| `to` | End date. |
| `customer_name` | Customer name search. |
| `loan_number` | Loan number search. |
| `transaction_type` | Transaction type search. |

## 9. Example Report Request

Example request for a loan portfolio report:

```json
{
  "user": "Admin",
  "user_id": 1,
  "officer": "All",
  "branch": "All",
  "productid": "All",
  "status": "ACTIVE",
  "date_from": "2026-01-01",
  "date_to": "2026-04-30"
}
```

Send it to:

```text
POST http://localhost:4500/generate-report-portfolio
```

The response will confirm that generation has started. Check the main application report list or the `reports` table for progress and the final download link.

## 10. Bulk Loan Date Correction

The service includes a browser page for correcting loan repayment schedules in bulk.

Open:

```text
http://localhost:4500/loan-date-batch-editor
```

### What This Tool Does

For each loan number entered, the tool:

1. Finds the loan in the database.
2. Reads the loan date already stored on that loan.
3. Rebuilds the repayment schedule using the existing loan product rules.
4. Preserves posted payments by installment number.
5. Reports whether the loan was updated, unchanged, or failed.

### How to Use It

1. Enter the operator name. This is optional.
2. Enter a batch note or reason. This is optional.
3. Paste one loan number per line.
4. Click `Run batch update`.
5. Review the result panel:
   - `Processed` shows the total number of submitted loans.
   - `Successful` shows loans that were updated.
   - `Unchanged` shows loans where no schedule change was needed.
   - `Failed` shows loans that could not be updated.

### Example Loan List

```text
SCL202502062568
SCL202502062570
SCL202507103448
```

### Notes

- Users do not enter loan dates manually. The tool reads the existing stored loan date.
- For monthly cutoff products, the effective schedule date may be moved to the beginning or end of a month according to existing product rules.
- If a loan number is missing or invalid, that row appears in the failed updates table.

## 11. Group Loan Batch Number Generator

The project includes a separate command-line script:

```text
generateGroupLoanBatches.js
```

It assigns batch numbers to qualifying individual customer loans that belong to groups but were created without batch tracking.

Run it from the `bulk_report` folder:

```powershell
node generateGroupLoanBatches.js
```

A loan qualifies only when it:

- Belongs to an individual customer.
- Has a customer linked to a group.
- Uses product ID `3`, `18`, or `21`.
- Was created on or after September 1, 2025.
- Does not already have a batch number.

Batch numbers use this format:

```text
BATCHYYYYMMDDGROUP_ID
```

The script runs inside a database transaction, so if an error occurs the changes are rolled back.

## 12. Generated Files

Generated report files are written to:

```text
C:\wamp64\www\public_html\bulk_report\reports
```

The database stores a relative link like:

```text
reports/report_portfolio_20260429_103000.html
```

The main application is expected to read this value from the `reports` table and present it to the user as the report download/open link.

## 13. Troubleshooting

### Service Does Not Start

Check that:

- Node.js is installed.
- You are in the `bulk_report` folder.
- Dependencies are installed with `npm install`.
- Port `4500` is not already being used.

If the port is busy, start the service with another port using `REPORT_PORT`.

### Database Connection Fails

Check that:

- MySQL/MariaDB is running.
- The database name is correct.
- The database user and password are correct.
- Environment variables match the server database settings.

### Report Stays In Progress

Check that:

- The Node service is still running.
- The database connection has not dropped.
- The `bulk_report\reports` folder is writable.
- The report row in the database does not contain an error message.

### Report Fails To Write File

Check folder permissions for:

```text
C:\wamp64\www\public_html\bulk_report\reports
```

The service must be able to create the folder and write HTML files inside it.

### Report Has No Data

Check that:

- The selected filters are not too restrictive.
- The requested date range contains data.
- The source loan, payment schedule, transaction, branch, product, and user records exist.

### Bulk Loan Date Update Fails

Check that:

- Each row contains a valid loan number.
- The loan exists.
- The loan product has enough schedule rules for rebuilding repayment schedules.
- Existing payment schedule records are consistent.

## 14. Safe Operating Practices

- Run report generation from the main application whenever possible.
- Avoid stopping the Node service while a large report is being generated.
- Keep old generated report files only as long as needed.
- Back up the database before running bulk correction scripts.
- Test bulk loan date corrections with a small loan list before running a large batch.
- Keep database credentials out of source code by using environment variables on production servers.

## 15. Quick Reference

| Task | Address or Command |
| --- | --- |
| Start service | `node index.js` |
| Default service URL | `http://localhost:4500/` |
| Loan date batch page | `http://localhost:4500/loan-date-batch-editor` |
| Report output folder | `bulk_report\reports` |
| Generate group loan batches | `node generateGroupLoanBatches.js` |
| Change port | Set `REPORT_PORT` before starting |
| Change database | Set `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` |
