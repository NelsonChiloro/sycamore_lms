<?php

$settings = get_by_id('settings','settings_id','1');

?>
<!-- Footer START -->
<footer class="footer">
	<div class="footer-content">
		<p class="m-b-0">Copyright © 2024 . All rights reserved. by Infocus Tech (0994099461)</p>
		<span>
                            <a href="#" class="text-gray m-r-15">Term &amp; Conditions</a>
                            <a href="#" class="text-gray">Privacy &amp; Policy</a>
                        </span>
	</div>
</footer>
<!-- Footer END -->

</div>
<!-- Page Container END -->

</div>
</div>

<div aria-hidden="true" class="onboarding-modal modal fade" id="kyc_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Add KYC to this customer</h4>
				<form action="<?php echo base_url('Proofofidentity/create_action')?>" method="post" class="form-row" enctype="multipart/form-data">
					<div class="form-group col-6">
						<input type="text" hidden name="ClientId" id="Client">
						<label for="enum">IDType </label>
						<select class="form-control" name="IDType" id="IDType" required >
							<option value="">--select--</option>
							<option value="NATIONAL_IDENTITY_CARD">NATIONAL IDENTITY CARD</option>
							<option value="DRIVING_LISENCE">DRIVING LICENCE</option>
							<option value="PASSPORT">PASSPORT</option>
							<option value="WORK_PERMIT">WORK PERMIT</option>
							<option value="VOTER_REGISTRATION">VOTER REGISTRATION</option>
							<option value="PUBLIC_STATE_OFFICIAL_LETTER">PUBLIC STATE OFFICIAL LETTER</option>

						</select>
					</div>
					<div class="form-group col-6">
						<label for="varchar">IDNumber </label>
						<input type="text" class="form-control" name="IDNumber" id="IDNumber" placeholder="IDNumber" required  />
					</div>
					<div class="form-group col-6">
						<label for="date">IssueDate </label>
						<input type="date" class="form-control" name="IssueDate" id="IssueDate" placeholder="IssueDate" required />
					</div>
					<div class="form-group col-6">
						<label for="date">ExpiryDate * </label>
						<input type="date" class="form-control" name="ExpiryDate" id="ExpiryDate" placeholder="ExpiryDate"  required />
					</div>
					<div class="form-group col-6">
						<label for="id_front" class="custom-file-upload"> Upload front photo of ID </label>
						<input type="file"  onchange="uploadcommon('id_front')"   id="id_front"  />
						<input type="text" id="id_front1"  name="id_front" hidden required>
						<div id="id_front2">
                            <img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
						</div>
					</div>
					<div class="form-group col-6">
						<label for="Id_back" class="custom-file-upload"> Upload Back photo of ID * </label>
						<input type="file" class="upload-btn-wrapper"  onchange="uploadcommon('Id_back')"  id="Id_back" placeholder="Id Back"  />
						<input type="text" id="Id_back1" name="Id_back" hidden required>
						<div id="Id_back2">
                            <img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
						</div>
					</div>

					<div class="form-group col-6">
						<label for="photograph"  class="custom-file-upload">Upload Photograph </label>
						<input type="file"  onchange="uploadcommon('photograph')"    id="photograph" placeholder="Photograph"  />
						<input type="text" id="photograph1" name="photograph" hidden required>
						<div id="photograph2">
                            <img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
						</div>
					</div>
					<div class="form-group col-6">
						<label for="signature" class="custom-file-upload"> Upload Signature </label>
						<input type="file" onchange="uploadcommon('signature')"    id="signature" placeholder="Signature" />
						<input type="text" id="signature1" name="signature" hidden required>
						<div id="signature2">
                            <img src="<?php echo base_url('uploads/holder.png')?>" alt="" height="100" width="100">
						</div>
					</div>


					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="pay_charge_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Pay loan processing charge</h4>
				<form action="<?php echo base_url('Transactions/create_action')?>" method="post" class="form-row" enctype="multipart/form-data">
					<input type="text" name="loan_id" id="loan_d" hidden>
					<div class="form-group col-6">
						<label for="varchar">Loan number </label>
						<input type="text" class="form-control" name="loan" id="loan_idd" placeholder="Loan number" disabled required  />
					</div>
					<div class="form-group col-6">
						<label for="date">Total amount MK</label>
						<input type="text" class="form-control" name="amount" id="charge_amount" readonly required />
					</div>


					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="modal-approval" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Approval process</h4>
				<p style="color: red;">Are you sure you want to do this process <i id="textb"></i></p>
				<form action="<?php echo base_url('Groups/approval')?>" method="post" class="form-row" enctype="multipart/form-data">
					<input type="text" name="group_id" id="group_id" hidden>
					<input type="text" name="action" id="actionb" hidden>

					<div class="form-group col-12">
						<textarea name="comment" id="" cols="30" rows="10" placeholder="Write comment"></textarea>
					</div>


					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="modal-approval-group" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Approval process</h4>
				<p style="color: red;">Are you sure you want to do this process <i id="textc"></i></p>
				<form action="<?php echo base_url('Group_assigned_amount/approval')?>" method="post" class="form-row" enctype="multipart/form-data">
					<input type="text" name="gid" id="gid" hidden>
					<input type="text" name="action" id="actionc" hidden>

					<div class="form-group col-12">
						<textarea name="comment" id="" cols="30" rows="10" placeholder="Write comment"></textarea>
					</div>


					<button type="submit" class="btn btn-primary">Save Changes</button>

				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="image_modal" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-lg modal-centered" role="document">
        <div class="modal-content text-center">
            <button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
            <div class="onboarding-content" style="padding: 1em;">
                <h4 class="onboarding-title">Image preview</h4>
                <div id="image_data_preview" style="background-color: red;">

                </div>

            </div>
        </div>
    </div>
</div>
<?php

$get_c = get_all('individual_customers');
$get_e = get_all('employees');
?>
<div aria-hidden="true" class="onboarding-modal modal fade" id="add_group_member_modal" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Assign customer to group</h4>
				<form action="<?php echo base_url('Customer_groups/create_action')?>" method="post" class="form-row" >
					<input type="text" name="group_id" id="group_idd"  hidden required>
					<div class="form-group col-12">
						<label for="varchar">Search customer</label>
						<select name="customer" id="" class="form-control" required>
							<option value="">--select customer--</option>
							<?php

							foreach ($get_c as $item){
								?>
								<option value="<?php echo $item->id ?>"><?php echo $item->Firstname.' '.$item->Lastname ?></option>
							<?php

							}
							?>
						</select>
					</div>
					<div class="form-group col-12">

					<button type="submit" class="btn btn-primary">Save Changes</button>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>
<div aria-hidden="true" class="onboarding-modal modal fade" id="change_officer" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg modal-centered" role="document">
		<div class="modal-content text-center">
			<button style="float: right;" aria-label="Close" class="close" data-dismiss="modal" type="button"><span class="close-label">Close</span><span class="anticon anticon-close"></span></button>
			<div class="onboarding-content" style="padding: 1em;">
				<h4 class="onboarding-title">Assign loan to  officer</h4>
                <p>Are you sure you want to assign <i id="officer_loan_number" style="color: red;"></i> to new Officer</p>
				<form action="<?php echo base_url('Loan/update_action2')?>" method="post" class="form-row" >
					<input type="text" name="loan_id" id="officer_loan_id"  hidden required>
					<div class="form-group col-12">
						<label for="officer">Search New officer</label><br/>
						<select name="loan_added_by" id="officer" class="form-control select2" required>
							<option value="">--select Officer--</option>
							<?php

							foreach ($get_e as $items){
								?>
								<option value="<?php echo $items->id ?>"><?php echo $items->Firstname.' '.$items->Lastname ?></option>
							<?php

							}
							?>
						</select>
					</div>
					<div class="form-group col-12">

					<button type="submit" class="btn btn-primary">Save Changes</button>
					</div>

				</form>
			</div>
		</div>
	</div>
</div>


<!-- Core Vendors JS -->
<script src="<?php echo base_url('admin_assets')?>/js/vendors.min.js"></script>

<!-- page js -->
<script src="<?php echo base_url('admin_assets')?>/vendors/chartjs/Chart.min.js"></script>
<script src="<?php echo base_url('admin_assets')?>/js/pages/dashboard-default.js"></script>
<script src="<?php echo base_url('admin_assets')?>/vendors/quill/quill.min.js"></script>
<!--data tables fuck-->
<script src="<?php echo base_url('admin_assets')?>/vendors/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url('admin_assets')?>/vendors/datatables/dataTables.bootstrap.min.js"></script>
<script src="<?php echo base_url('admin_assets')?>/js/pages/datatables.js"></script>
<!-- DataTables Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css">

<!-- Additional DataTables Scripts -->
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script type="text/javascript" src="<?php echo base_url()?>lib/sweetalerts/sweetalert.min.js"></script>
<script type="text/javascript" src="<?php echo base_url()?>admin_assets/js/security.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>

