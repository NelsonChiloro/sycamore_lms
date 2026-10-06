<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Forgot Password - Finance Realm</title>
    <link rel="shortcut icon" href="<?php echo base_url('admin_assets/images/logo/favicon.png'); ?>">
    <link href="<?php echo base_url('admin_assets/css/app.min.css'); ?>" rel="stylesheet">
</head>
<body>
<div class="app"><div class="container-fluid p-h-0 p-v-20 bg full-height d-flex" style="background-image:url('<?php echo base_url('admin_assets/images/others/login-3.png'); ?>')"><div class="container d-flex h-100"><div class="row align-items-center w-100"><div class="col-md-7 col-lg-5 m-h-auto"><div class="card shadow-lg"><div class="card-body" style="padding:2em;border:solid #24C16B thick;border-radius:50px 0 50px 0">
    <h2>Forgot Password</h2>
    <p class="text-muted">Enter your username or registered email address. We will email you a secure reset link.</p>
    <?php if (!empty($message)): ?><div class="alert alert-success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
    <form method="post" action="<?php echo base_url('forgot-password/submit'); ?>">
        <div class="form-group"><label for="identity">Username or email address</label><input required class="form-control" type="text" name="identity" id="identity" maxlength="200" autocomplete="username"></div>
        <button class="btn btn-block" style="background:#24C16B;color:white">Send Reset Link</button>
    </form>
    <p class="m-t-20"><a href="<?php echo base_url('login'); ?>">Return to login</a></p>
</div></div></div></div></div></div></div>
</body></html>
