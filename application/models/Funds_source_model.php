<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Funds_source_model extends CI_Model
{

    public $table = 'funds_source';
    public $id = 'funds_source';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }

    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        return $this->db->get($this->table)->result();
    }

    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('funds_source', $q);
        $this->db->or_like('source_name', $q);
        $this->db->or_like('description', $q);
        $this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('funds_source', $q);
        $this->db->or_like('source_name', $q);
        $this->db->or_like('description', $q);
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

    /**
     * Get all funds sources for dropdown
     *
     * @return object
     */
    public function get_all_funds_sources() {
        $this->db->order_by('source_name', 'ASC');
        return $this->db->get($this->table)->result();
    }

    /**
     * Get funds source by ID
     *
     * @param int $id
     * @return object
     */
    public function get_funds_source_by_id($id) {
        return $this->db->get_where($this->table, ['funds_source' => $id])->row();
    }

    /**
     * Check if source name already exists
     *
     * @param string $source_name
     * @param int $exclude_id
     * @return boolean
     */
    public function source_name_exists($source_name, $exclude_id = null) {
        $this->db->where('source_name', $source_name);
        if ($exclude_id) {
            $this->db->where('funds_source !=', $exclude_id);
        }
        $query = $this->db->get($this->table);
        return $query->num_rows() > 0;
    }

}