<?php
$products = get_all('loan_products');
$branches = get_all('branches');

if (!isset($relationship_supervisors)) {
    $ci =& get_instance();
    $ci->load->model('Employees_model');
    $relationship_supervisors = $ci->Employees_model->get_relationship_supervisors();
}
if (!isset($relationship_officers)) {
    if (!isset($ci)) {
        $ci =& get_instance();
        $ci->load->model('Employees_model');
    }
    $relationship_officers = $ci->Employees_model->get_relationship_officers(false);
}

$form_action = !empty($filter_form_action) ? $filter_form_action : base_url('loan/track');

$status_options = array(

    'All' => 'All statuses',

    'ACTIVE' => 'ACTIVE',

    'INITIATED' => 'INITIATED',

    'RECOMMENDED' => 'RECOMMENDED',

    'APPROVED' => 'APPROVED',

    'REJECTED' => 'REJECTED',

    'CLOSED' => 'CLOSED',

    'WRITTEN_OFF' => 'WRITTEN_OFF',

    'DELETED' => 'ARCHIVED',

);

$selected_status = $this->input->get('status');

if ($selected_status === null || $selected_status === '') {

    $selected_status = isset($filter_default_status) ? $filter_default_status : 'All';

}

$lock_officer = !empty($filter_lock_officer) && !empty($filter_officer_id);

$selected_supervisor = $this->input->get('supervisor');

$supervisor_active = ($selected_supervisor !== null && $selected_supervisor !== '' && $selected_supervisor !== 'All');

?>

<div class="loan-list-filters mb-3">

    <form action="<?php echo htmlspecialchars($form_action); ?>" method="get" class="loan-list-filters-form" id="loan-list-filters-form">

        <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">

            <label class="mb-0">Branch:</label>

            <select name="branch" class="select2">

                <option value="All">All Branch</option>

                <?php foreach ($branches as $branch) { ?>

                    <option value="<?php echo htmlspecialchars($branch->Code); ?>" <?php if ($this->input->get('branch') == $branch->Code) { echo 'selected'; } ?>><?php echo htmlspecialchars($branch->BranchName); ?></option>

                <?php } ?>

            </select>



            <label class="mb-0">Product:</label>

            <select name="product" class="select2">

                <option value="All">All Products</option>

                <?php foreach ($products as $product) { ?>

                    <option value="<?php echo (int) $product->loan_product_id; ?>" <?php if ($this->input->get('product') == $product->loan_product_id) { echo 'selected'; } ?>><?php echo htmlspecialchars($product->product_name . '(' . $product->product_code . ')'); ?></option>

                <?php } ?>

            </select>



            <label class="mb-0">Status:</label>

            <select name="status">

                <?php foreach ($status_options as $val => $label) {

                    $sel = ($selected_status === $val) ? ' selected' : '';

                    echo '<option value="' . htmlspecialchars($val) . '"' . $sel . '>' . htmlspecialchars($label) . '</option>';

                } ?>

            </select>



            <label class="mb-0">Rel. supervisor:</label>

            <select name="supervisor" id="loan-filter-supervisor" class="select2" <?php echo $lock_officer ? '' : ''; ?>>

                <option value="All">All supervisors</option>

                <?php foreach ($relationship_supervisors as $sup) { ?>

                    <option value="<?php echo (int) $sup->id; ?>" <?php if ($selected_supervisor == $sup->id) { echo 'selected'; } ?>>

                        <?php echo htmlspecialchars(trim($sup->Firstname . ' ' . $sup->Lastname)); ?>

                    </option>

                <?php } ?>

            </select>



            <?php if ($lock_officer) { ?>

                <label class="mb-0">Officer:</label>

                <span class="text-muted"><?php

                    $me = get_by_id('employees', 'id', $filter_officer_id);

                    echo $me ? htmlspecialchars($me->Firstname . ' ' . $me->Lastname) : 'My loans';

                ?></span>

                <input type="hidden" name="user" value="<?php echo (int) $filter_officer_id; ?>">

            <?php } else { ?>

                <label class="mb-0">Officer:</label>

                <select name="user" id="loan-filter-officer" class="select2">

                    <option value="All">All officers</option>

                    <?php

                    $officer_list = !empty($relationship_officers) ? $relationship_officers : array();

                    if (empty($officer_list)) {

                        $officer_list = get_all('employees');

                    }

                    foreach ($officer_list as $item) {

                        $oid = isset($item->id) ? $item->id : (isset($item->empid) ? $item->empid : 0);

                        ?>

                        <option value="<?php echo (int) $oid; ?>" <?php if ($this->input->get('user') == $oid) { echo 'selected'; } ?>><?php echo htmlspecialchars($item->Firstname . ' ' . $item->Lastname); ?></option>

                    <?php } ?>

                </select>

            <?php } ?>



            <label class="mb-0">Loan number:</label>

            <input type="text" name="loan_number" value="<?php echo htmlspecialchars((string) $this->input->get('loan_number')); ?>" placeholder="e.g. SCL202602066820" style="min-width: 160px;">



            <label class="mb-0">Batch:</label>

            <input type="text" name="batch" value="<?php echo htmlspecialchars((string) $this->input->get('batch')); ?>" placeholder="Batch no." style="min-width: 100px;">



            <label class="mb-0">From:</label>

            <input type="date" name="from" value="<?php echo htmlspecialchars((string) $this->input->get('from')); ?>">



            <label class="mb-0">To:</label>

            <input type="date" name="to" value="<?php echo htmlspecialchars((string) $this->input->get('to')); ?>">



            <button type="submit" class="btn btn-primary btn-sm" name="search" value="filter">Filter</button>

            <a href="<?php echo htmlspecialchars($form_action); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>

        </div>

    </form>

</div>

<script>

(function () {

    var sup = document.getElementById('loan-filter-supervisor');

    var usr = document.getElementById('loan-filter-officer');

    var form = document.getElementById('loan-list-filters-form');

    if (!form) {

        return;

    }

    function syncOfficerDisabled() {

        if (!sup || !usr) {

            return;

        }

        var useSupervisor = sup.value && sup.value !== 'All';

        if (useSupervisor) {

            usr.value = 'All';

            usr.disabled = true;

        } else {

            usr.disabled = false;

        }

    }

    if (sup) {

        sup.addEventListener('change', function () {

            if (this.value && this.value !== 'All' && usr) {

                usr.value = 'All';

            }

            syncOfficerDisabled();

        });

    }

    if (usr) {

        usr.addEventListener('change', function () {

            if (this.value && this.value !== 'All' && sup) {

                sup.value = 'All';

            }

        });

    }

    form.addEventListener('submit', function () {

        if (sup && sup.value && sup.value !== 'All' && usr) {

            usr.value = 'All';

            usr.disabled = false;

        }

    });

    syncOfficerDisabled();

})();

</script>


