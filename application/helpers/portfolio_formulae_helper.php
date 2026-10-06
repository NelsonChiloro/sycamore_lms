<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Portfolio formulae aligned with bulk_report/parReports.js (generatePARReportV2Enhanced)
 * and bulk_report/databaseHelpers.js charge-first allocation.
 */

if (!function_exists('sql_unpaid_charges_expr')) {
    /**
     * Unpaid charges (interest + loan cover + admin fee) after charge-first allocation.
     */
    function sql_unpaid_charges_expr($alias = 'ps')
    {
        return "GREATEST(
            (COALESCE({$alias}.amount, 0) - COALESCE({$alias}.principal, 0)) - COALESCE({$alias}.paid_amount, 0),
            0
        )";
    }
}

if (!function_exists('sql_unpaid_principal_expr')) {
    /**
     * Unpaid principal after charge-first allocation.
     */
    function sql_unpaid_principal_expr($alias = 'ps')
    {
        return "GREATEST(
            COALESCE({$alias}.principal, 0) - GREATEST(
                COALESCE({$alias}.paid_amount, 0) - (COALESCE({$alias}.amount, 0) - COALESCE({$alias}.principal, 0)),
                0
            ),
            0
        )";
    }
}

if (!function_exists('sql_portfolio_accrued_charges_case')) {
    /**
     * Accrued charges earned up to end of the as-of month (portfolio analysis rule).
     */
    function sql_portfolio_accrued_charges_case($alias = 'ps', $as_of_sql = 'CURDATE()')
    {
        $charges = sql_unpaid_charges_expr($alias);
        return "CASE WHEN {$alias}.status IN ('NOT PAID', 'PARTIAL PAID')
            AND {$alias}.payment_schedule <= LAST_DAY(DATE({$as_of_sql}))
            THEN {$charges} ELSE 0 END";
    }
}

if (!function_exists('sql_portfolio_arrears_case')) {
    /**
     * Overdue installment balance (portfolio analysis rule).
     */
    function sql_portfolio_arrears_case($alias = 'ps', $as_of_sql = 'CURDATE()')
    {
        return "CASE WHEN {$alias}.status IN ('NOT PAID', 'PARTIAL PAID')
            AND {$alias}.payment_schedule < DATE({$as_of_sql})
            THEN COALESCE({$alias}.amount, 0) - COALESCE({$alias}.paid_amount, 0) ELSE 0 END";
    }
}

if (!function_exists('sql_portfolio_outstanding_balance_expr')) {
    function sql_portfolio_outstanding_balance_expr($alias = 'ps', $as_of_sql = 'CURDATE()')
    {
        $principal = sql_unpaid_principal_expr($alias);
        $accrued = sql_portfolio_accrued_charges_case($alias, $as_of_sql);
        return "({$principal}) + ({$accrued})";
    }
}
