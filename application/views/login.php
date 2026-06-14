<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Finance Realm</title>

	<!-- Favicon -->
	<link rel="shortcut icon" href="<?php echo base_url('admin_assets')?>/images/logo/favicon.png">

	<!-- page css -->

	<!-- Core css -->
	<link href="<?php echo base_url('admin_assets')?>/css/app.min.css?v=<?php echo time(); ?>" rel="stylesheet">
	<link href="<?php echo base_url('admin_assets')?>/css/theme-colors.css?v=<?php echo time(); ?>" rel="stylesheet">

</head>

<body>
<?php
$settings = get_by_id('settings','settings_id','1');
?>
<div class="app">
	<div class="container-fluid p-h-0 p-v-20 bg full-height d-flex" style="background-image: url('<?php echo base_url('admin_assets')?>/images/others/login-3.png')">
		<div class="d-flex flex-column justify-content-between w-100">
			<div class="container d-flex h-100">
				<div class="row align-items-center w-100">
					<div class="col-md-7 col-lg-5 m-h-auto">
						<div class="card shadow-lg">
							<div class="card-body" style="padding:2em; border: solid #24C16B thick;border-radius: 50px 0px 50px 0px;">
								<div class="d-flex align-items-center justify-content-between m-b-30">
									<img class="img-fluid" alt="" src="<?php echo base_url('uploads/632824996757d9865.png')?>">
									<h2 class="m-b-0">Login</h2>
								</div>
							<?php
								$error = isset($error) ? $error : null;
								$active = isset($active) ? $active : null;
								if (!empty($active)) {
									?>
									<div class="alert-danger" style="padding: 2em;">
										Sorry another session with your credentials is on,Please log out or ask admin to clear you
									</div>
									<?php
								}
								?>
								<?php
								if (!empty($error)) {
									?>
									<div class="alert alert-danger text-black-50" role="alert">
										Sorry !, Either email or password is not correct;
									</div>
									<?php
								}
								?>
								<p class="text-muted" style="font-size:12px;">
									Page stuck loading?
									<a href="<?php echo base_url('clear_session.php'); ?>">Reset session</a>
								</p>
								<form method="post" action="<?php echo base_url('Auth/authenticate')?>">
									<div class="form-group">
										<label class="font-weight-semibold" for="userName">Username:</label>
										<div class="input-affix">
											<i class="prefix-icon anticon anticon-user"></i>
											<input required type="text" name="username" class="form-control" id="userName" placeholder="Enter username">
										</div>
									</div>
									<div class="form-group">
										<label class="font-weight-semibold" for="password">Password:</label>
										<a class="float-right font-size-13 text-muted" href="#">Forget Password?</a>
										<div class="input-affix m-b-10">
											<i class="prefix-icon anticon anticon-lock"></i>
											<input  required type="password"  name="password" class="form-control" id="password" placeholder="Password">
										</div>
									</div>
									<div class="form-group">
										<div class="d-flex align-items-center justify-content-between">

											<button class="btn  btn-block btn-sm" style="border:thin #24C16B solid; padding: 1em; background-color: #24C16B; color: white;">Sign In</button>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="d-none d-md-flex p-h-40 justify-content-between">
				<span class="">©    </span>
				<ul class="list-inline">
					<li class="list-inline-item">
						<a class="text-dark text-link" href="#">  </a>
					</li>
					<li class="list-inline-item">
						<a class="text-dark text-link" href="#">   </a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>


<!-- Core Vendors JS -->
<script src="<?php echo base_url('admin_assets')?>/js/vendors.min.js"></script>

<!-- page js -->

<!-- Core JS -->
<script src="<?php echo base_url('admin_assets')?>/js/app.min.js"></script>

</body>
</html>
