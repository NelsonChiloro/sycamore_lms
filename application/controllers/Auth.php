<?php


class Auth extends CI_Controller
{
public function __construct()
{
	parent::__construct();
	$this->load->library('form_validation');
	$this->load->model('User_access_model');
}

private function ensure_session_loaded()
{
	if (!isset($this->session)) {
		$this->load->library('session');
	}
}

//function load login page
public function index(){
		$error = $this->input->get('error') ? true : null;
		$active = $this->input->get('active') ? true : null;

		if (isset($this->session)) {
			$error = $error ?: $this->session->flashdata('error');
			$active = $active ?: $this->session->flashdata('active');
			if (function_exists('session_write_close')) {
				session_write_close();
			}
		}

		$this->load->view('login', array(
			'error' => $error,
			'active' => $active,
		));
	}

function update_state(){
	$this->load->model('Loan_model');
	$this->Loan_model->update_defaulters();
}

	public function logout(){
		if (isset($this->session)) {
			$user_id = $this->session->userdata('user_id');
			if (!empty($user_id)) {
				$this->User_access_model->update($user_id, array('is_logged_in' => 'No'));
			}
			$this->session->sess_destroy();
		}

		redirect('auth/index');
	}

public function authenticate(){
	$this->ensure_session_loaded();
	$this->load->model('Sytem_date_model');
	$this->load->model('Access_model');

	$this->load->library('form_validation');
	$this->form_validation->set_rules('username', 'Username', 'required');
	$this->form_validation->set_rules('password', 'Password', 'required');



	$this->form_validation->set_error_delimiters('<span class="text-danger">', '</span>');
	if($this->form_validation->run() == FALSE) {
		$this->index();

	}else{
		$result = $this->User_access_model->login_user($this->input->post('username'),sha1($this->input->post('password')));
		if(!empty($result)){
				$sdate =$this->Sytem_date_model->get_active();
				$rand_id = rand(100, 9999);
				$this->session->set_userdata('rand_id',$rand_id);
				$this->session->set_userdata('username',$result['AccessCode']);
				$this->session->set_userdata('user_id',$result['Employee']);
				$this->session->set_userdata('Firstname',$result['Firstname']);
				$this->session->set_userdata('Lastname',$result['Lastname']);
				$this->session->set_userdata('RoleName',$result['RoleName']);
				$this->session->set_userdata('role',$result['Role']);
				$this->session->set_userdata('profile_photo',$result['profile_photo']);
				$this->session->set_userdata('stamp',$result['server_date']);
				$this->session->set_userdata('system_date',$sdate->s_date);

				$data=$this->Access_model->get_all_acces($this->session->userdata('role'));
				$this->session->set_userdata('access',$data);
				$this->User_access_model->update($result['Employee'],array('is_logged_in'=>$rand_id));
				$logger = array(

					'user_id' => $this->session->userdata('user_id'),
					'activity' => 'logged in the system'

				);
				log_activity($logger);
				$this->toaster->success('Success you have logged in successfully');
				redirect('admin/index');

		}else{
			$this->toaster->error('error','Sorry username or password is not correct');
			redirect('auth/index?error=1');
		}
	}

}

public function forgot_password()
{
	$this->ensure_session_loaded();
	$this->load->view('forgot_password', array(
		'message' => $this->session->flashdata('password_reset_message'),
		'error' => $this->session->flashdata('password_reset_error'),
	));
}

public function request_password_reset()
{
	$this->ensure_session_loaded();
	$this->form_validation->set_rules('identity', 'Username or email address', 'trim|required|max_length[200]');
	if ($this->form_validation->run() === FALSE) {
		return $this->forgot_password();
	}

	$identity = trim((string) $this->input->post('identity', TRUE));
	$user = $this->User_access_model->find_password_reset_user($identity);
	if (!$user) {
		$this->session->set_flashdata('password_reset_error', 'The account does not exist.');
		redirect('forgot-password');
		return;
	}
	if (!filter_var($user->EmailAddress, FILTER_VALIDATE_EMAIL)) {
		$this->session->set_flashdata('password_reset_error', 'This account does not have a valid registered email address. Please contact the administrator.');
		redirect('forgot-password');
		return;
	}

	$recent = $this->db->where('employee_id', (int) $user->Employee)
		->where('used_at IS NULL', null, false)
		->where('created_at >=', date('Y-m-d H:i:s', time() - 300))
		->count_all_results('password_reset_tokens');
	if ($recent > 0) {
		$this->session->set_flashdata('password_reset_error', 'A reset link was requested recently. Please wait five minutes before trying again.');
		redirect('forgot-password');
		return;
	}

	$token = bin2hex(random_bytes(32));
	$this->db->where('employee_id', (int) $user->Employee)
		->where('used_at IS NULL', null, false)
		->update('password_reset_tokens', array('used_at' => date('Y-m-d H:i:s')));
	$this->db->insert('password_reset_tokens', array(
		'employee_id' => (int) $user->Employee,
		'token_hash' => hash('sha256', $token),
		'expires_at' => date('Y-m-d H:i:s', time() + 3600),
		'requested_ip' => $this->input->ip_address(),
	));
	$resetTokenId = (int) $this->db->insert_id();

	$resetUrl = site_url('reset-password/' . rawurlencode($token));
	$name = trim($user->Firstname . ' ' . $user->Lastname);
	$message = '<p>A password reset was requested for your Finance Realm account.</p>'
		. '<p><a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">Reset your password</a></p>'
		. '<p>This link expires in one hour. If you did not request this change, ignore this email.</p>';
	if (!$this->send_password_reset_email($user->EmailAddress, $name, $message)) {
		$this->db->where('id', $resetTokenId)->update('password_reset_tokens', array('used_at' => date('Y-m-d H:i:s')));
		log_message('error', 'Password reset email failed for employee ID ' . (int) $user->Employee);
		$this->session->set_flashdata('password_reset_error', 'The password reset email could not be sent. Please try again later or contact the administrator.');
		redirect('forgot-password');
		return;
	}

	$this->session->set_flashdata('password_reset_message', 'A password reset link has been sent to your email address.');
	redirect('forgot-password');
}

public function reset_password($token = '')
{
	$this->ensure_session_loaded();
	$reset = $this->valid_password_reset_token($token);
	$this->load->view('reset_password', array(
		'token' => $token,
		'valid_token' => !empty($reset),
		'error' => $this->session->flashdata('password_reset_error'),
	));
}

public function reset_password_submit()
{
	$this->ensure_session_loaded();
	$token = trim((string) $this->input->post('token'));
	$reset = $this->valid_password_reset_token($token);
	if (!$reset) {
		$this->session->set_flashdata('password_reset_error', 'This password reset link is invalid, expired, or has already been used.');
		redirect('reset-password/' . rawurlencode($token));
		return;
	}

	$this->form_validation->set_rules('password', 'New password', 'required|min_length[8]|max_length[72]');
	$this->form_validation->set_rules('password_confirm', 'Confirm password', 'required|matches[password]');
	if ($this->form_validation->run() === FALSE) {
		$this->load->view('reset_password', array('token' => $token, 'valid_token' => true, 'error' => null));
		return;
	}

	$this->db->trans_start();
	$this->User_access_model->update_auth((int) $reset->employee_id, array(
		'Password' => sha1($this->input->post('password')),
		'is_logged_in' => 'No',
	));
	$this->db->where('id', (int) $reset->id)
		->where('used_at IS NULL', null, false)
		->update('password_reset_tokens', array('used_at' => date('Y-m-d H:i:s')));
	$this->db->trans_complete();

	if ($this->db->trans_status() === FALSE) {
		$this->session->set_flashdata('password_reset_error', 'The password could not be changed. Please try again.');
		redirect('reset-password/' . rawurlencode($token));
		return;
	}

	log_activity(array('user_id' => (int) $reset->employee_id, 'activity' => 'reset password using password recovery'));
	$this->toaster->success('Password changed successfully. You can now sign in.');
	redirect('auth/index');
}

private function valid_password_reset_token($token)
{
	if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
		return null;
	}
	return $this->db->where('token_hash', hash('sha256', $token))
		->where('used_at IS NULL', null, false)
		->where('expires_at >=', date('Y-m-d H:i:s'))
		->get('password_reset_tokens')->row();
}

private function send_password_reset_email($to, $recipientName, $message)
{
	$settings = get_by_id('settings', 'settings_id', '1');
	if (!$settings || empty($settings->email_host) || empty($settings->email_user)) {
		return false;
	}
	$config = array(
		'protocol' => $settings->protocal ?: 'smtp',
		'smtp_host' => $settings->email_host,
		'smtp_port' => $settings->email_port,
		'smtp_user' => $settings->email_user,
		'smtp_pass' => $settings->email_pass,
		'smtp_crypto' => ((string) $settings->email_port === '465') ? 'ssl' : 'tls',
		'mailtype' => 'html',
		'charset' => 'utf-8',
		'smtp_timeout' => 15,
		'newline' => "\r\n",
		'crlf' => "\r\n",
	);
	$this->load->library('email', $config);
	$this->email->from($settings->email_user, $settings->company_name);
	$this->email->to($to);
	$this->email->subject('Password reset');
	$this->email->message('<p>Hello ' . htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8') . ',</p>' . $message);
	$sent = (bool) $this->email->send();
	if (!$sent) {
		log_message('error', 'SMTP password reset failure: ' . strip_tags($this->email->print_debugger(array('headers'))));
	}
	return $sent;
}
}