<!-- Core JS -->
<script src="<?php echo base_url('admin_assets')?>/js/app.min.js"></script>
<script src="<?php echo base_url('admin_assets/')?>js/toastr.min.js"></script>
<script src="<?php echo base_url('admin_assets/')?>ckeditor/ckeditor.js"></script>
<script src="<?php echo base_url('jquery-ui/')?>jquery-ui.js"></script>
<script src="<?php echo base_url('lib/')?>select2/dist/js/select2.full.min.js"></script>
<script type="text/javascript" src="<?php echo base_url()  ?>gisttech/js/xlsx.core.js"></script>
<script type="text/javascript" src="<?php echo base_url()  ?>gisttech/js/Blob.min.js"></script>
<script type="text/javascript" src="<?php echo base_url()  ?>gisttech/js/FileSaver.min.js"></script>
<script type="text/javascript" src="<?php echo base_url()  ?>gisttech/js/tableexport.min.js"></script>
<script>
	let logo = "<?php echo $settings->logo?>";
	var DefaultTable = document.getElementById('resulta');
	var loan = document.getElementById('loand');
	var crb = document.getElementsByClassName('crb');
	new TableExport(DefaultTable,{
		headers: true,                              // (Boolean), display table headers (th or td elements) in the <thead>, (default: true)
		footers: true,                              // (Boolean), display table footers (th or td elements) in the <tfoot>, (default: false)
		formats: ['xlsx', 'csv', 'txt'],            // (String[]), filetype(s) for the export, (default: ['xlsx', 'csv', 'txt'])
		filename: 'Arrears Report',                             // (id, String), filename for the downloaded file, (default: 'id')
		bootstrap: false,                           // (Boolean), style buttons using bootstrap, (default: false)
		position: 'bottom',                         // (top, bottom), position of the caption element relative to table, (default: 'bottom')
		ignoreRows: null,                           // (Number, Number[]), row indices to exclude from the exported file(s) (default: null)
		ignoreCols: null,                           // (Number, Number[]), column indices to exclude from the exported file(s) (default: null)
		ignoreCSS: '.tableexport-ignore',           // (selector, selector[]), selector(s) to exclude cells from the exported file(s) (default: '.tableexport-ignore')
		emptyCSS: '.tableexport-empty',             // (selector, selector[]), selector(s) to replace cells with an empty string in the exported file(s) (default: '.tableexport-empty')
		trimWhitespace: true,                       // (Boolean), remove all leading/trailing newlines, spaces, and tabs from cell text in the exported file(s) (default: true)
		RTL: false,                                 // (Boolean), set direction of the worksheet to right-to-left (default: false)
		sheetname: 'Arrears Report'                             // (id, String), sheet name for the exported spreadsheet, (default: 'id')
	});


	new TableExport(loan, {
		headers: true,                              // (Boolean), display table headers (th or td elements) in the <thead>, (default: true)
		footers: true,                              // (Boolean), display table footers (th or td elements) in the <tfoot>, (default: false)
		formats: ['xlsx', 'csv', 'txt'],            // (String[]), filetype(s) for the export, (default: ['xlsx', 'csv', 'txt'])
		filename: 'Arrears Report',                             // (id, String), filename for the downloaded file, (default: 'id')
		bootstrap: false,                           // (Boolean), style buttons using bootstrap, (default: false)
		position: 'bottom',                         // (top, bottom), position of the caption element relative to table, (default: 'bottom')
		ignoreRows: null,                           // (Number, Number[]), row indices to exclude from the exported file(s) (default: null)
		ignoreCols: null,                           // (Number, Number[]), column indices to exclude from the exported file(s) (default: null)
		ignoreCSS: '.tableexport-ignore',           // (selector, selector[]), selector(s) to exclude cells from the exported file(s) (default: '.tableexport-ignore')
		emptyCSS: '.tableexport-empty',             // (selector, selector[]), selector(s) to replace cells with an empty string in the exported file(s) (default: '.tableexport-empty')
		trimWhitespace: true,                       // (Boolean), remove all leading/trailing newlines, spaces, and tabs from cell text in the exported file(s) (default: true)
		RTL: false,                                 // (Boolean), set direction of the worksheet to right-to-left (default: false)
		sheetname: 'Arrears Report'                             // (id, String), sheet name for the exported spreadsheet, (default: 'id')
	});
    new TableExport(crb, {
		headers: true,                              // (Boolean), display table headers (th or td elements) in the <thead>, (default: true)
		footers: true,                              // (Boolean), display table footers (th or td elements) in the <tfoot>, (default: false)
		formats: ['xlsx', 'csv', 'txt'],            // (String[]), filetype(s) for the export, (default: ['xlsx', 'csv', 'txt'])
		filename: 'CRB Report',                             // (id, String), filename for the downloaded file, (default: 'id')
		bootstrap: false,                           // (Boolean), style buttons using bootstrap, (default: false)
		position: 'bottom',                         // (top, bottom), position of the caption element relative to table, (default: 'bottom')
		ignoreRows: null,                           // (Number, Number[]), row indices to exclude from the exported file(s) (default: null)
		ignoreCols: null,                           // (Number, Number[]), column indices to exclude from the exported file(s) (default: null)
		ignoreCSS: '.tableexport-ignore',           // (selector, selector[]), selector(s) to exclude cells from the exported file(s) (default: '.tableexport-ignore')
		emptyCSS: '.tableexport-empty',             // (selector, selector[]), selector(s) to replace cells with an empty string in the exported file(s) (default: '.tableexport-empty')
		trimWhitespace: true,                       // (Boolean), remove all leading/trailing newlines, spaces, and tabs from cell text in the exported file(s) (default: true)
		RTL: false,                                 // (Boolean), set direction of the worksheet to right-to-left (default: false)
		sheetname: 'CRB Report'                             // (id, String), sheet name for the exported spreadsheet, (default: 'id')
	});
	// **** jQuery **************************
	//    $(DefaultTable).tableExport({
	//        headers: true,
	//        footers: true,
	//        formats: ['xlsx', 'csv', 'txt'],
	//        filename: 'id',
	//        bootstrap: true,
	//        position: 'bottom',
	//        ignoreRows: null,
	//        ignoreCols: null,
	//        ignoreCSS: '.tableexport-ignore',
	//        emptyCSS: '.tableexport-empty',
	//        trimWhitespace: false,
	//        RTL: false,
	//        sheetname: 'id'
	//    });
	// **************************************

</script>

<script>
    function exportToExcel() {
        // Show loading indicator or any UI feedback
        // Example: showLoadingIndicator();

        // Send AJAX request to trigger background export
        $.ajax({
            url: '<?php echo base_url("index.php/reports/export_excel"); ?>',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // Hide loading indicator
                // Example: hideLoadingIndicator();

                // Check if export was successful
                if (response.status == 'success') {
                    // Prompt user to download the file
                    window.location = '<?php echo base_url("index.php/reports/download_excel"); ?>' + '?file=' + encodeURIComponent(response.file);
                } else {
                    // Handle error scenario if needed
                    alert('Export failed. Please try again later.');
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX error scenario
                var errorMessage = 'Error: Unable to initiate export process. ';
                if (xhr.status === 0) {
                    errorMessage += 'Network error (check your internet connection).';
                } else if (xhr.status == 404) {
                    errorMessage += 'Requested page not found (404).';
                } else if (xhr.status == 500) {
                    errorMessage += 'Internal Server Error (500).';
                } else {
                    errorMessage += 'Error: ' + xhr.statusText + ' (' + xhr.status + ')';
                }
                alert(errorMessage);
            }
        });
    }
</script>


<script type="text/javascript">

	function configure_teller(id){
		$("#tellering_account").val(id)
		$("#textt").html(id)
		$("#tellering").modal('show')
	}
	$('loan').select2({
		minimumInputLength: 3 // only start searching when the user has input 3 or more characters
	});

	$(document).ready(function() {
		$('.select2').select2();
		$(".sselect").select2();
		$('#d2,#d3').DataTable( {
			responsive: true
		} );
		$("#vtrans").click(function () {
			show_my_trans();
			$("#panelp").show();
			$("#vtrans").hide();
			$("#htrans").show();
		})
		$("#htrans").click(function () {
			$("#panelp").hide();
			$("#vtrans").show();
			$("#htrans").hide();
		})
	});
	$(function() {
		$( "#datepicker" ).datepicker({ minDate: 0});
	});
	$(function() {
		$(".dpicker").datepicker();
	});

	<?php if ($this->session->flashdata('success')) {?>
	toastr["success"]("<?php echo $this->session->flashdata('success'); ?>");
	<?php } else if ($this->session->flashdata('error')) {?>
	toastr["error"]("<?php echo $this->session->flashdata('error'); ?>");
	<?php } else if ($this->session->flashdata('warning')) {?>
	toastr["warning"]("<?php echo $this->session->flashdata('warning'); ?>");
	<?php } else if ($this->session->flashdata('info')) {?>
	toastr["info"]("<?php echo $this->session->flashdata('info'); ?>");
	<?php }?>
	toastr.options = {
		"closeButton": false,
		"debug": true,
		"newestOnTop": false,
		"progressBar": true,
		"rtl": false,
		"positionClass": "toast-top-center",
		"preventDuplicates": false,
		"onclick": null,
		"showDuration": 300,
		"hideDuration": 1000,
		"timeOut": 5000,
		"extendedTimeOut": 1000,
		"showEasing": "swing",
		"hideEasing": "linear",
		"showMethod": "fadeIn",
		"hideMethod": "fadeOut"
	}

</script>

