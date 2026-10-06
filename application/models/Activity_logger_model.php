<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Activity_logger_model extends CI_Model
{

    public $table = 'activity_logger';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }

    // get all
    function get_all()
    {
        $this->db->order_by('activity_logger.server_time', $this->order);
        $this->db->select("activity_logger.*, employees.Firstname, employees.Lastname, employees.EmailAddress, employees.Role, roles.name as role_name");
        $this->db->from($this->table);
        $this->db->join('employees','employees.id=activity_logger.user_id');
        $this->db->join('roles','roles.id=employees.Role', 'left');
        return $this->db->get()->result();
    }

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('id', $q);
	$this->db->or_like('user_id', $q);
	$this->db->or_like('activity', $q);
	$this->db->or_like('system_time', $q);
	$this->db->or_like('server_time', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('id', $q);
	$this->db->or_like('user_id', $q);
	$this->db->or_like('activity', $q);
	$this->db->or_like('system_time', $q);
	$this->db->or_like('server_time', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
    }

    // get data with limit and join for pagination
    function get_limit_data_with_join($limit, $start = 0, $q = NULL) {
        $this->db->order_by('activity_logger.server_time', $this->order);
        $this->db->select("activity_logger.*, employees.Firstname, employees.Lastname, employees.EmailAddress, employees.Role, roles.RoleName as role_name");
        $this->db->from($this->table);
        $this->db->join('employees','employees.id=activity_logger.user_id');
        $this->db->join('roles','roles.id=employees.Role', 'left');

        if ($q != NULL) {
            $this->db->like('activity_logger.id', $q);
            $this->db->or_like('activity_logger.user_id', $q);
            $this->db->or_like('activity_logger.activity', $q);
            $this->db->or_like('activity_logger.server_time', $q);
            $this->db->or_like('employees.Firstname', $q);
            $this->db->or_like('employees.Lastname', $q);
            $this->db->or_like('employees.EmailAddress', $q);
            $this->db->or_like('roles.name', $q);
        }

        $this->db->limit($limit, $start);
        return $this->db->get()->result();
    }

    // get data with filters (employee, date range, search)
    function get_limit_data_with_filters($limit, $start = 0, $employee_id = NULL, $date_from = NULL, $date_to = NULL, $search = NULL) {
        $this->db->order_by('activity_logger.server_time', $this->order);
        $this->db->select("activity_logger.*, employees.Firstname, employees.Lastname, employees.EmailAddress, employees.Role, roles.RoleName as role_name");
        $this->db->from($this->table);
        $this->db->join('employees','employees.id=activity_logger.user_id');
        $this->db->join('roles','roles.id=employees.Role', 'left');

        // Employee filter
        if ($employee_id && $employee_id != '') {
            $this->db->where('activity_logger.user_id', $employee_id);
        }

        // Date range filter
        if ($date_from && $date_from != '') {
            $this->db->where('DATE(activity_logger.server_time) >=', $date_from);
        }
        if ($date_to && $date_to != '') {
            $this->db->where('DATE(activity_logger.server_time) <=', $date_to);
        }

        // Search filter
        if ($search && $search != '') {
            $this->db->group_start();
            $this->db->like('activity_logger.activity', $search);
            $this->db->or_like('employees.Firstname', $search);
            $this->db->or_like('employees.Lastname', $search);
            $this->db->or_like('employees.EmailAddress', $search);
            $this->db->or_like('roles.RoleName', $search);
            $this->db->group_end();
        }

        $this->db->limit($limit, $start);
        return $this->db->get()->result();
    }

    // get total rows with filters
    function total_rows_filtered($employee_id = NULL, $date_from = NULL, $date_to = NULL, $search = NULL) {
        $this->db->from($this->table);
        $this->db->join('employees','employees.id=activity_logger.user_id');
        $this->db->join('roles','roles.id=employees.Role', 'left');

        // Employee filter
        if ($employee_id && $employee_id != '') {
            $this->db->where('activity_logger.user_id', $employee_id);
        }

        // Date range filter
        if ($date_from && $date_from != '') {
            $this->db->where('DATE(activity_logger.server_time) >=', $date_from);
        }
        if ($date_to && $date_to != '') {
            $this->db->where('DATE(activity_logger.server_time) <=', $date_to);
        }

        // Search filter
        if ($search && $search != '') {
            $this->db->group_start();
            $this->db->like('activity_logger.activity', $search);
            $this->db->or_like('employees.Firstname', $search);
            $this->db->or_like('employees.Lastname', $search);
            $this->db->or_like('employees.EmailAddress', $search);
            $this->db->or_like('roles.RoleName', $search);
            $this->db->group_end();
        }

        return $this->db->count_all_results();
    }

    // get all employees for dropdown
    function get_all_employees() {
        $this->db->select('id, Firstname, Lastname');
        $this->db->from('employees');
        $this->db->where('EmploymentStatus', 'CURRENT'); // Only current employees
        $this->db->order_by('Firstname', 'ASC');
        return $this->db->get()->result();
    }

    // get all filtered data for export (no pagination)
    function get_all_filtered_data($employee_id = NULL, $date_from = NULL, $date_to = NULL, $search = NULL) {
        $this->db->order_by('activity_logger.server_time', $this->order);
        $this->db->select("activity_logger.*, employees.Firstname, employees.Lastname, employees.EmailAddress, employees.Role, roles.RoleName as role_name");
        $this->db->from($this->table);
        $this->db->join('employees','employees.id=activity_logger.user_id');
        $this->db->join('roles','roles.id=employees.Role', 'left');

        // Employee filter
        if ($employee_id && $employee_id != '') {
            $this->db->where('activity_logger.user_id', $employee_id);
        }

        // Date range filter
        if ($date_from && $date_from != '') {
            $this->db->where('DATE(activity_logger.server_time) >=', $date_from);
        }
        if ($date_to && $date_to != '') {
            $this->db->where('DATE(activity_logger.server_time) <=', $date_to);
        }

        // Search filter
        if ($search && $search != '') {
            $this->db->group_start();
            $this->db->like('activity_logger.activity', $search);
            $this->db->or_like('employees.Firstname', $search);
            $this->db->or_like('employees.Lastname', $search);
            $this->db->or_like('employees.EmailAddress', $search);
            $this->db->or_like('roles.RoleName', $search);
            $this->db->group_end();
        }

        return $this->db->get()->result();
    }

    // insert data
    function insert($data)
    {
        $this->db->insert($this->table, $data);
    }

    // update data
    function update($id, $data)
    {
        $this->db->where($this->id, $id);
        $this->db->update($this->table, $data);
    }

    // delete data
    function delete($id)
    {
        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }

}

/* End of file Activity_logger_model.php */
/* Location: ./application/models/Activity_logger_model.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-10-14 06:27:32 */
/* http://harviacode.com */
