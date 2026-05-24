<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Employees_model extends CI_Model
{

    public $table = 'employees';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }

    // get all
<<<<<<< HEAD
    /**
     * Normalize role name for comparisons.
     */
    public static function normalize_role_name($role_name)
    {
        return strtoupper(trim(preg_replace('/\s+/', ' ', (string) $role_name)));
    }

    public static function role_is_relationship_officer($role_name)
    {
        $name = self::normalize_role_name($role_name);
        if ($name === '') {
            return false;
        }
        if (strpos($name, 'RELATIONSHIP') === false || strpos($name, 'OFFICER') === false) {
            return false;
        }
        return strpos($name, 'SUPERVISOR') === false;
    }

    public static function role_is_relationship_supervisor($role_name)
    {
        $name = self::normalize_role_name($role_name);
        return strpos($name, 'RELATIONSHIP') !== false && strpos($name, 'SUPERVISOR') !== false;
    }

    public function get_role_by_id($role_id)
    {
        return $this->db->where('id', (int) $role_id)->get('roles')->row();
    }

    /**
     * Role ids for Relationship officer (cached per request).
     */
    public function get_relationship_officer_role_ids()
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $cached = array();
        $this->db->reset_query();
        foreach ($this->db->get('roles')->result() as $role) {
            if (self::role_is_relationship_officer($role->RoleName)) {
                $cached[] = (int) $role->id;
            }
        }
        return $cached;
    }

    /**
     * Active relationship supervisors for dropdowns.
     */
    public function get_relationship_supervisors()
    {
        $rows = array();
        $this->db->reset_query();
        $this->db->select('employees.id, employees.Firstname, employees.Lastname, employees.Middlename, roles.RoleName');
        $this->db->from($this->table);
        $this->db->join('roles', 'roles.id = employees.Role');
        $this->db->order_by('employees.Firstname', 'ASC');
        $this->db->order_by('employees.Lastname', 'ASC');
        foreach ($this->db->get()->result() as $row) {
            if (self::role_is_relationship_supervisor($row->RoleName)) {
                $rows[] = $row;
            }
        }
        $this->db->reset_query();
        return $rows;
    }

    /**
     * Active relationship officers for dropdowns and mapping.
     */
    public function get_relationship_officers($include_supervisor_join = false)
    {
        $this->db->reset_query();
        if ($include_supervisor_join) {
            $this->db->select(
                'employees.id, employees.Firstname, employees.Lastname, employees.Middlename, employees.Supervisor, roles.RoleName,' .
                'sup.Firstname AS supervisor_firstname, sup.Lastname AS supervisor_lastname',
                false
            );
            $this->db->join('employees sup', 'sup.id = employees.Supervisor', 'left');
        } else {
            $this->db->select('employees.id, employees.Firstname, employees.Lastname, employees.Middlename, employees.Supervisor, roles.RoleName');
        }
        $this->db->from($this->table);
        $this->db->join('roles', 'roles.id = employees.Role');
        $this->db->order_by('employees.Firstname', 'ASC');
        $this->db->order_by('employees.Lastname', 'ASC');
        $rows = array();
        foreach ($this->db->get()->result() as $row) {
            if (self::role_is_relationship_officer($row->RoleName)) {
                $rows[] = $row;
            }
        }
        $this->db->reset_query();
        return $rows;
    }

    /**
     * Employee ids of relationship officers reporting to a supervisor.
     */
    public function get_officer_ids_under_supervisor($supervisor_id)
    {
        $supervisor_id = (int) $supervisor_id;
        if ($supervisor_id <= 0) {
            return array();
        }
        $role_ids = $this->get_relationship_officer_role_ids();
        if (empty($role_ids)) {
            return array();
        }
        $this->db->reset_query();
        $this->db->select('id');
        $this->db->from($this->table);
        $this->db->where('Supervisor', $supervisor_id);
        $this->db->where_in('Role', $role_ids);
        $ids = array();
        foreach ($this->db->get()->result() as $row) {
            $ids[] = (int) $row->id;
        }
        $this->db->reset_query();
        return $ids;
    }

    public function update_supervisor($employee_id, $supervisor_id)
    {
        $this->db->reset_query();
        $this->db->where($this->id, (int) $employee_id);
        $this->db->update($this->table, array(
            'Supervisor' => $supervisor_id > 0 ? (int) $supervisor_id : null,
        ));
        $this->db->reset_query();
    }

=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
    function get_all()
    {
        $this->db->order_by('employees.id', $this->order);
        $this->db->select("*,employees.id as empid");
        $this->db->join('roles','roles.id=employees.Role');
        return $this->db->get($this->table)->result();
    }
 function count_active($from,$to)
    {

        $this->db->select("*")->from($this->table);
		if($from !="" && $to !=""){
			$this->db->where('CreatedOn BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
        return $this->db->count_all_results();
    }    // get all
    function get_allb()
    {
        $this->db->order_by('employees.id', $this->order);
        $this->db->select("*,employees.id as empid");
        $this->db->join('roles','roles.id=employees.Role');
        return $this->db->get($this->table)->result();
    }

    // get data by id
    function get_by_id($id)
    {
    	$this->db->select("*,employees.id as empid");
		$this->db->join('roles','roles.id=employees.Role');
        $this->db->where('employees.id', $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('id', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('EmailAddress', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('AddressLine1', $q);
	$this->db->or_like('AddressLine2', $q);
	$this->db->or_like('Province', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('Role', $q);
	$this->db->or_like('BranchCode', $q);
	$this->db->or_like('Branch', $q);
	$this->db->or_like('EmploymentStatus', $q);
	$this->db->or_like('LastUpdatedOn', $q);
	$this->db->or_like('CreatedOn', $q);
	$this->db->or_like('system_date', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('id', $q);
	$this->db->or_like('Firstname', $q);
	$this->db->or_like('Middlename', $q);
	$this->db->or_like('Lastname', $q);
	$this->db->or_like('Gender', $q);
	$this->db->or_like('DateOfBirth', $q);
	$this->db->or_like('EmailAddress', $q);
	$this->db->or_like('PhoneNumber', $q);
	$this->db->or_like('AddressLine1', $q);
	$this->db->or_like('AddressLine2', $q);
	$this->db->or_like('Province', $q);
	$this->db->or_like('City', $q);
	$this->db->or_like('Country', $q);
	$this->db->or_like('Role', $q);
	$this->db->or_like('BranchCode', $q);
	$this->db->or_like('Branch', $q);
	$this->db->or_like('EmploymentStatus', $q);
	$this->db->or_like('LastUpdatedOn', $q);
	$this->db->or_like('CreatedOn', $q);
	$this->db->or_like('system_date', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
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

