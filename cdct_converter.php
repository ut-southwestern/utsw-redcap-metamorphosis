<?php
/*
 *  MetaMorphosis
 *  ClinicalDataConversionTool 
 */

$cdct = new UTSW\ClinicalDataConversionTool\ClinicalDataConversionTool();

$dataConvert = $cdct->getUrl('cdct_convert_data.php');
$progress = "<div id='progress' style='background-color:#FFF;width:500px;border:1px solid #A5CC7A;color:#800000;'><table cellpadding=10><tr>".
			"<td vertical-align=top><img src='" . APP_PATH_IMAGES . "progress.gif'></td>".
			"<td vertical-align=top style='padding-top:20px;'><b>Your data is currently being imported.<br>Please wait till the process has completed.</b><br>This could take several minutes.</td>".
			"</tr></table></div>";

$cdct_header = '
    <link rel="stylesheet" type="text/css" href="'.$cdct->getUrl('css/cdct.style.css').'">

    <script type="text/javascript">
        $(function(){
			var formData;
			
            $("#cdctForm").submit(function (e) {
                $("#errorMsg").hide();
                $("#successMsg").hide();
                var errMsg = [];
				
				//validate input
				if ($("#cdctDocument").val() != "") {
					var filename = $("#cdctDocument").val();
					var extension = filename.replace(/^.*\./, "");
					if (extension == filename) {
						extension = "";
					} else {
						extension = extension.toLowerCase();
					}
					if (extension != "csv") {
						errMsg.push("<strong>Document Error:</strong> File extension is invalid! Only .csv are valid.");
					}
				} else 
					errMsg.push("<strong>Document Error:</strong> File was not selected!");
				
				if ($("#cdctLabel").val() == "")
					errMsg.push("<strong>Label Error:</strong> Label was not enter!");
				if ($("#cdctIRB").val() == "")
					errMsg.push("<strong>IRB Error:</strong> IRB Number was not enter!");
				if ($("#cdctRequest").val() == "")
					errMsg.push("<strong>Request Error:</strong> Request Number was not enter!");
				if ($("#cdctPI").val() == "")
					errMsg.push("<strong>PI Error:</strong> Principal investigator Name was not enter!");
				
                $("#errorMsg").empty();
                if (errMsg.length > 0) {
                    $.each(errMsg, function (i, e) {
                        $("#errorMsg").append("<div>" + e + "</div>");
                    });
                    $("#errorMsg").show();
                    $("html,body").scrollTop(0);
                    return false;
                }
				
				formData = new FormData($("#cdctForm")[0]);
				
                ajaxRequestForm(formData,"'.$dataConvert.'","convert");

				return false;
            });

			$("#btnIgnore").on("click", function(e){
                $("#errorMsg").hide();
                $("#successMsg").hide();
				$("#errorMsg").empty();
				$("#successMsg").empty();
				
				formData = new FormData($("#cdctForm")[0]);
				
                ajaxRequestForm(formData,"'.$dataConvert.'","ignore");
				
				return false;
			});

			$("#btnImport").on("click", function(e){
                $("#errorMsg").hide();
                $("#successMsg").hide();
				$("#errorMsg").empty();
				$("#successMsg").empty();
				
				formData = new FormData($("#cdctForm")[0]);
				
                ajaxRequestForm(formData,"'.$dataConvert.'","import");
				
				return false;
			});

			$("#btnClear").on("click", function(e){
                $("#errorMsg").hide();
                $("#successMsg").hide();
				$("#cdctConverterMsg").hide();
				$("#errorMsg").empty();
				$("#successMsg").empty();
				$("#cdctMessage").empty();
				$("#cdctConverterForm").show();
				$("#cdctDocument").val("");
				$("#cdctLabel").val("");
				$("#cdctIRB").val("");
				$("#cdctRequest").val("");
				$("#cdctPI").val("");
				$("#cdctErrorIds").val("");
				return false;
			});

			$("#btnBack").on("click", function(e){
                $("#errorMsg").hide();
                $("#successMsg").hide();
				$("#cdctConverterMsg").hide();
				$("#errorMsg").empty();
				$("#successMsg").empty();
				$("#cdctMessage").empty();
				$("#cdctConverterForm").show();
				$("#cdctErrorIds").val("");
				return false;
			});

		});

		function ajaxRequestForm(formData, url, action) {
			formData.append("Action", action);
			$.ajax({
				type: "POST",
				url: url,
				data: formData,
				processData: false,
				contentType: false,
				cache: false,
				beforeSend: function(xhr) {
					$("#successMsg").html("<div>Please Wait, Processing...</div>");
					$("#successMsg").show();
					$("#cdctMessage").html("'.$progress.'");
					$("#cdctConverterMsg").show();
					$("html,body").scrollTop(0);
				},
				error: function (xhr, status, error) {
					$("#errorMsg").html("<div>" + xhr.responseText + "</div>");
					$("#errorMsg").show();
					$("#cdctConverterForm").show();
					$("html,body").scrollTop(0);
				},
				success: function (result) {
					jsonAjax = jQuery.parseJSON(result);
					$("#errorMsg").hide();
					$("#errorMsg").empty();
					$("#successMsg").hide();
					$("#successMsg").empty();
					$("#cdctConverterMsg").hide();
					$("#cdctConverterForm").hide();
					$("#btnImport").hide();
					$("#btnBack").hide();
					$("#btnIgnore").hide();
					$("#cdctErrorIds").val("");
					
					if(jsonAjax.status == "success") {
						if(jsonAjax.message != "" && jsonAjax.message != undefined){
							$("#cdctMessage").html("<div>" + jsonAjax.message + "</div>");
							$("#cdctConverterMsg").show();
						} else {
							$("#successMsg").html("File converted and Imported Successfully!");
							$("#successMsg").show();
						}
						$("#cdctConverterForm").show();
						$("html,body").scrollTop(0);
					} else if(jsonAjax.status == "invalidids") {
						//$("#errorMsg").html("<div>Demographic data could not be found for the following records!</div>");
						//$("#errorMsg").show();
						$("#cdctMessage").html("<div>" + jsonAjax.message + "</div>");
						$("#cdctErrorIds").val(jsonAjax.errorids);
						$("#btnBack").show();
						$("#btnIgnore").show();
						$("#cdctConverterMsg").show();
						$("html,body").scrollTop(0);
					} else if(jsonAjax.status == "invaliddata") {
						//$("#errorMsg").html("<div>Invalid Demographic data found!</div>");
						//$("#errorMsg").show();
						$("#cdctMessage").html("<div>" + jsonAjax.message + "</div>");
						$("#cdctErrorIds").val(jsonAjax.errorids);
						$("#btnBack").show();
						$("#btnIgnore").show();
						$("#cdctConverterMsg").show();
						$("html,body").scrollTop(0);
					} else if(jsonAjax.status == "loadeddata") {
						//$("#errorMsg").html("<div>Demographic data already loaded!</div>");
						//$("#errorMsg").show();
						$("#cdctMessage").html("<div>" + jsonAjax.message + "</div>");
						$("#btnBack").show();
						$("#cdctConverterMsg").show();
						$("html,body").scrollTop(0);
					} else if(jsonAjax.status == "uploaded") {
						$("#cdctMessage").html("<div>" + jsonAjax.message + "</div>");
						$("#cdctErrorIds").val(jsonAjax.errorids);
						$("#btnImport").show();
						$("#btnBack").show();
						$("#cdctConverterMsg").show();
						$("html,body").scrollTop(0);
					} else { //error
						$("#errorMsg").html("<div>" + jsonAjax.message + "</div>");
						$("#errorMsg").show();
						$("#cdctConverterForm").show();
						$("html,body").scrollTop(0);
					}
				},
				//complete: function() {
				//	$("#successMsg").html("<div>Processing Completed.</div>");
				//	$("#successMsg").show();
				//}
			});
        }

    </script>