<script>
	function pay_charge(loan_id,loan_number){
		$.ajax({
			url:"<?php echo base_url()?>Loan/get_charges/"+loan_id,
			method:"GET",

			beforeSend:()=>{

			},success:function(res){

				$("#charge_amount").val(res);
				$("#loan_idd").val(loan_number);
				$("#loan_d").val(loan_id);
			},error:()=>{
				errorToast('Error','Failed to interact with the server')
			}
		});
		$("#pay_charge_modal").modal('show');
	}
	function recommendGeneral(id){
$("#edit_id").val(id);
		$("#recommendgeneral").modal('show');
	}function add_expense(){

		$("#add_expense_modal").modal('show');
	}
	function assign_loan_officer(loan_number,loan_id){
        $("#officer_loan_number").html(loan_number);
        $("#officer_loan_id").val(loan_id);
		$("#change_officer").modal('show');
	}
	function approve_group(id,action,text){
		$("#group_id").val(id);
		$("#actionb").val(action);
		$("#textb").html(text);
		$("#modal-approval").modal('show');
	}function approve_group_amount(id,action,text){
		$("#gid").val(id);
		$("#actionc").val(action);
		$("#textc").html(text);
		$("#modal-approval-group").modal('show');
	}
	function pay_due(loan_id,schedule,loan_amount,paid_amount){
		console.log(loan_id)
		console.log(schedule)
		console.log(loan_amount)
		console.log(paid_amount)
		$.ajax({
			url:"<?php echo base_url()?>Loan/get_late_charg/"+loan_id,
			method:"GET",

			beforeSend:()=>{

			},success:function(res){

				$("#late_charge_amount").val(res);
				$("#spc").html(res);
				$("#pn").val(schedule);
				$("#spn").html(schedule);
				$("#lm").val((loan_amount)-(paid_amount));
				$("#slm").html(loan_amount);
			},error:()=>{
				errorToast('Error','Failed to interact with the server')
			}
		});
		$("#late_payment_modal").modal('show');
	}
	function pay_borrowed(id){

		$("#pay_borrowed_modal").modal('show');
	}
	function finish_payment(id){

		$("#finish_payment_modal").modal('show');
	}
	$('[data-toggle="tooltip"]').tooltip()
var baseURL = "<?php echo base_url()?>";
	// tooltip on payment modals
	var checkboxes = $(".check-cls"),
			submitButt = $(".submit-btn");

	checkboxes.click(function() {
		submitButt.attr("disabled", !checkboxes.is(":checked"));
	});

$(document).ready(function (){
	$("#by_date").change(function () {
		if($("#by_date").val()==="Custom"){
			$(".dpicker"). prop('disabled', false);

		}else  {
			$(".dpicker"). prop('disabled', true);
			$(".dpicker").val("");
		}

	});
	$("#charge_type").change(function () {
		if($("#charge_type").val()==="Variable"){
			$("#fixed_amount"). prop('readonly', true);
			$("#variable_value"). prop('readonly', false);
		}else  if ($("#charge_type").val()==="Fixed"){
			$("#fixed_amount"). prop('readonly', false);
			$("#variable_value"). prop('readonly', true);
		}

	});

	$("#above_charge_type").change(function () {
		if($("#above_charge_type").val()==="Variable"){
			$("#above_fixed_amount"). prop('readonly', true);
			$("#above_variable_amount"). prop('readonly', false);
		}else  if ($("#above_charge_type").val()==="Fixed"){
			$("#above_fixed_amount"). prop('readonly', false);
			$("#above_variable_amount"). prop('readonly', true);
		}

	});
	$("#search_account_form").submit(function (e) {

		e.preventDefault(); // avoid to execute the actual submit of the form.

		var form = $(this);
		// var url = form.attr('action');

		$.ajax({
			type: "POST",
			url: baseURL + 'Account/search',
			dataType: 'json',
			data: form.serialize(), // serializes the form's elements.
			beforeSend: () => {
				 $("#sbbtn").html("<i class='fa fa-spinner fa-spin'></i>Searching Details");
			},
			success: function (response) {
                $("#sbbtn").html('Submit Search');
				// $("#cover-spin").hide();
				var event_data ="";
				let lname = '';
				$.each(response.data, function (index, value) {
					if(value.Lastname !=undefined){
						lname = value.name +" " +value.Lastname;
					}else {
						lname = value.name;
					}

					event_data +=`
            <tr>
					<td><u><a href='#' class='selects' onclick='populate("${value.account_number}","${value.account_type_name}","${lname}","${value.balance}","${value.Currency}","${value.date_added }","${value.account_status}","${value.photograph}","${value.signature}","${value.id_front}","${value.Id_back}")'>select</a></u></td>

                    <td>${value.account_number}</td>

                    <td>${value.account_type_name}</td>
                    <td>${lname}</td>

                             </tr>
            `;

				});
				$("#search-r").html(event_data);

			},
			error: function (jqXHR, textStatus, errorThrown) {
				// $("#cover-spin").hide();
                $("#sbbtn").html('Submit Search');
				alert('connection error')


			}
		});


	});

	$("#search_input").click(function(){

		$("#search_modal").modal('show');
		// get_account_types();
	})
	$("#search_acount").click(function(){

		$("#search_modal").modal('show');
		// get_account_types();
	})

	$("#transaction_deposit_form").submit(function (e) {

		e.preventDefault(); // avoid to execute the actual submit of the form.
		let account_name = $('#account_name').val();
		var form = $(this);
		// var url = form.attr('action');
		if($("#tt").val()===''||$("#tt").val()===null){
			swal("Error","amount cannot be empty","error");
			return;
		}	if($("#dacn").val()===''||$("#dacn").val()===null){
			swal("Error","Sorry, Select account first","error");
			return;
		}
		if (!$("input[name='transaction_mode']:checked").val()) {
			swal("Error","Sorry Select Deposit or withdraw first","error");
			return false;
		}

		swal({
			title: "Are you sure you want to  make "+$("input[name='transaction_mode']:checked").val()+"?",
			text: "You need to be careful with this",
			type: "warning",
			showCancelButton: true,
			confirmButtonColor: "#DD6B55",
			confirmButtonText: "Yes, Proceed",
			closeOnConfirm: false
		}, function (isConfirm) {
			if (!isConfirm) return;
			$.ajax({
				type: "POST",
				url: baseURL + 'Account/cash_transaction',
				dataType: 'json',
				data: form.serialize(), // serializes the form's elements.
				beforeSend: () => {
					$("#svbutton").html("<i class='fa fa-spinner fa-spin'></i>Transacting please wait");
				},
				success: function (response) {
					$("#svbutton").html("Save changes");
					if(response.status=='success'){
						$("#tt").val('');
						$("#chartof").val('');
						$("#dacn").val('');
						$("#account_name").val('');
						$("#account_balance").val('');
						$("#account_date").val('');
						$("#account_status").val('');
						// $('#transaction_deposit_form input[type="radio":checked]').each(function(){
						// 	$(this).prop('checked', false);
						// });
						get_teller_balance();
						show_my_trans()
                        if(response.data.id ===undefined){
                            console.log('unde')
                        }else{
                            var url = baseURL+"account/print_receipt/"+response.data.id;

// Open the URL in a new tab
                            window.open(url, "_blank");

                        }


						swal("",'Transaction was successful','success');


					}else if(response.status=='error') {

						swal("Error",'Transaction failed because:'+response.message,'error');
						// alert('Transaction failed because: '+response.message);
					}

				},
				error: function (jqXHR, textStatus, errorThrown) {
					// $("#cover-spin").hide();
					$("#svbutton").html("Save changes");
					swal("Error","Failed to interact with server","error");

				}
			});

		});



	});
    // When customer type changes
    $("#customer_type").change(function () {
        var id = $("#customer_type").val();

        // Reset customer search and disable input if no type selected
        $("#customer_search").val('');
        $("#customer_search").prop('disabled', id === '');
        $("#customer_transact").html('<option value="">--select customer--</option>'); // Clear customer options
        $("#loan_dis").html('<option value="">--select loan--</option>'); // Clear loan options
    });

// Search for customers as user types
    $("#customer_search").on('input', function() {
        var customerType = $("#customer_type").val();
        var searchTerm = $("#customer_search").val();

        if (customerType && searchTerm.length > 2) {  // Start searching after 3 characters
            $.ajax({
                url: "<?php echo base_url()?>Loan/search_customer/" + customerType,
                method: "GET",
                data: { search: searchTerm },
                success: function(res) {
                    console.log("Response received from server:", res);  // Debugging
                    $("#customer_transact").html(res);  // Populate the dropdown with response
                },
                error: function() {
                    console.error('Failed to interact with the server');  // Show an error in the console
                }
            });
        } else {
            $("#customer_transact").html('<option value="">--select customer--</option>');  // Default message
        }
    });


// When customer is selected, fetch loans
    $("#customer_transact").change(function () {
        var id = $("#customer_transact").val();
        $.ajax({
            url: "<?php echo base_url()?>Loan/get_by_loan_transact/" + id,
            method: "GET",
            success: function(res) {
                $("#loan_dis").html(res); // Populate loan dropdown
            },
            error: function() {
                errorToast('Error', 'Failed to interact with the server');
            }
        });
    });



    $("#reconci").change(function (){
        show_reconciliation( $("#reconci").val());
    });
	$("#misheck").change(function (){

		$.ajax({
			type: "GET",
			url: baseURL + 'Account/get_vv/'+$("#misheck").val(),
			dataType: 'json',

			beforeSend: () => {
				// $("#cover-spin").show();
			},
			success: function (response) {
				// $("#cover-spin").hide();
				if (response.status == 'success') {



					let boss_a = `
						<table class="table table-bordered">
										<tr>
											<td>Account no:</td>
											<td>${response.data.account_number}</td>
										</tr>
										<tr>
											<td>Account Balance: MK</td>
											<td>${response.data.balance}</td>
										</tr>
									</table>
						`;




					$("#teller_display").html(boss_a);
				}else {
					swal('','Sorry, this account is not cashier','error')
				}

			},
			error: function (jqXHR, textStatus, errorThrown) {
				// $("#cover-spin").hide();
				alert('Sorry, error while interacting with the server')

			}
		});


	});
	$("#add_kyc").click(function (){
		// alert('hello')
		$("#Client").val($("#cid").val());
		$("#kyc_modal").modal('show');
	});
	$("#edit_kyc").click(function (){
		// alert('hello')
		$("#Client").val($("#cid").val());
		$("#kyc_modal_edit").modal('show');
	});

	$("#customer_loan").change(function (){
		var id = $("#customer_loan").val();
		$.ajax({
			url:"<?php echo base_url()?>Individual_customers/view_customer/"+id,
			method:"GET",
			dataType:"json",

			beforeSend:()=>{
				$("#image_actions").html("<i class='fa fa-spinner fa-spin'></i>Loading data");
			},success:function(res){

				var det = '';
				det +=`
				<table class="table">
									<tr>
										<td>Firstname</td>
										<td>${res.data.Firstname}</td>
									</tr>
									<tr>
										<td>Lastname</td>
										<td>${res.data.Lastname}</td>
									</tr>
									<tr>
										<td>Gender</td>
										<td>${res.data.Gender}</td>
									</tr>
									<tr>
										<td>Date of Birth</td>
										<td>${res.data.DateOfBirth}</td>
									</tr>
									<tr>
										<td>Contact No</td>
										<td>${res.data.PhoneNumber}</td>
									</tr><tr>
										<td>Profession</td>
										<td>${res.data.Profession}</td>
									</tr><tr>
										<td>Source of Income</td>
										<td>${res.data.SourceOfIncome}</td>
									</tr>
								</table>`;

				$("#customer-results").html(det);

				let dd ='';
				$.each(res.data.loan, function (index, value) {
					var color = 'orange';
					if(value.loan_status==='INITIATED'){
						color = "orange";
					}
					if(value.loan_status==='ACTIVE'){
						color = "green";
					}
					if(value.loan_status==='CLOSED'){
						color = "red";
					}
					dd += `

                    <li><a href="<?php echo base_url('loan/view/')?>${value.loan_id}">#${value.loan_number}-</a><span style="color: ${color}">${value.loan_status}</span></li>

    `
				});

				$("#loandd").html(dd);
				let kyc = '';
				if(isEmpty(res.data.kyc)){
					kyc = '';
				}else {
					kyc +=`
				<tr>
							<td>Photo</td>
							<td><img src="${baseURL+'uploads/'+res.data.kyc.photograph}" alt="" width="100" height="50"></td>
						</tr>
						<tr>
							<td>ID type</td>
							<td>${res.data.kyc.IDType}</td>
						</tr>
						<tr>
							<td>ID Number</td>
							<td>${res.data.kyc.IDNumber}</td>
						</tr>
						<tr>
							<td>ID issue date</td>
							<td>${res.data.kyc.IssueDate}</td>
						</tr>
						<tr>
							<td>ID Expiry date</td>
							<td>${res.data.kyc.ExpiryDate}</td>
						</tr>
						<tr>
							<td>ID front</td>
							<td><img src="${baseURL+'uploads/'+res.data.kyc.id_front}" alt="" width="100" height="50"></td>
						</tr>
						<tr>
							<td>ID back</td>
							<td><img src="${baseURL+'uploads/'+res.data.kyc.Id_back}" alt="" width="100" height="50"></td>
						</tr>
						<tr>
							<td>Sig/fingerprint</td>
							<td><img src="${baseURL+'uploads/'+res.data.kyc.signature}" alt="" width="100" height="50"></td>
						</tr>
				`;
				}


				$("#kyc_data").html(kyc)

			},error:()=>{

				alert('Failed to interact with server check internet connection')
			}
	});

});
	$("#group_c").change(function (){
		var id = $("#group_c").val();
		if(id===null || id===""){

		}else {
			$.ajax({
				url:"<?php echo base_url()?>Customer_groups/get_members/"+id,
				method:"GET",
				dataType:"json",

				beforeSend:()=>{
					$("#customer_loan").html("<i class='fa fa-spinner fa-spin'></i>Loading data");
				},success:function(res){

					let det = "";
					$.each(res.data, function (index, value) {
						det  +=`<li >${value.Firstname} &nbsp; ${value.Lastname}- (<font color="green"> Member</font>)</li>`
					})
					$("#customer_loan").html(det);
                    let dd ='';
                    $.each(res.loan, function (index, value) {
                        var color = 'orange';
                        if(value.loan_status==='INITIATED'){
                            color = "orange";
                        }
                        if(value.loan_status==='ACTIVE'){
                            color = "green";
                        }
                        if(value.loan_status==='CLOSED'){
                            color = "red";
                        }
                        dd += `

                    <li><a href="<?php echo base_url('loan/view/')?>${value.loan_id}">#${value.loan_number}-</a><span style="color: ${color}">${value.loan_status}</span></li>

    `
                    });

                    $("#loandd").html(dd);

                    let kyc = '';
                    if(isEmpty(res.group)){
                        kyc = '';
                    }else {
                        kyc +=`

						<tr>
							<td>Group Code</td>
							<td>${res.group.group_code}</td>
						</tr>
						<tr>
							<td>Group name</td>
							<td>${res.group.group_name}</td>
						</tr>
						<tr>
							<td>Registered date date</td>
							<td>${res.group.group_registered_date}</td>
						</tr>
						<tr>
							<td>Group Business type</td>
							<td>${res.group.group_category}</td>
						</tr>
						
						<tr>
							<td>Description</td>
								<td>${res.group.group_description}</td>
						</tr>
				`;
                    }


                    $("#kyc_data").html(kyc)

				},error:()=>{

					alert('Failed to interact with server check internet connection')
				}
			});
		}


});

});

	function isEmpty(obj) {
		for(var prop in obj) {
			if(obj.hasOwnProperty(prop))
				return false;
		}

		return true;
	}

