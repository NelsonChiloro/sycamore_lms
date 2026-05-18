<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Report extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Reports_model');
        $this->load->library('form_validation');
    }

    public function index()
    {


        $reports = $this->Reports_model->get_all();


        $data = array(
            'reports_data' => $reports,

        );
        $this->load->view('admin/header');
        $this->load->view('reports/reports_list', $data);
        $this->load->view('admin/footer');
    }

    public function read($id)
    {
        $row = $this->Reports_model->get_by_id($id);
        if ($row) {
            $data = array(
                'completed_time' => $row->completed_time,
                'download_link' => $row->download_link,
                'generated_time' => $row->generated_time,
                'id' => $row->id,
                'report_type' => $row->report_type,
                'status' => $row->status,
                'user' => $row->user,
                'user_id' => $row->user_id,
            );
            $this->load->view('reports/reports_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('reports'));
        }
    }

    public function create()
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('reports/create_action'),
            'completed_time' => set_value('completed_time'),
            'download_link' => set_value('download_link'),
            'generated_time' => set_value('generated_time'),
            'id' => set_value('id'),
            'report_type' => set_value('report_type'),
            'status' => set_value('status'),
            'user' => set_value('user'),
            'user_id' => set_value('user_id'),
        );
        $this->load->view('reports/reports_form', $data);
    }

    public function create_action()
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
                'completed_time' => $this->input->post('completed_time',TRUE),
                'download_link' => $this->input->post('download_link',TRUE),
                'generated_time' => $this->input->post('generated_time',TRUE),
                'report_type' => $this->input->post('report_type',TRUE),
                'status' => $this->input->post('status',TRUE),
                'user' => $this->input->post('user',TRUE),
                'user_id' => $this->input->post('user_id',TRUE),
            );

            $this->Reports_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('reports'));
        }
    }

    public function update($id)
    {
        $row = $this->Reports_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('reports/update_action'),
                'completed_time' => set_value('completed_time', $row->completed_time),
                'download_link' => set_value('download_link', $row->download_link),
                'generated_time' => set_value('generated_time', $row->generated_time),
                'id' => set_value('id', $row->id),
                'report_type' => set_value('report_type', $row->report_type),
                'status' => set_value('status', $row->status),
                'user' => set_value('user', $row->user),
                'user_id' => set_value('user_id', $row->user_id),
            );
            $this->load->view('reports/reports_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('reports'));
        }
    }

    public function update_action()
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
                'completed_time' => $this->input->post('completed_time',TRUE),
                'download_link' => $this->input->post('download_link',TRUE),
                'generated_time' => $this->input->post('generated_time',TRUE),
                'report_type' => $this->input->post('report_type',TRUE),
                'status' => $this->input->post('status',TRUE),
                'user' => $this->input->post('user',TRUE),
                'user_id' => $this->input->post('user_id',TRUE),
            );

            $this->Reports_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('reports'));
        }
    }

    public function delete($id)
    {
        $row = $this->Reports_model->get_by_id($id);

        if ($row) {
            $this->Reports_model->delete($id);
            $this->toaster->success('Success, Delete was successful');

            redirect(site_url('report'));
        } else {
            $this->toaster->error('Success, Delete was not successful');
            redirect(site_url('report'));
        }
    }

    public function download($id)
    {
        $row = $this->Reports_model->get_by_id($id);

        if (!$row || empty($row->download_link)) {
            $this->toaster->error('Download link was not found for this report.');
            redirect(site_url('report'));
            return;
        }

        $rawLink = trim(str_replace('\\', '/', $row->download_link));
        $resolvedPath = null;

        $candidates = array();

        // Handle absolute paths previously saved in the database.
        if (preg_match('/^[A-Za-z]:\//', $rawLink) === 1 || strpos($rawLink, '/') === 0) {
            $candidates[] = $row->download_link;
            $candidates[] = str_replace('/', DIRECTORY_SEPARATOR, $rawLink);
        }

        $relativeLink = preg_replace('#^/?bulk_report/#', '', $rawLink);
        $relativeLink = ltrim($relativeLink, '/');

        if (strpos($relativeLink, '..') === false && $relativeLink !== '') {
            $candidates[] = FCPATH . 'bulk_report/' . str_replace('/', DIRECTORY_SEPARATOR, $relativeLink);
        }

        $fileNameOnly = basename($rawLink);
        if ($fileNameOnly !== '') {
            $candidates[] = FCPATH . 'bulk_report/reports/' . $fileNameOnly;
        }

        foreach ($candidates as $candidate) {
            if (!empty($candidate) && is_file($candidate)) {
                $resolvedPath = $candidate;
                break;
            }
        }

        if (!$resolvedPath) {
            $this->toaster->error('Report file was not found on server. Please regenerate the report.');
            redirect(site_url('report'));
            return;
        }

        $this->load->helper('download');
        force_download(basename($resolvedPath), file_get_contents($resolvedPath));
    }

    public function _rules()
    {
        $this->form_validation->set_rules('completed_time', 'completed time', 'trim|required');
        $this->form_validation->set_rules('download_link', 'download link', 'trim|required');
        $this->form_validation->set_rules('generated_time', 'generated time', 'trim|required');
        $this->form_validation->set_rules('report_type', 'report type', 'trim|required');
        $this->form_validation->set_rules('status', 'status', 'trim|required');
        $this->form_validation->set_rules('user', 'user', 'trim|required');
        $this->form_validation->set_rules('user_id', 'user id', 'trim|required');

        $this->form_validation->set_rules('id', 'id', 'trim');
        $this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }
}