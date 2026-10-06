<?php


class Migration extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('Individual_customers_model');
        $this->load->model('Corporate_customers_model');
        $this->load->model('Branches_model');
        $this->load->model('Loan_model');
        $this->load->model('Groups_model');
        $this->load->model('Customer_groups_model');
        $this->load->model('Account_model');
        $this->load->model('Geo_countries_model');
        $this->load->model('Proofofidentity_model');
		$this->load->model('Migration_model');
        $this->load->library('form_validation');
    }
    function  migrate_individual_customers()
    {
     $customers = get_all('newgroup');

     $c = 1;
     foreach ($customers as $customer){
         	$clientid = rand(10,100).time().$c;

         $data = array(
             'ClientId' => $clientid,
             'Title' => $customer->Title,
             'Firstname' => $customer->Firstname,
             'Middlename' => $customer->Middlename,
             'Lastname' => $customer->Lastname,
             'Gender' => $customer->Gender,
             'DateOfBirth' => $customer->dateofbirth,
             'PhoneNumber' => $customer->PhoneNumber,
             'Village' => $customer->Village,
             'TA' => $customer->TA,
             'City' => $customer->City,
             'Country' => $customer->Country,
             'Marital_status' => $customer->MarritalStatus,
             'ResidentialStatus' => $customer->ResidentialStatus,
             'Profession' => $customer->Profession,
             'SourceOfIncome' => $customer->SourceOfIncome,
             'GrossMonthlyIncome' => $customer->GrossMonthlyIncome,
//             'bankname'=>$customer->bankname,
//             'bank_branch'=>$customer->branch,
//             'banckAccountname'=>$customer->banckAccountname,
//             'accountNumber'=>$customer->accountNumber,
             'Branch' => 7,
             'added_by' => 69,
             'approval_status' => 'Approved',
             'CreatedOn' => $customer->disburseddate

         );


         $insert_id =  $this->Individual_customers_model->insert($data);

         $at = get_all_by_id('account','account_type','1');
         $ct = 1;
         foreach ($at as $cc){
             $ct ++;
         }
         $account = 500000+$ct;
         $data = array(
             'client_id' => $insert_id,
             'account_number' => $account,
             'balance' => 0,
             'account_type' => 1,
             'account_type_product' => 2,

             'added_by' => 69,

         );

         $this->Account_model->insert($data);

         $data = array(
             'IDType' => $customer->Idtype,
             'IDNumber' => $customer->IDnumber,
             'IssueDate' => $customer->issueddate,
             'ExpiryDate' => $customer->expiredate,
             'ClientId' => $clientid,
             'photograph' => "NULL",
             'signature' => "NULL",
             'Id_back' => "NULL",
             'id_front' => "NULL",
         );
         $this->Proofofidentity_model->insert($data);
$d = array(
    'customer_id'=>$insert_id
);
$this->Migration_model->update($customer->id,$d);
$c++;

     }

    }
    function  migrate_witness()
    {
     $customers = get_witness();

     $c = 1;
     foreach ($customers as $customer){
         	$clientid = rand(10,100).time().$c;

         $data = array(
             'ClientId' => $clientid,
             'Title' => $customer->Title_witness,
             'Firstname' => $customer->Firstname_witness,
             'Middlename' => $customer->Middlename_witness,
             'Lastname' => $customer->Lastname_witness,
             'Gender' => $customer->Gender_witness,
             'DateOfBirth' => $customer->dateofbirth_witness,
             'PhoneNumber' => $customer->PhoneNumber_phone_witness,
             'Village' => $customer->Village_witness,
             'TA' => $customer->TA_witness,
             'City' => $customer->City_winess,
             'Country' => $customer->Country_witness,
             'Marital_status' => $customer->MarritalStatus_witness,
			 'Branch' => 7,
             'added_by' => 69,
             'user_type' => 'witness',
             'approval_status' => 'Approved',
             'CreatedOn' => $customer->disburseddate

         );


         $insert_id =  $this->Individual_customers_model->insert($data);

         $at = get_all_by_id('account','account_type','1');
         $ct = 1;
         foreach ($at as $cc){
             $ct ++;
         }
         $account = 500000+$ct;
         $data = array(
             'client_id' => $insert_id,
             'account_number' => $account,
             'balance' => 0,
             'account_type' => 1,
             'account_type_product' => 2,

             'added_by' => 69,

         );

//         $this->Account_model->insert($data);

         $data = array(
             'IDType' => $customer->Idtype_witness,
             'IDNumber' => $customer->Idnumber_winess,
             'IssueDate' => $customer->issueddate_witness,
             'ExpiryDate' => $customer->expiredate_witness,
             'ClientId' => $clientid,
             'photograph' => "NULL",
             'signature' => "NULL",
             'Id_back' => "NULL",
             'id_front' => "NULL",
         );
         $this->Proofofidentity_model->insert($data);
$d = array(
    'witness_id'=>$insert_id
);
$this->Migration_model->update2($customer->Lastname_witness,$d);
$c++;

     }

    }
    function  migrate_relative()
    {
     $customers = get_relative();

     $c = 1;
     foreach ($customers as $customer){
         	$clientid = rand(10,100).time().$c;

         $data = array(
             'ClientId' => $clientid,
             'Title' => $customer->Title_relative,
             'Firstname' => $customer->Firstname_relative,
             'Middlename' => $customer->Middlename_relative,
             'Lastname' => $customer->Lastname_relative,
             'Gender' => $customer->Gender_relative,
             'DateOfBirth' => $customer->dateofbirth_relative,
             'PhoneNumber' => $customer->PhoneNumber_phone_relative,
             'Village' => $customer->Village_relative,
             'TA' => $customer->TA_relative,
             'City' => $customer->City_relative,
             'Country' => $customer->Country_relative,
             'Marital_status' => $customer->MarritalStatus_relative,
			 'Branch' => 7,
             'added_by' => 69,
             'user_type' => 'relative',
             'approval_status' => 'Approved',
             'CreatedOn' => $customer->disburseddate

         );


         $insert_id =  $this->Individual_customers_model->insert($data);

         $at = get_all_by_id('account','account_type','1');
         $ct = 1;
         foreach ($at as $cc){
             $ct ++;
         }
         $account = 500000+$ct;
         $data = array(
             'client_id' => $insert_id,
             'account_number' => $account,
             'balance' => 0,
             'account_type' => 1,
             'account_type_product' => 2,

             'added_by' => 69,

         );

//         $this->Account_model->insert($data);

         $data = array(
             'IDType' => $customer->Idtype_relative,
             'IDNumber' => $customer->Idnumber_relative,
             'IssueDate' => $customer->issueddate_relative,
             'ExpiryDate' => $customer->expiredate_relative,
             'ClientId' => $clientid,
             'photograph' => "NULL",
             'signature' => "NULL",
             'Id_back' => "NULL",
             'id_front' => "NULL",
         );
         $this->Proofofidentity_model->insert($data);
$d = array(
    'relative_id'=>$insert_id
);
$this->Migration_model->update3($customer->Lastname_relative,$d);
$c++;

     }

    }
    function format_dates (){
        $customers = get_all('newgroup');
        foreach ($customers as $customer) {
            $newDate = date("Y-m-d", strtotime($customer->loan_added_date));
            $disburseddate = date("Y-m-d", strtotime($customer->disburseddate));
//            $CreatedOnCustomer = date("Y-m-d", strtotime($customer->CreatedOnCustomer));
            $issueddate = date("Y-m-d", strtotime($customer->issueddate));
            $expiredate = date("Y-m-d", strtotime($customer->expiredate));
            $dateofbirth = date("Y-m-d", strtotime($customer->dateofbirth));
            $d = array(
                'loan_added_date'=>$newDate,
                'disburseddate'=>$disburseddate,
//                'CreatedOnCustomer'=>$CreatedOnCustomer,
                'issueddate'=>$issueddate,
                'expiredate'=>$expiredate,
                'dateofbirth'=>$dateofbirth,
            );
            $this->Migration_model->update($customer->id,$d);
        }
    }
    function remove_coma (){
        $customers = get_all('newgroup');
      $c =0;
        foreach ($customers as $customer) {

           $new_gloss= str_replace(',', '', $customer->GrossMonthlyIncome);
           $new_principal= str_replace(',', '', $customer->loan_principal);
           $group_loan_amount= str_replace(',', '', $customer->group_loan_amount);

            $d = array(
                'loan_principal'=>$new_principal,
                'GrossMonthlyIncome'=>$new_gloss,
                'group_loan_amount'=>$group_loan_amount,
            );
            $this->Migration_model->update($customer->id,$d);
            $c++;
        }
        echo $c;
    }
    function add_groups (){
        $customers = get_groups();
      $c =0;
        foreach ($customers as $customer) {

			$data = array(
				'group_code' =>$customer->LB,
				'group_name' => $customer->groupname,
				'branch' => 7,
				'group_description' => 'N/A',
				'file' => 'N/A',
				'group_added_by' => 69,
				'group_status'=>'Active'

			);

			$id = $this->Groups_model->insert($data);
			$d = array(
				'groupid'=> $id
			);
            $this->Migration_model->update4($customer->groupname,$d);

        }
        echo $c;
    }
    function add_group_member (){
        $customers = get_groups();
      $c =0;
        foreach ($customers as $customer) {
        	$members = get_all_by_id('newgroup','groupid', $customer->groupid);

        	foreach ($members as $m){
				$menu=array(
					'group_id'=>$m->groupid,
					'customer'=>$m->customer_id,

				);
				$this->Customer_groups_model->insert($menu);
			}



//			$at = get_all_by_id('account','account_type','1');
//			$ct = 1;
//			foreach ($at as $cc){
//				$ct ++;
//			}
//			$account = 500000+$ct;
//			$data = array(
//				'client_id' => $customer->LB,
//				'account_number' => $account,
//				'balance' => 0,
//				'account_type' => 1,
//				'account_type_product' => 2,
//
//				'added_by' => 69,
//
//			);
//
//			$this->Account_model->insert($data);

        }
        echo $c;
    }
