<a href="<?php echo base_url('Reports/export_it')?>">Export</a>

<form action="<?php echo base_url('Reports/export_it')?>" method="get">
	<input type="text" name="filename" value="<?php echo $this->input->get('filename')?>">
	<input type="submit" name="search" value="export">
	<input type="submit" name="search" value="filter">
	<input type="submit" name="search" value="pdf">
</form>

<table border="1">
	<tr>
		<th>NO</th>
		<th>isjgsdj</th>
		<th>Path</th>
	</tr>
	<?php
	$count = 1;
	foreach ($toexport as $a){
		?>

	<tr>
		<td><?php  echo  $count ;?></td>
		<td><?php echo $a->repayment_automatic ?></td>
		<td><?php echo $a->cron_path ?></td>

	</tr>


	<?php
	}
	?>

</table>