CKEDITOR.replace( 'AddressLine1' );
CKEDITOR.replace( 'AddressLine2' );
CKEDITOR.replace( 'address' );
CKEDITOR.replace( 'group_description' );
CKEDITOR.replace( 'AddressLine3' );
CKEDITOR.replace( 'narration' );
	function populate(id,chart,name,balance,currency,cdate,status,photo,si,id_fron,id_back){
		$("#search_modal").modal('hide');
		$("#dacn").val(id);
		$("#chartof").val(chart);
		$("#account_name").val(name);
		$("#account_balance").val(balance);
		$("#account_currency").val(currency);
		$("#account_date").val(cdate);
		$("#account_status").val(status);
		let photograph = '<img src="'+baseURL+'uploads/'+photo+'" style="border: thick solid coral; border-radius: 15px;" height="150" width="150">';
		let siginature = '<img src="'+baseURL+'uploads/'+si+'" style="border: thick solid coral; border-radius: 15px;" height="150" width="150">';
		let front_id = '<img src="'+baseURL+'uploads/'+id_fron+'" style="border: thick solid coral; border-radius: 15px;" height="150" width="300">';
		let back_id = '<img src="'+baseURL+'uploads/'+id_back+'" style="border: thick solid coral; border-radius: 15px;" height="150" width="300">';
		$("#photoid").html(photograph);
		$("#sigid").html(siginature);
		$("#idfront").html(front_id);
		$("#idback").html(back_id);
	}
	function show_my_trans(){
		$.ajax({

			url: baseURL + 'Account/get_teller_transaction/'+$("#myacc").val(),
			type: "GET",
			// data: user_data, // serializes the form's elements.
			success: function (data) {
				$("#panelp").html(data);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				swal("Error","server interaction failed",'error');

			}
		});

	}
	function show_reconciliation(id){
		$.ajax({

			url: baseURL + 'Account/get_teller_transaction_reconciliation/'+id,
			type: "GET",
			// data: user_data, // serializes the form's elements.
			success: function (data) {
				$("#reconci_data").html(data);
			},
			error: function (jqXHR, textStatus, errorThrown) {
				swal("Error","server interaction failed",'error');

			}
		});

	}
function get_customer(){

	$.ajax({
		url:"<?php echo base_url()?>Individual_customers/view_customer/"+id,
		method:"GET",
		dataType:"json",

		beforeSend:()=>{
			$("#image_actions").html("<i class='fa fa-spinner fa-spin'></i>Loading data");
		},success:function(res){


		},error:()=>{

			alert('Failed to interact with server check internet connection')
		}
	});

}

    function image_preview(name){
        var ii = "<?php echo base_url('uploads/')?>";
        var image = "<img src='"+ii+name+"'  style='object-fit: cover; height: 100%; width: 100%;'>";
        $('#image_data_preview').html(image)
        $("#image_modal").modal('show');

    }

function get_teller_balance(){
		let id = "<?php echo $this->session->userdata('user_id') ?>";
	$.ajax({
		url:"<?php echo base_url()?>Account/get_teller_balance/"+id,
		method:"GET",
		dataType:"json",

		beforeSend:()=>{

		},success:function(res){

			$("#drawer_balance").val(res.balance);

		},error:()=>{

			alert('Failed to interact with server check  connection')
		}
	});
}
function add_group_member(id){
	$("#group_idd").val(id);
	$("#add_group_member_modal").modal('show');
}
function pay_current(){
	$("#payment_modal").modal('show');
}
function advance_payment(){
	$("#advance_payment_modal").modal('show');
}

	function uploadpro(id) {

		uploadp(document.getElementById(id).files[0],id);
	}
	function uploadp(file,id){

		let formData = new FormData();
		let photo = file;
		formData.append("file", photo);

		$.ajax({
			url: "<?php echo base_url()?>Proofofidentity/upload",
			method: "POST",
			contentType:false,
			data: formData,
			cache: false,
			processData: false,
			dataType:"json",
			beforeSend: () => {
				$("#ppp").attr("disabled", true);
				$("#ppp").html("<i class='fa fa-spinner fa-spin'></i>Processing");
			},
			success: (response)=>{
				$("#ppp").attr("disabled", false);
				$("#ppp").html("<font color='green'>Featured image uploaded</font>");
				if (response.status == "success") {

					// alert(response.data.file_name);
					var tf = "<?php echo base_url('uploads/')?>"+response.data.file_name;
					$("#"+id+"1").val(response.data.file_name);


				} else {
					$("#ppp").attr("disabled", false);
					$("#ppp").html("Featured image was not uploaded");
					// $("#pvu").html("");

                    alert(response.details || response.message);
				}

			}, error: (xht, error, e)=>{
				// $("#pvu").html("");
				alert("Error "+xht.status);
			}
		});


	}
	function uploadcommon(id) {

		upload2(document.getElementById(id).files[0],id);
	}
	function upload2(file,id){

		let formData = new FormData();
		let photo = file;
		formData.append("file", photo);

		$.ajax({
			xhr: function() {
				var xhr = new window.XMLHttpRequest();

				xhr.upload.addEventListener("progress", function(evt) {
					if (evt.lengthComputable) {
						var percentComplete = evt.loaded / evt.total;
						percentComplete = parseInt(percentComplete * 100);
						// var $link = $('.'+ids);
						// var $img = $link.find('i');
						$("#"+id+"3").html('Uploading..('+percentComplete+'%)');
						// $link.append($img);
					}
				}, false);

				return xhr;
			},
			url: "<?php echo base_url()?>Proofofidentity/upload",
			method: "POST",
			contentType:false,
			data: formData,
			cache: false,
			processData: false,
			dataType:"json",
			beforeSend: () => {
				//var tf = "<?php //echo base_url('uploads/scanningwoohoo.gif')?>//";
				//$("#featured_image").val(tf);
				//var img = `<img src="${tf}"  alt="" height="70" width="100">`;
				//$("#pvu").html(img);
			},
			success: (response)=>{

				if (response.status == "success") {

					// alert(response.data.file_name);
					var tf = "<?php echo base_url('uploads/')?>"+response.data.file_name;
					$("#"+id+"1").val(response.data.file_name);
					var img = `<img src="${tf}"  alt="" height="100" width="100">`;
					$("#"+id+"2").html(img);



				} else {
					// $("#pvu").html("");

                    alert(response.details || response.message);
				}

			}, error: (xht, error, e)=>{
				// $("#pvu").html("");
				alert("Error "+xht.status);
			}
		});


	}
