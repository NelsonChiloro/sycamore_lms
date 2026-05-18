<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User_access extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('User_access_model');

		$this->load->model('Employees_model');
        $this->load->library('form_validation');
    }
public function cancel($em){
    	$this->session->set_flashdata('success','Your action succeeded');
	$this->User_access_model->update($em,array('is_logged_in'=>'No'));
	redirect($_SERVER['HTTP_REFERER']);
}
public function logout_all_d(){
    	$this->User_access_model->logout_a();
}
public function logout_all(){
    	$this->User_access_model->logout_all();
	$this->toaster->success('Success, your action was successful');
	redirect($_SERVER['HTTP_REFERER']);
}
    public function index()
    {



        $data = array(
            'user_access_data' =>$this->User_access_model->get_all(),

        );
	$this->load->view('admin/header');
		$this->load->view('user_access/user_access_list', $data);
		$this->load->view('admin/footer');

    }
    public function approve()
    {



        $data = array(
            'user_access_data' =>$this->User_access_model->get_all(),

        );
	$this->load->view('admin/header');
		$this->load->view('user_access/approve', $data);
		$this->load->view('admin/footer');

    }
public function change_password(){
	$this->load->view('admin/header');
	$this->load->view('user_access/profile');
	$this->load->view('admin/footer');
}
	public function change_password_update(){
		$this->_rules3();

		if ($this->form_validation->run() == FALSE) {
			$this->change_password();
		} else {
			$data = array(
				'Password' => sha1($this->input->post('new_pass'))
			);
			$this->User_access_model->update($this->session->userdata('user_id'), $data);
			$data2 = array(

				'last_changed' => $this->session->userdata('system_date'),

			);
			//$this->Password_change_tracker_model->update2($this->session->userdata('user_id'),$data2);
			$this->toaster->success('Success, password was changed');
			redirect($_SERVER['HTTP_REFERER']);
		}
	}
	public  function flip(){
$this->load->view('page2');
	}
	public function exp(){
		$this->_rules3();

		if ($this->form_validation->run() == FALSE) {
			$this->expired_password();
		} else {
			$data = array(
				'Password' => sha1($this->input->post('new_pass'))
			);
			$this->User_access_model->update($this->session->userdata('user_id'), $data);
			$data2 = array(

				'last_changed' => $this->session->userdata('system_date'),

			);
			$this->Password_change_tracker_model->update2($this->session->userdata('user_id'),$data2);
			$this->session->set_flashdata('message', 'Password was changed successfully');
			redirect(site_url('Dashboard'));
		}
	}
public function expired_password(){
    	$this->load->view('auth/lock_screen');
}
	public function _rules3()
	{
		$this->form_validation->set_rules('old_pass', 'old password', 'trim|required|callback_password_check');
		$this->form_validation->set_rules('new_pass', 'new Password', 'trim|required');
		$this->form_validation->set_rules('confirm_pass', 'Confirm Password', 'trim|required|matches[new_pass]');


		$this->form_validation->set_rules('id', 'id', 'trim');
		$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
	}
	public function password_check($oldpass)
	{
		$id = $this->session->userdata('user_id');
		$user = $this->User_access_model->get_by_id($id);

		if($user->Password !== sha1($oldpass)) {
			$this->form_validation->set_message('password_check', 'The {field} does not match');
			return false;
		}

		return true;
	}

	public function read($id)
    {
        $row = $this->User_access_model->get_by_id($id);
        if ($row) {
            $data = array(
		'AccessCode' => $row->AccessCode,
		'Password' => $row->Password,
		'Employee' => $row->Employee,
		'Status' => $row->Status,
	    );
            $this->load->view('user_access/user_access_read', $data);
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('user_access'));
        }
    }

    public function create()
    {
        $data = array(
            'button' => 'Create',
            'action' => site_url('user_access/create_action'),
	    'AccessCode' => set_value('AccessCode'),
	    'Password' => set_value('Password'),
	    'Employee' => set_value('Employee'),

	);
		$this->load->view('admin/header');
		$this->load->view('user_access/user_access_form', $data);
		$this->load->view('admin/footer');
    }

    public function create_action()
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
        	$dd = $this->Employees_model->get_by_id($this->input->post('Employee',TRUE));
            $data = array(
				'AccessCode' => strtoupper($dd->Firstname).strtoupper($dd->Lastname).rand(100,999),
		'Password' => sha1($this->input->post('Password',TRUE)),
		'Employee' => $this->input->post('Employee',TRUE),

	    );


            $this->User_access_model->insert($data);

            $this->session->set_flashdata('message', 'Create Record Success');
            redirect(site_url('user_access'));
        }
    }

    public function update($id)
    {
        $row = $this->User_access_model->get_by_id($id);

        if ($row) {
            $data = array(
                'button' => 'Update',
                'action' => site_url('user_access/update_action'),
		'AccessCode' => set_value('AccessCode', $row->AccessCode),
		'Password' => set_value('Password', $row->Password),
		'Employee' => set_value('Employee', $row->Employee),
		'Status' => set_value('Status', $row->Status),
	    );
			$$this->load->view('admin/header');
			$this->load->view('user_access/user_access_form', $data);
			$this->load->view('admin/footer');
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('user_access'));
        }
    }
    function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
