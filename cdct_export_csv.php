<?php
/*
 *  MetaMorphosis
 *  ClinicalDataConversionTool 
 */

$cdct = new UTSW\ClinicalDataConversionTool\ClinicalDataConversionTool();

$errors = $cdct->download_audit_csv();

if (!empty($errors))
{
	$errors_list = "";
	foreach($errors as $error) {
		$errors_list .= '<p>'.$error.'</p>';
	}
	
	$cdct_header = '
		<link rel="stylesheet" type="text/css" href="'.$cdct->getUrl('css/cdct.style.css').'">
	'; //end header

	$cdct_body = '
		<div class="col-md-12 page_title"><img src="'.$cdct->getUrl('img/cdct.logo.png').'" title="MetaMorphosis" style="height:100px;"> Clinical Data Conversion Tool </div>
		<div class="cdct_alert alert alert-danger col-md-12" id="errorMsg" role="alert">'.$errors_list.'</div>
	'; //end body
	
	$cdct_window = '<div id="cdct_window">'.$cdct_header.$cdct_body.'</div>';

	// view the page in Control Center
	$cdct->viewHtml($cdct_window, 'control');
}