'; //end header

$cdct_body = '
	<!-- CONVERT FORM -->
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-12 page_title"><img src="'.$cdct->getUrl('img/cdct.logo.png').'" title="MetaMorphosis" style="height:100px;"> Clinical Data Conversion Tool </div>
			<div class="col-md-12">
				<div class="cdct_success alert alert-success green" id="successMsg" role="alert" style="display:none;"></div>
				<div class="cdct_alert alert alert-danger red" id="errorMsg" role="alert" style="display:none;"></div>
			</div>
			<div class="col-md-12" id="cdctConverterMsg" style="display:none;">
				<div id="cdctMessage"></div><br>
				<button type="button" class="btn btn-default pull-right cdct_forms_button cdct_forms_button_color" id="btnBack">Back</button>
				<button type="submit" class="btn btn-info pull-right cdct_forms_button" id="btnIgnore">Ignore&Continue</button>
				<button type="submit" class="btn btn-info pull-right cdct_forms_button" id="btnImport">Import Data</button>
			</div>
			<div class="col-md-12" id="cdctConverterForm">
				<form class="form-inline"  id="cdctForm" action="#" method="post" enctype="multipart/form-data" style="max-width:800px;">
					<table class="table table-bordered table-hover" style="margin-bottom:0">
						<tr class="table_subheader panel-heading"">
							<td colspan="2">
								The MetaMorphosis Clinical Data Conversion Tool External Module allows REDCap Administrators to take a collection of exported patient identifiers from a source system (TriNetX, i2b2, other data warehouse) and convert them into an identified patient cohort in a standard demographics REDCap template.
							</td>
						</tr>
						<tr class="table_header">
							<td>Field</td>
							<td>Data</td>
						</tr>
						<tr class="panel-default" aria-expanded="true">
							<td class="table_col_1"><span style="padding-left:5px;"><strong>Document</strong></span>
								<div class="description_config">Select Document. </div>
							</td>
							<td class="table_col_2">
								<input type="file" name="Document" id="cdctDocument" style="width:100%;border:none;" value="">
							</td>
						</tr>
						<tr class="panel-default" aria-expanded="true">
							<td class="table_col_1"><span style="padding-left:5px;"><strong>Name/Label</strong></span>
								<div class="description_config">Enter Name or Label. </div>
							</td>
							<td class="table_col_2">
								<input type="text" name="Label" id="cdctLabel" style="width:100%;" placeholder="Text" value="">
							</td>
						</tr>
						<tr class="panel-default" aria-expanded="true">
							<td class="table_col_1"><span style="padding-left:5px;"><strong>IRB Number</strong></span>
								<div class="description_config">Enter IRB Number. </div>
							</td>
							<td class="table_col_2">
								<input type="text" name="IRB" id="cdctIRB" style="width:100%;" placeholder="Id" value="">
							</td>
						</tr>
						<tr class="panel-default" aria-expanded="true">
							<td class="table_col_1"><span style="padding-left:5px;"><strong>Request Number</strong><span>
								<div class="description_config">Enter Request Number. </div>
							</td>
							<td class="table_col_2">
								<input type="text" name="Request" id="cdctRequest" style="width:100%;" placeholder="Number" value="">
							</td>
						</tr>
						<tr class="panel-default" aria-expanded="true">
							<td class="table_col_1">
								<span style="padding-left:5px;"><strong>PI Name</strong><span>
								<div class="description_config">Enter Principal Investigator name.</div>
							</td>
							<td class="table_col_2">
								<input type="text" name="PI" id="cdctPI" style="width:100%;" placeholder="Name" value=""/>
							</td>
						</tr>
					</table>
					<input type="hidden" name="ErrorIds" id="cdctErrorIds" value=""/>
				</form>
				<button type="button" class="btn btn-default pull-right cdct_forms_button cdct_forms_button_color" id="btnClear">Clear</button>
				<button type="submit" class="btn btn-info pull-right cdct_forms_button" id="btnConvert" form="cdctForm">Submit</button>
			</div>
		</div>
	</div>
'; //end body

$cdct_window = '<div id="cdct_window">'.$cdct_header.$cdct_body.'</div>';

// view the page window in My Projects
$cdct->viewHtml($cdct_window, 'project');

