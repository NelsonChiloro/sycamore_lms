<?php


class Send_sms extends  CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}
	public function smssend(){
		$message = "Testing app";
		$URL = "https://206.225.81.36/ucm_api/index.php";
		$org_id=227;
		$user_id=486;
		$app_id=600240;
		$app_password= "!QAZ2wsx*";
		$date = new DateTime();

		$timestam =  $date->getTimestamp();
		$auth = md5($app_id.$timestam.$app_password);

		$payload = array(
			"reference"=>"msg_id_".rand(100,9999),
			"user_id"=>$user_id,
			"org_id"=>$org_id,
			"src_address"=>"KakuPay",
			"dst_address"=>"+265994099461",
			"message_type"=>"1",
			"auth_key"=>$auth,
			"message"=>$message,
			"app_id"=>$app_id,
			"operation"=>"send",
			"timestamp"=>$timestam
		);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $URL);
		curl_setopt($ch,  CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json'
		));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		$result = curl_exec ($ch);
		curl_close ($ch);
		$json = json_decode($result);


		echo $json;

	}
}
