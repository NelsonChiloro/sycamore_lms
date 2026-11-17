<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once APPPATH . 'libraries/Loan_charge_policy.php';

class Late_charges_model extends CI_Model
{
    public function assert_installed() {
        if (!$this->db->table_exists('loan_charge_state') || !$this->db->table_exists('loan_payment_components') || !$this->db->table_exists('loan_charge_policy')) {
            throw new RuntimeException('Install database/20261022_late_charge_framework.sql before using late charges.');
        }
    }

    public function states($loan_id, $rows) {
        $this->assert_installed();
        $policy = $this->db->where('id',1)->get('loan_charge_policy')->row();
        if (!$policy) throw new RuntimeException('Late charge policy start date is missing.');
        $states = array();
        foreach ($this->db->where('loan_id',(int)$loan_id)->get('loan_charge_state')->result_array() as $state) $states[$state['payment_number']] = $state;
        foreach ($rows as $row) {
            if (isset($states[$row->payment_number])) continue;
            $state = array_merge(Loan_charge_policy::seed($row), array(
                'loan_id'=>(int)$loan_id, 'payment_number'=>(int)$row->payment_number,
                'penalty_base'=>max(0,(float)$row->total_late_charge), 'penalty_accrued'=>0, 'penalty_paid'=>0,
                'accrued_through'=>Loan_charge_policy::date($policy->starts_on)->modify('-1 day')->format('Y-m-d')
            ));
            $this->db->insert('loan_charge_state',$state);
            $states[$row->payment_number] = $state;
        }
        if ($rows) {
            $numbers=array_map(function($row){return (int)$row->payment_number;},$rows);
            $first=$numbers[0]; $moved=false;
            foreach(array_keys($states) as $number) {
                if (in_array((int)$number,$numbers,true)) continue;
                // A shorter restructure must retain penalties on removed terms.
                foreach(array('penalty_base','penalty_accrued','penalty_paid') as $field) $states[$first][$field]+=$states[$number][$field];
                unset($states[$number]); $moved=true;
            }
            if ($moved) {
                $this->db->where('loan_id',(int)$loan_id)->where('payment_number',$first)->update('loan_charge_state',$states[$first]);
                // Keep the penalty ledger keyed to the surviving installment so
                // later reversal repair can still restore its paid penalties.
                $this->db->where('loan_id',(int)$loan_id)->where_not_in('payment_number',$numbers)->where('component','penalty')
                    ->update('loan_payment_components',array('payment_number'=>$first));
                $this->db->where('loan_id',(int)$loan_id)->where_not_in('payment_number',$numbers)->delete('loan_charge_state');
            }
        }
        return $states;
    }

    public function save_states($loan_id, $rows, $states, $paid_date = null) {
        foreach ($rows as $row) {
            $n = $row->payment_number; $state = $states[$n];
            $this->db->where('loan_id',(int)$loan_id)->where('payment_number',$n)->update('loan_charge_state',$state);
            $paid = 0;
            foreach (Loan_charge_policy::$components as $component=>$field) $paid += (float)$state['paid_'.$component];
            $paid = round($paid,2);
            $penalty = round(max(0,$state['penalty_base']+$state['penalty_accrued']-$state['penalty_paid']),2);
            $full = $paid + 0.005 >= (float)$row->amount && $penalty <= 0.005;
            $update = array('paid_amount'=>$paid, 'total_late_charge'=>$penalty,
                'status'=>$full ? 'PAID' : ($paid>0 ? 'PARTIAL PAID' : 'NOT PAID'),
                'partial_paid'=>!$full && $paid>0 ? 'YES' : 'NO');
            if ($paid_date !== null && abs($paid-(float)$row->paid_amount)>.005) $update['paid_date']=$paid>0 ? $paid_date : null;
            $this->db->where('id',$row->id)->update('payement_schedules',$update);
        }
    }

