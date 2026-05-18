<?php

if(!$this->session->userdata('user_id')){
    redirect(base_url().'auth/logout');
}
$session = get_by_id('user_access','Employee',$this->session->userdata('user_id'));
if($this->session->userdata('rand_id') !=$session->is_logged_in){
    redirect(base_url().'auth/logout');
}
$settings = get_by_id('settings','settings_id','1');
if(!empty($toggles)){
    $tg = $toggles;
}else{
    $tg = '';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $settings->company_name; ?> - Admin Dashboard </title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo base_url('admin_assets')?>/images/logo/favicon.png">

    <!-- page css -->
    <link href="<?php echo base_url('admin_assets')?>/vendors/datatables/dataTables.bootstrap.min.css" rel="stylesheet">
    <!-- Core css -->
    <link href="<?php echo base_url('admin_assets')?>/css/app.min.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo base_url('admin_assets')?>/css/theme-colors.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?php echo base_url('admin_assets/')?>css/toastr.min.css" rel="stylesheet">
    <link href="//cdn.quilljs.com/1.3.6/quill.bubble.css" rel="stylesheet">
    <link href="<?php echo base_url('jquery-ui/')?>jquery-ui.css" rel="stylesheet">
    <link type="text/css" href="<?php echo base_url()  ?>gisttech/css/tableexport.min.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="<?php echo base_url()?>lib/sweetalerts/sweetalert.css">
    <link href="<?php echo base_url('lib/')?>select2/dist/css/select2.min.css" rel="stylesheet" type="text/css"/>
    <style>
        .wrapper {
            position: relative;
        }
        .heading {
            margin: 25px 0;
            font-size: 24px;
        }
        .dashboard-stat {
            position: relative;
            display: block;
            margin: 0 0 25px;
            overflow: hidden;
            border-radius: 4px;
        }
        .dashboard-stat .visual {
            width: 80px;
            height: 80px;
            display: block;
            float: left;
            padding-top: 10px;
            padding-left: 15px;
            margin-bottom: 15px;
            font-size: 35px;
            line-height: 35px;
        }
        .dashboard-stat .visual > i {
            margin-left: -15px;
            font-size: 110px;
            line-height: 110px;
            color: #fff;
            opacity: 0.1;
        }
        .dashboard-stat .details {
            position: absolute;
            right: 15px;
            padding-right: 15px;
            color: #fff;
        }
        .dashboard-stat .details .number {
            padding-top: 25px;
            text-align: right;
            font-size: 34px;
            line-height: 36px;
            letter-spacing: -1px;
            margin-bottom: 0;
            font-weight: 300;
        }.dashboard-stat .details .numberr {
             padding-top: 25px;
             text-align: right;
             font-size: 20px;
             line-height: 36px;
             letter-spacing: -1px;
             margin-bottom: 0;
             font-weight: 300;
         }
        .dashboard-stat .details .number .desc {
            text-transform: capitalize;
            text-align: right;
            font-size: 16px;
            letter-spacing: 0;
            font-weight: 300;
        }
        .dashboard-stat.blue {
            background-color: #337ab7;
        }  .dashboard-stat.green {
               background-color: #24C16B;
           }
        .dashboard-stat.red {
            background-color: #e7505a;
        }
        .dashboard-stat.purple {
            background-color: #8E44AD;
        }
        .dashboard-stat.hoki {
            background-color: #67809F;
        }
        .dashboard-stat.orange {
            background-color: #ffaf7a;
        }
        fieldset {
            margin: 0 0 30px 0;
            border: 1px solid #ccc;
        }
        hr.dash {
            border: 1px solid #24C16B;
        }

        legend {
            background: #eee;
            padding: 4px 10px;
            color: #000;
            margin: 0 auto;
            display: block;
        }
        input[type="file"] {
            display: none;
        }
        .custom-file-upload {
            border: 1px solid #ccc;
            display: inline-block;
            padding: 6px 12px;
            cursor: pointer;
        }

        .anticon {
            line-height: 0;c
        vertical-align: -.125em;
            color: #ffff;
        }

        .tableCss
        {
            border: solid 1px #e6e5e5;
        }


        .tableCss thead
        {
            background-color: #0094ff;
            color:#fff;
            padding: 5px;
            text-align:center;
        }

        .tableCss td
        {
            border: solid 1px #e6e5e5;
            padding: 5px;
        }

        /*for footer*/
        .tabTask tfoot
        {
            background-color: #000;
            color: #fff;
            padding: 5px;
        }

        /*for body*/
        .tabTask tbody
        {
            background-color: #e9e7e7;
            color: #000;
            padding: 5px;
        }
        .due {background-color: #F3D8D8}
        .paid {background-color: #D1EFD1}
        .due_now {background-color: #DFEFFF}

        .tool-tip {
            display: inline-block;
        }

        .tool-tip [disabled] {
            pointer-events: none;
        }
        .mishe{

        }
        :root{
            --white:#fff;
            --smoke-white:#f1f3f5;
            --blue:#4169e1;
        }
        .container{
            position:relative;
            width:100%;
            height:100%;
            display:flex;
            justify-content:center;
            align-items:center;
        }
        .selector{
            position:relative;
            width:60%;
            background-color:#f1f3f5;
            height:60px;
            display:flex;
            justify-content:space-around;
            align-items:center;
            border-radius:9999px;
            box-shadow:0 0 16px rgba(0,0,0,.2);
        }
        .selecotr-item{
            position:relative;
            flex-basis:calc(70% / 3);
            height:100%;
            display:flex;
            justify-content:center;
            align-items:center;
        }
        .selector-item_radio{
            appearance:none;
            display:none;
        }
        .selector-item_label{
            position:relative;
            height:80%;
            width:100%;
            text-align:center;
            border-radius:9999px;
            line-height:400%;
            font-weight:900;
            transition-duration:.5s;
            transition-property:transform, color, box-shadow;
            transform:none;
        }
        .selector-item_radio:checked + .selector-item_label{
            background-color:#24C16B;
            color:white;
            box-shadow:0 0 4px rgba(0,0,0,.5),0 2px 4px rgba(0,0,0,.5);
            transform:translateY(-2px);
        }
        @media (max-width:480px) {
            .selector{
                width: 90%;
            }
        }
    </style>

</head>

<body>
<div class="app is-primary">
    <div class="layout">
        <!-- Header START -->
        <div class="header" style="border:thin #24C16B solid;border-radius: 50px 50px 0px 0px;">
            <div class="logo logo-dark">
                <a href="<?php echo base_url('Admin')?>">
                    <img src="<?php echo base_url('uploads/').$settings->logo?>" alt="Logo">
                    <img class="logo-fold" src="<?php echo base_url('uploads/').$settings->logo?>" alt="Logo">
                </a>
            </div>
            <div class="logo logo-white">
                <a href="<?php echo base_url('Admin')?>">
                    <img src="<?php echo base_url('uploads/').$settings->logo?>" alt="Logo" style="border-radius: 15px;">
                    <img class="logo-fold" src="<?php echo base_url('uploads/').$settings->logo?>" alt="Logo">
                </a>
            </div>
            <div class="nav-wrap">
                <ul class="nav-left">
                    <li class="desktop-toggle">
                        <a href="javascript:void(0);">
                            <i class="anticon"></i>
                        </a>
                    </li>
                    <li class="mobile-toggle">
                        <a href="javascript:void(0);">
                            <i class="anticon"></i>
                        </a>
                    </li>
                    <h5 style="font-family:'fantasy';color: #24C16B; font-weight: bolder;background-color: #fff; padding: 0.5em;border-radius: 50px 0px 50px 0px;">Welcome to Finance Realm System  </h5>
                </ul>
                <ul class="nav-right">
                    <h5 style="font-family:'fantasy';color: #24C16B; font-weight: bolder;background-color: #fff; padding: 0.5em;border-radius: 12px;">CURRENT USER: <font color="#24C16B" style="text-underline: green;"><?php echo $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname')."(".$this->session->userdata('RoleName').")"; ?></font> </h5>

                    <li class="dropdown dropdown-animated scale-left">
                        <div class="pointer" data-toggle="dropdown">
                            <div class="avatar avatar-image  m-h-10 m-r-15">
                                <img src="<?php echo base_url('uploads/').$this->session->userdata('profile_photo')?>"  alt="">
                            </div>
                        </div>
                        <div class="p-b-15 p-t-20 dropdown-menu pop-profile">
                            <div class="p-h-20 p-b-15 m-b-10 border-bottom">
                                <div class="d-flex m-r-50">
                                    <div class="avatar avatar-lg avatar-image">
                                        <img src="<?php echo base_url('uploads/').$this->session->userdata('profile_photo')?>" alt="">
                                    </div>
                                    <div class="m-l-10">
                                        <p class="m-b-0 text-dark font-weight-semibold"><?php echo $this->session->userdata('Firstname')." " .  $this->session->userdata('Lastname')?></p>
                                        <p class="m-b-0 opacity-07"><?php echo $this->session->userdata('Firstname') ?></p>
                                    </div>
                                </div>
                            </div>
                            <a href="<?php  echo base_url('Employees/profile')?>" class="dropdown-item d-block p-h-15 p-v-10">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="anticon opacity-04 font-size-16 anticon-user"></i>
                                        <span class="m-l-10">Edit Profile</span>
                                    </div>
                                    <i class="anticon font-size-10 anticon-right"></i>
                                </div>
                            </a>

                            <a href="<?php  echo base_url('auth/logout')?>" class="dropdown-item d-block p-h-15 p-v-10">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <i class="anticon opacity-04 font-size-16 anticon-logout"></i>
                                        <span class="m-l-10">Logout</span>
                                    </div>
                                    <i class="anticon font-size-10 anticon-right"></i>
                                </div>
                            </a>
                        </div>
                    </li>

                </ul>
            </div>
        </div>
        <!-- Header END -->

        <!-- Side Nav START -->
        <div class="side-nav">
            <div class="side-nav-inner" style="border: thin solid #0e9970; border-radius: 50px 0px 50px 0px;">
                <br>

                <ul class="side-nav-menu scrollable">
                    <li class="nav-item">
                        <a  href="<?php echo base_url('Admin')?>">
                                <span class="icon-holder">
                                    <i class="fa fa-home"></i>
                                </span>
                            <span class="title">Home</span>
                            <span class="arrow">
                                    <i class="arrow-icon"></i>
                                </span>
                        </a>

                    </li>


                    <?= display_menu_admin(0, 1, $tg); ?>


                    <li class="nav-item ">
                        <a class="dropdown-toggle" href="<?php  echo base_url('auth/logout')?>">
                                <span class="icon-holder">
                                    <i class="fa fa-lock"></i>
                                </span>
                            <span class="title">Logout</span>
                            <span class="arrow">
                                    <i class="arrow-icon"></i>
                                </span>
                        </a>


                    </li>
                </ul>
            </div>
        </div>
        <!-- Side Nav END -->
        <div class="page-container">
