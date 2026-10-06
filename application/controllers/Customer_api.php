<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Customer_api extends  CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Customer_access_model');
        $this->load->model('Account_model');
        $this->load->library('form_validation');
    }
    public function savings_balance()
    {
        $res = array();
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method != 'GET') {
            json_output(400, array('status' => 400, 'message' => 'Bad request.'));
        } else {
            $check_auth_client = check_auth();
            if ($check_auth_client) {
                $response = check_auth_user();
                if ($response['status'] == 200) {
                    //$resp = $this->Branch_model->get_all();
                    $user_id = $this->input->get_request_header('USER-ID', TRUE);
                    $get = get_by_id('individual_customers','PhoneNumber',$user_id);
                    if (!empty($get->id)) {
                        $balance = $this->Account_model->get_customer_balance($get->id);
 if (!empty($balance)) {
     $res['status'] = 'success';
     $res['data'] = number_format($balance->balance, 2);
 }else{
     $res['status'] = 'error';
     $res['message'] = 'Sorry you do not have active savings account';
 }
                    } else {
                        $res['status'] = 'error';
                        $res['message'] = 'There is an error';
                    }
                    echo json_encode($res);
                }else{
                    echo json_encode($response);
                }
            }
        }
    }
    public function transfer()
    {
        $res = array();
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method != 'POST') {
            json_output(400, array('status' => 400, 'message' => 'Bad request.'));
        } else {
            $check_auth_client = check_auth();
            if ($check_auth_client) {
                $response = check_auth_user();
                if ($response['status'] == 200) {
                    //$resp = $this->Branch_model->get_all();
                    $user_id = $this->input->get_request_header('USER-ID', TRUE);
                    $recepient = $this->input->post('receiver');
                    $amount = $this->input->post('amount');
                    $get = get_by_id('individual_customers','PhoneNumber',$user_id);
                    if (!empty($get->id)) {
                        $balance = $this->Account_model->get_customer_balance($get->id);
 if (!empty($balance)) {
     $res['status'] = 'success';
     $res['data'] = number_format($balance->balance, 2);
 }else{
     $res['status'] = 'error';
     $res['message'] = 'Sorry you do not have active savings account';
 }
                    } else {
                        $res['status'] = 'error';
                        $res['message'] = 'There is an error';
                    }
                    echo json_encode($res);
                }else{
                    echo json_encode($response);
                }
            }
        }
    }

    public function login()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $res = array();

        if ($method != 'POST') {
            json_output(400, array('status' => 400, 'message' => 'Bad request.'));
        } else {

            $check_auth_client = check_auth();

            if ($check_auth_client) {
                $params = $_REQUEST;

                $username = $this->input->post('phone_number');
                $password = $this->input->post('password');




                    $response = $this->Customer_access_model->login($username, $password);

                    echo json_encode($response);
               

            } else {
                json_output(401, array('status' => 401, 'message' => 'App not authorised.'));
            }
        }

    }


}



?>
