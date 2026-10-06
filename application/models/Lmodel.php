<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Lmodel extends CI_Model
{

	public $table = 'loan';
	public $table_d = array('loan', 'transactions', 'payement_schedules');
	public $id = 'loan_id';
	public $order = 'DESC';

	function __construct()
	{
		parent::__construct();
	}
	function calculate($amount, $months, $loan_id, $loan_date)
	{
		//get loan parameters
		$this->db->where('loan_product_id', $loan_id);
		$loan = $this->db->get('loan_products')->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case '2 Weeks':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}
		$amount_interest = $amount *( ($loan->interest/100)*12);


		//total payments applying interest
		$amount_total = $amount + $amount_interest * $months * $divisor;

		//payment per term
		$amount_term = number_format(round($amount / ($months * $divisor), 2) + $amount_interest, 2, '.', '');

		$date = $loan_date;
		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


		//interest

		$i = ($loan->interest / 100) * 12;
		$af = ($loan->admin_fees / 100) * 12;
		$lc = ($loan->loan_cover / 100) * 12;
		$total_deduction = $i + $af + $lc;


		$monthly_payment = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$monthly_payment1 = $amount * ($total_deduction / 12) * pow((1 + $total_deduction / 12), $months) / (pow((1 + $total_deduction / 12), $months) - 1);
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$total_interest = 0;
		$total_interest1 = 0;
		$total_admin_fees = 0;
		$total_admin_fees1 = 0;
		$total_loan_cover = 0;
		$total_loan_cover1 = 0;


		while ($current_balance1 > 0) {
			//create rows


			$towards_interest1 = ($i / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards interest
			$towards_fees = ($af / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees
			$towards_lc = ($lc / 12) * $current_balance1;  //this calculates the portion of your monthly payment that goes towards administration fees

			if ($monthly_payment1 > $current_balance1) {
				$monthly_payment1 = $current_balance1 + $towards_interest1 + $towards_fees+ $towards_lc;

				$towards_balance1 = $monthly_payment1 - ($towards_interest1 + $towards_fees + $towards_lc);
				$total_interest1 = $total_interest1 + $towards_interest1;
				$total_admin_fees = $total_admin_fees + $towards_fees;
				$total_loan_cover = $total_loan_cover + $towards_lc;
				$current_balance1 = $current_balance1 - $towards_balance1;


			}

			//Loan info
			$table = '<div id="calculator"><h3>Loan Info</h3>';
			$table = $table . '<table border="1" class="table">';
			$table = $table . '<tr><td>Loan Name:</td><td>' . $loan->product_name . '</td></tr>';
			$table = $table . '<tr><td>Interest:</td><td>' . $loan->interest . '%</td></tr>';
			$table = $table . '<tr><td>Admin Fee %:</td><td>' . $loan->admin_fees . '%</td></tr>';
			$table = $table . '<tr><td>Loan cover %:</td><td>' . $loan->loan_cover . '%</td></tr>';
			$table = $table . '<tr><td>Terms:</td><td>' . $months . '</td></tr>';
			$table = $table . '<tr><td>Frequency:</td><td>Every ' . $loan->frequency . ' days</td></tr>';
			$table = $table . '</table>';
			$table = $table . '<h3>Computation</h3>';
			$table = $table . '<table>';
			$table = $table . '<tr><td>Loan Amount:</td><td> ' . $this->config->item('currency_symbol') . number_format($amount, 2, '.', ',') . '</td></tr>';
//        $table = $table . '<tr><td>Interest per First Month:</td><td> '.$this->config->item('currency_symbol') . $amount*$i.'</td></tr>';
			$table = $table . '<tr><td>Total interest:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_interest1), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Admin fee:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_admin_fees), 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Loan cover:</td><td> ' . $this->config->item('currency_symbol') . number_format(($total_loan_cover), 2) . '</td></tr>';
			$table = $table . '<tr><td>Amount Per Term:</td><td> ' . $this->config->item('currency_symbol') . number_format($monthly_payment1, 2) . '</td></tr>';
			$table = $table . '<tr><td>Total Payment:</td><td> ' . $this->config->item('currency_symbol') . number_format($total_interest1 + $amount + $total_admin_fees + $towards_lc, 2, '.', ',') . '</td></tr>';
			$table = $table . '</table>';

			//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


			$table = $table . '<table class="table" >

				<tr>
					<th width="30" align="center"><b>Pmt</b></th>
					<th width="60" align="center"><b>Payment</b></th>
					<th width="60" align="center"><b>Principal</b></th>
					<th width="60" align="center"><b>Interest</b></th>
					
					<th width="85" align="center"><b>Interest Paid</b></th>
					<th width="60" align="center"><b>Admin Fee</b></th>
					<th width="60" align="center"><b>Admin Fee Paid</b></th>
						<th width="60" align="center"><b>Loan cover</b></th>
					<th width="60" align="center"><b>Lona cover Paid</b></th>
					<th width="70" align="center"><b>Balance</b></th>
				</tr>	
			';


			$table = $table . "<tr>";
			$table = $table . "<td width='30'>0</td>";
			$table = $table . "<td width='60'>&nbsp;</td>";
			$table = $table . "<td width='60'>&nbsp;</td>";
			$table = $table . "<td width='60'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='85'>&nbsp;</td>";
			$table = $table . "<td width='70'>" . round($amount, 2) . "</td>";
			$table = $table . "</tr>";

			while ($current_balance > 0) {
				//create rows


				$towards_interest = ($i / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_fees1 = ($af / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest
				$towards_lc1 = ($lc / 12) * $current_balance;  //this calculates the portion of your monthly payment that goes towards interest

				if ($monthly_payment > $current_balance) {
					$monthly_payment = $current_balance + $towards_interest + $towards_fees1 + $towards_lc1;
				}


				$towards_balance = $monthly_payment - ($towards_interest + $towards_fees1 + $towards_lc1);
				$total_interest = $total_interest + $towards_interest;
				$total_admin_fees1 = $total_admin_fees1 + $towards_fees1;
				$total_loan_cover1 = $total_loan_cover1 + $towards_lc1;
				$current_balance = $current_balance - $towards_balance;


				// display row

				$table = $table . "<tr class='table_info'>";
				$table = $table . "<td>" . $payment_counter . "</td>";
				$table = $table . "<td>" . round($monthly_payment, 2) . "</td>";
				$table = $table . "<td>" . round($towards_balance, 2) . "</td>";
				$table = $table . "<td>" . round($towards_interest, 2) . "</td>";
				$table = $table . "<td>" . round($total_interest, 2) . "</td>";
				$table = $table . "<td>" . round($towards_fees1, 2) . "</td>";
				$table = $table . "<td>" . round($total_admin_fees1, 2) . "</td>";
				$table = $table . "<td>" . round($towards_lc1, 2) . "</td>";
				$table = $table . "<td>" . round($total_loan_cover1, 2) . "</td>";
				$table = $table . "<td>" . round($current_balance, 2) . "</td>";
				$table = $table . "</tr>";


				$payment_counter++;


			}

			$table = $table . '</table></div>';

			return $table;
		}
	}
	function add_loan($lamount, $lmonths, $product_id, $ldate,$loan_customer,$worthness_file,$narration,$added_by)
	{
		//set Time Zone
		//date_default_timezone_set('Africa/Blantyre');
		$this->db->select('MAX(counter) as max_c');
		$lid = $this->db->get('loan');
		$result = $lid->row();
		$loanid='AL'.date("Ymd").(100+$result->max_c);
		$fcounter=$result->max_c+1;
		$amount = $lamount;
		$loan_date = $ldate;
		$months = $lmonths;
		//get loan parameters
		$loan = $this->db->select("*")->from('loan_products')->where('loan_product_id',$product_id)->get()->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case '2 Weeks':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}

		//interest
		$amount_interest = $amount *( ($loan->interest/100)*12);


		//total payments applying interest
		$amount_total = $amount + $amount_interest * $months * $divisor;

		//payment per term
		$amount_term = number_format(round($amount / ($months * $divisor), 2) + $amount_interest, 2, '.', '');

		$date = $loan_date;
		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);

		$i=($loan->interest/100)*12;

		$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);
		$monthly_payment1 = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$ii=1;
		$total_interest = 0;
		$total_interest1=0;




		while($current_balance1 > 0) {
			//create rows


			$towards_interest1 = ($i/12)*$current_balance1;
			//this calculates the portion of your monthly payment that goes towards interest

			if ($monthly_payment1 > $current_balance1){
				$monthly_payment1 = $current_balance1 + $towards_interest1;
			}


			$towards_balance1 = $monthly_payment1 - $towards_interest1;
			$total_interest1 = $total_interest1 + $towards_interest1;
			$current_balance1 = $current_balance1 - $towards_balance1;

		}



		//additional info to be insert


		$data = array(
			'loan_number'=>$loanid,
			'loan_product'=>$product_id,
			'loan_customer'=>$loan_customer,
			'loan_date'=>$loan_date,
			'loan_principal'=>$lamount,
			'loan_period'=>$lmonths,
			'worthness_file'=>$worthness_file,
			'narration'=>$narration,
			'period_type'=> $loan->frequency,
			'loan_amount_term' => $monthly_payment,
			'loan_interest'=> $loan->interest,
			'loan_interest_amount'=> $amount*($loan->interest/100),
			'loan_amount_total'=> $total_interest1+$amount,
			'next_payment_id'=>1,
			'loan_added_by'=>$added_by,
			'counter'=>$fcounter
		);
		$this->db->insert($this->table,$data);


		//borrower_loan_id
		$id = $this->db->insert_id();

		//insert each payment records to lend_payments
		if($loan->frequency == '2 Weeks') {
			$date = $loan_date;
			$frequency = $months*2;
			$start_day = 0;
			$loan_day = date('d', strtotime($date));
			$loan_month = date('m', strtotime($date));

			//get first payment day if 15 or 30
			if($loan_day >= 15) {
				if($loan_month == '02') {
					$start_day = 28;
				} else {
					$start_day = 30;
				}
			} elseif($loan_day == 30 OR $loan_day > 15) {
				$start_day = 15;
			} else {
				$start_day = 15;
			}

			$date = date('Y/m/'.$start_day, strtotime($date));
			for ($i=1; $i<=$frequency; $i++) {

				$this->db->insert(
					'payement_schedules', array(

						'customer' => $loan_customer,
						'loan_id' => $id,
						'payment_schedule' => $date,
						'payment_number' => $i,
						'amount' => $monthly_payment1,
						'principal' => $towards_balance1,
						'interest' => $total_interest1,
						'paid_amount' => 0,
						'loan_balance' => $current_balance1,
						'loan_date' => $loan_date,

					)
				);

				$day = date('d', strtotime($date));
				if($day == 15) {
					//check if February
					if(date('m', strtotime($date)) == '02') {
						$date = date('Y/02/28', strtotime($date));
					} else {
						$date = date('Y/m/30', strtotime($date));
					}
				} elseif($day == 30 OR $day > 15) {
					//check if January, going to February
					if(date('m', strtotime($date)) == '01') {
						$date = date('Y/02/15', strtotime('+1 month', strtotime($date)));
					} else {
						$date = date('Y/m/15', strtotime('+1 month', strtotime($date)));
					}
				}

			}
		} else {
			while ($current_balance > 0)
			{

				$towards_interest = ($i/12)*$current_balance;  //this calculates the portion of your monthly payment that goes towards interest

				if ($monthly_payment > $current_balance){
					$monthly_payment = $current_balance + $towards_interest;
				}


				$towards_balance = $monthly_payment - $towards_interest;
				$total_interest = $monthly_payment - $towards_balance;
				$current_balance = $current_balance - $towards_balance;


				$frequency = $days * $ii;
				$newdate = strtotime ('+'.$frequency.' day', strtotime ($date)) ;

				//check if payment date landed on weekend
				//if Sunday, make it Monday. If Saturday, make it Friday
				if(date ('D', $newdate) == 'Sun') {
					$newdate = strtotime('+1 day', $newdate) ;
				} elseif(date('D', $newdate) == 'Sat') {
					$newdate = strtotime('-1 day', $newdate) ;
				}

				$newdate = date('Y-m-d', $newdate );

				$this->db->insert(
					'payement_schedules', array(

						'customer' => $loan_customer,
						'loan_id' => $id,
						'payment_schedule' => $newdate,
						'payment_number' => $ii,
						'amount' => $monthly_payment,
						'principal' => $towards_balance,
						'interest' => $total_interest,
						'paid_amount' => 0,
						'loan_balance' => $current_balance,
						'loan_date' => $loan_date,

					)
				);


				//$table = $table . '<tr><td>'.$i.'</td><td>'.$amount_term.'</td><td>'.$newdate.'</td></tr>';
				$ii ++;
			}

		}

		//get next payment id and insert to lend_borrower_loans.next_payment_id
//		$payment = $this->Loan_model->next_payment($id);
//		$this->db->update('lend_borrower_loans', array('next_payment_id' => $payment->id), array('id' => $id));
		$data_account = array(
			'client_id' => $loan_customer,
			'account_number' => $loanid,
			'balance' => 0,
			'account_type' => 2,
			'account_type_product' => $product_id,


		);

		$this->db->insert('account',$data_account);
		return true;
	}
	function add_loans($amount, $months, $loan_id, $loan_date,$loan_customer)
	{


		//get loan parameters
		$this->db->where('loan_product_id',$loan_id);
		$loan = $this->db->get('loan_products')->row();

		//divisor
		switch ($loan->frequency) {
			case 'Monthly':
				$divisor = 1;
				$days = 30;
				break;
			case '2 Weeks':
				$divisor = 2;
				$days = 15;
				break;
			case 'Weekly':
				$divisor = 4;
				$days = 7;
				break;
		}

		//interest
		$amount_interest = $amount * ($loan->interest/100)/$divisor;

		//total payments applying interest
		$amount_total = $amount + $amount_interest * $months * $divisor;

		//payment per term
		$amount_term = number_format(round($amount / ($months * $divisor), 2) + $amount_interest, 2, '.', ',');


		$date = $loan_date;
		$i=($loan->interest/100)*12;


		$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);
		$monthly_payment1 = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);
		$current_balance = $amount;
		$current_balance1 = $amount;
		$payment_counter = 1;
		$total_interest = 0;
		$total_interest1=0;




		while($current_balance1 > 0) {
			//create rows


			$towards_interest1 = ($i/12)*$current_balance1;  //this calculates the portion of your monthly payment that goes towards interest

			if ($monthly_payment1 > $current_balance1){
				$monthly_payment1 = $current_balance1 + $towards_interest1;
			}


			$towards_balance1 = $monthly_payment1 - $towards_interest1;
			$total_interest1 = $total_interest1 + $towards_interest1;
			$current_balance1 = $current_balance1 - $towards_balance1;

		}

		//Loan info
		$table = '<div id="calculator"><h3>Loan Info</h3>';
		$table = $table . '<table border="1" class="table">';
		$table = $table . '<tr><td>Loan Name:</td><td>'.$loan->product_name.'</td></tr>';
		$table = $table . '<tr><td>Interest:</td><td>'.$loan->interest.'%</td></tr>';
		$table = $table . '<tr><td>Terms:</td><td>'.$months.'</td></tr>';
		$table = $table . '<tr><td>Frequency:</td><td>Every '.$loan->frequency.' days</td></tr>';
		$table = $table . '</table>';
		$table = $table . '<h3>Computation</h3>';
		$table = $table . '<table>';
		$table = $table . '<tr><td>Loan Amount:</td><td> '.$this->config->item('currency_symbol') . number_format($amount, 2, '.', ',').'</td></tr>';