    public function refresh_loan($loan_id, $as_of = null) {
        $this->assert_installed();
        $as_of = $as_of ?: (new DateTimeImmutable('now',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d');
        $this->db->trans_begin();
        try {
            $loan = $this->db->query('SELECT * FROM loan WHERE loan_id = ? FOR UPDATE',array((int)$loan_id))->row();
            if (!$loan) throw new RuntimeException('Loan was not found.');
            $rows=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get('payement_schedules')->result();
            $states=$this->states($loan_id,$rows);
            $added=0;
            foreach($rows as $row) {
                $n=$row->payment_number; $state=&$states[$n];
                // A backdated payment cannot rewrite already-accrued history.
                if (substr($as_of,0,10)<$state['accrued_through']) throw new RuntimeException('Payment date precedes the last late-charge calculation. Use the reversal/reconciliation workflow.');
                $outstanding=max(0,(float)$row->amount-(float)$row->paid_amount);
                $charge=$loan->loan_status==='ACTIVE' ? Loan_charge_policy::accrue($row->payment_schedule,$state['accrued_through'],$as_of,$outstanding) : 0;
                $state['penalty_accrued']+=$charge; $added+=$charge;
                $state['accrued_through']=substr($as_of,0,10);
                unset($state);
            }
            $this->save_states($loan_id,$rows,$states);
            if (!$this->db->trans_status()) throw new RuntimeException('Late charge update failed.');
            $this->db->trans_commit();
            return array('rows'=>$rows,'states'=>$states,'charges_added'=>$added);
        } catch (Throwable $e) { $this->db->trans_rollback(); throw $e; }
    }

    private function valid_history_date($date) {
        $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',(string)$date,new DateTimeZone('Africa/Blantyre'));
        return $parsed && $parsed->format('Y-m-d')===$date && $date!=='0000-00-00';
    }

    /** Only use a matching account's effective payment date, never a guessed ref date. */
    private function account_history_date($reference, $loan_number, $today) {
        foreach(array('account_number','credit','system_time') as $field) {
            if (!$this->db->field_exists($field,'transaction')) return null;
        }
        $records=$this->db->select('system_time')->where('transaction_id',$reference)
            ->where('account_number',$loan_number)->where('credit >',0)->get('transaction')->result();
        $dates=array();
        foreach($records as $record) {
            $date=substr((string)$record->system_time,0,10);
            if (!$this->valid_history_date($date) || $date>$today) return null;
            $dates[$date]=true;
        }
        return count($dates)===1 ? key($dates) : null;
    }

    /** Repair missing legacy dates in bulk, with explicit opt-in for inferred reference dates. */
    public function repair_history_dates($loan_id, $apply = false, $allow_reference_dates = false) {
        $this->assert_installed();
        if (!$this->db->table_exists('loan_history_date_repairs')) throw new RuntimeException('Install database/20261022_bulk_history_dates.sql first.');
        $this->db->trans_begin();
        try {
            $loan=$this->db->query('SELECT * FROM loan WHERE loan_id=? FOR UPDATE',array((int)$loan_id))->row();
            if (!$loan) throw new RuntimeException('Loan not found.');
            $today=(new DateTimeImmutable('today',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d');
            $rows=$this->db->query("SELECT t.* FROM transactions t WHERE t.loan_id=? AND t.transaction_type IN (2,3) AND t.amount>0
                AND NOT EXISTS(SELECT 1 FROM transactions receipt WHERE receipt.loan_id=t.loan_id AND receipt.ref=t.ref
                    AND receipt.transaction_type=1 AND receipt.amount>0 AND receipt.ref NOT LIKE 'GF.%'
                    AND receipt.payment_number>0)
                AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=t.ref AND a.reversed='Yes') ORDER BY t.transaction_id",array((int)$loan_id))->result();
            $result=array('applied'=>$apply,'repairs'=>array(),'unresolved'=>array());
            foreach($rows as $row) {
                $original=$row->date_stamp; $date=substr((string)$original,0,10);
                if ($this->valid_history_date($date) && $date <= $today) continue;
                $replacement=null; $source=null;
                // Future dates require source review; do not silently replace them.
                if (!$this->valid_history_date($date)) {
                    $replacement=$this->account_history_date($row->ref,$loan->loan_number??'',$today);
                    if ($replacement!==null) $source='matching_account_date';
                    $has_account_credit=false;
                    if ($this->db->field_exists('account_number','transaction') && $this->db->field_exists('credit','transaction')) {
                        $has_account_credit=$this->db->where('transaction_id',$row->ref)->where('account_number',$loan->loan_number??'')->where('credit >',0)->count_all_results('transaction')>0;
                    }
                    if ($replacement===null && !$has_account_credit && $allow_reference_dates) {
                        $digits=null;
                        if (preg_match('/^(?:GF|ST)\.(\d{8})\.\d+$/',$row->ref,$match)) $digits=$match[1];
                        elseif (preg_match('/^TR-S\d{3,4}(\d{8})$/',$row->ref,$match)) $digits=$match[1];
                        if ($digits!==null) {
                            $candidate=substr($digits,0,4).'-'.substr($digits,4,2).'-'.substr($digits,6,2);
                            $loan_date=isset($loan->loan_date) ? substr($loan->loan_date,0,10) : null;
                            if ($this->valid_history_date($candidate) && $candidate<=$today && (!$this->valid_history_date($loan_date) || $candidate >= $loan_date)) {
                                $replacement=$candidate; $source='reference_posting_date_inferred';
                            }
                        }
                    }
                }
                if ($replacement===null) {
                    $result['unresolved'][]=array('transaction_id'=>$row->transaction_id,'ref'=>$row->ref,'date'=>$original,'reason'=>'No unambiguous permitted date source; future/conflicting dates require review.');
                    continue;
                }
                $repair=array('transaction_id'=>(int)$row->transaction_id,'loan_id'=>(int)$loan_id,'ref'=>$row->ref,
                    'old_date'=>$original,'new_date'=>$replacement.' 00:00:00','source'=>$source);
                $result['repairs'][]=$repair;
                if ($apply) {
                    $this->db->where('transaction_id',$row->transaction_id)->where('date_stamp',$original)->update('transactions',array('date_stamp'=>$repair['new_date']));
                    if ($this->db->affected_rows()!==1) throw new RuntimeException('Repayment changed concurrently; retry this loan.');
                    $this->db->insert('loan_history_date_repairs',array_merge($repair,array('repaired_at'=>(new DateTimeImmutable('now',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d H:i:s'))));
                }
            }
            if (!$this->db->trans_status()) throw new RuntimeException('Historical date repair failed.');
            $this->db->trans_commit(); return $result;
        } catch(Throwable $e) {$this->db->trans_rollback();throw $e;}
    }

    /** Run against the original schedules, inside the restructure transaction. */
    public function prepare_restructure($loan_id, $apply = false, $allow_reference_dates = false, $pending_approval_id = null) {
        $this->db->trans_begin();
        try {
            $dates=$this->repair_history_dates($loan_id,true,$allow_reference_dates);
            if ($dates['unresolved']) throw new RuntimeException('Unresolved payment dates: '.json_encode($dates['unresolved']));
            $original=$this->db->query('SELECT * FROM loan WHERE loan_id=? FOR UPDATE',array((int)$loan_id))->row();
            if (!$original || $original->loan_status!=='ACTIVE') throw new RuntimeException('Repair and restructure requires an active loan.');
            $original_schedules=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get('payement_schedules')->result();
            $history=$this->replay_sequential_repayments($loan_id,$pending_approval_id);
            $audit_id=$this->db->insert_id();
            $this->load->model('Payement_schedules_model');
            $this->Payement_schedules_model->normalize_after_reversal($loan_id);
            $payments=$history['payment_reconciliation'];
            $audit_row=$this->db->where('id',$audit_id)->get('loan_penalty_reconstructions')->row();
            if (!$audit_row) throw new RuntimeException('The reconstruction audit was not saved.');
            $audit=json_decode($audit_row->audit_json,true);
            $audit['before_payment_reconciliation']=$original_schedules;
            $audit['payment_reconciliation']=$payments;
            $this->db->where('id',$audit_id)->update('loan_penalty_reconstructions',array('audit_json'=>json_encode($audit)));
            $current=$this->refresh_loan($loan_id);
            $penalty_due=0;$contract_due=0;
            foreach($current['rows'] as $schedule) {
                $state=$current['states'][$schedule->payment_number];
                $penalty_due+=max(0,$state['penalty_base']+$state['penalty_accrued']-$state['penalty_paid']);
                $contract_due+=max(0,(float)$schedule->amount-(float)$schedule->paid_amount);
            }
            $report=array('historical_opening_penalty'=>$history['opening_penalty'],
                'historical_cutoff'=>$history['cutoff_on'],'contract_payments'=>$payments['applied'],
                'remaining_contract_balance'=>round($contract_due,2),'outstanding_penalties'=>round($penalty_due,2),
                'total_outstanding_before_restructure'=>round($contract_due+$penalty_due,2),
                'charges_added_today'=>round($current['charges_added'],2),'date_repairs'=>$dates['repairs']);
            if (!$this->db->trans_status()) throw new RuntimeException('Loan repair failed.');
            if ($apply) $this->db->trans_commit();else $this->db->trans_rollback();
            return $report;
        } catch(Throwable $e) {$this->db->trans_rollback();throw $e;}
    }

    /** Preview or atomically replace the pre-deployment opening penalty balance. */
    private function replay_sequential_repayments($loan_id,$pending_approval_id) {
        // The caller holds the loan lock and owns the transaction. Rebuild from
        // dated cash receipts, accruing before each receipt is allocated.
        $today=(new DateTimeImmutable('today',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d');
        $rows=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get('payement_schedules')->result();
        $loan=$this->db->where('loan_id',(int)$loan_id)->get('loan')->row();
        $date_corrections=array();
        if ($this->db->table_exists('approval_edits')) {
            $this->db->where('id',(string)$loan_id)->where('state','Approved')->where('type','Loan edit');
            if ($pending_approval_id!==null) $this->db->where('approval_edits_id !=',(int)$pending_approval_id);
            foreach($this->db->order_by('approval_edits_id','ASC')->get('approval_edits')->result() as $edit) {
                $old=json_decode($edit->old_info,true);$new=json_decode($edit->new_info,true);
                foreach(array('loan_principal','loan_period','loan_product') as $field) {
                    if (!isset($old[$field],$new[$field]) || (string)$old[$field] !== (string)$new[$field]) throw new RuntimeException('Historical loan terms changed. Original schedule versions are required before penalties can be reconstructed.');
                }
                // Approved date-only corrections are retroactive: the stored
                // corrected schedule governs the complete repayment history.
                // Principal/product/tenure changes still require schedule versions.
                $old_date=$old['loan_date']??'';$new_date=$new['loan_date']??'';
                if (!$this->valid_history_date($old_date) || !$this->valid_history_date($new_date)) {
                    throw new RuntimeException('Historical loan edit has an invalid date; review its approval record.');
                }
                if ($old_date!==$new_date) {
                    $date_corrections[]=array('approval_edits_id'=>(int)$edit->approval_edits_id,
                        'old_date'=>$old_date,'corrected_date'=>$new_date);
                }
            }
        }
        if ($date_corrections) {
            $latest=end($date_corrections);
            if (!$loan || (string)$loan->loan_date!==$latest['corrected_date']) {
                throw new RuntimeException('The loan date does not match its latest approved correction. Review the stored corrected schedule before rebuilding.');
            }
        }
        $transactions=$this->db->query("SELECT t.* FROM transactions t WHERE t.loan_id=? AND t.transaction_type IN (2,3)
            AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=t.ref AND a.reversed='Yes')
            ORDER BY t.date_stamp,t.transaction_id",array((int)$loan_id))->result_array();
        $groups=$this->recorded_repayment_groups($loan,$transactions);
        if (!$groups) {
            foreach($rows as $row) if ((float)$row->paid_amount>0.005) {
                throw new RuntimeException('Paid installments have no verified repayment history. Review payment sources before rebuilding.');
            }
        }
        $before_states=$this->db->where('loan_id',(int)$loan_id)->get('loan_charge_state')->result_array();
        $before_components=$this->db->where('loan_id',(int)$loan_id)->get('loan_payment_components')->result_array();
        $baseline=$this->recorded_rebuild_baseline($loan_id,$groups,$rows,$before_states,$before_components);
        $policy=$this->db->where('id',1)->get('loan_charge_policy')->row();
        if (!$policy || !$this->valid_history_date($policy->starts_on)) throw new RuntimeException('A valid charge policy start date is required.');
        $policy_start=Loan_charge_policy::date($policy->starts_on)->modify('-1 day')->format('Y-m-d');
        $states=array();$last=array();$capacity=0;
        foreach($rows as $row) {
            $due=substr((string)$row->payment_schedule,0,10);
            if (!$this->valid_history_date($due)) throw new RuntimeException('Invalid original installment due date.');
            $component_total=0;
            foreach(Loan_charge_policy::$components as $component=>$field) $component_total+=max(0,(float)$row->$field);
            if (abs($component_total-(float)$row->amount)>.02) throw new RuntimeException('Installment '.$row->payment_number.' components do not reconcile with its amount.');
            $copy=clone $row;$copy->paid_amount=0;$n=$row->payment_number;$capacity+=(float)$row->amount;
            $states[$n]=array_merge(Loan_charge_policy::seed($copy),array('loan_id'=>(int)$loan_id,'payment_number'=>(int)$n,
                'penalty_base'=>$baseline['states'][$n]['penalty_base']??0,
                'penalty_accrued'=>$baseline['states'][$n]['penalty_accrued']??0,
                'penalty_paid'=>0,'accrued_through'=>$today));
            // Rebuilding is not permission to backdate the new penalty policy
            // to disbursement or divert recorded contract cash to fresh charges.
            $last[$n]=max($policy_start,$baseline['as_of'],$baseline['states'][$n]['accrued_through']??$policy_start);
        }
        foreach($groups as $group) {
            $date=substr((string)$group['template']['date_stamp'],0,10);
            if (!$this->valid_history_date($date) || $date>$today) throw new RuntimeException('Invalid receipt date for '.$group['template']['ref']);
        }
        $accrue=function($date) use (&$states,&$last,$rows) {
            foreach($rows as $row) {
                $n=$row->payment_number;$paid=0;
                foreach(Loan_charge_policy::$components as $component=>$field) $paid+=$states[$n]['paid_'.$component];
                $states[$n]['penalty_accrued']+=Loan_charge_policy::accrue($row->payment_schedule,$last[$n],$date,max(0,(float)$row->amount-$paid));
                if ($date>$last[$n]) $last[$n]=$date;
            }
        };
        $this->db->where('loan_id',(int)$loan_id)->delete('loan_payment_components');
        $cash=0;$unapplied=0;$paid_dates=array();
        foreach($groups as $group) {
            $template=$group['template'];$date=substr($template['date_stamp'],0,10);$accrue($date);
            $penalty_parts=$baseline['penalties'][$template['ref']]??array();
            $recorded_penalty=array_sum($penalty_parts);
            if ($recorded_penalty>$group['amount']+0.005) throw new RuntimeException('Recorded penalty payments exceed receipt '.$template['ref']);
            $allocation=Loan_charge_policy::allocate($rows,$states,round($group['amount']-$recorded_penalty,2),$date,false);
            foreach($penalty_parts as $number=>$paid) {
                if (!isset($states[$number])) throw new RuntimeException('Recorded penalty payment references an obsolete installment.');
                $allocation['states'][$number]['penalty_paid']+=$paid;
                $allocation['allocations'][]=array('payment_number'=>(int)$number,'component'=>'penalty','amount'=>$paid);
            }
            $states=$allocation['states'];$cash+=$group['amount'];$unapplied+=$allocation['remaining'];
            // Retain original IDs, with the complete before-image in the audit.
            // Reclassification changes no receipt/account credit cash value.
            foreach($group['rows'] as $original) $this->db->where('transaction_id',$original['transaction_id'])->update('transactions',array('amount'=>0));
            $ledger=array();
            foreach($allocation['allocations'] as $part) {
                if ($part['component']!=='penalty') $paid_dates[$part['payment_number']]=$template['date_stamp'];
                $this->db->insert('loan_payment_components',array_merge($part,array('loan_id'=>(int)$loan_id,'ref'=>$template['ref'],'paid_at'=>$template['date_stamp'])));
                $type=$part['component']==='penalty'?2:3;$key=$part['payment_number'].'|'.$type;
                if (!isset($ledger[$key])) $ledger[$key]=array('payment_number'=>$part['payment_number'],'transaction_type'=>$type,'amount'=>0);
                $ledger[$key]['amount']+=$part['amount'];
            }
            // Preserve any excess cash as type 3 credit, reported as unapplied.
            if ($allocation['remaining']>0.005) $ledger['excess']=array('payment_number'=>0,'transaction_type'=>3,'amount'=>$allocation['remaining']);
            $index=0;
            foreach($ledger as $entry) {
                $entry['description']='charge_policy_v1';
                $entry['date_stamp']=$template['date_stamp'];
                if (isset($group['rows'][$index])) {
                    $this->db->where('transaction_id',$group['rows'][$index++]['transaction_id'])->update('transactions',$entry);
                } else {
                    $insert=array_merge($template,$entry);unset($insert['transaction_id']);
                    // The receipt owns the external reference; derived rows only
                    // share the internal ref, as in normal repayment allocation.
                    $insert['payment_reference']=null;
                    $this->db->insert('transactions',$insert);
                }
            }
        }
        $accrue($today);
        foreach($rows as $row) {
            $exists=$this->db->where('loan_id',(int)$loan_id)->where('payment_number',$row->payment_number)->count_all_results('loan_charge_state');
            if ($exists) $this->db->where('loan_id',(int)$loan_id)->where('payment_number',$row->payment_number)->update('loan_charge_state',$states[$row->payment_number]);
            else $this->db->insert('loan_charge_state',$states[$row->payment_number]);
        }
        $this->save_states($loan_id,$rows,$states);
        foreach($rows as $row) $this->db->where('id',$row->id)->update('payement_schedules',array('paid_date'=>$paid_dates[$row->payment_number]??null));
        $contract=0;$penalties_paid=0;$gross=0;
        foreach($states as $state) {
            foreach(Loan_charge_policy::$components as $component=>$field) $contract+=$state['paid_'.$component];
            $penalties_paid+=$state['penalty_paid'];$gross+=$state['penalty_base']+$state['penalty_accrued'];
        }
        if (abs($cash-$contract-$penalties_paid-$unapplied)>.02) throw new RuntimeException('Sequential repayment reallocation did not conserve the receipt total.');
        $payments=array('loan_id'=>(int)$loan_id,'total_txn_paid'=>round($cash-$penalties_paid,2),'total_schedule_amount'=>round($capacity,2),
            'applied'=>round($contract,2),'unapplied'=>round($unapplied,2));
        $report=array('opening_penalty'=>round($gross-$penalties_paid,2),'cutoff_on'=>$today,'payment_reconciliation'=>$payments,
            'total_receipts'=>round($cash,2),'penalties_paid'=>round($penalties_paid,2),'payment_allocation'=>'preserve_recorded_contract_allocations',
            'source_audit_id'=>$baseline['audit_id'],
            'historical_date_policy'=>'approved_date_corrections_are_retroactive','retroactive_date_corrections'=>$date_corrections);
        $audit=array('before_states'=>$before_states,'before_schedules'=>$rows,'before_transactions'=>$transactions,'receipt_sources'=>$groups,'before_components'=>$before_components,'report'=>$report);
        $this->db->insert('loan_penalty_reconstructions',array('loan_id'=>(int)$loan_id,'cutoff_on'=>$today,
            'input_hash'=>hash('sha256',json_encode(array($rows,$transactions))),'audit_json'=>json_encode($audit),'applied_at'=>date('Y-m-d H:i:s')));
        if (!$this->db->trans_status()) throw new RuntimeException('Sequential repayment repair failed.');
        return $report;
    }

    /** Recover the pre-rebuild split; prior derived reallocations are not receipts. */
    private function recorded_rebuild_baseline($loan_id,$groups,$rows,$states,$components) {
        $original_groups=array();$audit_id=null;
        $as_of=(new DateTimeImmutable('today',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d');
        foreach($this->db->where('loan_id',(int)$loan_id)->order_by('id','ASC')->get('loan_penalty_reconstructions')->result() as $record) {
            $audit=json_decode($record->audit_json,true);
            if (!isset($audit['receipt_sources'],$audit['before_transactions'],$audit['before_schedules'])) continue;
            $audit_id=(int)$record->id;
            $as_of=substr((string)$record->applied_at,0,10);
            if (!$this->valid_history_date($as_of)) throw new RuntimeException('The original rebuild audit has an invalid date.');
            $states=$audit['before_states']??array();$components=$audit['before_components']??array();
            $rows=$audit['before_schedules'];
            foreach($audit['receipt_sources'] as $group) $original_groups[$group['template']['ref']]=array();
            foreach($audit['before_transactions'] as $transaction) $original_groups[$transaction['ref']][]=$transaction;
            break;
        }
        $penalties=array();
        foreach($groups as $ref=>$group) {
            $source=array_key_exists($ref,$original_groups)?$original_groups[$ref]:$group['rows'];
            foreach($source as $transaction) {
                if ((int)$transaction['transaction_type']!==2 || (float)$transaction['amount']<=0) continue;
                $n=(int)$transaction['payment_number'];
                $penalties[$ref][$n]=($penalties[$ref][$n]??0)+(float)$transaction['amount'];
            }
            // Some legacy versions retained only the component allocation.
            $from_components=array();
            foreach($components as $component) {
                if ($component['ref']!==$ref || $component['component']!=='penalty') continue;
                $n=(int)$component['payment_number'];
                $from_components[$n]=($from_components[$n]??0)+(float)$component['amount'];
            }
            if (!isset($penalties[$ref]) && $from_components) $penalties[$ref]=$from_components;
        }
        $by_number=array();
        foreach($states as $state) $by_number[(int)$state['payment_number']]=$state;
        foreach($rows as $row) {
            $row=(array)$row;$n=(int)$row['payment_number'];
            if (isset($by_number[$n])) continue;
            $paid=0;foreach($penalties as $parts) $paid+=$parts[$n]??0;
            $by_number[$n]=array('penalty_base'=>max(0,(float)($row['total_late_charge']??0))+$paid,'penalty_accrued'=>0);
        }
        return array('audit_id'=>$audit_id,'as_of'=>$as_of,'states'=>$by_number,'penalties'=>$penalties);
    }

    /** One cash amount per reference; receipt rows are never changed by replay. */
    private function recorded_repayment_groups($loan, $allocations) {
        if ($this->db->where('loan_id',(int)$loan->loan_id)->where_in('transaction_type',array(1,2,3))
            ->where('amount <',0)->count_all_results('transactions')) {
            throw new RuntimeException('Negative payment adjustments require source review before rebuilding.');
        }
        $groups=array();
        foreach($allocations as $row) {
            $ref=trim((string)$row['ref']);
            if ($ref==='' || (float)$row['amount']<0) throw new RuntimeException('Ambiguous repayment allocation; review the source history.');
            if (!isset($groups[$ref])) $groups[$ref]=array('rows'=>array(),'amount'=>0,'template'=>$row);
            $groups[$ref]['rows'][]=$row;
            $groups[$ref]['amount']+=(float)$row['amount'];
        }
        $receipts=$this->db->query("SELECT t.* FROM transactions t WHERE t.loan_id=? AND t.transaction_type=1 AND t.amount>0
            AND t.ref NOT LIKE 'GF.%' AND t.payment_number>0
            AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=t.ref AND a.reversed='Yes')
            ORDER BY t.date_stamp,t.transaction_id",array((int)$loan->loan_id))->result_array();
        $seen=array();$external_refs=array();
        foreach($receipts as $receipt) {
            $ref=trim((string)$receipt['ref']);
            if ($ref==='' || isset($seen[$ref])) throw new RuntimeException('Duplicate or missing receipt reference: '.$ref);
            $external=trim((string)($receipt['payment_reference']??''));
            if ($external!=='' && isset($external_refs[$external])) throw new RuntimeException('Duplicate customer payment reference: '.$external);
            if ($external!=='') $external_refs[$external]=true;
            $seen[$ref]=true;
            $old=$groups[$ref]['rows']??array();
            $groups[$ref]=array('rows'=>$old,'amount'=>(float)$receipt['amount'],'template'=>$receipt,'source'=>'receipt');
        }
        foreach($groups as $ref=>&$group) {
            if (isset($group['source'])) continue;
            if ($group['amount']<=0) {unset($groups[$ref]);continue;}
            // Legacy allocations are accepted only when a real account deposit
            // corroborates their total. Internal "other" fee credits never qualify.
            $ledger=$this->db->where('transaction_id',$ref)->where('account_number',$loan->loan_number)
                ->where('transaction_type','deposit')->where('credit >',0)->get('transaction')->result();
            if (count($ledger)!==1 || abs((float)$ledger[0]->credit-$group['amount'])>.01) {
                throw new RuntimeException('No unambiguous customer receipt/deposit for '.$ref.'. Review payment sources before rebuilding.');
            }
            $dates=array();
            foreach($group['rows'] as $row) if ((float)$row['amount']>0) $dates[(string)$row['date_stamp']]=true;
            if (count($dates)!==1) throw new RuntimeException('Conflicting legacy allocation dates for '.$ref);
            $ledger_date=substr((string)$ledger[0]->system_time,0,10);
            if ($this->valid_history_date($ledger_date) && substr((string)key($dates),0,10)!==$ledger_date) {
                throw new RuntimeException('Legacy payment and deposit dates conflict for '.$ref);
            }
            $group['source']='verified_legacy_deposit';
        }
        unset($group);
        uasort($groups,function($a,$b){
            $order=strcmp((string)$a['template']['date_stamp'],(string)$b['template']['date_stamp']);
            return $order ?: ((int)$a['template']['transaction_id'] <=> (int)$b['template']['transaction_id']);
        });
        return $groups;
    }

    public function reconstruct_history($loan_id, $apply = false, $pending_approval_id = null, $replay_sequentially = false) {
        $this->assert_installed();
        if (!$this->db->table_exists('loan_penalty_reconstructions')) throw new RuntimeException('Install database/20261022_historical_penalties.sql first.');
        $this->db->trans_begin();
        try {
            $loan=$this->db->query('SELECT * FROM loan WHERE loan_id=? FOR UPDATE',array((int)$loan_id))->row();
            if (!$loan || $loan->loan_status!=='ACTIVE') throw new RuntimeException('Historical reconstruction is available for active loans only.');
            $policy=$this->db->where('id',1)->get('loan_charge_policy')->row();
            if (!$policy) throw new RuntimeException('The deployment start date is missing.');
            $cutoff=Loan_charge_policy::date($policy->starts_on)->modify('-1 day')->format('Y-m-d');
            $today=(new DateTimeImmutable('today',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d');
            if ($cutoff >= $today) throw new RuntimeException('The policy start date must not be in the future.');
            $rows=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get('payement_schedules')->result();
            if (!$rows) throw new RuntimeException('Loan schedules were not found.');
            // Current schedules cannot establish historical terms after a material edit.
            if ($this->db->table_exists('approval_edits')) {
                $this->db->where('id',(string)$loan_id)->where('state','Approved')->where('type','Loan edit');
                // Approval is marked Approved before its generator is invoked.
                // That one request has not changed the original schedules yet.
                if ($pending_approval_id !== null) $this->db->where('approval_edits_id !=',(int)$pending_approval_id);
                $edits=$this->db->get('approval_edits')->result();
                foreach($edits as $edit) {
                    $old=json_decode($edit->old_info,true); $new=json_decode($edit->new_info,true);
                    if (!is_array($old) || !is_array($new)) throw new RuntimeException('An approved edit has unreadable historical terms. Review its original schedules first.');
                    foreach(array('loan_date','loan_principal','loan_period','loan_product') as $field) {
                        if (!isset($old[$field],$new[$field]) || (string)$old[$field] !== (string)$new[$field]) throw new RuntimeException('Historical loan terms changed. Original schedule versions are required before penalties can be reconstructed.');
                    }
                }
            }
            $transactions=$this->db->query("SELECT t.* FROM transactions t WHERE t.loan_id=? AND t.transaction_type IN (2,3) AND t.amount>0
                AND NOT EXISTS(SELECT 1 FROM transaction a WHERE a.transaction_id=t.ref AND a.reversed='Yes')
                ORDER BY t.date_stamp,t.transaction_id",array((int)$loan_id))->result_array();
            $by_number=array(); $numbers=array(); $capacity=0; $schedule_paid=0; $valid_paid=0;
            foreach($rows as $row) {$numbers[(int)$row->payment_number]=true; $capacity+=(float)$row->amount;$schedule_paid+=(float)$row->paid_amount;}
            $invalid_dates=array(); $date_sources=array(); $account_dates=array();$dated_contract_payments=array();
            foreach($transactions as $txn) {
                $date=substr($txn['date_stamp'],0,10);
                if (!$this->valid_history_date($date)) {
                    if (!array_key_exists($txn['ref'],$account_dates)) $account_dates[$txn['ref']]=$this->account_history_date($txn['ref'],$loan->loan_number??'',$today);
                    if ($account_dates[$txn['ref']]!==null) {
                        $date=$account_dates[$txn['ref']];
                        $date_sources[]=array('transaction_id'=>$txn['transaction_id'],'reference'=>$txn['ref'],'effective_date'=>$date,'source'=>'Matching loan account system_time');
                    }
                }
                if (!$this->valid_history_date($date) || $date>$today) {
                    $invalid_dates[]='transaction '.$txn['transaction_id'].' / '.$txn['ref'].' / date "'.$txn['date_stamp'].'"';
                    continue;
                }
                if ((int)$txn['transaction_type']===3) $valid_paid+=(float)$txn['amount'];
                if ($date>$cutoff) continue;
                if ($replay_sequentially && (int)$txn['transaction_type']===3) {
                    $dated_contract_payments[]=array('date'=>$date,'amount'=>(float)$txn['amount'],'transaction_id'=>(int)$txn['transaction_id']);
                    continue;
                }
                $n=(int)$txn['payment_number'];
                if (!isset($numbers[$n])) throw new RuntimeException('Historical payments reference obsolete installment numbers. Original schedules are required.');
                if (!isset($by_number[$n])) $by_number[$n]=array('payments'=>array(),'penalties_paid'=>0);
                if ((int)$txn['transaction_type']===3) $by_number[$n]['payments'][]=array('date'=>$date,'amount'=>(float)$txn['amount']);
                else $by_number[$n]['penalties_paid']+=(float)$txn['amount'];
            }
            if ($invalid_dates) throw new RuntimeException(count($invalid_dates).' repayment(s) have an invalid or future date (server business date '.$today.'): '.implode('; ',array_slice($invalid_dates,0,10)).'. No unambiguous matching loan-account payment date was available for invalid dates. Restore verified payment dates in transactions.date_stamp; dates embedded in references are not assumed to be payment dates.');
            if (abs(min($capacity,$valid_paid)-$schedule_paid)>.02) throw new RuntimeException('Schedule payments do not reconcile with valid repayments. Repair the loan before reconstructing penalties.');
            if ($replay_sequentially) {
                // Carry each dated repayment across the original installments,
                // including amounts recorded against an already-full term.
                usort($dated_contract_payments,function($a,$b){return strcmp($a['date'],$b['date']) ?: ($a['transaction_id']<=>$b['transaction_id']);});
                $index=0;$used=0.0;
                foreach($dated_contract_payments as $payment) {
                    $remaining=$payment['amount'];
                    while($remaining>0.0001 && isset($rows[$index])) {
                        $schedule=$rows[$index];$due=max(0,(float)$schedule->amount-$used);
                        if ($due<=0.0001) {$index++;$used=0;continue;}
                        $portion=min($remaining,$due);$n=(int)$schedule->payment_number;
                        if (!isset($by_number[$n])) $by_number[$n]=array('payments'=>array(),'penalties_paid'=>0);
                        $by_number[$n]['payments'][]=array('date'=>$payment['date'],'amount'=>$portion);
                        $used+=$portion;$remaining-=$portion;
                    }
                }
            }
            $detail=array(); $gross_total=0; $paid_total=0; $net_total=0;
            foreach($rows as $row) {
                $date=substr($row->payment_schedule,0,10); $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$date);
                if (!$parsed || $parsed->format('Y-m-d')!==$date) throw new RuntimeException('Invalid installment due date.');
                $history=$by_number[$row->payment_number]??array('payments'=>array(),'penalties_paid'=>0);
                $gross=round(Loan_charge_policy::historical_accrual($date,(float)$row->amount,$history['payments'],$cutoff),2);
                if ($history['penalties_paid']>$gross+.01) throw new RuntimeException('Historical penalty payments exceed calculated charges; review prior waivers or policy differences.');
                $net=round(max(0,$gross-$history['penalties_paid']),2);
                $detail[]=array('payment_number'=>(int)$row->payment_number,'due_date'=>$date,'historical_accrued'=>$gross,
                    'historical_paid'=>$history['penalties_paid'],'opening_penalty'=>$net,'currently_stored'=>(float)$row->total_late_charge);
                $gross_total+=$gross; $paid_total+=$history['penalties_paid']; $net_total+=$net;
            }
            $fingerprint=hash('sha256',json_encode(array($policy->starts_on,$rows,$transactions)));
            $report=array('loan_id'=>(int)$loan_id,'loan_number'=>$loan->loan_number??(string)$loan_id,'cutoff_on'=>$cutoff,
                'historical_accrued'=>round($gross_total,2),'historical_paid'=>round($paid_total,2),'opening_penalty'=>round($net_total,2),
                'applied'=>false,'installments'=>$detail,'resolved_payment_dates'=>$date_sources,
                'payment_allocation'=>$replay_sequentially?'chronological_sequential_replay':'recorded_installment_numbers');
            if ($apply) {
                $before=$this->db->where('loan_id',(int)$loan_id)->get('loan_charge_state')->result_array();
                $states=$this->states($loan_id,$rows);
                foreach($detail as $item) $states[$item['payment_number']]['penalty_base']=$item['opening_penalty'];
                $this->save_states($loan_id,$rows,$states);
                $audit=array('before_states'=>$before,'before_schedules'=>$rows,'report'=>$report,'reversal_rule'=>'Reversed references are voided from historical repayment history.');
                $this->db->insert('loan_penalty_reconstructions',array('loan_id'=>(int)$loan_id,'cutoff_on'=>$cutoff,
                    'input_hash'=>$fingerprint,'audit_json'=>json_encode($audit),'applied_at'=>(new DateTimeImmutable('now',new DateTimeZone('Africa/Blantyre')))->format('Y-m-d H:i:s')));
                if (!$this->db->trans_status()) throw new RuntimeException('Historical penalty update failed.');
                $report['applied']=true;
            }
            $this->db->trans_commit();
            return $report;
        } catch(Throwable $e) {$this->db->trans_rollback();throw $e;}
    }

    public function calculate_and_update_late_charges() {
        try {
            $this->assert_installed(); $count=0; $schedules=0; $added=0;
            $loans=$this->db->query("SELECT l.loan_id FROM loan l WHERE l.loan_status='ACTIVE'
                AND EXISTS (SELECT 1 FROM payement_schedules ps WHERE ps.loan_id=l.loan_id
                    AND ps.amount>ps.paid_amount AND ps.payment_schedule <= ?)",
                array((new DateTimeImmutable('now',new DateTimeZone('Africa/Blantyre')))->modify('-3 days')->format('Y-m-d')))->result();
            foreach($loans as $loan) {
                $result=$this->refresh_loan($loan->loan_id); ++$count;
                $schedules+=count($result['rows']); $added+=$result['charges_added'];
            }
            return array('success'=>true,'processed_loans'=>$count,'updated_schedules'=>$schedules,'total_late_charges_added'=>round($added,2));
        } catch(Throwable $e) { return array('success'=>false,'message'=>$e->getMessage()); }
    }
    public function get_loan_late_charges($loan_id) {
        $this->refresh_loan($loan_id);
        $rows=$this->db->where('loan_id',(int)$loan_id)->order_by('payment_number')->get('payement_schedules')->result();
        $today=new DateTimeImmutable('today',new DateTimeZone('Africa/Blantyre'));
        foreach($rows as $row) {
            $grace=Loan_charge_policy::date($row->payment_schedule)->modify('+2 days');
            $row->days_overdue=$today>$grace && (float)$row->amount>(float)$row->paid_amount ? (int)$grace->diff($today)->days : 0;
        }
        return $rows;
    }
    public function get_total_loan_late_charges($loan_id) {
        $rows=$this->get_loan_late_charges($loan_id); $total=0;
        foreach($rows as $row) $total+=(float)$row->total_late_charge;
        return $total;
    }
    public function clear_schedule_late_charges($schedule_id) {
        // A payment must be recorded with its reference; silently clearing a
        // balance would destroy the audit trail and resurrect it on next accrual.
        throw new RuntimeException('Allocate a referenced payment to settle late charges.');
    }
}
