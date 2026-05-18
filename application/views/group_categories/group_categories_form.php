
        <form action="<?php echo $action; ?>" method="post">
	    <div class="form-group">
            <label for="varchar">Group Category Name <?php echo form_error('group_category_name') ?></label>
            <input type="text" class="form-control" name="group_category_name" id="group_category_name" placeholder="Group Category Name" value="<?php echo $group_category_name; ?>" />
        </div>
	    <input type="hidden" name="group_category_id" value="<?php echo $group_category_id; ?>" /> 
	    <button type="submit" class="btn btn-primary"><?php echo $button ?></button> 
	    <a href="<?php echo site_url('group_categories') ?>" class="btn btn-default">Cancel</a>
		</form>