//        $table = $table . '<tr><td>Interest per First Month:</td><td> '.$this->config->item('currency_symbol') . $amount*$i.'</td></tr>';
//		$table = $table . '<tr><td>Interest per Term:</td><td> '.$this->config->item('currency_symbol') . $amount_interest.'</td></tr>';
		$table = $table . '<tr><td>Amount Per Term:</td><td> '.$this->config->item('currency_symbol') . round($monthly_payment,2).'</td></tr>';
		$table = $table . '<tr><td>Total Payment:</td><td> '.$this->config->item('currency_symbol') . number_format($total_interest1+$amount, 2, '.', ',').'</td></tr>';
		$table = $table . '</table>';

		//$monthly_payment = $amount*($i/12)*pow((1+$i/12),$months)/(pow((1+$i/12),$months)-1);


		$table = $table . '<table class="table" cellpadding="15" >
				<tr>
					<td width="30" align="center"><b>Pmt</b></td>
					<td width="60" align="center"><b>Payment</b></td>
					<td width="60" align="center"><b>Principal</b></td>
					<td width="60" align="center"><b>Interest</b></td>
					<td width="85" align="center"><b>Interest Paid</b></td>
					<td width="70" align="center"><b>Balance</b></td>
				</tr>	
			</table>';

		$table = $table ."<table  class='table' cellpadding='15' ";

		$table = $table ."<tr>";
		$table = $table . "<td width='30'>0</td>";
		$table = $table . "<td width='60'>&nbsp;</td>";
		$table = $table . "<td width='60'>&nbsp;</td>";
		$table = $table . "<td width='60'>&nbsp;</td>";
		$table = $table . "<td width='85'>&nbsp;</td>";
		$table = $table . "<td width='70'>".round($amount,2)."</td>";
		$table = $table . "</tr>";
		$data = array(
			'loan_number'=>rand(100,9999),
			'loan_product'=>$loan_id,
			'loan_customer'=>$loan_customer,
			'loan_date'=>$loan_date,
			'loan_principal'=>$amount,
			'loan_period'=>$months,
			'period_type'=> $loan->frequency,
			'loan_interest'=> $loan->interest,
			'loan_amount_total'=> $total_interest1+$amount,
			'next_payment_id'=>1,
			'loan_added_by'=>$this->session->userdata('user_id')
		);
		$this->db->insert($this->table,$data);
		$lid= $this->db->insert_id();
		while($current_balance > 0) {
			//create rows


			$towards_interest = ($i/12)*$current_balance;  //this calculates the portion of your monthly payment that goes towards interest

			if ($monthly_payment > $current_balance){
				$monthly_payment = $current_balance + $towards_interest;
			}


			$towards_balance = $monthly_payment - $towards_interest;
			$total_interest = $total_interest + $towards_interest;
			$current_balance = $current_balance - $towards_balance;


			// display row

			$table = $table . "<tr class='table_info'>";
			$table = $table . "<td>".$payment_counter."</td>";
			$table = $table ."<td>".round($monthly_payment,2)."</td>";
			$table = $table . "<td>".round($towards_balance,2)."</td>";
			$table = $table . "<td>".round($towards_interest,2)."</td>";
			$table = $table ."<td>".round($total_interest,2)."</td>";
			$table = $table ."<td>".round($current_balance,2)."</td>";
			$table = $table . "</tr>";

			$schedules = array(

				'customer' => $loan_customer,
				'loan_id' => $lid,
				'payment_schedule' => $this->input->post('payment_schedule',TRUE),
				'payment_number' => $payment_counter,
				'amount' => $monthly_payment,
				'principal' => $towards_balance,
				'interest' => $total_interest,
				'paid_amount' => 0,
				'loan_balance' => $current_balance,
				'loan_date' => $loan_date,

			);
			$payment_counter++;


		}

		$table = $table . '</table></div>';


		return true;
	}
	// get all
	function get_all($status)
	{

		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');
		if($status !=""){
			$this->db->where('loan_status',$status);
		}
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function  get_all2(){
		$this->db->select("*")
			->from($this->table);
		$this->db->where('loan_status',"CLOSED");
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function get_all_mod($status)
	{

		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');
		if($status !=""){
			$this->db->where('loan_status',$status);
//			$this->db->where('written_off_by !=', NULL);
			$this->db->where('written_off_by is NOT NULL', NULL, FALSE);
		}
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function get_disbursed()
	{

		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('disbursed','Yes');

		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}

	function track_individual($user)
	{
		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');
		if($user !=""){
			$this->db->where('loan_added_by',$user);
		}
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function loan_user($id)
	{
		$this->db->select("*")
			->from($this->table)

			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('loan_id',$id);

		return $this->db->get()->row();
	}
	public function sum_loans($from ,$to){
		$this->db->select('SUM(loan_principal) as total');
		$this->db->from('loan');
//	$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');
		$this->db->where('disbursed','Yes');
		$this->db->where('loan_status','ACTIVE');
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
		return $this->db->get()->row();
	}
	public  function update_defaulters(){
		$get_days = check_exist_in_table('settings','settings_id ',1);
		$this->db->select("*")
			->from($this->table);
		$r = $this->db->get()->result();
		foreach ($r as $m){
			$this->db->select_max('payment_schedule')
				->from('payement_schedules')
				->where('loan_id',$m->loan_id);
			$result = $this->db->get()->row();
			$date=	date('Y-m-d', strtotime($result->payment_schedule. ' + '.$get_days->defaulter_durations.' days'));
//		echo $result->payment_schedule.' '.$date;
//		echo "<br>";
			if($date < date('Y-m-d')){
//				echo $result->payment_schedule.' '.$date;

				$this->db->where('loan_id',$m->loan_id)
					->update('loan',array('loan_status'=>'DEFAULTED'));
			}
		}


	}
	public function count_disbursed_loans($from,$to){
		$this->db->select('*');
		$this->db->from('loan');
		$this->db->where('disbursed','Yes');
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
		return $this->db->count_all_results();
	}
	public function sum_total($from,$to){
		$this->db->select('*,loan.loan_principal as lm');
		$this->db->from('loan');
		$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
		// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');
		$this->db->where('disbursed','Yes');
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}

		return $this->db->get()->result();
	}public function sum_total_par(){
	$this->db->select('*,loan.loan_principal as lm');
	$this->db->from('loan');
	$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
	// $this->db->join('lend_payments','lend_payments.borrower_loan_id=lend_borrower_loans.id');
	$this->db->where('disbursed','Yes');


	return $this->db->get()->result();
}
	public function sum_total2($q){
		if(!empty($q)){
			$this->db->select('*,loan.loan_principal as lm');
			$this->db->from('loan');
			$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
			$this->db->where('loan_status','ACTIVE');
		}else{
			$this->db->select('*,loan.loan_principal as lm');
			$this->db->from('loan');
			$this->db->join('payement_schedules','payement_schedules.loan_id=loan.loan_id');
			$this->db->where('loan_status','ACTIVE');

		}


		return $this->db->get()->result();
	}
	function get_summaryu($user)
	{

		$query = $this->db->query('
			SELECT
			 AA.loan_id as loan_id,AA.loan_number as lnumber,AA.loan_principal as lm,BB.id as bid,BD.Firstname,BD.Lastname,
			 ROUND(IFNULL((SELECT sum(amount) FROM payement_schedules WHERE customer = AA.loan_customer AND status = "PAID" AND payment_schedule <= DATE(NOW()) ),0),2) AS t_payment,
			 ROUND(IFNULL((SELECT sum(amount) FROM payement_schedules WHERE customer = AA.loan_customer AND status = "NOT PAID" AND payment_schedule <= DATE(NOW()) ),0),2) AS u_payment,
			  (SELECT MIN(payment_schedule) FROM payement_schedules WHERE customer = AA.loan_customer AND status = "NOT PAID" AND payment_schedule <= DATE(NOW()) ) AS max_d,
		   
			 ROUND(IFNULL((SELECT sum(amount) FROM payement_schedules WHERE customer = AA.loan_customer AND status = "NOT PAID" ),0),2) AS t_balance,
			 ROUND(IFNULL((SELECT sum(principal) FROM payement_schedules WHERE customer = AA.loan_customer AND status = "NOT PAID" AND payment_schedule <= DATE(NOW()) ),0),2) AS t_principal,
			ROUND(IFNULL((SELECT sum(interest) FROM payement_schedules WHERE customer = AA.loan_customer AND status = "NOT PAID" AND payment_schedule <= DATE(NOW()))  ,0),2) AS t_interest,
			 CC.loan_id as is_due, CC.loan_status as l_state
		   FROM loan AS AA
		   
		   
			 INNER JOIN payement_schedules AS BB
			   ON AA.loan_id = BB.loan_id
				INNER JOIN individual_customers AS BD
				ON BB.customer = BD.id
			 LEFT JOIN (SELECT a.* FROM loan a INNER JOIN payement_schedules b ON a.loan_id = b.loan_id	WHERE payment_schedule <= DATE(NOW()) AND a.loan_status = \'ACTIVE\' AND b.status = \'NOT PAID\' GROUP BY a.loan_id) as CC
			   ON CC.loan_id = AA.loan_id
			   
			   
		   GROUP BY AA.loan_id
		   ORDER BY AA.loan_id
		');



		if ($query->num_rows() > 0)
		{
			return $query->result();
		}

		return FALSE;
	}
	function get_filter($user,$product,$status,$from,$to)
	{

		$this->db->select("*,employees.Firstname as efname, employees.Lastname as elname,individual_customers.Firstname as cfname,individual_customers.Lastname as clname,individual_customers.id as cid")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer')
			->join('employees','employees.id = loan.loan_added_by');
		if($status !="All"){
			$this->db->where('loan_status',$status);
		}
		if($user !="All"){
			$this->db->where('loan_added_by',$user);
		}
		if($product !="All"){
			$this->db->where('loan_product',$product);
		}
		if($from !="" && $to !=""){
			$this->db->where('loan_added_date BETWEEN "'. date('Y-m-d', strtotime($from)). '" and "'. date('Y-m-d', strtotime($to)).'"');

		}
		$this->db->order_by('loan.loan_id', 'DESC');
		return $this->db->get()->result();
	}
	function get_user_loan($id)
	{
		$this->db->order_by($this->id, $this->order);
		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');

		$this->db->where('loan_customer',$id);


		return $this->db->get()->result();
	}

	// get data by id
	function get_by_id($id)
	{
		$this->db->select("*")
			->from($this->table)
			->join('loan_products','loan_products.loan_product_id =loan.loan_product')
			->join('individual_customers','individual_customers.id = loan.loan_customer');
//			->join('employees','employees.id = loan.loan_added_by');
		$this->db->where($this->id, $id);
		return $this->db->get()->row();
	}

	// get total rows
	function total_rows($q = NULL) {
		$this->db->like('loan_id', $q);
		$this->db->or_like('loan_number', $q);
		$this->db->or_like('loan_product', $q);
		$this->db->or_like('loan_customer', $q);
		$this->db->or_like('loan_date', $q);
		$this->db->or_like('loan_principal', $q);
		$this->db->or_like('loan_period', $q);
		$this->db->or_like('period_type', $q);
		$this->db->or_like('loan_interest', $q);
		$this->db->or_like('loan_amount_total', $q);
		$this->db->or_like('next_payment_id', $q);
		$this->db->or_like('loan_added_by', $q);
		$this->db->or_like('loan_approved_by', $q);
		$this->db->or_like('loan_status', $q);
		$this->db->or_like('loan_added_date', $q);
		$this->db->from($this->table);
		return $this->db->count_all_results();
	}

	// get data with limit and search
	function get_limit_data($limit, $start = 0, $q = NULL) {
		$this->db->order_by($this->id, $this->order);
		$this->db->like('loan_id', $q);
		$this->db->or_like('loan_number', $q);
		$this->db->or_like('loan_product', $q);
		$this->db->or_like('loan_customer', $q);
		$this->db->or_like('loan_date', $q);
		$this->db->or_like('loan_principal', $q);
		$this->db->or_like('loan_period', $q);
		$this->db->or_like('period_type', $q);
		$this->db->or_like('loan_interest', $q);
		$this->db->or_like('loan_amount_total', $q);
		$this->db->or_like('next_payment_id', $q);
		$this->db->or_like('loan_added_by', $q);
		$this->db->or_like('loan_approved_by', $q);
		$this->db->or_like('loan_status', $q);
		$this->db->or_like('loan_added_date', $q);
		$this->db->limit($limit, $start);
		return $this->db->get($this->table)->result();
	}

	// insert data
	function insert($data)
	{
		$this->db->insert($this->table, $data);
	}

	// update data
	function update($id, $data)
	{
		$this->db->where($this->id, $id);
		$this->db->update($this->table, $data);
	}
	function update1($id, $data)
	{
		$this->db->where('loan_customer', $id);
		$this->db->update($this->table, $data);
	}

	// delete data
	function delete($id)
	{
		$this->db->where($this->id, $id);
		$this->db->delete($this->table);
	}

	// delete data
	function delete_data($id)
	{
		$this->db->where($this->id, $id);
		$this->db->delete($this->table_d);
	}


}

