<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Activity_logger extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Activity_logger_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        // Load pagination library
        $this->load->library('pagination');

        // Get filter parameters
        $employee_id = $this->input->get('employee_id');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $search = $this->input->get('search');

        // Build suffix for pagination URLs to include current filters
        $url_suffix = '';
        $suffix_params = array();
        if ($employee_id) $suffix_params[] = 'employee_id=' . urlencode($employee_id);
        if ($date_from) $suffix_params[] = 'date_from=' . urlencode($date_from);
        if ($date_to) $suffix_params[] = 'date_to=' . urlencode($date_to);
        if ($search) $suffix_params[] = 'search=' . urlencode($search);

        if (!empty($suffix_params)) {
            $url_suffix = '?' . implode('&', $suffix_params);
        }

        // Pagination configuration
        $config['base_url'] = base_url('Activity_logger/index');
        $config['total_rows'] = $this->Activity_logger_model->total_rows_filtered($employee_id, $date_from, $date_to, $search);
        $config['per_page'] = 10; // 10 records per page
        $config['uri_segment'] = 3;
        $config['suffix'] = $url_suffix;
        $config['first_url'] = $config['base_url'] . $url_suffix;

        // Pagination styling
        $config['full_tag_open'] = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = 'First';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_link'] = 'Last';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_link'] = 'Next';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] = 'Previous';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);

        $start = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;

        // Get employees for dropdown
        $employees = $this->Activity_logger_model->get_all_employees();

        $data = array(
            'activity_logger_data' => $this->Activity_logger_model->get_limit_data_with_filters($config['per_page'], $start, $employee_id, $date_from, $date_to, $search),
            'pagination_links' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start' => $start + 1,
            'end' => min($start + $config['per_page'], $config['total_rows']),
            'employees' => $employees
        );

        $this->load->view('admin/header');
        $this->load->view('activity_logger/activity_logger_list',$data);
        $this->load->view('admin/footer');
    }

    public function read($id) 
    {
        $row = $this->Activity_logger_model->get_by_id($id);
        if ($row) {
            $data = array(
		'id' => $row->id,
		'user_id' => $row->user_id,
		'activity' => $row->activity,
		'system_time' => $row->system_time,
		'server_time' => $row->server_time,
	    );
            $this->load->view('activity_logger/activity_logger_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('activity_logger'));
        }
    }

    public function create() 
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('activity_logger/create_action'),
	    'id' => set_value('id'),
	    'user_id' => set_value('user_id'),
	    'activity' => set_value('activity'),
	    'system_time' => set_value('system_time'),
	    'server_time' => set_value('server_time'),
	);
        $this->load->view('activity_logger/activity_logger_form', $data);
    }
    
    public function create_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = array(
		'user_id' => $this->input->post('user_id',TRUE),
		'activity' => $this->input->post('activity',TRUE),
		'system_time' => $this->input->post('system_time',TRUE),
		'server_time' => $this->input->post('server_time',TRUE),
	    );

            $this->Activity_logger_model->insert($data);
            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('activity_logger'));
        }
    }
    
    public function update($id) 
    {
        $row = $this->Activity_logger_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('activity_logger/update_action'),
		'id' => set_value('id', $row->id),
		'user_id' => set_value('user_id', $row->user_id),
		'activity' => set_value('activity', $row->activity),
		'system_time' => set_value('system_time', $row->system_time),
		'server_time' => set_value('server_time', $row->server_time),
	    );
            $this->load->view('activity_logger/activity_logger_form', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('activity_logger'));
        }
    }
    
    public function update_action() 
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('id', TRUE));
        } else {
            $data = array(
		'user_id' => $this->input->post('user_id',TRUE),
		'activity' => $this->input->post('activity',TRUE),
		'system_time' => $this->input->post('system_time',TRUE),
		'server_time' => $this->input->post('server_time',TRUE),
	    );

            $this->Activity_logger_model->update($this->input->post('id', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('activity_logger'));
        }
    }
    
    public function delete($id) 
    {
        $row = $this->Activity_logger_model->get_by_id($id);

        if ($row) {
            $this->Activity_logger_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('activity_logger'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('activity_logger'));
        }
    }

    public function export_excel()
    {
        // Get filter parameters
        $employee_id = $this->input->get('employee_id');
        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $search = $this->input->get('search');

        // Get all filtered data
        $activity_data = $this->Activity_logger_model->get_all_filtered_data($employee_id, $date_from, $date_to, $search);

        // Set headers for Excel download
        $filename = 'Activity_Logger_' . date('YmdHis') . '.xls';
        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Pragma: no-cache");
        header("Expires: 0");

        // Get employee name if filtered
        $employee_name = 'All Employees';
        if ($employee_id) {
            $employees = $this->Activity_logger_model->get_all_employees();
            foreach ($employees as $emp) {
                if ($emp->id == $employee_id) {
                    $employee_name = $emp->Firstname . ' ' . $emp->Lastname;
                    break;
                }
            }
        }

        // Output Excel content
        echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
        echo '<head>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo '<style>';
        echo 'table { border-collapse: collapse; width: 100%; }';
        echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
        echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        echo '.header { font-size: 18px; font-weight: bold; text-align: center; margin-bottom: 10px; }';
        echo '.filter-info { background-color: #f0f0f0; padding: 10px; margin-bottom: 15px; }';
        echo '</style>';
        echo '</head>';
        echo '<body>';

        // Header
        echo '<div class="header">Activity Logger Report</div>';
        echo '<div class="header" style="font-size: 12px;">Generated: ' . date('Y-m-d H:i:s') . '</div>';
        echo '<br>';

        // Filter info
        echo '<div class="filter-info">';
        echo '<strong>Applied Filters:</strong><br>';
        echo '<strong>Employee:</strong> ' . $employee_name . '<br>';
        if ($date_from || $date_to) {
            echo '<strong>Date Range:</strong> ';
            if ($date_from && $date_to) {
                echo $date_from . ' to ' . $date_to;
            } elseif ($date_from) {
                echo 'From ' . $date_from;
            } elseif ($date_to) {
                echo 'Up to ' . $date_to;
            }
            echo '<br>';
        }
        if ($search) {
            echo '<strong>Search Term:</strong> ' . htmlspecialchars($search) . '<br>';
        }
        echo '<strong>Total Records:</strong> ' . count($activity_data);
        echo '</div>';
        echo '<br>';

        // Data table
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>#</th>';
        echo '<th>Full Name</th>';
        echo '<th>Email</th>';
        echo '<th>Role</th>';
        echo '<th>Activity</th>';
        echo '<th>Date & Time</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if (!empty($activity_data)) {
            $serial = 1;
            foreach ($activity_data as $activity) {
                echo '<tr>';
                echo '<td>' . $serial . '</td>';
                echo '<td>' . htmlspecialchars($activity->Firstname . ' ' . $activity->Lastname) . '</td>';
                echo '<td>' . htmlspecialchars($activity->EmailAddress ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($activity->role_name ?: 'No Role') . '</td>';
                echo '<td>' . htmlspecialchars($activity->activity) . '</td>';
                echo '<td>';
                if (!empty($activity->server_time)) {
                    echo date('M d, Y g:i A', strtotime($activity->server_time));
                } else {
                    echo 'N/A';
                }
                echo '</td>';
                echo '</tr>';
                $serial++;
            }
        } else {
            echo '<tr><td colspan="6" style="text-align:center;">No activity logs found</td></tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit;
    }

    public function _rules()
    {
	$this->form_validation->set_rules('user_id', 'user id', 'trim|required');
	$this->form_validation->set_rules('activity', 'activity', 'trim|required');
	$this->form_validation->set_rules('system_time', 'system time', 'trim|required');
	$this->form_validation->set_rules('server_time', 'server time', 'trim|required');

	$this->form_validation->set_rules('id', 'id', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}

/* End of file Activity_logger.php */
/* Location: ./application/controllers/Activity_logger.php */
/* Please DO NOT modify this information : */
/* Generated by Harviacode Codeigniter CRUD Generator 2021-05-05 22:08:21 */
/* http://harviacode.com */
