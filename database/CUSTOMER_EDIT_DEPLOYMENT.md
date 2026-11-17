# Customer editing and soft deletion

1. Back up the database and changed files.
2. Import `database/20261109_customer_soft_delete.sql` before uploading PHP. It adds
   `Deleted` to the existing approval-status enum and retains the existing states.
3. Upload these files to their matching application directories:

```
application/controllers/Individual_customers.php
application/controllers/Proofofidentity.php
application/models/Individual_customers_model.php
application/models/Proofofidentity_model.php
application/views/individual_customers/individual_customers_list.php
application/views/individual_customers/individual_customers_toedit.php
application/views/individual_customers/individual_customers_kyc_edit.php
application/views/individual_customers/view_kyc.php
application/views/individual_customers/my_customers.php
application/views/individual_customers/individual_customers_delete.php
application/views/individual_customers/_list_layout.php
application/views/individual_customers/_delete_button.php
```

4. Reload the customer, Edit Customer and Edit Customer KYC lists. Their tables
   scroll horizontally and keep the Action column on the right.
5. Save a KYC text change without selecting pictures. Existing pictures must remain.
6. Use Delete on a test customer. The database row must remain with status `Deleted`,
   disappear from customer lists and stop blocking a new customer's ID-number check.

Deletion uses an authenticated POST form with the application's CSRF token when
enabled. Existing archive links still archive rather than delete. Accounts, loans,
KYC and transaction history are retained. Generated ClientIds remain globally unique
even for deleted records so historical references cannot collide. Business ID-number
checks ignore deleted customers in both the live check and server-side creation.

Any loan record linked to the individual customer blocks deletion, regardless of
loan status (including closed, written-off and deleted loans). Group loans whose
group ID merely equals the individual customer's ID do not count. The controller
shows a clear rejection, and the model independently enforces the guard on UPDATE.
Upload the updated customer controller and model for this rule; no new SQL is needed.

Local validation: PHP syntax checks for all changed files and
`tests/customer_edit_soft_delete_test.php` using only the disposable
`charge_framework_test` database. Live browser layout has not been verified locally.
