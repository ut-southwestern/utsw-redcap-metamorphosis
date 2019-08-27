<?php
/*
 *  MetaMorphosis
 *  ClinicalDataConversionTool 
 */
$cdct = new UTSW\ClinicalDataConversionTool\ClinicalDataConversionTool();

//get screen data
foreach ($_POST as $key=>$value) {
	$_POST[$key] = db_escape($value);
}
$dateFrom	= $_POST['dateFrom'] ? $_POST['dateFrom'] : "";
$dateTo		= $_POST['dateTo'] ? $_POST['dateTo'] : "";

//get Audit records
$audit_list = $cdct->get_audit_trail_report($dateFrom, $dateTo);

//create page
$cdct_header = '
	<link rel="stylesheet" type="text/css" href="'.$cdct->getUrl('css/cdct.style.css').'">
    <script type="text/javascript">
        $(function(){
			
			$("#btnSubmit").on("click", function(e){
				$("#cdctForm").attr("action","'.$cdct->getUrl("cdct_report.php").'");
				$("#cdctForm").submit();
			});

			$("#btnExport").on("click", function(e){
				$("#cdctForm").attr("action","'.$cdct->getUrl("cdct_export_csv.php").'");
				$("#cdctForm").submit();
			});
			
		});
    </script>
'; //end header
	
$cdct_body = '
	<!-- AUDIT TABLE -->
	<form class="form-inline" action="#" method="post" id="cdctForm" enctype="multipart/form-data" style="width:100%;">
		<div class="container-fluid wiki">
			<div class="row">
				<div class="col-md-12 page_title"><img src="'.$cdct->getUrl('img/cdct.logo.png').'" title="MetaMorphosis" style="height:100px;"> Audit Report </div>
				<div id="errMsgContainer" class="alert alert-danger col-md-12" role="alert" style="display:none;margin-bottom:20px;"></div>
				<div class="alert alert-success fade in col-md-12" style="border-color: #b2dba1 !important;display: none;" id="succMsgContainer"></div>
				<div class="col-md-12">
					<table class="table text-nowrap">
						<tr class="table_standard">
							<td class="table_col_middle">
								<div class="form-group">
									<label for="dateFrom">Date From:&nbsp;</label>
									<input type="date" id="dateFrom" name="dateFrom" value="'.$dateFrom.'">
								</div>
							</td>
							<td class="table_col_middle">
								<div class="form-group">
									<label for="dateTo">Date To:&nbsp;</label>
									<input type="date" id="dateTo" name="dateTo" value="'.$dateTo.'">
								</div>
							</td>
							<td class="table_col_middle">
								<button type="button" class="btn btn-default pull-right cdct_forms_button cdct_forms_button_color" id="btnSubmit" form="cdctForm">Submit</button>
							</td>
							<td class="table_col_middle">
								<button type="button" class="btn btn-info pull-left cdct_forms_button" id="btnExport" form="cdctForm">Export</button>
							</td>
						</tr>
					</table>
					<table class="table table-bordered table-hover text-nowrap" style="margin-bottom: 0">
						<tr class="table_subheader panel-heading"">
							<td colspan="9">
								This Report shows an audit trail of MetaMorphosis clinical data conversion tool.
							</td>
						</tr>
						<tr class="table_header">
							<td>Date</td>
							<td>Project Id</td>
							<td>User</td>
							<td>Name/Label</td>
							<td>IRB Number</td>
							<td>Request #</td>
							<td>PI Name</td>
							<td>Document</td>
							<td>Records<br>Loaded</td>
						</tr>
						'.$audit_list.'
 					</table>
				</div>
			</div>
		</div>
	</form>
'; //end body

$cdct_window = '<div id="cdct_window">'.$cdct_header.$cdct_body.'</div>';

// view the page in Control Center
$cdct->viewHtml($cdct_window, 'control');

