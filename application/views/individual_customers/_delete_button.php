<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<form method="post" action="<?php echo site_url('individual_customers/delete/'.(int)$customer_delete_id); ?>" onsubmit="return confirm('Delete this customer from customer lists? Historical records will be retained.');">
    <?php if($this->config->item('csrf_protection')): ?>
    <input type="hidden" name="<?php echo htmlspecialchars($this->security->get_csrf_token_name(),ENT_QUOTES,'UTF-8'); ?>" value="<?php echo htmlspecialchars($this->security->get_csrf_hash(),ENT_QUOTES,'UTF-8'); ?>">
    <?php endif; ?>
    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
</form>
