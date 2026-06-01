<?php
if (!isset($relationship_supervisors)) {
    $relationship_supervisors = report_load_relationship_supervisors();
}
$selected_supervisor = $this->input->get('supervisor');
if ($selected_supervisor === null || $selected_supervisor === '') {
    $selected_supervisor = $this->input->post('supervisor');
}
$supervisor_active = ($selected_supervisor !== null && $selected_supervisor !== '' && $selected_supervisor !== 'All');
$officer_select_id = !empty($officer_select_id) ? $officer_select_id : 'report-filter-officer';
?>
<label class="mb-0">Rel. supervisor:</label>
<select name="supervisor" id="report-filter-supervisor" class="select2 form-control" style="min-width: 160px;">
    <option value="All">All supervisors</option>
    <?php foreach ($relationship_supervisors as $sup) { ?>
        <option value="<?php echo (int) $sup->id; ?>" <?php if ((string) $selected_supervisor === (string) $sup->id) { echo 'selected'; } ?>>
            <?php echo htmlspecialchars(trim($sup->Firstname . ' ' . $sup->Lastname)); ?>
        </option>
    <?php } ?>
</select>
<script>
(function () {
    var sup = document.getElementById('report-filter-supervisor');
    var off = document.getElementById('<?php echo addslashes($officer_select_id); ?>');
    if (!sup || !off) return;
    function syncOfficer() {
        var on = sup.value && sup.value !== 'All';
        off.disabled = on;
        if (on) off.value = 'All';
    }
    sup.addEventListener('change', syncOfficer);
    syncOfficer();
})();
</script>
