<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Relationship supervisor filters/columns for loan reports.
 */

function report_supervisor_input_value($method = null)
{
    $ci =& get_instance();
    if ($method === 'get' || $method === null) {
        $val = $ci->input->get('supervisor');
        if ($val !== null && $val !== '' && $val !== 'All') {
            return (int) $val;
        }
    }
    if ($method === 'post' || $method === null) {
        $val = $ci->input->post('supervisor');
        if ($val !== null && $val !== '' && $val !== 'All') {
            return (int) $val;
        }
    }
    return null;
}

function report_resolve_supervisor_officer_ids($supervisor_id)
{
    $supervisor_id = (int) $supervisor_id;
    if ($supervisor_id <= 0) {
        return array();
    }
    $ci =& get_instance();
    $ci->load->model('Employees_model');
    return $ci->Employees_model->get_officer_ids_under_supervisor($supervisor_id);
}

function report_supervisor_display_name($supervisor_id)
{
    $supervisor_id = (int) $supervisor_id;
    if ($supervisor_id <= 0) {
        return '';
    }
    $row = get_by_id('employees', 'id', $supervisor_id);
    return $row ? trim($row->Firstname . ' ' . $row->Lastname) : '';
}

/**
 * Payload fields for Node report API requests.
 */
function report_supervisor_curl_payload()
{
    $id = report_supervisor_input_value('post');
    if (!$id) {
        $id = report_supervisor_input_value('get');
    }
    if (!$id) {
        return array();
    }
    return array(
        'supervisor' => $id,
        'supervisor_name' => report_supervisor_display_name($id),
    );
}

/**
 * Restrict query builder to loans of officers under the supervisor.
 */
function report_apply_supervisor_loan_filter($supervisor_id, $loan_alias = 'loan')
{
    if (!$supervisor_id) {
        return;
    }
    $ci =& get_instance();
    $ids = report_resolve_supervisor_officer_ids($supervisor_id);
    if (empty($ids)) {
        $ci->db->where('1 = 0', null, false);
    } else {
        $ci->db->where_in($loan_alias . '.loan_added_by', $ids);
    }
}

/**
 * Join supervisor employee on loan officer (employees alias must already be joined).
 */
function report_join_relationship_supervisor($officer_alias = 'employees', $supervisor_alias = 'rel_supervisor')
{
    $ci =& get_instance();
    $ci->db->join(
        'employees ' . $supervisor_alias,
        $supervisor_alias . '.id = ' . $officer_alias . '.Supervisor',
        'left'
    );
}

function report_supervisor_select_sql($supervisor_alias = 'rel_supervisor')
{
    return 'TRIM(CONCAT(COALESCE(' . $supervisor_alias . '.Firstname, \'\'), \' \', COALESCE('
        . $supervisor_alias . '.Lastname, \'\'))) AS relationship_supervisor_name';
}

function report_format_supervisor_name($row)
{
    if (!empty($row->relationship_supervisor_name)) {
        $name = trim($row->relationship_supervisor_name);
        if ($name !== '') {
            return $name;
        }
    }
    if (!empty($row->sup_fname) || !empty($row->sup_lname)) {
        return trim($row->sup_fname . ' ' . $row->sup_lname);
    }
    return 'N/A';
}

function report_load_relationship_supervisors()
{
    $ci =& get_instance();
    $ci->load->model('Employees_model');
    return $ci->Employees_model->get_relationship_supervisors();
}

/**
 * Resolve branch name when loan.branch stores id, Code, or BranchCode.
 */
function report_sql_loan_branch_display_expr($loan_alias = 'loan')
{
    return "(SELECT b.BranchName FROM branches b
        WHERE b.id = {$loan_alias}.branch
           OR b.Code = {$loan_alias}.branch
           OR b.BranchCode = {$loan_alias}.branch
        LIMIT 1) AS branch_display_name";
}

/**
 * Filter loans by branch (dropdown uses branches.id).
 */
function report_apply_loan_branch_value_filter($branch_value, $loan_alias = 'loan')
{
    if ($branch_value === '' || $branch_value === null || $branch_value === 'All') {
        return;
    }
    $ci =& get_instance();
    $escaped = $ci->db->escape($branch_value);
    $ci->db->where("(
        {$loan_alias}.branch = {$escaped}
        OR {$loan_alias}.branch IN (SELECT Code FROM branches WHERE Code = {$escaped} OR BranchCode = {$escaped} OR CAST(id AS CHAR) = {$escaped})
        OR {$loan_alias}.branch IN (SELECT BranchCode FROM branches WHERE Code = {$escaped} OR BranchCode = {$escaped} OR CAST(id AS CHAR) = {$escaped})
        OR {$loan_alias}.branch IN (SELECT CAST(id AS CHAR) FROM branches WHERE Code = {$escaped} OR BranchCode = {$escaped} OR CAST(id AS CHAR) = {$escaped})
    )", null, false);
}
