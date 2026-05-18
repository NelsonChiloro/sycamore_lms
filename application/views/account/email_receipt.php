<?php

$settings = get_by_id('settings','settings_id','1');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>



    <style>
        * {
            border: 0;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body, input {
            color: #242424;
            font-size: calc(20px + (30 - 20) * (100vw - 480px) / (1136 - 480));
            line-height: 1;
        }
        body {

            font-family: Helvetica, sans-serif;
            overflow-x: hidden;
        }

        form {
            margin: 3em auto 0 auto;
            position: relative;
            perspective: 1000px;
            height: 13.75em;
            width: 30em;
        }
        h1 {
            font-size: 0.6em;
            text-transform: uppercase;
        }
        form input {
            background: transparent;
            font-family: "Shadows Into Light", sans-serif;
            height: 1.5em;
        }
        form label {
            font-size: 0.4em;
            font-weight: bold;
        }
        label.amount-label
        {
            box-shadow: 1px 0 0 inset, 0 -1px 0 inset;
            display: inline-block;
            padding: 0.25rem 0 2rem 0.4rem;
            padding-left: 0.4rem;
            height: 1rem;
            text-transform: none;
        }
        code {
            font-family: monospace;
            font-size: 0.8em;
            font-weight: bold;
            letter-spacing: 0.1em;
            padding: 0.1em 0;
        }
        input,
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        input {
            border-radius: 0;
        }
        input:focus {
            background: #5785f622;
            outline: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
        }
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button  {
            margin: 0;
        }
        table {
            border-collapse: collapse;
            margin-bottom: 0.75em;
        }
        tr:last-of-type td:first-of-type {
            text-align: center;
        }
        tbody tr:not(:last-of-type) td:nth-of-type(2) {
            text-align: right;
        }
        tbody tr:not(:last-of-type) td:nth-of-type(2):before {
            content: "X";
            float: left;
        }
        tbody tr:last-of-type {
            font-size: 0.6em;
            font-weight: bold;
        }
        tbody tr:not(:last-of-type) td {
            padding: 0.625em 0.5em 0.125em 0.5em;
        }
        tbody tr:last-of-type td {
            padding: 0.125em 0.25em;
        }
        tr {
            font-size: 0.4em;
        }
        th, td {
            border: 1px solid;
        }
        th {
            font-weight: normal;
            padding: 0.125em 0.5em;
        }
        td:nth-of-type(1) {
            width: 38%;
        }
        td:nth-of-type(2) {
            width: 12%;
        }
        td:nth-of-type(3) {
            width: 35%;
        }
        td:nth-of-type(4) {
            width: 15%;
        }

        /* Sides */
        form, .side1, .side2 {
            transition: transform 0.2s linear;
        }
        input[type=checkbox] {
            top: -1em;
            left: -1em;
        }
        input[type=checkbox]:checked ~ form {
            transform: translateY(8.1em) rotateZ(90deg);
        }
        input[type=checkbox]:checked ~ form .side1 {
            transform: rotateX(180deg);
        }
        input[type=checkbox]:checked ~ form .side2 {
            transform: translate(-50%,-50%) scaleX(-1) rotateZ(90deg) rotateY(-180deg);
        }
        .side1, .side2 {
            background: #fff;
            position: absolute;
        }
        .side1 {
            display: grid;
            grid-template-columns: 1.2em 17.5em 10.675em;
            padding: 0.5em 0.5em 1.5em 0.125em;
            top: 0;
            left: 0;
            height: inherit;
            width: inherit;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;

            border: thin solid black;
        }
        .side2 {
            color: #ffff;
            font-family: "Tinos", serif;
            padding: 0.5em 0.75em;
            top: 50%;
            left: 50%;
            text-transform: uppercase;
            transform: translate(-50%,-50%) scaleX(-1) rotateZ(90deg);
            height: 30em;
            width: 13.75em;

        }
        .number-fields {
            margin-bottom: -0.4em;
        }

        .number-fields input {
            box-shadow: 0 0 0 0.15em #ddd inset;
            margin-right: -0.15em;
            text-align: center;
            height: 1.5em;
            width: 1.2em;
        }
        .note p em, .details label {
            text-transform: uppercase;
        }

        /* Note column */
        .note p {
            font-size: 0.25em;
            text-align: center;
            writing-mode: tb-rl;
            transform: translateY(3.3em) rotate(180deg);
            width: 100%;
        }
        .form-no {
            line-height: 2;
        }

        /* Details column (main) */
        .customer-no {
            margin: 0 0 0.5em 1em;
            text-align: center;
            width: 11.5em;
        }
        .customer-details {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-end;
            margin: 0 0.75em 0 0.25em;
        }
        .customer-details label:not(:last-of-type) {
            flex-basis: 8%;
        }
        .customer-details input {
            flex-basis: 100%;
            transform: translateY(0.2em);
        }
        .customer-details label + input, input + .detail-tip:not(:last-of-type) {
            flex-basis: 92%;
        }
        .signature {
            font-family: "Mr Dafoe", cursive;
            text-align: center;
        }
        .detail-tip {
            box-shadow: 0 1px 0 inset;
            display: block;
            font-size: 0.3em;
            line-height: 2;
            margin: -0.5em 0 -1.5em 8%;
            text-align: center;
            text-transform: uppercase;
            flex-basis: 100%;
        }
        input + .detail-tip:last-of-type {
            margin-left: 0;
        }
        .bank-name {
            font: 0.4em "Tinos", serif;
            margin: 0.8rem auto 0 auto;
        }
        .bank-name span {
            color: #888;
        }
        .bank-no:before, .bank-no:after {
            content: "|:";
            letter-spacing: -0.4em;
        }
        .bank-no:before {
            margin-right: 0.25em;
        }
        .bank-no:after {
            margin-left: -0.25em;
        }
        .details label.amount-label {
            text-transform: none;
            padding-left: 0.25em;
            transform: translateX(2em);
        }

        /* Amounts column */
        .amounts {
            display: grid;

            grid-template-rows: 0.6em repeat(7, 1.5em);
        }
        .amount-col-title {
            font-size: 0.5em;
            font-weight: bold;
            text-align: center;
        }
        .amounts label.amount-label {
            font-weight: normal;
            margin-right: 0.9em;
            align-self: end;
        }
        .amounts label.amount-label:first-of-type {
            font-size: 0.24em;
            padding-top: 0;
            text-align: center;
            text-transform: uppercase;
        }
        .amounts .number-fields {
            grid-column: 2 / 4;
        }
        .amounts .number-fields input {
            height: 1.6em;
        }
        .amounts .number-fields:not(:last-of-type) input:nth-child(5),
        .amounts .number-fields:last-of-type input:nth-child(8) {
            margin-right: 0;
        }
        .amounts .number-fields:last-of-type {
            grid-column: 1 / 4;
        }
        .two-line, .two-line-small {
            display: inline-block;
        }
        .two-line {
            transform: translateY(-50%);
        }
        .two-line-small {
            font-size: 0.65em;
            transform: translateY(-25%);
        }
        .dollars {
            display: inline-block;
            font-weight: bold;
            text-align: center;
            width: 0.9em;
        }
        .two-digits {
            margin-left: 1.2em;
        }

        /* Side 2 */
        .checks {
            display: grid;
            grid-template-columns: 27% 52% 21%;
            grid-template-rows: 0.75em repeat(12, 1.7em);
        }
        .checks-col-main-title {
            font-size: 0.75em;
        }
        .checks-col-main-title span {
            font-size: 0.6em;
            vertical-align: top;
        }
        .checks-col-title, .checks input, .total-tip {
            align-self: end;
        }
        .checks-col-title, .total-tip {
            text-align: center;
        }
        .checks-col-title {
            font-size: 0.4em;
        }
        .checks > input {
            box-shadow: 0 -1px 0 #aaa inset;
            width: 100%;
        }
        .checks input + div, .checks .total-tip + div {
            grid-column: 2 / 4;
        }
        .checks .number-fields {
            color: #fff;
            margin: 0;
            justify-self: end;
            align-self: end;
            position: relative;
        }
        .checks .number-fields input {
            box-shadow:
                0 -0.7em 0 #ddd,
                -0.3em -0.7em 0 #ddd,
                -0.3em 0 0 #ddd,
                -0.3em 1px 0 #ddd;
            margin-right: 0.2em;
            text-align: center;
            vertical-align: top;
            height: 1em;
            width: 1em;
        }
        .checks .number-fields input:first-of-type {
            box-shadow:
                0 -0.7em 0 #ddd,
                -0.5em -0.7em 0 #ddd,
                -0.5em 0 0 #ddd,
                -0.5em 1px 0 #ddd;
        }
        .checks .number-fields input:last-of-type {
            box-shadow:
                0 -0.7em 0 #ddd,
                -0.15em -0.7em 0 #ddd,
                -0.15em 0 0 #ddd,
                0.3em -0.7em 0 #ddd,
                0.3em 0 0 #ddd,
                0.3em 1px 0 #ddd;
            transform: translateX(-0.1em);
        }
        .checks .number-fields:before, .checks .number-fields:after {
            color: inherit;
            content: "";
            display: block;
            position: absolute;
            top: 100%;
            z-index: 1;
        }
        .checks .number-fields:before {
            border: 0;
            border-left: 0.2em solid transparent;
            border-right: 0.2em solid transparent;
            border-bottom: 0.4em solid;
            left: 2em;
            width: 0;
            height: 0;
        }
        .checks .number-fields:after {
            background: currentColor;
            box-shadow:
                -1.15em -0.65em 0,
                -1.25em -0.65em 0,
                -2.35em -0.65em 0,
                -2.45em -0.65em 0,
                -3.55em -0.65em 0,
                -3.65em -0.65em 0,
                -4.75em -0.65em 0,
                -4.85em -0.65em 0;
            right: 2.4em;
            width: 0.3em;
            height: 0.3em;
        }
        .checks .number-fields:last-child {
            box-shadow: -0.5em 0.7em 0 #ddd, 0 0.7em 0 #ddd;
        }
        .total-tip {
            font-size: 0.32em;
        }

    </style>




</head><body translate="no">
<form action="" id="myDiv">
    <div class="side1">
        <div class="note">
            <p><span class="form-no">Sycamore credit terms:</span> <br><em>Checks and other items are received for deposit subject to the provisions <br>of the uniform commercial code or applicable collection agreement</em>.</p>
        </div>
        <div class="details">
            <div class="customer-no">
                <h1>SYCAMORE CREDIT- Transaction Receipt</h1>
                <div class="">
                    <input type="text" size="20" value="<?php echo $customer_account ?>" style="border: solid thick gray;">
                </div>
                <label>LOAN ACCOUNT Number</label>
            </div>
            <div class="customer-details">
                <span><label class="amount-label">Current deposit -MWK  :<?php echo number_format($amount,2)?>&nbsp;|Total loan_payments -MWK  :<?php echo number_format($total_paid_amount,2)?> &nbsp&nbsp;|&nbsp&nbsp;Loan Balance  MK: <?php  echo number_format($total_amount-$total_paid_amount,2); ?> | Next Payment Date:<?php echo $next_date; ?></label> </span><input type="text"  style="font-size: 5px;" value="">
                <label for="customername">Name</label><input id="customername" type="text" name="customername" value="<?php echo $name ?>">
                <span class="detail-tip"><em>(Please print)</em></span>
                <label for="checkdate">Date</label><input id="checkdate" type="text" name="checkdate" value="<?php echo $date; ?>">
                <span class="detail-tip"><em>Deposits may not be available for immediate withdrawal</em></span>
                <input type="text" name="signature" class="signature">
                <span class="detail-tip">Sign here for cash received (if required)</span>
                <div class="bank-name">Sycamore credit <span>[Powered by Finrealm Micro finance Bank software]</span></div>

            </div>
            <code class="bank-no"><?php echo $transaction_id ?></code>
        </div>
        <div class="amounts" style="font-size: 12px; padding: 1em;">

            <img src="<?php echo base_url('uploads/').$settings->logo?>" alt="logo" width="200">
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br>


            <p style="margin-top: 80px;"><?php echo $settings->phone_number."/".$settings->company_email; ?></p>
            <pre style="margin-top: 100px;">
                    <?php echo $settings->address ?>
                </pre>


        </div>

    </div>

</form>

</body></html>