public function reset_pass ($id){
       $new_pass =  $this->generateRandomString();

        $get_user = get_by_id('employees','id',$id);
        $this->User_access_model->update_auth($id,array('Password'=>sha1($new_pass)));
	$curl = curl_init();

	curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://fin.infocustech-mw.com/User_access/why',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'POST',
		CURLOPT_POSTFIELDS => array('to' => $get_user->EmailAddress,'subject' => 'Password reset','name' => $get_user->Lastname.' '.$get_user->Firstname,'message' => 'Dear '.$get_user->Lastname.' '.$get_user->Firstname.' ,this is your new password: <>  '.$new_pass.'  <>  ,you may change after login',),
		CURLOPT_HTTPHEADER => array(
			'Cookie: ci_session=fc20ac5d161a693f0f9e7e3965f1fd7738706149'
		),
	));

 $res =curl_exec($curl);

	curl_close($curl);

	$this->toaster->success('Password reset was successful, new login '.$new_pass.'sent to:'.$get_user->EmailAddress);

	redirect($_SERVER["HTTP_REFERER"]);


//        $this->notify_email($get_user->EmailAddress,'Email Reset', 'This is your new password:'.$new_pass, $get_user->Lastname.' '.$get_user->Firstname);
}
    public function update_action()
    {
        $this->_rules();

        if ($this->form_validation->run() == FALSE) {
            $this->update($this->input->post('AccessCode', TRUE));
        } else {
            $data = array(
		'Password' => $this->input->post('Password',TRUE),
		'Employee' => $this->input->post('Employee',TRUE),

	    );

            $this->User_access_model->update($this->input->post('AccessCode', TRUE), $data);
            $this->session->set_flashdata('message', 'Update Record Success');
            redirect(site_url('user_access'));
        }
    }

    public function notify_email($to,$subject,$message,$recipient_name){

        $settings = get_by_id('settings','settings_id','1');
        $config = array('protocol' => $settings->protocal,
            'smtp_host' => $settings->email_host,
            'smtp_port' => $settings->email_port,
            'smtp_user' => $settings->email_user,
            'smtp_pass' => $settings->email_pass,

        );

        $this->load->library('email',$config);


        $message = $message;
        $subject = $subject;





                $output = "";
                $output .= '
         <html>
         <head>
         <title>' . $settings->company_name . '</title>
         <style>
         body {
  background: whitesmoke;
  text-align: center;
}

.card {
 text-align: center;
   margin: auto;
  width: 50%;

  padding: 10px;

  background: #E7E9EB;
  border-radius: 15px;

  height: 500px;

}

.card-1 {
  box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
  transition: all 0.3s cubic-bezier(.25,.8,.25,1);
}

.card-1:hover {
  box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
}

.card-2 {
  box-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
}

.card-3 {
  box-shadow: 0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23);
}

.card-4 {
  box-shadow: 0 14px 28px rgba(0,0,0,0.25), 0 10px 10px rgba(0,0,0,0.22);
}

.card-5 {
  box-shadow: 0 19px 38px rgba(0,0,0,0.30), 0 15px 12px rgba(0,0,0,0.22);
}


</style>

         </head>
         <body >
         <center><img src="' . base_url('uploads/') . $settings->logo . '" alt="logo" height="100" width="100" style="border-radius: 50px; border: thick solid lightslategray;"></center>
         <br>
          <center><a href="' . base_url() . '">' . $settings->company_name . '</a></center>
            <div class="card card-5" style="justify-items: center; align-items: center;">
            <p style="font-weight: bolder; font-size: 30px;">Hello: ' . $recipient_name . '</p>

            <p style="font-weight: bolder; font-size: 15px;">' . $message . '</p>


         </div>

         <center>Powered by:<a href="' . base_url() . '">' . $settings->company_name . '</a></center>
         </body>
         </html>
         ';


                $this->email->set_newline("\r\n");
                $this->email->from($settings->email_user, $settings->company_name);
                $this->email->to($to);
                $this->email->subject($subject);
                $this->email->message($output);
                $this->email->set_mailtype('html');
                $this->email->send();

        redirect($_SERVER["HTTP_REFERER"]);


    }
    public function approved($id)
    {

            $data = array(
		'status' => 'AUTHORIZED',


	    );

            $this->User_access_model->update_auth($id,  $data);
            $this->toaster->success( 'Approval of user access approved');
            redirect($_SERVER["HTTP_REFERER"]);

    }
    public function block($id)
    {

            $data = array(
		'status' => 'BLOCKED',


	    );

            $this->User_access_model->update_auth($id,  $data);
            $this->toaster->success( 'User was blocked');
            redirect($_SERVER["HTTP_REFERER"]);

    }  public function unblock($id)
    {

            $data = array(
		'status' => 'AUTHORIZED',


	    );

            $this->User_access_model->update_auth($id,  $data);
            $this->toaster->success( 'User was unblocked');
            redirect($_SERVER["HTTP_REFERER"]);

    }
    public function reject($id)
    {

            $data = array(
		'status' => 'REJECTED',

	    );

            $this->User_access_model->update_auth($id, $data);
            $this->toaster->success( 'Approval of user access approved');
            redirect($_SERVER["HTTP_REFERER"]);

    }

    public function delete($id)
    {
        $row = $this->User_access_model->get_by_id($id);

        if ($row) {
            $this->User_access_model->delete($id);
            $this->session->set_flashdata('message', 'Delete Record Success');
            redirect(site_url('user_access'));
        } else {
            $this->session->set_flashdata('message', 'Record Not Found');
            redirect(site_url('user_access'));
        }
    }

    public function _rules()
    {
	$this->form_validation->set_rules('Password', 'password', 'trim|required');
	$this->form_validation->set_rules('Employee', 'employee', 'trim|required|is_unique[user_access.Employee]');
		$this->form_validation->set_message('is_unique', 'The %s is already configured');

	$this->form_validation->set_rules('AccessCode', 'AccessCode', 'trim');
	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
    }

}


