<?php
$linkk = base_url('admin_assets/images/pattern.png');
$imgg = 'data:image;base64,' . base64_encode(file_get_contents($linkk));
$settings = get_by_id('settings', 'settings_id', '1');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<?php $this->load->view('transactions/_print_transactions_styles', array('imgg' => $imgg)); ?>
</head>
<body>

<?php
$batch_meta = '<strong>Batch:</strong> ' . htmlspecialchars($batch)
    . ' &nbsp;|&nbsp; <strong>Group:</strong> ' . htmlspecialchars($group_name)
    . ' (' . htmlspecialchars($group_code) . ')'
    . ' &nbsp;|&nbsp; <strong>Members:</strong> ' . count($statements);
$this->load->view('transactions/_print_transactions_letterhead', array(
    'settings' => $settings,
    'batch_subtitle' => 'Group Batch Account Statements',
    'batch_meta' => $batch_meta,
));
?>

<?php foreach ($statements as $index => $statement): ?>
<div class="batch-member-statement<?php echo ($index > 0) ? ' batch-member-break' : ''; ?>">
<?php $this->load->view('transactions/_print_transactions_body', $statement); ?>
</div>
<?php endforeach; ?>

</body>
</html>
