# Finance Realm System — User Manual

**Version:** 1.0  
**Application:** Finance Realm (Core Banking / Microfinance)  
**Technology:** CodeIgniter (PHP), role-based access, optional Node.js report service

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Login and Session](#2-login-and-session)
3. [Dashboard (Home)](#3-dashboard-home)
4. [User and Access Management](#4-user-and-access-management)
5. [Settings and Configuration](#5-settings-and-configuration)
6. [Organization Structure](#6-organization-structure)
7. [Customer Management](#7-customer-management)
8. [Loan Management](#8-loan-management)
9. [Savings and Accounts](#9-savings-and-accounts)
10. [Transactions and Tellering](#10-transactions-and-tellering)
11. [Reports](#11-reports)
12. [Backup](#12-backup)
13. [SMS and Notifications](#13-sms-and-notifications)
14. [Menu and Access Control](#14-menu-and-access-control)
15. [Activity Logger](#15-activity-logger)
16. [Customer API](#16-customer-api-mobile--external-apps)
17. [Security and Best Practices](#17-security-and-best-practices)
18. [Troubleshooting](#18-troubleshooting)
19. [Quick Reference — Main URLs](#19-quick-reference--main-urls-examples)

---

## 1. Introduction

### 1.1 About the System

**Finance Realm System** is a web-based core banking and microfinance application built on CodeIgniter (PHP). It supports:

- **Customer management** — Individual and corporate customers, and groups
- **Loan lifecycle** — Loan products, applications, disbursement, repayment schedules, and tracking
- **Savings & accounts** — Account types, internal accounts, teller operations, and vault management
- **Tellering** — Cash deposits, withdrawals, and loan payments at the counter
- **Reports** — Arrears, collections, revenue, PAR, CRB, period analysis, and financial analysis
- **Security** — Role-based access, user approval, and activity logging
- **Customer portal/API** — Mobile or external app access for balance and login (API)

The system uses a **role-based menu**: the sidebar shows only items you are allowed to access. The timezone is set to **Africa/Blantyre**.

---

### 1.2 How to Access the System

1. Open a supported web browser (Chrome, Firefox, Edge, etc.).
2. Go to the system URL (e.g. `http://your-server/sycamore-rodlick-infocus/` or as provided by your administrator).
3. You will see the **Login** page.

---

## 2. Login and Session

### 2.1 Logging In

1. On the login page, enter your **Username** (assigned by an administrator).
2. Enter your **Password**.
3. Click **Sign In**.

- On success you are taken to the **Admin Dashboard** (Home).
- If another session is already using your account, you may see a message asking you to log out from the other session or ask an admin to clear it.
- If credentials are wrong, an error message is shown; correct the username/password and try again.

**Note:** The “Forget Password?” link on the login page may not be active; contact your administrator to reset your password.

### 2.2 After Login

- The **header** shows the application name (“Welcome to Finance Realm System”) and the **current user** (name and role).
- The **sidebar** shows **Home**, menu groups (from the database), and **Logout**.
- Only menu items you have permission to use are visible.

### 2.3 Logging Out

- Click **Logout** in the sidebar, or  
- Click your **profile picture/avatar** in the top-right and choose **Logout**.

This ends your session and returns you to the login page.

### 2.4 Profile and Password

- Click your **avatar** (top-right) → **Edit Profile** to open your profile (e.g. `Employees/profile`).
- To change password: go to **User Access** → **Change Password** (or the profile screen that hosts it). Enter current and new password; the system stores passwords using secure hashing.

---

## 3. Dashboard (Home)

After login you land on the **Admin** dashboard.

- **Welcome** message with your first name.
- **Recent Activities** — Your last few actions (from the activity logger), with relative time (e.g. “5 minutes ago”).
- **Recent Reports** — List of recently generated reports with status (e.g. completed/warning) and **Download** link when the report is ready. A link to **View All Reports** takes you to the reports section.

Use the dashboard to see what you did lately and to open recent reports quickly.

---

## 4. User and Access Management

### 4.1 User Access (Staff Users)

**User Access** is used to manage staff who can log in to the system.

- **List users** — View all user access records (linked to employees).
- **Approve** — Open the approval screen for pending user access (e.g. new or inactive users).
- **Cancel session** — Administrators can cancel a user’s session (set `is_logged_in` to ‘No’) so the user can log in again if locked out.
- **Logout all** — Option to log out all users (use with care).

User access is tied to **Employee** and **Role**. Each user has an access code (username) and password.

### 4.2 Roles and Permissions

- **Roles** define job functions (e.g. Teller, Loan Officer, Admin).
- **Access** (permissions) are assigned per role and control which **menu items** (screens) a user can see.
- The menu is built from the `menu` and `menuitems` tables; each item can be linked to a controller/method. Only items whose IDs are in the user’s role access list are shown.

So: **User → Role → Access (menu items)**. Changing role or access changes what the user sees in the sidebar.

### 4.3 User Groups

**User Group** module allows grouping of users (e.g. for reporting or bulk permissions). You can list, add, edit, and read user groups as allowed by your role.

---

## 5. Settings and Configuration

### 5.1 System Settings

**Settings** (e.g. `Settings/update/1`) store organization-wide configuration:

- **Company name**
- **Logo** (image file)
- **Address, phone number, company email**
- **Currency**
- **Time zone**
- **Tax**
- **Defaulter durations** (used to mark loans as in arrears/default)

Only users with access to Settings can change these. Changes are logged in the activity logger.

### 5.2 Global Config

**Global Config** holds other system-wide options. Use it as directed by your administrator for feature flags or global parameters.

### 5.3 Financial Year and Working Days

- **Financial year** — Define or select the active financial year used in reporting and dates.
- **Working days** — Define which days of the week are working days (affects scheduling and possibly interest calculations).
- **Fyer holiday** (Financial year holidays) — Define holidays so they can be considered in due dates or reporting.

### 5.4 System Date

**System date** (Sytem_date) is the business date used by the application (e.g. for transactions and reports). An “active” system date is used after login. Only authorized users can change it.

---

## 6. Organization Structure

### 6.1 Branches

- **Branches** — Create, edit, list, and view branches.
- Used across the system for customers, loans, employees, and reports (filter by branch).

### 6.2 Employees

- **Employees** — Add and manage staff (name, branch, role, contact, etc.).
- Employees are linked to **User Access** for login.
- Your **profile** and **profile photo** come from the employee record.

### 6.3 Currency and Countries

- **Currency** — Manage currencies used in products and accounts.
- **Geo countries** — Maintain country list for customer addresses and reporting.

---

## 7. Customer Management

### 7.1 Individual Customers

**Individual Customers** are natural persons (retail clients).

- **List** — View all individual customers. You can **filter** by user (officer), country, branch, status, gender, and date range.
- **Add** — Register a new individual customer (name, contact, address, ID, gender, etc.).
- **View/Read** — Open full customer details.
- **Edit** — Update customer information.
- **Export** — Export filtered list to **PDF** or **Excel**.
- **Products** — From a customer record you can see linked products (e.g. accounts and loans).

Customer data is used for loans, savings accounts, and customer portal/API (e.g. phone number for API login).

### 7.2 Corporate Customers

**Corporate Customers** are organizations (companies, associations).

- **List, Add, Read, Edit** — Same idea as individual customers but for legal entities.
- Useful when you offer products to businesses or groups with a single legal identity.

### 7.3 Customer Groups

- **Customer Groups** — Define groups (e.g. savings groups, loan groups).
- **Group categories** — Classify groups.
- **Groups** — Create and manage groups; link members (individual/corporate) to the group.
- **Group assigned amount** — Allocate amounts or limits to groups if your processes use this.
- **Group loan tracker** — Track group-based loans (e.g. group loan products and repayment).

Groups are used for group lending and possibly group savings.

### 7.4 Customer Access (Portal / Mobile Approval)

**Customer Access** allows customers to use an external channel (e.g. mobile app) that uses the **Customer API**.

- **Track** — View requests in status “Initiated”.
- **Approve** — Approve or reject initiated customer access requests.
- **Approved** — List access records with status “Active”.
- **Rejected** — List rejected requests.

When approved, the customer can use the API (e.g. login with phone and password, check savings balance). Approve only after verifying the customer’s identity.

---

## 8. Loan Management

### 8.1 Loan Products

**Loan Products** define the types of loans you offer.

- **List** — View all loan products.
- **Add / Edit** — Set product name, interest rate, period, fees, and other terms.
- **Read** — View product details.

Loan applications and disbursements are linked to a loan product.

### 8.2 Loans (Applications and Disbursement)

**Loan** module covers the full loan lifecycle.

- **List** — View loans; filter by product, officer, branch, status, dates, etc.
- **Add** — Create a new loan application (customer, product, amount, period, interest, collateral if applicable).
- **Edit** — Change loan details. Some edits may go through **approval workflow** (recommend → approve).
- **Read** — View loan details, schedule, and status.
- **Disburse** — When approved, disburse the loan (creates ledger entries and activates repayment schedule).
- **Payment schedule** — View or generate installment schedule (payment dates and amounts).

Loan can be for **individual** or **group** customer. The system can mark loans as **defaulted** based on defaulter duration in Settings.

### 8.3 Payment Schedules

**Payement_schedules** (Payment Schedules) store installments per loan.

- Each installment has due date, principal, interest, fees, and status (e.g. paid/pending).
- Tellering (loan payment) applies payments to these installments and updates balances.

### 8.4 Borrowed Repayments

**Borrowed_repayements** — Record and track repayments (and possibly additional payments) against loans. Used for history and for financial/interest reports.

### 8.5 Collateral and Charges

- **Collateral** — Register collateral linked to loans (e.g. asset type, value, description).
- **Charges** — Define fees or charges that can be applied to loans or transactions (e.g. admin fee, late fee). Used in schedules and financial reports.

### 8.6 Loan Approval Workflow

For **loan edits** (and possibly disbursement), the system can use a two-step approval:

- **Recommend** — One user recommends (approve/reject) the change.
- **Approve** — Another user gives final approval or rejection.

**Approval_general** — Screen to view pending approval items (e.g. `Approval_general/auth_data/<id>/recommend/approve`). You see old vs new data and recommend or approve/reject with optional comments.

---

## 9. Savings and Accounts

### 9.1 Account Types

**Account_types** — Define types of accounts (e.g. Savings, Current, Loan). Used when creating accounts.

### 9.2 Internal Accounts

**Internal_accounts** — Ledger accounts for the organization (e.g. vault, suspense, income accounts). Used in transfers and reconciliation.

### 9.3 Accounts (Customer and Teller)

**Account** module manages:

- **Customer savings/current accounts** — Linked to individual or corporate customer; have balance and account number.
- **Teller (drawer) accounts** — Linked to an employee; used for teller cash operations.

- **List / Read** — View accounts and balances.
- **Create** — Open new account for a customer (account type, product if applicable).
- **Edit / Block / Delete** — Modify or block accounts as per your policy.

### 9.4 Savings Products

**Savings_products** — Define savings product types (e.g. interest rate, minimum balance). When opening a savings account, you typically select a savings product and account type.

### 9.5 Cashier (Tellering) Operations

**Account/cashier** — Main teller screen for **savings** operations.

- **Teller account** — Your drawer account and current balance are shown. If you do not have a teller account, you cannot perform teller transactions.
- **Search account** — Enter customer account number to load **Account details** (name, balance, opening date, status).
- **Deposit / Withdraw** — Choose transaction mode, enter amount and date, then submit. The system posts to the customer account and your teller account.
- **View transactions** — See recent teller transactions and print **deposit receipt** or **loan payment receipt** by transaction.

**Loan payments** can be made from the same or a dedicated teller screen: select loan/account, amount, and date; the system allocates to the payment schedule and updates loan balance.

### 9.6 Vault and Cashier Transfers

- **Vault → Teller:** A vault user initiates a transfer to a teller’s drawer. The request appears in **Vault_cashier_pends** (vault-to-cashier pending).
- **Teller → Vault:** A teller sends cash back to vault. The request appears in **Cashier_vault_pends** (cashier-to-vault pending).

**Approval:**

- **Vault_cashier_pends/acceptance** — List pending vault-to-teller requests; approver can **approve** so the system moves cash from vault account to teller account.
- **Account/accept_credit_teller** — Approve a specific vault-to-teller request.
- **Account/accept_credit_vault** — Approve a specific teller-to-vault request.

If the vault (or teller) has insufficient balance, the system shows an error and does not post.

### 9.7 Reconciliation and Journal

- **Account/reconciliation** (or **account_reconciliation**) — Reconcile teller or internal accounts (e.g. by selecting account and date range). You can export to Excel.
- **Account/journal** — View journal (ledger) entries for auditing.

### 9.8 Receipts

- **Print deposit receipt** — From transaction list, print receipt for a deposit (customer name, amount, date, balance).
- **Print loan payment receipt** — Print receipt for a loan payment (amount, next due date, total paid, etc.).
- **Email receipt** — Option to generate PDF receipt for email (implementation may vary).

---

## 10. Transactions and Tellering

### 10.1 Transaction Types

**Transaction_type** — Define transaction types (e.g. Deposit, Withdrawal, Loan Payment, Transfer). Used for categorizing and reporting.

### 10.2 Tellering (General)

**Tellering** — General teller transaction list and tracking.

- **Track transaction** — Search by loan number; view transactions; export to PDF if needed.
- **Track transactions view** — Alternate view for transaction search.

### 10.3 Transactions List

**Transactions** — List and view posted transactions (debit/credit, account, date, reference). Useful for audit and dispute checks.

---

## 11. Reports

Reports are available from the **Report** menu and from dedicated report controllers. Access depends on your role.

### 11.1 Reports Dashboard

**Report** (or **report/index**) — Lists **generated reports** (type, status, date, user). When status is “completed”, a **Download** link is available. Some reports are generated asynchronously (e.g. by a separate service on port 4300); you submit parameters and later download from this list.

### 11.2 Arrears Report

**Arrears_report** — Loans in arrears (overdue installments).

- **Filter** by date range, officer, branch.
- **Generate** — Sends a request to the report service (e.g. `http://localhost:4300/generate-report-arrears`). When ready, download from the Report list.

### 11.3 Collections Report

**Collections_report** — Collection performance (e.g. amounts collected in a period). Use filters as provided on the screen and generate; download when completed.

### 11.4 Revenue Report

**Revenue_report** — Revenue breakdown (interest, fees, etc.). Filter and generate; download from Report list if async.

### 11.5 Upcoming Installment Report

**Upcoming_installment_report** — Installments due in a chosen period. Helps with collection planning.

### 11.6 PAR (Portfolio at Risk)

**Reports/par_filter** — PAR (principal balance at risk) report. You submit product, officer, branch, and date range. Request is sent to the report service (e.g. `generate-report-par-principal-balance`). Download when ready from **Report**.

**Reports/par_filter_portfolio** — Portfolio loan book report; same flow, different endpoint (e.g. `generate-report-par-v2`).

### 11.7 CRB (Credit Reference Bureau)

**Reports/crb** — CRB-style report (e.g. for regulatory or bureau submission). Paginated. **Export CRB** sends a request to the service (`generate-report-crb`); file appears in Report list when done.

### 11.8 Period Analysis

**Reports/period_analysis** — Summary for a date range: total loan principal, number of loans, customers (total/male/female), employees, groups. You can filter and export to **PDF**.

### 11.9 Financial Analysis

**Reports/financial_analysis** — Income and expense breakdown: interest income, loan cover, admin fee, late fees, bad debits, interest paid, expenses. Filter by date; view on screen or export to **PDF**.

### 11.10 Other Reports

- **Summary** — High-level summary view.
- **PAR report (parfilter)** — PAR with officer/product pre-selected from session.
- **Portfolio analysis (portfolio_filter)** — Portfolio view with filters.
- **CAPAR (caparfilter)** — Another PAR-related view.

**Note:** Some reports depend on a **Node.js report service** running at `http://localhost:4300`. If reports stay “pending” or never complete, ensure that service is running and that the URLs in the code match your environment.

---

## 12. Backup

**Backup** module (admin only):

- **Database backup** — Creates a zip of the database dump and triggers download (filename like `backup-on-YYYY-mm-dd-HH-ii-ss.zip`).
- **Files backup** — Zips the `uploads` directory and downloads it (e.g. for logos and attachments).

Run backups regularly and store copies in a safe location.

---

## 13. SMS and Notifications

### 13.1 SMS Settings

**Sms_settings** — Configure SMS gateway (e.g. API URL, credentials) so the system can send SMS. Used for notifications or reminders if enabled elsewhere.

### 13.2 Send SMS

**Send_sms** — Send ad-hoc SMS to a phone number. Use according to your organization’s policy and data privacy rules.

---

## 14. Menu and Access Control

- The **sidebar menu** is built from the `menu` and `menuitems` tables (menu_type_id = 1 for admin).
- Each menu group has **menu items** (links). Your **role’s access** list determines which items you see.
- **Access** (or **Access/give_menu**) — Administrators assign which menu items (controllers/methods) a role can use. After assignment, users with that role see the corresponding menu entries.

**Menuitems** and **Menu** — Advanced admins can add or reorder menu groups and items in the database; the helper `display_menu_admin()` reads from there.

---

## 15. Activity Logger

User actions (e.g. login, settings update, loan edit) are logged in **activity_logger** (user_id, activity description, server_time). The dashboard “Recent Activities” shows your last actions. Full logs can be viewed from **Activity_logger** if your role has access (list/read).

---

## 16. Customer API (Mobile / External Apps)

The system exposes an API for customer-facing apps (e.g. mobile):

- **Login:** `POST apiv1/customer/login` — Body: phone_number, password. Returns success/error.
- **Savings balance:** `GET apiv1/customer/savings/balance` — Requires auth (e.g. API key and USER-ID header). Returns balance for the customer linked to that phone number.

**Customer Access** must be **Approved** (Active) for the customer to use the API. API authentication (e.g. API key and user validation) is enforced in the controller; keep API keys and credentials secure.

---

## 17. Security and Best Practices

- **Passwords** — Stored hashed (e.g. SHA1 in code; consider stronger hashing in future). Do not share passwords.
- **Session** — Only one active session per user may be enforced; log out when leaving a shared computer.
- **Roles** — Assign minimum required permissions; avoid giving everyone “admin” access.
- **Approvals** — Use recommend/approve for sensitive changes (e.g. loan edits, disbursement).
- **Backup** — Schedule regular DB and file backups; test restore once in a while.
- **System date** — Restrict who can change the business date; avoid changing it without a clear process.

---

## 18. Troubleshooting

| Issue | What to check |
|-------|----------------|
| Cannot log in | Correct username/password; account not locked; ask admin to clear session if “another session” message appears. |
| Missing menu items | Your role may not have access to those items; ask admin to assign access. |
| Teller cannot do cash ops | Ensure the employee has a **teller account** (Account/Tellering setup). |
| Vault/teller transfer fails | Check vault and teller balances; ensure approval is done by authorized user. |
| Reports never complete | Ensure the report service (e.g. Node.js on port 4300) is running and URLs in code match. |
| “Not authorized” on API | Check API key/headers and that Customer Access for that customer is Approved (Active). |

---

## 19. Quick Reference — Main URLs (Examples)

- Login: `Auth` or `Auth/index`
- Dashboard: `Admin` or `Admin/index`
- User Access: `User_access/index`, `User_access/approve`
- Individual Customers: `Individual_customers/index`
- Corporate Customers: `Corporate_customers/index`
- Groups: `Groups/index`
- Loan Products: `Loan_products/index`
- Loans: `Loan/index` (and add, read, edit, disburse as per menu)
- Accounts: `Account/index`, `Account/cashier`
- Vault–Teller acceptance: `Vault_cashier_pends/acceptance`
- Reports list: `Report/index`
- Settings: `Settings/update/1`
- Backup: `Backup/index`
- Logout: `Auth/logout`

*(Actual URLs depend on your base_url and whether index.php is in the URL; use the menu links when in doubt.)*

---

*End of User Manual. For technical or installation details, refer to the developer or system administrator.*