function uploadprofile(id) {

	uploadd(document.getElementById(id).files[0],id);
}
function uploadd(file,id){

	let formData = new FormData();
	let photo = file;
	formData.append("file", photo);

	$.ajax({
		url: "<?php echo base_url()?>Proofofidentity/upload",
		method: "POST",
		contentType:false,
		data: formData,
		cache: false,
		processData: false,
		dataType:"json",
		beforeSend: () => {
			$("#ppp").attr("disabled", true);
			$("#ppp").html("<i class='fa fa-spinner fa-spin'></i>Uploading file please wait");
		},
		success: (response)=>{


			if (response.status == "success") {
				$("#ppp").attr("disabled", false);
				$("#ppp").html("<i class='fa fa-check bg-success' style='color: green;'>File was uploaded</i>");
				// alert(response.data.file_name);
				successToast('Success','file was successfully you may proceed')
				$("#"+id+"1").val(response.data.file_name);




			} else {
				// $("#pvu").html("");
                errorToast(response.details || response.message || 'Sorry something went wrong when uploading, please try again');
			}

		}, error: (xht, error, e)=>{
			// $("#pvu").html("");
			errorToast("Error "+xht.status);

		}
	});


}



	function showToast() {
		var toastHTML = `<div class="toast fade hide bg-success" data-delay="3000">
        <div class="toast-header">
            <i class="anticon anticon-info-circle text-white m-r-5"></i>
            <strong class="mr-auto">Upload success</strong>

            <button type="button" class="ml-2 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body text-white">
            Wow, image was uploaded successfully.
        </div>
    </div>`

		$('#notification-toast').append(toastHTML)
		$('#notification-toast .toast').toast('show');
		setTimeout(function(){
			$('#notification-toast .toast:first-child').remove();
		}, 3000);
	}
	function successToast(header,message) {
		var toastHTML = `<div class="toast fade hide bg-success" data-delay="3000">
        <div class="toast-header">
            <i class="anticon anticon-info-circle text-white m-r-5"></i>
            <strong class="mr-auto">${header}</strong>

            <button type="button" class="ml-2 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body text-white">
            ${message}.
        </div>
    </div>`

		$('#notification-toast').append(toastHTML)
		$('#notification-toast .toast').toast('show');
		setTimeout(function(){
			$('#notification-toast .toast:first-child').remove();
		}, 3000);
	}
	function errorToast(header,message) {
		var toastHTML = `<div class="toast fade hide bg-danger" data-delay="3000">
        <div class="toast-header">
            <i class="anticon anticon-info-circle text-white m-r-5"></i>
            <strong class="mr-auto">${header}</strong>

            <button type="button" class="ml-2 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body text-white">
            ${message}.
        </div>
    </div>`

		$('#notification-toast').append(toastHTML)
		$('#notification-toast .toast').toast('show');
		setTimeout(function(){
			$('#notification-toast .toast:first-child').remove();
		}, 3000);
	}
	function deleteToast() {
		var toastHTML = `<div class="toast fade hide bg-success" data-delay="3000">
        <div class="toast-header">
            <i class="anticon anticon-info-circle text-white m-r-5"></i>
            <strong class="mr-auto">Delete success</strong>

            <button type="button" class="ml-2 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body text-white">
            Wow, image was deleted successfully.
        </div>
    </div>`

		$('#notification-toast').append(toastHTML)
		$('#notification-toast .toast').toast('show');
		setTimeout(function(){
			$('#notification-toast .toast:first-child').remove();
		}, 3000);
	}

