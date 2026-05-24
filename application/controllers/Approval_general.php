<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Approval_general extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Menu_model');
<<<<<<< HEAD
        $this->load->model('Group_batch_model');
=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d

    }
    public  function auth_data(){
        $id = $this->uri->segment(3);
        $recommend = $this->uri->segment(4);
        $approve = $this->uri->segment(5);
        $row = get_by_id('approval_edits','approval_edits_id',$id);
        if ($row) {
<<<<<<< HEAD
            $old_info = json_decode($row->old_info);
            $new_info = json_decode($row->new_info);
            $data = array(
                'id' => $id,
                'type' => $row->type,
                'summary' => $row->summary,
                'old_info' => $old_info,
                'new_info' => $new_info,
                'is_group_batch_edit' => $this->Group_batch_model->is_group_batch_approval($row),
=======
            $data = array(
                'id' => $id,
                'type' => $row->type,
                'old_info' => json_decode($row->old_info),
                'new_info' => json_decode($row->new_info),
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
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
<<<<<<< HEAD
        $approval_id = $this->input->post('id');
        $approval_row = get_by_id('approval_edits', 'approval_edits_id', $approval_id);
        $batch = $approval_row ? $this->Group_batch_model->get_batch_from_approval($approval_row) : null;

        $is_reject = $this->input->post('Approval') === 'Reject'
            || strtolower((string) $this->input->post('approval')) === 'reject';

        if ($is_reject) {
            $this->db->where('approval_edits_id', $approval_id)
=======
        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $this->input->post('id'))
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                ->update('approval_edits',
                    array('state' => 'Rejected', 'recommed_reject_by' => $this->session->userdata('user_id'), 'recommed_reject_date' => date('Y-m-d'), 'recommed_reject_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Recommendation was rejected successfully');
<<<<<<< HEAD
        } else {
            $this->db->where('approval_edits_id', $approval_id)
=======
        }else {
            $this->db->where('approval_edits_id', $this->input->post('id'))
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                ->update('approval_edits',
                    array('state' => 'recommended', 'recommended_by' => $this->session->userdata('user_id'), 'recommended_date' => date('Y-m-d'), 'recommend_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Recommendation was successful');
        }
<<<<<<< HEAD

        if (!$is_reject && $batch !== null) {
            redirect('loan/group_batch_loans/' . rawurlencode($batch));
            return;
        }

=======
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
        redirect('loan/edit_recommend');
    }
    public function edit_approve()
    {
<<<<<<< HEAD
        $approval_id = $this->input->post('id');
        $approval_row = get_by_id('approval_edits', 'approval_edits_id', $approval_id);

        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $approval_id)
=======
        if($this->input->post('Approval')=="Reject"){
            $this->db->where('approval_edits_id', $this->input->post('id'))
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                ->update('approval_edits',
                    array('state' => 'Rejected', 'approval_reject_by' => $this->session->userdata('user_id'), 'approval_reject_date' => date('Y-m-d'), 'approval_reject_comment' => $this->input->post('comment')
                    )
                );
            $this->toaster->success('Approval was rejected successfully');
            redirect('loan/edit_approve');
        }else {
<<<<<<< HEAD
            $this->db->where('approval_edits_id', $approval_id)
=======
            $this->db->where('approval_edits_id', $this->input->post('id'))
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
                ->update('approval_edits',
                    array('state' => 'Approved', 'approved_by' => $this->session->userdata('user_id'), 'approved_date' => date('Y-m-d'), 'approval_comment' => $this->input->post('comment')
                    )
                );
<<<<<<< HEAD

            if ($approval_row && $this->Group_batch_model->is_group_batch_approval($approval_row)) {
                $this->session->set_userdata('group_batch_edit', $approval_id);
                redirect('loan/create_act_batch_edit');
                return;
            }

            $this->session->set_userdata('loan_data', $approval_id);
=======
$this->session->set_userdata('loan_data',$this->input->post('id'));
>>>>>>> 808554ff5caea0db9a21de0721b02d4d60db333d
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