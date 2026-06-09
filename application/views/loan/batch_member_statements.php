<?php
$linkk = base_url('admin_assets/images/pattern.png');
$imgg = 'data:image;base64,' . base64_encode(file_get_contents($linkk));
$settings = get_by_id('settings', 'settings_id', '1');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head>
    <style>
        p { text-align: justify; margin: 0; }
        table { width: 100%; }
        table.collapse { border-collapse: collapse; }
        tr td, tr th { text-align: right; }
        hr { margin: 15px 0; }
        h1, h2 { margin: 0; }
        .title { color: #000; font-size: 18px; font-weight: normal; }
        .section { border-bottom: 1px #D4D4D4 solid; padding: 10px 0; margin-bottom: 20px; }
        .section .content { margin-left: 10px; }
        #pattern-style-a {
            font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
            font-size: 12px;
            width: 100%;
            text-align: left;
            border-collapse: collapse;
            background: url('<?php echo $imgg; ?>');
        }
        #pattern-style-a th {
            font-size: 13px;
            font-weight: normal;
            padding: 8px;
            border-bottom: 1px solid #fff;
            color: #039;
        }
        #pattern-style-a td {
            padding: 3px;
            border-bottom: 1px solid #fff;
            color: #000;
        }
        .page-break { page-break-before: always; }
        .batch-cover {
            text-align: center;
            padding: 30px 0 40px;
        }
    </style>
</head><body>

<div class="batch-cover">
    <h1><?php echo htmlspecialchars($settings->company_name); ?></h1>
    <h2>Group Batch Loan Statements</h2>
    <p><strong>Batch:</strong> <?php echo htmlspecialchars($batch); ?></p>
    <p><strong>Group:</strong> <?php echo htmlspecialchars($group_name); ?> (<?php echo htmlspecialchars($group_code); ?>)</p>
    <p><strong>Members:</strong> <?php echo (int) $member_count; ?></p>
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
</div>

<?php
$index = 0;
foreach ($statements as $stmt):
    if ($index > 0):
?>
<div class="page-break"></div>
<?php
    endif;
    $index++;
    $stmt['statement_title'] = 'Member Loan Statement — ' . $stmt['loan_customer'];
    $stmt['batch_label'] = $batch;
    $stmt['group_label'] = $group_name . ' (' . $group_code . ')';
    $stmt['settings'] = $settings;
    $this->load->view('loan/_loan_statement_page', $stmt);
endforeach;
?>

<p style="text-align: center; margin-top: 30px;"><strong>********** END OF BATCH STATEMENTS **********</strong></p>

</body></html>
