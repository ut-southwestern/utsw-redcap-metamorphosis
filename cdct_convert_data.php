<?php
/*
 *  MetaMorphosis
 *  ClinicalDataConversionTool 
 */

$cdct = new UTSW\ClinicalDataConversionTool\ClinicalDataConversionTool();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$output = array (
	'status' 	=> "error",
	'message' 	=> "Unkown Error! Please call System Support.",
	'errorids'	=> ""
);

$prefix 	= @$_GET['prefix'];
$project_id = @$_GET['pid'];
$upload_dir = APP_PATH_TEMP;

//example of calling debugLogMsg
//$audit = "Audit data";
//$cdct->debugLogMsg('Audit: ' . $audit);

//get config parameters and template file
$settings = $cdct->getFormattedSettings($prefix);
/*Array ( 
	[enabled] => 
	[discoverable-in-project] => 
	[config-require-user-permission] => 
	[cdct-config] => 
	[cdct-username] => trinetxtool 
	[cdct-password] => *********** 
	[cdct-host] => swlxaisdev1.swmed.org 
	[cdct-port] => 1522 
	[cdct-sid] => dwdevf1 
	[cdct-import] => 
	[cdct-template] => 88151 
)*/
if (empty($settings)) {
	$output["status"]  = "error";
	$output["message"] = "Unable to read configuration file. <br>"; 
} else {
	//get template file
	$cdct_templ = $settings['cdct-template'];
	$templ_file = $cdct->getFileInfo((integer) $cdct_templ);
	$templ_name = $templ_file["filename"];
	$templ_data = $templ_file["filedata"];

	//get screen data
	foreach ($_POST as $key=>$value) {
		$_POST[$key] = db_escape($value);
	}
	$formdata = [
		"label" => $_POST['Label'],
		"irb" => $_POST['IRB'],
		"request" => $_POST['Request'],
		"pi" => $_POST['PI'],
		"api" => $_POST['API'],
		"action" => $_POST['Action'],
		"errorids" => $_POST['ErrorIds']
	];

	//get input document
	if ($_FILES['Document']['name'] == "") {
		$output["status"]  = "error";
		$output["message"] = "Unable to get Document file!"; 
	} else {
		$document	= $_FILES['Document']['name'];
		$doctemp 	= $_FILES['Document']['tmp_name'];
		$docname 	= substr($_FILES['Document']['name'], 0, strripos($_FILES['Document']['name'], ".")); 
		$docext 	= substr(strrchr($_FILES['Document']['name'], "."), 1); 
		
		//set temp document file
		$docimport = date('YmdHis')."_".$docname ."_".$project_id."_import_data.".$docext;
		$docimport = str_replace("\\", "\\\\", $upload_dir . $docimport);
		
		if (!file_exists($doctemp)) {
			$output["status"] = "error";
			$output["message"] = "Unable to access Document file!";
		} else {
			if ($formdata["action"] == "convert") {
				//merge document with Demographic data 
				$response = $cdct->createDemographicFile($settings,$formdata,$doctemp,$docimport);
				if ($response["status"] == "converted") {
					//import merge file into project but do not update records
					$response = $cdct->importDemographicFile($docimport);
				}
			} elseif ($formdata["action"] == "ignore") { //user decided to ignore errors
				//merge document with Demographic data 
				$response = $cdct->createDemographicFile($settings,$formdata,$doctemp,$docimport);
				if ($response["status"] == "converted" || $response["status"] == "invalidids") {
					//import merge file into project but do not update records
					$response = $cdct->importDemographicFile($docimport);
					if ($response["errorids"] == "") //keep errorids from previous ignore
						$response["errorids"] = $formdata["errorids"];
				}
			} elseif ($formdata["action"] == "import") { //user ok to import data
				$response = $cdct->createDemographicFile($settings,$formdata,$doctemp,$docimport);
				if ($response["status"] != "error") {
					//import merge file into project and update records
					$response = $cdct->importDemographicData($docimport);
					if ($response["status"] == "success") {
						//create audit trail
						$loaded = isset($response["loaded"]) ? $response["loaded"] : 0;
						$audit = json_encode(array("label" => $formdata["label"], "irb" => $formdata["irb"], "request" => $formdata["request"], "pi" => $formdata["pi"], "document" => $document, "loaded" => $loaded));
						$cdct->debugLog('MetaMorphosis Audit', $audit);
					}
				}
			}
			unlink($docimport);
			if (isset($response["status"]) && isset($response["message"])) {
				$output["status"] 	= $response["status"];
				$output["message"]	= $response["message"];
				$output["errorids"]	= $response["errorids"];
			}
		}
	}
}
echo json_encode( $output );
