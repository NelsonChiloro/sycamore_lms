<?php
$linkk = base_url('admin_assets/images/pattern.png');
$imgg = 'data:image;base64,' . base64_encode(file_get_contents($linkk));
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<?php $this->load->view('transactions/_print_transactions_styles', array('imgg' => $imgg)); ?>
</head>
<body>
<?php $this->load->view('transactions/_print_transactions_letterhead'); ?>
<?php $this->load->view('transactions/_print_transactions_body'); ?>
</body>
</html>
