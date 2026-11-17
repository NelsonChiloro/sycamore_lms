<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Money is allocated in cents; accrual retains precision until display. */
class Loan_charge_policy
{
    const MONTHLY_RATE = 0.10;
    const DAYS_PER_MONTH = 30;
    const GRACE_DAYS = 2;
    const MAX_DAYS = 360;
    public static $components = array('cover' => 'ploan_cover', 'admin' => 'padmin_fee', 'interest' => 'interest', 'principal' => 'principal');

    public static function date($value) {
        return new DateTimeImmutable(substr((string)$value, 0, 10), new DateTimeZone('Africa/Blantyre'));
    }

    public static function accrue($due, $last, $as_of, $outstanding) {
        $first = self::date($due)->modify('+3 days');
        $end = $first->modify('+359 days');
        $from = max($first, self::date($last)->modify('+1 day'));
        $to = min($end, self::date($as_of));
        $days = $to < $from ? 0 : (int)$from->diff($to)->days + 1;
        return max(0, $outstanding) * self::MONTHLY_RATE / self::DAYS_PER_MONTH * $days;
    }

    public static function seed($row) {
        $paid = max(0, min((float)$row->amount, (float)$row->paid_amount));
        $state = array();
        $left = (int)round($paid * 100);
        $limits=self::component_limits($row);
        foreach (self::$components as $component => $field) {
            // Historical payments used proportional allocation; preserve that split.
            $value = $component === 'principal' ? $left : min($left, (int)round(($row->amount > 0 ? $paid / $row->amount : 0) * $limits[$component] * 100));
            $state['paid_'.$component] = $value / 100;
            $left -= $value;
        }
        return $state;
    }

    /** Reconcile legacy rounded fee components to the contractual installment. */
    public static function component_limits($row) {
        $limits=array();$total=0;
        foreach(self::$components as $component=>$field) {
            $limits[$component]=(int)round(max(0,(float)$row->$field)*100);
            $total+=$limits[$component];
        }
        $difference=(int)round((float)$row->amount*100)-$total;
        if (abs($difference)>2) throw new RuntimeException('Installment components do not reconcile with the contractual amount.');
        // Preserve the contractual principal; absorb rounding in a financed fee.
        foreach(array('admin','interest','cover','principal') as $component) {
            if ($limits[$component]>0 && $limits[$component]+$difference>=0) {
                $limits[$component]+=$difference;break;
            }
        }
        foreach($limits as $component=>$cents) $limits[$component]=$cents/100;
        return $limits;
    }

    /** Reconstruct one installment up to a cutoff; payments take effect after that day's accrual. */
    public static function historical_accrual($due, $amount, $payments, $cutoff) {
        usort($payments, function($a,$b){return strcmp($a['date'],$b['date']);});
        $last=self::date($due)->format('Y-m-d'); $remaining=(float)$amount; $gross=0;
        foreach($payments as $payment) {
            if ($payment['date']>$cutoff) continue;
            if ($payment['amount']<0 || $payment['amount']>$remaining+0.01) throw new RuntimeException('Historical allocations exceed installment amount.');
            if ($payment['date'] >= $last) {
                $gross+=self::accrue($due,$last,$payment['date'],$remaining);
                $last=$payment['date'];
            }
            $remaining=max(0,$remaining-$payment['amount']);
        }
        if ($cutoff >= $last) $gross+=self::accrue($due,$last,$cutoff,$remaining);
        return $gross;
    }

    /** Clear each installment completely before advancing to the next one. */
    public static function allocate($rows, $states, $amount, $date, $include_penalties = true) {
        usort($rows,function($a,$b){return (int)$a->payment_number <=> (int)$b->payment_number;});
        $left = (int)round($amount * 100);
        $allocations = array();
        $take = function($number, $component, $due) use (&$left, &$allocations, &$states) {
            $cents = min($left, max(0, (int)round($due * 100)));
            if ($cents <= 0) return;
            $left -= $cents;
            $allocations[] = array('payment_number' => $number, 'component' => $component, 'amount' => $cents / 100);
            if ($component === 'penalty') $states[$number]['penalty_paid'] += $cents / 100;
            else $states[$number]['paid_'.$component] += $cents / 100;
        };
        foreach ($rows as $row) {
            $n=$row->payment_number;
            $limits=self::component_limits($row);
            if ($include_penalties) $take($n,'penalty',$states[$n]['penalty_base']+$states[$n]['penalty_accrued']-$states[$n]['penalty_paid']);
            foreach (self::$components as $component => $field) {
                $take($n, $component, $limits[$component] - $states[$n]['paid_'.$component]);
            }
            if ($left<=0) break;
        }
        return array('states' => $states, 'allocations' => $allocations, 'remaining' => $left / 100);
    }
}
