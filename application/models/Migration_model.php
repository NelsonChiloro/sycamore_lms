<?php


class Migration_model extends CI_Model
{
    public $table = 'newgroup';
    public $id = 'id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
    // update data
    function update($id, $data)
    {
        $this->db->where($this->id, $id);
        $this->db->update($this->table, $data);
    }
 function update2($id, $data)
    {
        $this->db->where('Lastname_witness', $id);
        $this->db->update($this->table, $data);
    }function update3($id, $data)
    {
        $this->db->where('Lastname_relative', $id);
        $this->db->update($this->table, $data);
    }
    function update4($id, $data)
    {
        $this->db->where('groupname', $id);
        $this->db->update($this->table, $data);
    }

}
