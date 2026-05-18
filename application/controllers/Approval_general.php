<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Approval_general extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_model');

    }
    public  function auth_data(){
        $id = $this->uri->segment(3);
        $recommend = $this->uri->segment(4);
        $approve = $this->uri->segment(5);
        $row = get_by_id('approval_edits','approval_edits_id',$id);
        if ($row) {
            $data = array(
                'id' => $id,
                'type' => $row->type,
                'old_info' => json_decode($row->old_info),
                'new_info' => json_decode($row->new_info),
                'state' => $row->state,

                'stamp' => $row->stamp,
                'Initiated_by' => $row->Initiated_by,
                'action_recommend'=> $recommend,
                'action_approve'=> $approve,

            );
            $menu_toggle['toggles'] = 23;
            $this->load->view('admin/header', $menu_toggle);
            $this->load->view('admin/auth_action',$data);
            $this->load->view('admin/footer');
        } else {
            echo "Not found";
        }

    }

    public function edit_recommend()
    {
        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'Rejected', 'recommed_reject_by' => $this->session->userdata('user_id'), 'recommed_reject_date' => date('Y-m-d'), 'recommed_reject_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Recommendation was rejected successfully');
        }else {
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'recommended', 'recommended_by' => $this->session->userdata('user_id'), 'recommended_date' => date('Y-m-d'), 'recommend_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Recommendation was successful');
        }
        redirect('loan/edit_recommend');
    }
    public function edit_approve()
    {
        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'Rejected', 'approval_reject_by' => $this->session->userdata('user_id'), 'approval_reject_date' => date('Y-m-d'), 'approval_reject_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Approval was rejected successfully');
            redirect('loan/edit_approve');
        }else {
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'Approved', 'approved_by' => $this->session->userdata('user_id'), 'approved_date' => date('Y-m-d'), 'approval_comment' => $this->input->post('comment')
                    )
                );
$this->session->set_userdata('loan_data',$this->input->post('id'));
            redirect('loan/create_act_edit');
        }


    }

    public function delete_recommend()
    {
        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'Rejected', 'recommed_reject_by' => $this->session->userdata('user_id'), 'recommed_reject_date' => date('Y-m-d'), 'recommed_reject_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Recommendation was rejected successfully');
        }else {
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'recommended', 'recommended_by' => $this->session->userdata('user_id'), 'recommended_date' => date('Y-m-d'), 'recommend_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Recommendation was successful');
        }
        redirect('loan/delete_recommend');
    }
    public function delete_approve()
    {
        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'Rejected', 'approval_reject_by' => $this->session->userdata('user_id'), 'approval_reject_date' => date('Y-m-d'), 'approval_reject_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Approval was rejected successfully');
            redirect('loan/delete_approve');
        }else {
            $this->db->where('approval_edits_id', $this->input->post('id'))
                ->update('approval_edits',
                    array('state' => 'Approved', 'approved_by' => $this->session->userdata('user_id'), 'approved_date' => date('Y-m-d'), 'approval_comment' => $this->input->post('comment')
                    )
                );
            $this->session->set_userdata('loan_delete',$this->input->post('id'));
            redirect('loan/create_act_delete');
        }


    }
}

?>