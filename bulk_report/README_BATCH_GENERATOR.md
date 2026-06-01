# Group Loan Batch Number Generator

This script generates batch numbers for group member loans that were created without batch tracking.

## What It Does

The script identifies individual customer loans that are part of a group and assigns them batch numbers for easier identification and management.

### Criteria for Batch Assignment

A loan will receive a batch number if it meets ALL of these conditions:

1. **Customer Type**: `individual` (not group loans)
2. **Customer is in a Group**: Customer must be a member of a group (via `customer_groups` table)
3. **Loan Product**: Must be product ID 3, 18, or 21
4. **Creation Date**: Created on or after **September 1, 2025**
5. **No Existing Batch**: Doesn't already have a batch number

### Batch Grouping Logic

Loans are grouped into the same batch if they share:
- Same **group_id**
- Same **creation date** (loan_added_date)
- Same **loan_product**

### Batch Number Format

`BATCH{YYYYMMDD}{GROUP_ID}`

**Examples:**
- `BATCH20251207123` - Loans for group 123 created on December 7, 2025
- `BATCH2025090145` - Loans for group 45 created on September 1, 2025

### Database Updates

For each qualifying loan, the script updates:
- `batch` = Generated batch number
- `from_group` = 'Yes'
- `group_id` = The group ID from customer_groups

## Prerequisites

1. Node.js installed on your system
2. Required npm packages (already installed in this project):
   - mysql2
   - moment

## How to Run

### Step 1: Navigate to the bulk_report directory

```bash
cd C:\wamp64\www\newsycamore\bulk_report
```

### Step 2: Run the script

```bash
node generateGroupLoanBatches.js
```

## Output

The script provides detailed console output showing:

1. Number of loans found
2. Number of unique batches identified
3. Details of each batch:
   - Batch number
   - Group ID and name
   - Creation date
   - Loan product
   - Number of loans in batch
   - List of updated loans
4. Summary of total loans and batches processed

### Example Output

```
====== GROUP LOAN BATCH GENERATION STARTED ======
Start Time: 2025-12-22 14:30:00

✓ Database connection established
✓ Transaction started

Fetching loans that meet criteria...
✓ Found 45 loans to process

Identified 8 unique batches

================================================================================

Batch #1:
  Batch Number: BATCH2025090115
  Group ID: 15
  Group Name: Women Empowerment Group
  Creation Date: 20250901
  Loan Product: 3
  Loans in Batch: 5
    ✓ Updated Loan: LN2025001 (ID: 1234)
    ✓ Updated Loan: LN2025002 (ID: 1235)
    ...

================================================================================

✓ Successfully updated 45 loans across 8 batches
✓ Transaction committed

====== SUMMARY ======
Total Loans Updated: 45
Total Batches Created: 8
End Time: 2025-12-22 14:30:15
====== BATCH GENERATION COMPLETED SUCCESSFULLY ======
```

## Safety Features

- Uses database transactions - if any error occurs, all changes are rolled back
- Only processes loans without existing batch numbers
- Provides detailed logging of all changes
- Safe to run multiple times (won't re-process already batched loans)

## Database Configuration

The script uses the following database configuration:
- Host: `localhost`
- User: `root`
- Password: `` (empty)
- Database: `finfin`

If your database configuration is different, edit the `dbConfig` object in the script.

## Verification

After running the script, you can verify the results with this SQL query:

```sql
-- View all generated batches
SELECT
    batch,
    group_id,
    COUNT(*) as loan_count,
    DATE(loan_added_date) as creation_date,
    loan_product
FROM loan
WHERE from_group = 'Yes'
  AND batch IS NOT NULL
GROUP BY batch, group_id, DATE(loan_added_date), loan_product
ORDER BY batch;
```

```sql
-- View loans in a specific batch
SELECT
    loan_id,
    loan_number,
    loan_customer,
    batch,
    group_id,
    from_group,
    loan_added_date
FROM loan
WHERE batch = 'BATCH2025090115';
```

## Troubleshooting

### Error: "Cannot find module 'mysql2'"

Run `npm install` in the bulk_report directory to install dependencies.

### Error: "Access denied for user"

Check your database credentials in the script's `dbConfig` section.

### No loans found

This is normal if:
- All qualifying loans already have batch numbers
- No loans meet the criteria (product 3/18/21, created after Sept 1, 2025, etc.)

## Notes

- The script uses a transaction, so either all updates succeed or none do
- It's safe to run this script multiple times
- Only loans without batch numbers will be processed
- The script can be modified to use different date ranges or loan products if needed
