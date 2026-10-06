<!doctype html>
<html>
    <head>
        <title>harviacode.com - codeigniter crud generator</title>
        <link rel="stylesheet" href="<?php echo base_url('assets/bootstrap/css/bootstrap.min.css') ?>"/>
        <style>
            body{
                padding: 15px;
            }
        </style>
    </head>
    <body>
        <h2 style="margin-top:0px">Menuitems Read</h2>
        <table class="table">
	    <tr><td>Mid</td><td><?php echo $mid; ?></td></tr>
	    <tr><td>Label</td><td><?php echo $label; ?></td></tr>
	    <tr><td>Method</td><td><?php echo $method; ?></td></tr>
	    <tr><td>Fa Icon</td><td><?php echo $fa_icon; ?></td></tr>
	    <tr><td>Sortt</td><td><?php echo $sortt; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('menuitems') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>