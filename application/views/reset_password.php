<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Reset Password - Finance Realm</title>
    <link rel="shortcut icon" href="<?php echo base_url('admin_assets/images/logo/favicon.png'); ?>">
    <link href="<?php echo base_url('admin_assets/css/app.min.css'); ?>" rel="stylesheet">
</head>
<body>
<div class="app"><div class="container-fluid p-h-0 p-v-20 bg full-height d-flex" style="background-image:url('<?php echo base_url('admin_assets/images/others/login-3.png'); ?>')"><div class="container d-flex h-100"><div class="row align-items-center w-100"><div class="col-md-7 col-lg-5 m-h-auto"><div class="card shadow-lg"><div class="card-body" style="padding:2em;border:solid #24C16B thick;border-radius:50px 0 50px 0">
    <h2>Reset Password</h2>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
    <?php if (!$valid_token): ?>
        <div class="alert alert-danger">This password reset link is invalid, expired, or has already been used.</div>
        <a href="<?php echo base_url('forgot-password'); ?>">Request another reset link</a>
    <?php else: ?>
        <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>
        <form method="post" action="<?php echo base_url('reset-password/submit'); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-group"><label for="password">New password</label><input required class="form-control" type="password" name="password" id="password" minlength="8" maxlength="72" autocomplete="new-password"></div>
            <div class="form-group"><label for="password_confirm">Confirm new password</label><input required class="form-control" type="password" name="password_confirm" id="password_confirm" minlength="8" maxlength="72" autocomplete="new-password"></div>
            <button class="btn btn-block" style="background:#24C16B;color:white">Change Password</button>
        </form>
    <?php endif; ?>
    <p class="m-t-20"><a href="<?php echo base_url('login'); ?>">Return to login</a></p>
</div></div></div></div></div></div></div>
</body></html>
