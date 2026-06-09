<?php
if (!isset($settings)) {
    $settings = get_by_id('settings', 'settings_id', '1');
}
$link = base_url('uploads/') . $settings->logo;
$img = 'data:image;base64,' . base64_encode(file_get_contents($link));
?>
<div class="section">
	<div class="content">
		<h1 style="text-align: center;"><?php echo $settings->company_name; ?></h1>
		<table width="100%" cellpadding="0" cellspacing="0">
			<tr>
				<td width="45%" valign="top" style="padding-right: 1em;">
					<img src="<?php echo $img; ?>" alt="">
				</td>
				<td width="55%" valign="top" align="right" style="text-align: right;">
					<div class="letterhead-address" style="text-align: right;">
						<?php echo $settings->address; ?>
						<p style="margin: 4px 0 0 0; padding: 0; text-align: right; line-height: 1.4;">
							<?php echo $settings->company_email; ?>/<?php echo $settings->phone_number; ?>
						</p>
					</div>
				</td>
			</tr>
		</table>
		<hr>
		<?php if (!empty($batch_subtitle)): ?>
			<h2 style="text-align: center;"><?php echo htmlspecialchars($batch_subtitle); ?></h2>
			<?php if (!empty($batch_meta)): ?>
				<p style="text-align: center;"><?php echo $batch_meta; ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
