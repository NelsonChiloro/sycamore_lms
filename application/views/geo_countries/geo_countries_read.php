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
        <h2 style="margin-top:0px">Geo_countries Read</h2>
        <table class="table">
	    <tr><td>Name</td><td><?php echo $name; ?></td></tr>
	    <tr><td>Abv3</td><td><?php echo $abv3; ?></td></tr>
	    <tr><td>Abv3 Alt</td><td><?php echo $abv3_alt; ?></td></tr>
	    <tr><td>Code</td><td><?php echo $code; ?></td></tr>
	    <tr><td>Slug</td><td><?php echo $slug; ?></td></tr>
	    <tr><td></td><td><a href="<?php echo site_url('geo_countries') ?>" class="btn btn-default">Cancel</a></td></tr>
	</table>
        </body>
</html>