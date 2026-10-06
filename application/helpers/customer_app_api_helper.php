<?php

class security_helper{

    var $client_services = "customer-app";
    var $auth_key       = "schicksalfirstborn1D";
    var $ci;

    public function __construct()
    {

        $this->ci = & get_instance();

    }


    function check_auth_client(){

        $client_service = $this->ci->input->get_request_header('Client-Service', TRUE);
        $auth_key  = $this->ci->input->get_request_header('Auth-Key', TRUE);

        if($client_service == 'customer-app' && $auth_key == "schicksalfirstborn1D"){
            return true;
        } else {
            return false;
        }
    }
    function auth()
    {
        $dbb = & get_instance();
        $dbb->load->database();
        $users_id  = $this->ci->input->get_request_header('User-ID', TRUE);
        $token     = $this->ci->input->get_request_header('Authorization', TRUE);
        $q=$dbb->db->query("SELECT *  FROM users_authentication WHERE users_id ='$users_id' AND token = '$token' ")->row();

        if(empty($q)){
            return array('status' => 401,'message' => 'Unauthorized.','user'=>$users_id,'toke'=>$token);
        } else {
            if($q->expired_at < date('Y-m-d H:i:s')){
                return array('status' => 401,'message' => 'Your session has been expired.');
            } else {
                $updated_at = date('Y-m-d H:i:s');
                $expired_at = date("Y-m-d H:i:s", strtotime('+12 hours'));
                $this->ci->db->where('users_id',$users_id)->where('token',$token)->update('users_authentication',array('expired_at' => $expired_at,'updated_at' => $updated_at));
                return array('status' => 200,'message' => 'Authorized.');
            }
        }
    }

}

function check_auth(){
    $securityHelper = new security_helper();
    return  $securityHelper->check_auth_client();
}
function check_auth_user(){
    $securityHelper = new security_helper();
    return  $securityHelper->auth();
}