function mish(id){

		$("#tellering_account").val(id)
		$("#textt").html(id)
	$("#tellering-modal").modal('show');
}


    var fieldId = 0;

    function addElement(parentId, elementTag, elementId, html){
        var id = document.getElementById(parentId);
        var newElement = document.createElement(elementTag);
        newElement.setAttribute('id', elementId);
        newElement.innerHTML = html;
        id.appendChild(newElement);

    }

    function removeField(elementId){
        var fieldId = "field-"+elementId;
        var element = document.getElementById(fieldId);
        element.parentNode.removeChild(element);
    }

    function addField(){
        fieldId++;
        var html = '<br /><hr/>  <div class="row">\n' +
            '                                    <div class="col-6 mt-2"><input type="text" name="name[]" placeholder="collateral name" class="form-control"></div>\n' +
            '                                    <div class="col-6 mt-2"><input type="text" name="type[]" placeholder="collateral type" class="form-control"></div>\n' +
            '                                </div>\n' +
            '                                <div class="row">\n' +
            '                                    <div class="col-6 mt-2"><input type="text" name="serial[]" placeholder="serial number" class="form-control"></div>\n' +
            '                                    <div class="col-6 mt-2"><input type="text" name="value[]" placeholder="collateral value" class="form-control"></div>\n' +
            '                                </div>\n' +
            '                                <div class="row">\n' +
            '                                    <div class="col-6 mt-2"><label for="ifi"  >upload attachment</label><input type="file" name="files[]" style="display: block" placeholder="Attachment" class="form-control"></div>\n' +
            '                                </div>\n' +
            '                                <div class="row">\n' +
            '                                    <div class="col-12 mt-2"><textarea class="form-control" name="desc[]" id="" cols="30" rows="6"></textarea></div>\n' +
            '                                </div>' + '<button class="btn btn-danger" onclick="removeField('+fieldId+');"><span class="fa fa-minus"></span></button><br />';
        addElement('forms', 'div', 'field-'+ fieldId, html);
    }


    function formatCurrencyInput(input) {
        // Format the input while typing
        input.addEventListener('input', function () {
            let rawValue = input.value.replace(/,/g, ''); // Remove commas
            if (!isNaN(rawValue) && rawValue !== '') {
                // Format the number with commas and decimals
                input.value = parseFloat(rawValue).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else if (rawValue === '') {
                input.value = ''; // Clear input if empty
            } else {
                input.value = '0.00'; // Default to zero if invalid
            }
        });

        // Prepare raw value for submission
        input.addEventListener('blur', function () {
            let formattedValue = input.value.replace(/,/g, ''); // Remove commas for submission
            if (!isNaN(formattedValue) && formattedValue !== '') {
                input.dataset.rawValue = parseFloat(formattedValue); // Store raw value in a data attribute
            }
        });
    }

    // Usage
    const amountInput = document.querySelector('#amountInput'); // Replace with your input ID or selector
    formatCurrencyInput(amountInput);





</script>
<script>



    /**
     * Exporting Table Data into CSV
     */

    var exportTableCsvBtn = document.getElementById("exportTableCSV");
    if (exportTableCsvBtn) exportTableCsvBtn.addEventListener("click", function(e){

        e.preventDefault()

        var _tbl_rows = document.querySelectorAll('#data-table tr')
        var csv ="";
        var rows = []
        _tbl_rows.forEach(el => {
            var row = []
            el.querySelectorAll('th, td').forEach(ele => {
                var ele_clone = ele.cloneNode(true)
                ele_clone.innerText = (ele_clone.innerText).replace(/\"/gi, '\"\"')
                ele_clone.innerText = ('"' + ele_clone.innerText + '"')
                row.push(ele_clone.innerText)
            })
            rows.push(row.join(","));
        })
        csv += rows.join(`\r\n`)
        var file = new Blob([csv], {type:'text/csv'});
        var dl_anchor = document.createElement('a')
        dl_anchor.style.display = this.nonce;
        dl_anchor.style.display = this.nonce;
        const d = new Date();
        var ddate=d.toDateString()
        var dc="collection_report" +ddate;
        dl_anchor.download = dc+".csv";

        dl_anchor.href = window.URL.createObjectURL(file);

        document.body.appendChild(dl_anchor)
        dl_anchor.click()
    })
</script>

<script>
    // AJAX request to fetch HTML table data from server
    $.ajax({
        url: 'reports/to_pay_today?search=excel', // Make sure to include the search parameter to trigger Excel export
        type: 'GET',
        success: function(html_tablemonth) {
            // Convert HTML string to a DOM element
            var tableElement = document.createElement('div');
            tableElement.innerHTML = htmlData;
            const d = new Date();

            // Get the table element from the HTML
            var table = tableElement.querySelector('#data-table-monthly-payment');

            // Convert the table to a worksheet
            var ws = XLSX.utils.table_to_sheet(table);

            // Create a new workbook
            var wb = XLSX.utils.book_new();

            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

            // Export the workbook to an Excel file
            XLSX.writeFile(wb, 'monthly_collection_report.xls');
        }
    });


</script>


<script>
    // AJAX request to fetch HTML table data from server
    $.ajax({
        url: 'reports/to_pay_today?search=excel', // Make sure to include the search parameter to trigger Excel export
        type: 'GET',
        success: function(htmlData) {
            // Convert HTML string to a DOM element
            var tableElement = document.createElement('div');
            tableElement.innerHTML = htmlData;
            const d = new Date();
            var ddate=d.toDateString()
            var dc="collection_report" +ddate;
            // Get the table element from the HTML
            var table = tableElement.querySelector('#data-table-today-payment');

            // Convert the table to a worksheet
            var ws = XLSX.utils.table_to_sheet(table);

            // Create a new workbook
            var wb = XLSX.utils.book_new();

            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

            // Export the workbook to an Excel file
            XLSX.writeFile(wb, 'Collection_today.xls');
        }
    });


</script>


<script>
    // AJAX request to fetch HTML table data from server
    $.ajax({
        url: 'reports/to_pay_week?search=excel', // Make sure to include the search parameter to trigger Excel export
        type: 'GET',
        success: function(html_tableweekly) {
            // Convert HTML string to a DOM element
            var tableElement = document.createElement('div');
            tableElement.innerHTML = html_tableweekly;
            const d = new Date();
            var ddate=d.toDateString()
            var dc="collection_report" +ddate;
            // Get the table element from the HTML
            var table = tableElement.querySelector('#data-table-weekly-payment');

            // Convert the table to a worksheet
            var ws = XLSX.utils.table_to_sheet(table);

            // Create a new workbook
            var wb = XLSX.utils.book_new();

            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

            // Export the workbook to an Excel file
            XLSX.writeFile(wb, 'Weekly_Collection.xls');
        }
    });


</script>


<script>
    // AJAX request to fetch HTML table data from server
    $.ajax({
        url: 'reports/to_pay_week?search=excel', // Make sure to include the search parameter to trigger Excel export
        type: 'GET',
        success: function(html_tableloanreport {
            // Convert HTML string to a DOM element
            var tableElement = document.createElement('div');
            tableElement.innerHTML = html_tableloanreport;
            const d = new Date();
            var ddate=d.toDateString()
            var dc="collection_report" +ddate;
            // Get the table element from the HTML
            var table = tableElement.querySelector('#data-table-loan-portfolio');

            // Convert the table to a worksheet
            var ws = XLSX.utils.table_to_sheet(table);

            // Create a new workbook
            var wb = XLSX.utils.book_new();

            // Add the worksheet to the workbook
            XLSX.utils.book_append_sheet(wb, ws, "Sheet1");

            // Export the workbook to an Excel file
            XLSX.writeFile(wb, 'loan_portfolio.xls');
        }
    });


</script>

<script>
    function previewAndExport(type) {
        // First, fetch the raw data
        fetch('<?php echo base_url('reports/get_weekly_data') ?>')
            .then(response => response.json())
            .then(data => {
                // Open new window with preview
                const previewWindow = window.open('', '_blank');
                previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Weekly Collection Preview</title>
                    <style>
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                        th { background-color: #f4f4f4; }
                        .export-btn {
                            padding: 10px 20px;
                            background-color: #4CAF50;
                            color: white;
                            border: none;
                            border-radius: 4px;
                            cursor: pointer;
                            margin: 20px;
                        }
                    </style>
                </head>
                <body>
                    <button class="export-btn" onclick="exportToExcel()">Export to Excel</button>
                    <table id="previewTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Loan Customer</th>
                                <th>Loan Number</th>
                                <th>Loan Product</th>
                                <th>Check Date</th>
                                <th>Amount to collect</th>
                                <th>Payment number</th>
                                <th>Officer</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map((loan, index) => `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${loan.customer_name}</td>
                                    <td>${loan.loan_number}</td>
                                    <td>${loan.product_name}</td>
                                    <td>${loan.payment_schedule}</td>
                                    <td>${loan.amount}</td>
                                    <td>${loan.payment_number}</td>
                                    <td>${loan.efname} ${loan.elname}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5"></th>
                                <th>MK${data.reduce((sum, loan) => sum + parseFloat(loan.amount), 0).toFixed(2)}</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                    <script>
                        function exportToExcel() {
                            const table = document.getElementById('previewTable');
                            const html = table.outerHTML;
                            const url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
                            const downloadLink = document.createElement("a");
                            document.body.appendChild(downloadLink);
                            downloadLink.href = url;
                            downloadLink.download = 'Weekly_Collection.xlsx';
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                        }
                    <\/script>
                </body>
                </html>
            `);
                previewWindow.document.close();
            })
            .catch(error => console.error('Error:', error));
    }


</script>
<script>
    // DataTables initialization with export and loading indicators
    // DataTables initialization with export progress indicator
    $(document).ready(function() {
        // Create a loading overlay function
        function createLoadingOverlay() {
            return $('<div>', {
                id: 'export-loading-overlay',
                css: {
                    position: 'fixed',
                    top: 0,
                    left: 0,
                    width: '100%',
                    height: '100%',
                    backgroundColor: 'rgba(0, 0, 0, 0.5)',
                    display: 'flex',
                    justifyContent: 'center',
                    alignItems: 'center',
                    zIndex: 9999
                }
            }).append(
                $('<div>', {
                    css: {
                        backgroundColor: 'white',
                        padding: '20px',
                        borderRadius: '10px',
                        textAlign: 'center',
                        boxShadow: '0 4px 6px rgba(0,0,0,0.1)'
                    }
                }).append(
                    $('<div>', {
                        class: 'spinner-border text-primary',
                        role: 'status'
                    }),
                    $('<p>', {
                        text: 'Exporting data. This may take a few moments...',
                        css: { marginTop: '15px' }
                    })
                )
            );
        }

        function parseAmountValue(value) {
            if (typeof value === 'string') {
                value = value.replace(/<[^>]*>/g, '');
                value = value.replace(/[^0-9.\-]/g, '');
            }
            var parsed = parseFloat(value);
            return isNaN(parsed) ? 0 : parsed;
        }

        function updateCollectionFooterTotal(dtApi) {
            var tableNode = dtApi.table().node();
            var $table = $(tableNode);
            var $totalCell = $table.find('tfoot .collection-total-cell');
            if (!$totalCell.length) {
                return;
            }

            var amountColIdx = -1;
            $table.find('thead th').each(function(index) {
                if ($(this).text().trim().toLowerCase() === 'amount to collect') {
                    amountColIdx = index;
                    return false;
                }
            });
            if (amountColIdx === -1) {
                return;
            }

            var total = dtApi
                .column(amountColIdx, { search: 'applied' })
                .data()
                .reduce(function(sum, value) {
                    return sum + parseAmountValue(value);
                }, 0);

            $totalCell.text('MK' + total.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }

        function loanListServerExportUrl(format) {
            var form = document.getElementById('loan-list-filters-form');
            var params;
            var baseUrl;
            if (form) {
                params = new URLSearchParams(new FormData(form));
                baseUrl = form.getAttribute('action') || window.location.href.split('?')[0];
            } else {
                params = new URLSearchParams(window.location.search);
                baseUrl = window.location.pathname;
            }
            params.delete('page');
            params.delete('per_page');
            params.delete('search');
            params.set('export', format);
            var join = baseUrl.indexOf('?') >= 0 ? '&' : '?';
            return baseUrl.split('?')[0] + join + params.toString();
        }

        function loanListServerExport(format) {
            window.location.href = loanListServerExportUrl(format);
        }

        function runDataTableButtonWithOverlay(defaultAction, e, dt, node, config) {
            var overlay = createLoadingOverlay().appendTo('body');
            setTimeout(function() {
                try {
                    defaultAction.call(this, e, dt, node, config);
                } catch (err) {
                    console.error('DataTables export failed:', err);
                } finally {
                    overlay.remove();
                }
            }.bind(this), 100);
        }

        function buildLoanListExportButtons() {
            return [
                {
                    extend: 'copy',
                    text: 'Copy',
                    action: function(e, dt, node, config) {
                        runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.copyHtml5.action, e, dt, node, config);
                    }
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    title: 'Loan List Export',
                    action: function(e, dt, node, config) {
                        runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.csvHtml5.action, e, dt, node, config);
                    }
                },
                {
                    text: 'Excel',
                    action: function(e) {
                        if (e) {
                            e.preventDefault();
                        }
                        loanListServerExport('excel');
                    }
                },
                {
                    text: 'PDF',
                    action: function(e) {
                        if (e) {
                            e.preventDefault();
                        }
                        loanListServerExport('pdf');
                    }
                },
                {
                    extend: 'print',
                    text: 'Print',
                    action: function(e, dt, node, config) {
                        runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.print.action, e, dt, node, config);
                    }
                }
            ];
        }

        function initLoanListDataTable(selector, options) {
            if (!$(selector).length || $.fn.dataTable.isDataTable(selector)) {
                return;
            }
            var settings = $.extend({
                responsive: true,
                dom: 'Bfrtip',
                buttons: buildLoanListExportButtons()
            }, options || {});
            $(selector).DataTable(settings);
        }

        // Check if DataTables is loaded
        if ($.fn.DataTable) {
            if ($('.loan-list-filters-form').length) {
                if ($('#data-table1').length) {
                    initLoanListDataTable('#data-table1');
                }
                if ($('#data-table').length) {
                    initLoanListDataTable('#data-table');
                }
            } else if ($('#data-table1').length && !$.fn.dataTable.isDataTable('#data-table1')) {
                $('#data-table1').DataTable({
                    responsive: true,
                    dom: 'Bfrtip',
                    drawCallback: function() {
                        updateCollectionFooterTotal(this.api());
                    },
                    buttons: [
                        {
                            extend: 'copy',
                            text: 'Copy',
                            action: function(e, dt, node, config) {
                                runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.copyHtml5.action, e, dt, node, config);
                            }
                        },
                        {
                            extend: 'csv',
                            text: 'CSV',
                            title: 'Report Export',
                            action: function(e, dt, node, config) {
                                runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.csvHtml5.action, e, dt, node, config);
                            }
                        },
                        {
                            extend: 'excel',
                            text: 'Excel',
                            title: 'Report Export',
                            action: function(e, dt, node, config) {
                                runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.excelHtml5.action, e, dt, node, config);
                            }
                        },
                        {
                            extend: 'pdf',
                            text: 'PDF',
                            title: 'Report Export',
                            action: function(e, dt, node, config) {
                                runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.pdfHtml5.action, e, dt, node, config);
                            }
                        },
                        {
                            extend: 'print',
                            text: 'Print',
                            action: function(e, dt, node, config) {
                                runDataTableButtonWithOverlay.call(this, $.fn.dataTable.ext.buttons.print.action, e, dt, node, config);
                            }
                        }
                    ]
                });
            }
        } else {
            console.error('DataTables is not loaded');
        }
        
        
            if ($.fn.DataTable && $('#data-table-arrears').length && !$.fn.dataTable.isDataTable('#data-table-arrears')) {
                $('#data-table-arrears').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        text: 'Copy',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            // Use setTimeout to ensure overlay is visible
                            setTimeout(() => {
                                try {
                                    // Perform the actual copy action
                                    $.fn.dataTable.ext.buttons.copyHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('Copy failed:', err);
                                } finally {
                                    // Remove overlay
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        title: 'Arrears Report Export',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('CSV export failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'excel',
                        text: 'Excel',
                        title: 'Arrears Report Export',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('Excel export failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'pdf',
                        text: 'PDF',
                        title: 'Arrears Report Export',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('PDF export failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('Print failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    }
                ]
            });
        } else {
            console.error('DataTables is not loaded');
        }
        
        if ($.fn.DataTable && $('#data-table-collection').length && !$.fn.dataTable.isDataTable('#data-table-collection')) {
            $('#data-table-collection').DataTable({
                responsive: true,
                dom: 'Bfrtip',
                drawCallback: function() {
                    updateCollectionFooterTotal(this.api());
                },
                buttons: [
                    {
                        extend: 'copy',
                        text: 'Copy',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            // Use setTimeout to ensure overlay is visible
                            setTimeout(() => {
                                try {
                                    // Perform the actual copy action
                                    $.fn.dataTable.ext.buttons.copyHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('Copy failed:', err);
                                } finally {
                                    // Remove overlay
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        title: 'Collection Sheet Report Export',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.csvHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('CSV export failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'excel',
                        text: 'Excel',
                        title: 'Collection Sheet Report Export',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.excelHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('Excel export failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'pdf',
                        text: 'PDF',
                        title: 'Collection Sheet Export',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.pdfHtml5.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('PDF export failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        action: function(e, dt, node, config) {
                            var overlay = createLoadingOverlay().appendTo('body');

                            setTimeout(() => {
                                try {
                                    $.fn.dataTable.ext.buttons.print.action.call(this, e, dt, node, config);
                                } catch(err) {
                                    console.error('Print failed:', err);
                                } finally {
                                    overlay.remove();
                                }
                            }, 100);
                        }
                    }
                ]
            });
        } else {
            console.error('DataTables is not loaded');
        }
    });
</script>
<script>
    $(document).ready(function() {
        $('#arrearsTable').DataTable({
            "pageLength": 25,
            "order": [[ 12, "desc" ]], // Sort by arrear days by default
            "responsive": true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">',
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ loans in arrears",
                "infoEmpty": "Showing 0 to 0 of 0 loans in arrears",
                "infoFiltered": "(filtered from _MAX_ total loans)"
            }
        });
    });
</script>

<script>
    $(document).ready(function() {
        // Initialize datepicker if needed
        if ($.fn.datepicker) {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true
            });
        }

        // Form validation
        $('#revenue-report-form').on('submit', function(e) {
            var startDate = $('#start_date').val();
            var endDate = $('#end_date').val();

            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                alert('Start date cannot be after end date');
                e.preventDefault();
                return false;
            }

            return true;
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('#revenueTable').DataTable({
            "pageLength": 25,
            "order": [[ 8, "desc" ]], // Sort by total by default
            "responsive": true,
            "dom": '<"top"lf>rt<"bottom"ip><"clear">',
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "infoEmpty": "Showing 0 to 0 of 0 entries",
                "infoFiltered": "(filtered from _MAX_ total entries)"
            }
        });

        <?php if (!empty($revenue_data)): ?>
        // Prepare data for revenue composition chart
        var compositionCtx = document.getElementById('revenueCompositionChart').getContext('2d');
        var compositionChart = new Chart(compositionCtx, {
            type: 'pie',
            data: {
                labels: ['Interest', 'Admin Fees', 'Loan Cover', 'Processing Fees', 'Penalties', 'Write Off'],
                datasets: [{
                    data: [
                        <?php echo $totals['interest']; ?>,
                        <?php echo $totals['admin_fees']; ?>,
                        <?php echo $totals['loan_cover']; ?>,
                        <?php echo $totals['processing_fees']; ?>,
                        <?php echo $totals['penalties']; ?>,
                        <?php echo $totals['write_off']; ?>
                    ],
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#9b59b6',
                        '#f39c12',
                        '#e74c3c',
                        '#34495e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: {
                    position: 'right'
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(previousValue, currentValue) {
                                return previousValue + currentValue;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.floor(((currentValue/total) * 100)+0.5);
                            return data.labels[tooltipItem.index] + ': K' + currentValue.toFixed(2) + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        });

        // Prepare data for branch revenue chart
        var branchData = {};
        <?php foreach ($revenue_data as $item): ?>
        if (!branchData['<?php echo $item['branch_name']; ?>']) {
            branchData['<?php echo $item['branch_name']; ?>'] = 0;
        }
        branchData['<?php echo $item['branch_name']; ?>'] += <?php echo $item['total']; ?>;
        <?php endforeach; ?>

        var branchLabels = [];
        var branchValues = [];

        for (var branch in branchData) {
            branchLabels.push(branch);
            branchValues.push(branchData[branch]);
        }

        var branchCtx = document.getElementById('revenueBranchChart').getContext('2d');
        var branchChart = new Chart(branchCtx, {
            type: 'bar',
            data: {
                labels: branchLabels,
                datasets: [{
                    label: 'Revenue by Branch',
                    data: branchValues,
                    backgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {
                                return 'K' + value.toLocaleString();
                            }
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            return 'Revenue: K' + tooltipItem.yLabel.toFixed(2);
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    });

    // Handle period selection
    $('select[name="period"]').change(function() {
        var period = $(this).val();
        var fromDate = $('input[name="from"]');
        var toDate = $('input[name="to"]');

        var today = new Date();

        if (period === 'today') {
            var formattedDate = formatDate(today);
            fromDate.val(formattedDate);
            toDate.val(formattedDate);
        } else if (period === 'this_week') {
            var firstDay = new Date(today.setDate(today.getDate() - today.getDay()));
            var lastDay = new Date(today.setDate(today.getDate() - today.getDay() + 6));
            fromDate.val(formatDate(firstDay));
            toDate.val(formatDate(lastDay));
        } else if (period === 'this_month') {
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            fromDate.val(formatDate(firstDay));
            toDate.val(formatDate(lastDay));
        } else if (period === 'last_month') {
            var firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            var lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
            fromDate.val(formatDate(firstDay));
            toDate.val(formatDate(lastDay));
        }
    });

    // Format date helper function
    function formatDate(date) {
        var d = new Date(date),
            month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }
    });
</script>
<!-- Add this before the closing body tag -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Report Trends Chart
        var ctx1 = document.getElementById('reportTrendsChart').getContext('2d');
        var reportTrendsChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    // Get last 6 months
                    $labels = array();
                    for($i = 5; $i >= 0; $i--) {
                        $month = date('M Y', strtotime("-$i month"));
                        $labels[] = "'$month'";
                    }
                    echo implode(',', $labels);
                    ?>
                ],
                datasets: [{
                    label: 'Reports Generated',
                    data: [
                        <?php
                        // Get report counts for last 6 months
                        $counts = array();
                        for($i = 5; $i >= 0; $i--) {
                            $start_date = date('Y-m-01', strtotime("-$i month"));
                            $end_date = date('Y-m-t', strtotime("-$i month"));

                            $this->db->where('generated_time >=', $start_date);
                            $this->db->where('generated_time <=', $end_date);
                            $count = $this->db->count_all_results('reports');
                            $counts[] = $count;
                        }
                        echo implode(',', $counts);
                        ?>
                    ],
                    borderColor: '#3f87f5',
                    backgroundColor: 'rgba(63, 135, 245, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Report Types Chart
        var ctx2 = document.getElementById('reportTypesChart').getContext('2d');
        var reportTypesChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php
                    $this->db->select('report_type, COUNT(*) as count');
                    $this->db->group_by('report_type');
                    $report_types = $this->db->get('reports')->result();

                    $type_labels = array();
                    $type_data = array();
                    $type_colors = array('#3f87f5', '#ff6b88', '#ffaf28', '#00c3aa', '#6b7280', '#a855f7');
                    $color_index = 0;

                    foreach($report_types as $type) {
                        $type_labels[] = "'".$type->report_type."'";
                        $type_data[] = $type->count;
                        $color_index++;
                    }

                    echo implode(',', $type_labels);
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(',', $type_data); ?>],
                    backgroundColor: [<?php
                        $colors = array();
                        for($i = 0; $i < count($type_data); $i++) {
                            $colors[] = "'".$type_colors[$i % count($type_colors)]."'";
                        }
                        echo implode(',', $colors);
                        ?>]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });

    // Group File AJAX functionality
    $(document).ready(function() {
        // Group File page functionality
        if (window.location.pathname.includes('group_file')) {
            $('#group_select').change(function() {
                const groupId = $(this).val();
                console.log('Group selected for file:', groupId);
                
                if (groupId) {
                    // Show loading
                    $('#batch-loading').show();
                    $('#batch-selection').hide();
                    $('#batch-message').hide();
                    
                    $.ajax({
                        url: '<?php echo base_url("loan/get_group_batches"); ?>',
                        type: 'POST',
                        data: { group_id: groupId },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Batches Response:', response);
                            
                            $('#batch-loading').hide();
                            
                            if (response.success && response.batches.length > 0) {
                                // Populate batch dropdown
                                let batchOptions = '<option value="">--select batch--</option>';
                                response.batches.forEach(function(batch) {
                                    batchOptions += `<option value="${batch.batch}">${batch.batch}</option>`;
                                });
                                $('#batch_select').html(batchOptions);
                                $('#batch-selection').show();
                            } else {
                                $('#batch-message').html(`
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>No batches found for this group
                                    </div>
                                `).show();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX Error:', {xhr: xhr, status: status, error: error});
                            $('#batch-loading').hide();
                            $('#batch-message').html(`
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle mr-2"></i>Error loading batches: ${error}
                                </div>
                            `).show();
                        }
                    });
                } else {
                    $('#batch-selection').hide();
                    $('#batch-loading').hide();
                    $('#batch-message').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>Please select a group first
                        </div>
                    `).show();
                }
            });
            
            // Batch selection change
            $('#batch_select').change(function() {
                const batch = $(this).val();
                if (batch) {
                    $('#continue_btn').prop('disabled', false);
                } else {
                    $('#continue_btn').prop('disabled', true);
                }
            });
            
            // Continue button click
            $('#continue_btn').click(function() {
                const batch = $('#batch_select').val();
                if (batch) {
                    window.location.href = '<?php echo base_url("loan/group_batch_loans/"); ?>' + encodeURIComponent(batch);
                }
            });
        }
    });

    // Group Member Loans AJAX functionality
    $(document).ready(function() {
        $('#group_select').change(function() {
            const groupId = $(this).val();
            console.log('Group selected:', groupId);
            
            if (groupId) {
                $.ajax({
                    url: '<?php echo base_url("Customer_groups/get_members/"); ?>' + groupId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        console.log('AJAX Response:', response);
                        
                        if (response.data && response.data.length > 0) {
                            let membersList = `
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle mr-2"></i>Found ${response.data.length} member(s) in this group
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Member Name</th>
                                                <th>Loan Amount (MK)</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                            `;
                            
                            response.data.forEach(function(member) {
                                membersList += `
                                    <tr class="member-row" data-member-id="${member.customer}">
                                        <td>
                                            <strong>${member.Firstname} ${member.Lastname}</strong>
                                            <input type="hidden" name="member_ids[]" value="${member.customer}">
                                        </td>
                                        <td>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">MK</span>
                                                </div>
                                                <input type="number" name="member_amounts[]" class="form-control" 
                                                       placeholder="0.00" step="0.01" min="0" required>
                                            </div>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger remove-member-btn" title="Remove member from loan">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            
                            membersList += `
                                        </tbody>
                                    </table>
                                </div>
                            `;
                            
                            $('#members-list').html(membersList);
                            
                            // Add remove member functionality
                            $('.remove-member-btn').off('click').on('click', function() {
                                const totalMembers = $('.member-row').length;
                                
                                if (totalMembers <= 1) {
                                    alert('At least one member must remain in the loan group.');
                                    return;
                                }
                                
                                const memberRow = $(this).closest('.member-row');
                                const memberName = memberRow.find('strong').text();
                                const memberId = memberRow.data('member-id');
                                
                                if (confirm(`Are you sure you want to remove ${memberName} from this loan?`)) {
                                    console.log('Removing member:', memberName, 'ID:', memberId);
                                    memberRow.remove();
                                    
                                    // Debug: Log remaining members
                                    console.log('Remaining members:', $('.member-row').length);
                                    $('.member-row').each(function(index) {
                                        const id = $(this).find('input[name="member_ids[]"]').val();
                                        const amount = $(this).find('input[name="member_amounts[]"]').val();
                                        console.log(`Member ${index}: ID=${id}, Amount=${amount}`);
                                    });
                                    
                                    // Update member count display
                                    const remainingMembers = $('.member-row').length;
                                    $('.alert-success').html(`
                                        <i class="fas fa-check-circle mr-2"></i>Found ${remainingMembers} member(s) selected for loan
                                    `);
                                    
                                    // Update group info
                                    updateGroupMemberCount();
                                }
                            });
                            
                            // Store group data for later use
                            const groupData = response;
                            
                            // Function to update group member count
                            function updateGroupMemberCount() {
                                const selectedMembers = $('.member-row').length;
                                const groupInfo = `
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-users mr-2"></i>Group Details</h6>
                                        <p><strong>Group Name:</strong> ${groupData.group.group_name || 'Unknown'}</p>
                                        <p><strong>Group Code:</strong> ${groupData.group.group_code || 'Unknown'}</p>
                                        <p><strong>Total Members:</strong> ${groupData.data.length}</p>
                                        <p><strong>Selected for Loan:</strong> <span class="badge badge-primary">${selectedMembers}</span></p>
                                    </div>
                                `;
                                $('#group-info').html(groupInfo);
                            }
                            
                            // Update group info section
                            let groupInfo = `
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-users mr-2"></i>Group Details</h6>
                                    <p><strong>Group Name:</strong> ${response.group.group_name || 'Unknown'}</p>
                                    <p><strong>Group Code:</strong> ${response.group.group_code || 'Unknown'}</p>
                                    <p><strong>Total Members:</strong> ${response.data.length}</p>
                                    <p><strong>Selected for Loan:</strong> <span class="badge badge-primary">${response.data.length}</span></p>
                                </div>
                            `;
                            $('#group-info').html(groupInfo);
                        } else {
                            $('#members-list').html(`
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>No members found in this group
                                </div>
                            `);
                            $('#group-info').html(`
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>Select a group to view information
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error:', {xhr: xhr, status: status, error: error});
                        $('#members-list').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-times-circle mr-2"></i>Error loading group members: ${error}
                            </div>
                        `);
                    }
                });
            } else {
                $('#members-list').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>Select a group to load members
                    </div>
                `);
                $('#group-info').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>Select a group to view information
                    </div>
                `);
            }
        });
    });
    
    // Form validation function for group member loans
    function validateFormSubmission() {
        const memberRows = $('.member-row');
        const memberIds = [];
        const memberAmounts = [];
        let hasValidAmount = false;
        
        console.log('Validating form submission...');
        console.log('Total member rows:', memberRows.length);
        
        if (memberRows.length === 0) {
            alert('Please select a group first to load members.');
            return false;
        }
        
        // Check each member row
        memberRows.each(function(index) {
            const memberId = $(this).find('input[name="member_ids[]"]').val();
            const memberAmount = $(this).find('input[name="member_amounts[]"]').val();
            const memberName = $(this).find('strong').text();
            
            console.log(`Member ${index}: ${memberName}, ID: ${memberId}, Amount: ${memberAmount}`);
            
            memberIds.push(memberId);
            memberAmounts.push(memberAmount);
            
            if (memberAmount && parseFloat(memberAmount) > 0) {
                hasValidAmount = true;
            }
        });
        
        if (!hasValidAmount) {
            alert('Please enter loan amounts for at least one member.');
            return false;
        }
        
        console.log('Member IDs:', memberIds);
        console.log('Member Amounts:', memberAmounts);
        
        return confirm(`Are you sure you want to create loans for ${memberRows.length} selected member(s)?`);
    }
</script>
<script>
$(document).ready(function(){
    // Payment form actions to protect against double submission
    var paymentActions = ['pay_loan','pay_advance','pay_late_loan','finish_loan'];

    $('form').on('submit', function(e){
        var form = $(this);
        var action = form.attr('action') || '';

        // Check if this form posts to a payment endpoint
        var isPaymentForm = false;
        for(var i = 0; i < paymentActions.length; i++){
            if(action.indexOf(paymentActions[i]) !== -1){
                isPaymentForm = true;
                break;
            }
        }

        if(!isPaymentForm) return;

        // Prevent double submission
        if(form.data('submitted') === true){
            e.preventDefault();
            return false;
        }

        // Mark as submitted
        form.data('submitted', true);

        // Find and disable the submit button, show processing
        var btn = form.find('button[type="submit"]');
        btn.each(function(){
            var $btn = $(this);
            $btn.data('original-text', $btn.html());
            $btn.html('<i class="fa fa-spinner fa-spin" style="margin-right:5px;"></i>Processing...');
            $btn.prop('disabled', true);
            $btn.css({'opacity':'0.7','cursor':'not-allowed'});
        });

        // Safety: re-enable after 30 seconds in case of network issues
        setTimeout(function(){
            form.data('submitted', false);
            btn.each(function(){
                var $btn = $(this);
                $btn.html($btn.data('original-text'));
                $btn.prop('disabled', false);
                $btn.css({'opacity':'1','cursor':'pointer'});
            });
        }, 30000);
    });
});
</script>
</body>
</html>
