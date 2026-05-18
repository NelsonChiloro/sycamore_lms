<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Account_model extends CI_Model
{

    public $table = 'account';
    public $id = 'account_id';
    public $order = 'DESC';

    function __construct()
    {
        parent::__construct();
    }
    public function cash_transaction_modified($teller_account,$account,$amount,$mode,$transid,$paid_date, $type){

        if($mode=='deposit'){
            $teller = $this->db->select("balance")->from($this->table)->where('account_number',$teller_account)->get()->row();
            $customer = $this->db->select("balance")->from($this->table)->where('account_number',$account)->get()->row();
            $teller_balance = $teller->balance-$amount;
            $c_balance = $customer->balance+$amount;

            $this->db->where('account_number',$teller_account);
            $this->db->update($this->table,array('balance'=>$teller_balance));
            $this->db->where('account_number',$account);
            $this->db->update($this->table,array('balance'=>$c_balance));

            $tellert = array(
                'account_number'=>$teller_account,
                'transaction_id'=>$transid,
                'credit'=>0,
                'debit'=>$amount,
                'balance'=>$teller_balance,
                'system_time'=>$paid_date,
                'transaction_type'=> $type
            );
            $this->db->insert('transaction', $tellert);
            $vt = array(
                'account_number'=>$account,
                'transaction_id'=>$transid,
                'credit'=>$amount,
                'debit'=>0,
                'balance'=>$c_balance,
                'system_time'=>$paid_date,
                'transaction_type'=> $type

            );
            $this->db->insert('transaction', $vt);
            return 'success';

        }
        else{
            $teller = $this->db->select("balance")->from($this->table)->where('account_number',$teller_account)->get()->row();
            $customer = $this->db->select("balance")->from($this->table)->where('account_number',$account)->get()->row();
            $teller_balance = $teller->balance+$amount;
            $c_balance = $customer->balance-$amount;
            if($customer->balance < $amount){
                return 'customer';
            }elseif($teller->balance + $amount > 0){
                return 'teller';
            }else{
                $this->db->where('account_number',$teller_account);
                $this->db->update($this->table,array('balance'=>$teller_balance));
                $this->db->where('account_number',$account);
                $this->db->update($this->table,array('balance'=>$c_balance));

                $tellert = array(
                    'account_number'=>$teller_account,
                    'transaction_id'=>$transid,
                    'credit'=>$amount,
                    'debit'=>0,
                    'system_time'=>$paid_date,
                    'balance'=>$teller_balance,
                    'transaction_type'=> $type


                );
                $this->db->insert('transaction', $tellert);
                $vt = array(
                    'account_number'=>$account,
                    'transaction_id'=>$transid,
                    'credit'=>0,
                    'debit'=>$amount,
                    'system_time'=>$paid_date,
                    'balance'=>$c_balance,
                    'transaction_type'=> $type


                );
                $this->db->insert('transaction', $vt);
                return 'success';
            }

        }


    }
    // get all
    function get_all()
    {
        $this->db->order_by($this->id, $this->order);
        $this->db->select("*,savings_products.name as sname,employees.Firstname as fname, employees.Lastname as lname, individual_customers.Firstname as ifname, individual_customers.Lastname as ilname")->from($this->table)->join('individual_customers','individual_customers.id=account.client_id')->join('employees','employees.id=account.added_by')
			->join('account_types','account_types.account_type_id = account.account_type')
            ->join('savings_products','savings_products.saviings_product_id=account.account_type_product');
        return $this->db->get()->result();
    }

    function get_teller_accounts(){
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*")
			->from($this->table)
			->join('internal_accounts','internal_accounts.internal_account_id= account.client_id');
		$this->db->where('internal_accounts.is_cash_account','Yes');
		$this->db->where('account.is_teller','Yes');
		return $this->db->get()->result();

	}
	function get_cash_accounts(){
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*")
			->from($this->table)
			->join('internal_accounts','internal_accounts.internal_account_id= account.client_id');
		$this->db->where('internal_accounts.is_cash_account','Yes');
		$this->db->where('account.is_teller','No');
		return $this->db->get()->result();

	}
	function get_internalaccounts(){
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*")
			->from($this->table)
			->join('internal_accounts','internal_accounts.internal_account_id= account.client_id');
		$this->db->where('internal_accounts.is_cash_account','No');
		$this->db->where('account.is_teller','No');
		return $this->db->get()->result();

	}
	public function vault_to_teller($vault_account,$account,$amount){
		$transid ="TR-S".rand(100,9999).date('Y').date('m').date('d');
		$vault = $this->db->select("balance")->from($this->table)->where('account_number',$vault_account)->get()->row();
		$teller = $this->db->select("balance")->from($this->table)->where('account_number',$account)->get()->row();
		$teller_balance = $teller->balance+$amount;
		$v_balance = $vault->balance-$amount;
		if($teller_balance  > 0){
			return 'teller';
		}else {
			$this->db->where('account_number', $vault_account);
			$this->db->update($this->table, array('balance' => $v_balance));
			$this->db->where('account_number', $account);
			$this->db->update($this->table, array('balance' => $teller_balance));
			$tellert = array(
				'account_number' => $account,
				'transaction_id' => $transid,
				'credit' => $amount,
				'debit' => 0,
				'balance' => $teller_balance,

			);
			$this->db->insert('transaction', $tellert);
			$vt = array(
				'account_number' => $vault_account,
				'transaction_id' => $transid,
				'credit' => 0,
				'debit' => $amount,
				'balance' => $v_balance,

			);

			$this->db->insert('transaction', $vt);
			return 'success';
		}
	}
	public function teller_to_vault($vault_account,$account,$amount){
		$transid ="TR-S".rand(100,9999).date('Y').date('m').date('d');
		$vault = $this->db->select("balance")->from($this->table)->where('account_number',$vault_account)->get()->row();
		$teller = $this->db->select("balance")->from($this->table)->where('account_number',$account)->get()->row();
		$teller_balance = $teller->balance-$amount;
		$v_balance = $vault->balance+$amount;
		if($v_balance  > 0){
			return 'teller';
		}else {
			$this->db->where('account_number', $vault_account);
			$this->db->update($this->table, array('balance' => $v_balance));
			$this->db->where('account_number', $account);
			$this->db->update($this->table, array('balance' => $teller_balance));
			$tellert = array(
				'account_number' => $account,
				'transaction_id' => $transid,
				'credit' => $amount,
				'debit' => 0,
				'balance' => $teller_balance,

			);
			$this->db->insert('transaction', $tellert);
			$vt = array(
				'account_number' => $vault_account,
				'transaction_id' => $transid,
				'credit' => 0,
				'debit' => $amount,
				'balance' => $v_balance,

			);

			$this->db->insert('transaction', $vt);
			return 'success';
		}
	}
    // get data by id
    function get_by_id($id)
    {
        $this->db->where($this->id, $id);
        return $this->db->get($this->table)->row();
    }
    function get_customer_balance($id)
    {
        $this->db->where('client_id', $id);
        $this->db->where('account_type', '1');
        return $this->db->get($this->table)->row();
    }
    function get_account($id)
    {
        $this->db->where('account_number', $id);
        return $this->db->get($this->table)->row();
    }
    
    // get total rows
    function total_rows($q = NULL) {
        $this->db->like('account_id', $q);
	$this->db->or_like('client_id', $q);
	$this->db->or_like('account_number', $q);
	$this->db->or_like('balance', $q);
	$this->db->or_like('account_type', $q);
	$this->db->or_like('account_type_product', $q);
	$this->db->or_like('added_by', $q);
	$this->db->or_like('date_added', $q);
	$this->db->from($this->table);
        return $this->db->count_all_results();
    }

    // get data with limit and search
    function get_limit_data($limit, $start = 0, $q = NULL) {
        $this->db->order_by($this->id, $this->order);
        $this->db->like('account_id', $q);
	$this->db->or_like('client_id', $q);
	$this->db->or_like('account_number', $q);
	$this->db->or_like('balance', $q);
	$this->db->or_like('account_type', $q);
	$this->db->or_like('account_type_product', $q);
	$this->db->or_like('added_by', $q);
	$this->db->or_like('date_added', $q);
	$this->db->limit($limit, $start);
        return $this->db->get($this->table)->result();
    }

    // insert data
    function insert($data)
    {
        $this->db->insert($this->table, $data);
    }
    function transfer_funds($senderr, $recepientt,$amount, $transid){


            $sender = $this->db->select("balance")->from($this->table)->where('account_number',$senderr)->get()->row();
            $recepient = $this->db->select("balance")->from($this->table)->where('account_number',$recepientt)->get()->row();
            $sender_balance = $sender->balance-$amount;
            $c_balance = $recepient->balance+$amount;

            $this->db->where('account_number',$senderr);
            $this->db->update($this->table,array('balance'=>$sender_balance));

            $this->db->where('account_number',$recepientt);
            $this->db->update($this->table,array('balance'=>$c_balance));

            $tellert = array(
                'account_number'=>$senderr,
                'transaction_id'=>$transid,
                'credit'=>0,
                'debit'=>$amount,
                'balance'=>$sender_balance,
                'system_time'=>date('Y-m-d H:i:s'),

            );
            $this->db->insert('transaction', $tellert);
            $vt = array(
                'account_number'=>$recepientt,
                'transaction_id'=>$transid,
                'credit'=>$amount,
                'debit'=>0,
                'balance'=>$c_balance,
                'system_time'=>date('Y-m-d H:i:s'),

            );
            $this->db->insert('transaction', $vt);
            return 'success';
    }
    function transfer_funds1($senderr, $recepientt,$amount, $transid,$date){


        $sender = $this->db->select("balance")->from($this->table)->where('account_number',$senderr)->get()->row();
        $recepient = $this->db->select("balance")->from($this->table)->where('account_number',$recepientt)->get()->row();
        $sender_balance = $sender->balance-$amount;
        $c_balance = $recepient->balance+$amount;

        $this->db->where('account_number',$senderr);
        $this->db->update($this->table,array('balance'=>$sender_balance));

        $this->db->where('account_number',$recepientt);
        $this->db->update($this->table,array('balance'=>$c_balance));

        $tellert = array(
            'account_number'=>$senderr,
            'transaction_id'=>$transid,
            'credit'=>0,
            'debit'=>$amount,
            'balance'=>$sender_balance,
            'system_time'=>$date,

        );
        $this->db->insert('transaction', $tellert);
        $vt = array(
            'account_number'=>$recepientt,
            'transaction_id'=>$transid,
            'credit'=>$amount,
            'debit'=>0,
            'balance'=>$c_balance,
            'system_time'=>$date,

        );
        $this->db->insert('transaction', $vt);
        return 'success';
    }
	public function cash_transaction($teller_account,$account,$amount,$mode,$transid, $date){

		if($mode=='deposit'){
			$teller = $this->db->select("balance")->from($this->table)->where('account_number',$teller_account)->get()->row();
			$customer = $this->db->select("balance")->from($this->table)->where('account_number',$account)->get()->row();
			$teller_balance = $teller->balance-$amount;
			$c_balance = $customer->balance+$amount;

			$this->db->where('account_number',$teller_account);
			$this->db->update($this->table,array('balance'=>$teller_balance));
			$this->db->where('account_number',$account);
			$this->db->update($this->table,array('balance'=>$c_balance));

			$tellert = array(
				'account_number'=>$teller_account,
				'transaction_id'=>$transid,
				'credit'=>0,
				'debit'=>$amount,
				'balance'=>$teller_balance,
				'system_time'=>$date,

			);
			$this->db->insert('transaction', $tellert);
			$id = $this->db->insert_id();
			$vt = array(
				'account_number'=>$account,
				'transaction_id'=>$transid,
				'credit'=>$amount,
				'debit'=>0,
				'balance'=>$c_balance,
				'system_time'=>$date,

			);
			$this->db->insert('transaction', $vt);
			return $id;

		}else{
			$teller = $this->db->select("balance")->from($this->table)->where('account_number',$teller_account)->get()->row();
			$customer = $this->db->select("balance")->from($this->table)->where('account_number',$account)->get()->row();
			$teller_balance = $teller->balance+$amount;
			$c_balance = $customer->balance-$amount;
			if($customer->balance < $amount){
				return 'customer';
			}elseif($teller->balance + $amount > 0){
				return 'teller';
			}else{
				$this->db->where('account_number',$teller_account);
				$this->db->update($this->table,array('balance'=>$teller_balance));
				$this->db->where('account_number',$account);
				$this->db->update($this->table,array('balance'=>$c_balance));

				$tellert = array(
					'account_number'=>$teller_account,
					'transaction_id'=>$transid,
					'credit'=>$amount,
					'debit'=>0,

					'balance'=>$teller_balance,


				);
				$this->db->insert('transaction', $tellert);
				$id = $this->db->insert_id();
				$vt = array(
					'account_number'=>$account,
					'transaction_id'=>$transid,
					'credit'=>0,
					'debit'=>$amount,

					'balance'=>$c_balance,


				);
				$this->db->insert('transaction', $vt);
				return $id;
			}

		}


	}
	public  function search($search_code,$search_by,$group){
		$arr = array();
		if($group == 'customer'){
			if($search_by =='account'){
				$this->db->select("*,Firstname as name")->from('individual_customers')
                    ->join('account','account.client_id=individual_customers.id')
                    ->join('proofofidentity','proofofidentity.ClientId=individual_customers.ClientId')
                    ->join('account_types','account_types.account_type_id=account.account_type')
                ->where('account_number',$search_code);


				$r1 =	$this->db->get()->result();
				foreach ($r1 as $value){
					$arr[] = $value;
				}


				return  $arr;

			}
			else if($search_by =='name'){
				$this->db->select("*,Firstname as name")->from('individual_customers')->join('account','account.client_id=individual_customers.id')->join('proofofidentity','proofofidentity.ClientId=individual_customers.ClientId')->join('account_types','account_types.account_type_id=account.account_type');
				$this->db->like('Firstname', $search_code);
				$this->db->or_like('Middlename', $search_code);
				$this->db->or_like('Lastname', $search_code);

				$r1 =	$this->db->get()->result();
				foreach ($r1 as $value){
					$arr[] = $value;
				}

				return  $arr;

			}
		}if($group == 'group'){
			if($search_by =='account'){
				$this->db->select("*,group_name as name")->from('groups')->join('account','account.client_id=groups.group_id')->where('account_number',$search_code)->join('account_types','account_types.account_type_id=account.account_type');


				$r1 =	$this->db->get()->result();
				foreach ($r1 as $value){
					$arr[] = $value;
				}


				return  $arr;

			}
			else if($search_by =='name'){
                $this->db->select("*,group_name as name")->from('groups')->join('account','account.client_id=groups.group_id')->join('account_types','account_types.account_type_id=account.account_type');
                $this->db->like('group_name', $search_code);


				$r1 =	$this->db->get()->result();
				foreach ($r1 as $value){
					$arr[] = $value;
				}

				return  $arr;

			}
		}
		else{
			if($search_by =='account'){
				$this->db->select("*,name as name")->from('office_account')->join('accounts','accounts.ClientId=office_account.ClientId')->join('chart_of_accounts','chart_of_accounts.Code=accounts.ChartCode')->where('AccountNumber',$search_code);


				$r1 =	$this->db->get()->result();
				foreach ($r1 as $value){
					$arr[] = $value;
				}

				return  $arr;

			}
			else if($search_by =='name'){
				$this->db->select("*,name as name")->from('office_account')->join('accounts','accounts.ClientId=office_account.ClientId')->join('chart_of_accounts','chart_of_accounts.Code=accounts.ChartCode');
				$this->db->like('name', $search_code);
				$this->db->or_like('office_account.description', $search_code);


				$r1 =	$this->db->get()->result();
				foreach ($r1 as $value){
					$arr[] = $value;
				}


				return  $arr;

			}
		}


	}
	public  function search_name($search_code){


		$this->db->select("*,Firstname as name")->from('individual_customers')->join('accounts','accounts.ClientId=individual_customers.ClientId')->join('chart_of_accounts','chart_of_accounts.Code=accounts.ChartCode')->where('AccountNumber',$search_code);


		$r1 =	$this->db->get()->row();
		if(empty($r1)){
			$this->db->select("*,EntityName as name")->from('corporate_customers')->join('accounts','accounts.ClientId=corporate_customers.ClientId')->join('chart_of_accounts','chart_of_accounts.Code=accounts.ChartCode')->where('AccountNumber',$search_code);
			$r2 =	$this->db->get()->row();
			if(empty($r2)){
				$this->db->select("*,name as name")->from('office_account')->join('accounts','accounts.ClientId=office_account.ClientId')->join('chart_of_accounts','chart_of_accounts.Code=accounts.ChartCode')->where('AccountNumber',$search_code);
				$r3 =	$this->db->get()->row();
				return  $r3;

			}else{
				return  $r2;
			}
		}else{
			return $r1;
		}






	}
    // update data
    function update($id, $data)
    {
        $this->db->where($this->id, $id);
        $this->db->update($this->table, $data);
    }
    function update_vault($id)
    {
    	$data = array(
    		'is_vault'=>'No'
		);

        $this->db->update($this->table, $data);
		$data2 = array(
			'is_vault'=>'Yes'
		);
		$this->db->where('account_number',$id);
		$this->db->update($this->table, $data2);
    }
public function update_approval($id,$data){
    $this->db->where('client_id',$id);
    $this->db->update($this->table, $data);
}
    // delete data
    function delete($id)
    {
        $this->db->where($this->id, $id);
        $this->db->delete($this->table);
    }

}