function create_loan(){
    $customers = get_all_by_id('newgroup','rerun','Yes');
    foreach ($customers as $customer) {

        $this->Loan_model->add_loan($customer->loan_principal, $customer->loan_period , $customer->loan_product_id , $customer->disburseddate,$customer->customer_id,'individual','N/A','N/A',$customer->emp_id);

        $d = array(
            'processed'=>'Again'
                  );
        $this->Migration_model->update($customer->in_id,$d);
    }
}
function create_loan_group(){
    $customers = get_groups();
    foreach ($customers as $customer) {

        $this->Loan_model->add_loan($customer->group_loan_amount, $customer->loan_period , '6' , $customer->disburseddate,$customer->groupid,'group','N/A','N/A',69);

        $d = array(
            'processed'=>'yes'
                  );
        $this->Migration_model->update($customer->id,$d);
    }
}
function fdate (){
//    $date_str = 	$rowcofi->disbursed_date;
//    echo $date_str."<br>";
//    $datep='25/05/2021';
    $datep='04/25/2023';
    $newdate=date("Y-m-d",strtotime($datep));
    $dateofbirth = date("Y-m-d", strtotime($datep));
    echo $newdate."<br>";
    echo $dateofbirth."<br>";
}
    public function calculate_amortization_weeklyw($loan_amount, $interest_rate, $loan_term, $grace_period) {
        // Calculate the total number of payments, including grace period
        $num_payments = $loan_term + $grace_period;

        // Calculate the weekly interest rate
        $weekly_interest_rate = ($interest_rate / 100) / 52;

        // Calculate the payment amount
        $payment_amount = $loan_amount / $num_payments;

        // Calculate the interest amount for each payment
        $interest_amount = ($loan_amount * $weekly_interest_rate);

        // Calculate the principal amount for each payment
        $principal_amount = $payment_amount - $interest_amount;

        // Initialize the amortization schedule array
        $amortization_schedule = array();

        // Set the start date to the current date
        $start_date = date('Y-m-d');

        // Loop through each payment period and calculate the payment details
        for ($i = 1; $i <= $num_payments; $i++) {
            // Calculate the payment date
            $payment_date = date('Y-m-d', strtotime("+".($i-1+$grace_period)." week", strtotime($start_date)));

            // Check if the payment date falls on a weekend (Saturday or Sunday)
            $day_of_week = date('N', strtotime($payment_date));
            if ($day_of_week == 6) { // Saturday
                $payment_date = date('Y-m-d', strtotime("+2 days", strtotime($payment_date)));
            } elseif ($day_of_week == 7) { // Sunday
                $payment_date = date('Y-m-d', strtotime("+1 day", strtotime($payment_date)));
            }

            // Calculate the remaining loan balance
            $loan_balance = $loan_amount - (($i-$grace_period) * $payment_amount);

            // Calculate the interest and principal amounts for this payment
            if ($i <= $grace_period) {
                $interest_payment = 0;
                $principal_payment = 0;
            } else {
                $interest_payment = ($loan_balance * $weekly_interest_rate);
                $principal_payment = $payment_amount - $interest_payment;
            }

            // Add the payment details to the amortization schedule array
            $amortization_schedule[] = array(
                'payment_number' => $i,
                'payment_date' => $payment_date,
                'payment_amount' => $payment_amount,
                'interest_amount' => $interest_payment,
                'principal_amount' => $principal_payment,
                'loan_balance' => $loan_balance,
            );
        }

        // Return the amortization schedule
        return $amortization_schedule;
    }


    public function calculate_amortization_weekly($loan_amount, $interest_rate, $loan_term, $start_date) {
        // Calculate the total number of payments
        $num_payments = $loan_term;

        // Calculate the weekly interest rate
        $weekly_interest_rate = ($interest_rate / 100) / 52;

        // Calculate the payment amount
        $payment_amount = $loan_amount / $num_payments;

        // Calculate the interest amount for each payment
        $interest_amount = ($loan_amount * $weekly_interest_rate);

        // Calculate the principal amount for each payment
        $principal_amount = $payment_amount - $interest_amount;

        // Initialize the amortization schedule array
        $amortization_schedule = array();

        // Initialize the payment date to the given start date
        $payment_date = new DateTime($start_date);

        // Loop through each payment period and calculate the payment details
        for ($i = 1; $i <= $num_payments; $i++) {
            // Check if the payment date falls on a weekend (Saturday or Sunday)
            if ($payment_date->format('N') >= 6) {
                // If so, adjust the payment date to the next available weekday (Monday)
                $payment_date->modify('next monday');
            }

            // Calculate the remaining loan balance
            $loan_balance = $loan_amount - ($i * $payment_amount);

            // Calculate the interest and principal amounts for this payment
            $interest_payment = ($i == 1) ? $interest_amount : $interest_amount + ($loan_balance * $weekly_interest_rate);
            $principal_payment = $payment_amount - $interest_payment;

            // Add the payment details to the amortization schedule array
            $amortization_schedule[] = array(
                'payment_number' => $i,
                'payment_date' => $payment_date->format('Y-m-d'),
                'payment_amount' => $payment_amount,
                'interest_amount' => $interest_payment,
                'principal_amount' => $principal_payment,
                'loan_balance' => $loan_balance,
            );

            // Move the payment date to the next week
            $payment_date->modify('+1 week');
        }

        // Return the amortization schedule
        return $amortization_schedule;
    }

    function view(){
        $data['r'] = $this->calculate_amortization_weekly(1000, 6,10, '2023/04/09');
        $this->load->view('testv', $data);
    }
    function delete_wrong_loans(){
    	$re = delete_wrong_loans();
    	$count =0;
    	foreach ($re as $item){
//    		echo $item->loan_customer."- ".$item->loan_id;
			delete_record('loan', 'loan_id', $item->loan_id);
			delete_record('payement_schedules', 'loan_id', $item->loan_id);
			delete_record('account', 'account_number', $item->loan_number);
    		$count ++;

		}
echo  $count;
	}

}
