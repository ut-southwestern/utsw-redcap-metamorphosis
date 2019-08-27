<?php
/**
 *  MetaMorphosis
 *  ClinicalDataConversionTool 
 *  
 * @institution UTSW - UT Southwestern Medical Center. 
 * @author Alberto DeCabrera
 * @version 1.0
 * @date 20190425
 *
	UTSD: 367
	TITLE: MetaMorphsis Clinical Data Conversion Tool
	CONTRIBUTORS: Teresa Bosler, Alberto DeCabrera

	a.     Copyright ©2019, The University of Texas Southwestern Medical Center.  All rights reserved; 
	and
	b.    This software and any related documentation constitutes published and/or unpublished works and may contain valuable trade secrets and proprietary information belonging to The University of Texas Southwestern Medical Center (UT SOUTHWESTERN).  None of the foregoing material may be copied, duplicated or disclosed without the express written permission of UT SOUTHWESTERN.  IN NO EVENT SHALL UT SOUTHWESTERN BE LIABLE TO ANY PARTY FOR DIRECT, INDIRECT, SPECIAL, INCIDENTAL, OR CONSEQUENTIAL DAMAGES, INCLUDING LOST PROFITS, ARISING OUT OF THE USE OF THIS SOFTWARE AND ITS DOCUMENTATION, EVEN IF UT SOUTHWESTERN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.  UT SOUTHWESTERN SPECIFICALLY DISCLAIMS ANY WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE SOFTWARE AND ACCOMPANYING DOCUMENTATION, IF ANY, PROVIDED HEREUNDER IS PROVIDED "AS IS". UT SOUTHWESTERN HAS NO OBLIGATION TO PROVIDE MAINTENANCE, SUPPORT, UPDATES, ENHANCEMENTS, OR MODIFICATIONS.
	c.    This software contains copyrighted materials from the MIT License and the Vanderbilt REDCap Group.  Corresponding terms and conditions apply.
 */

namespace UTSW\ClinicalDataConversionTool;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

use \Logging;
use \REDCap;
use \Message;
use \HtmlPage;
use \Files;
use \System;
use \RCView;
use \DateTimeRC;
use \Records;
use \MetaData;
use \Project;
use \ODM;
use \User;
use \SendIt;
use \DataExport;


// you can include other files and use those classes in your code, just be sure you have the namespace line in those.
//include_once 'LoggingHandler.php';

class ClinicalDataConversionTool extends AbstractExternalModule
{
	private $projectId;

	public $debug_mode_log_system;
	public $debug_mode_log_project;
	public $debugLogFlag;

	private $dbdriver = "";
	private $dbconn = 0;
	private $dbstid = 0;
	
	/**
	 * - set up defaults.
	 */
	function __construct($pid = null)
	{
		parent::__construct();
		
		$this->projectId = null;

		// project ID of project 
		if ($pid) {
			$projectId = $pid;
		} else {
			$projectId = (isset($_GET['pid']) ? $_GET['pid'] : 0);
		}
		
		if ($projectId > 0) {
			$this->projectId = $projectId;
		} 

		// ***** ***** Blank init CONFIG values ***** ***** 
		// init these values and set them after we load up the config

		//$this->debug_mode_log_project = null;
		//$this->debug_mode_log_system = null;

		
		// ***** Load CONFIG *****
		//$this->loadConfig($projectId);
		// ******************************
	}
	
	/**
	 * loadConfig - configuration settings here.
	 */
/*	public function loadConfig($projectId = 0) 
	{
		if ($projectId > 0) {
			$this->loadProjectConfig($projectId);
		} else {
			$this->loadProjectConfigDefaults();
		}

		$this->loadSystemConfig();

		$this->debugLogFlag = ($this->debug_mode_log_project || $this->debug_mode_log_system ? true : false);
	}
*/
	/**
	 * loadSystemConfig - System configuration settings here.
	 */
/*	public function loadSystemConfig() 
	{
		$this->debug_mode_log_system = $this->getSystemSetting('debug_mode_log_system');
		
		// put some of your other config settings here

	}
*/
	/**
	 * loadProjectConfig - Project configuration settings here.
	 */
/*	public function loadProjectConfig($projectId = 0) 
	{
		if ($projectId > 0) {
			$this->debug_mode_log_project = $this->getProjectSetting('debug_mode_log_project');

			// put some of your other config settings here
		}
	}
*/
	/**
	 * loadProjectConfigDefaults - set up our defaults.
	 */
/*	public function loadProjectConfigDefaults()
	{
		$this->debug_mode_log_project   = false;
	}
*/
//-------------------------------------------------------------------------------------------------

	/**
	 * debugLog - (debug version) Simplified Logger messaging.
	 */
	public function debugLog($msg = '', $logDisplayMsg = 'MetaMorphosis')
	{
		// $sql, $table, $event, $record, $display, $descrip="", $change_reason="",
		//									$userid_override="", $project_id_override="", $useNOW=true, $event_id_override=null, $instance=null
		$logSql         = '';
		$logTable       = '';
		$logEvent       = 'OTHER';  // what events can we have? ENUM('UPDATE', 'INSERT', 'DELETE', 'SELECT', 'ERROR', 'LOGIN', 'LOGOUT', 'OTHER', 'DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE', 'MANAGE', 'LOCK_RECORD', 'ESIGNATURE')
		$logRecord      = '';
		$logDisplay     = $logDisplayMsg; // data_values  text
		$logDescription = $msg;
		Logging::logEvent($logSql, $logTable, $logEvent, $logRecord, $logDisplay, $logDescription);
	}
	
	/**
	 * debugLogMsg - .
	 */
	public function debugLogMsg($debugmsg)
	{
			if ($this->debugLogFlag) {
				$this->debugLog($debugmsg);
			}
	}

	/**
	 * viewHtml - the front end part, display the external module page. This method has an added feature for use with the control center, includes all the REDCap navigation.
	 */
	public function viewHtml($body = 'NA', $flag = '')
	{
		$HtmlPage = new HtmlPage(); 

		if (!SUPER_USER) { 
			//redirect(APP_PATH_WEBROOT); 
			exit("Only super users can access this page!");
		}
		
		switch ($flag) {
			case 'project':
				$HtmlPage->ProjectHeader();
				echo $body;
				$HtmlPage->ProjectFooter();
				break;

			case 'control':
				global $lang;  // this is needed for these two to work properly
				include APP_PATH_DOCROOT . 'ControlCenter/header.php';
				echo $body;
				include APP_PATH_DOCROOT . 'ControlCenter/footer.php';
				break;

			default:  // system
				$HtmlPage->setPageTitle($this->projectName);
				$HtmlPage->PrintHeaderExt();
				echo $body;
				$HtmlPage->PrintFooterExt();
				break;
		}
	}

//-------------------------------------------------------------------------------------------------

    /**
     * Formats settings into a hierarchical key-value pair array.
     *
     * @param int $project_id
     *   Enter a project ID to get project settings.
     *   Leave blank to get system settings.
     *
     * @return array
     *   The formatted settings.
     */
    function getFormattedSettings($prefix = null, $project_id = null) 
	{
        $settings = $this->getConfig();

        if ($project_id) {
            $settings = $settings['project-settings'];
            $values = ExternalModules::getProjectSettingsAsArray($prefix, $project_id);
        }
        else {
            $settings = $settings['system-settings'];
            $values = ExternalModules::getSystemSettingsAsArray($prefix);
        }

        return $this->_getFormattedSettings($settings, $values);
    }
	
    /**
     * Auxiliary function for getFormattedSettings().
     */
    protected function _getFormattedSettings($settings, $values, $inherited_deltas = array()) 
	{
        $formatted = array();

        foreach ($settings as $setting) {
            $key = $setting['key'];
            $value = $values[$key]['value'];

            foreach ($inherited_deltas as $delta) {
                $value = $value[$delta];
            }

            if ($setting['type'] == 'sub_settings') {
                $deltas = array_keys($value);
                $value = array();

                foreach ($deltas as $delta) {
                    $sub_deltas = array_merge($inherited_deltas, array($delta));
                    $value[$delta] = $this->_getFormattedSettings($setting['sub_settings'], $values, $sub_deltas);
                }

                if (empty($setting['repeatable'])) {
                    $value = $value[0];
                }
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }
	
    public function getFileInfo($edoc = null) 
	{
		$file_data = ["filename" => "", "filedata" => ""];
		if (($edoc) && (is_numeric($edoc))) {
			$edoc_data = Files::getEdocContentsAttributes((integer) $edoc);
			$file_data["filename"] = $edoc_data[1];
			$file_data["filedata"] = $edoc_data[2];
		}
		return $file_data;
	}
	
// *****************************************************************************
// * Merge Input File Ids with Demographic Data.
// *****************************************************************************
	public function createDemographicFile($settings,$formdata,$doctemp,$docdemo)
	{
		$output = array (
			'status' 	=> "error",
			'message' 	=> "Unkown error! Please call System Support.",
			'errorids'	=> ""
		);

		//get list of error ids
		$errorids = array();
		if ($formdata['errorids'] != "") {
			$errorids = explode(";",$formdata['errorids']);
			foreach ($errorids as $key => $value){
				$errorids[$key] = explode(",", $value);
			}
		}
		
		//get template file
		$template	= $settings['cdct-template'];
		$templ_file	= $this->getFileInfo((integer) $template);
		$templ_name	= $templ_file["filename"];
		$templ_data	= $templ_file["filedata"];
		$templ_col	= explode(",",$templ_data);
		$templ_size	= count(file($templ_name));

		if ($templ_size < 1) {
			$output['status'] 	= "error";
			$output['message'] 	= "Selected file is empty!".$templ_size;
			return $output;
		}
		
		//connect to Demographic DB
		$response = $this->connectDemographicDB($settings);
		if ($response) { 
			$output['status'] 	= "error";
			$output['message'] 	= "Unable to connect to Demographic DB! Error ".$response;
			return $output;
		}
		
		//read Document
		/*might need to change this to write to temp file. here is example
		$buffer = fopen('php://temp', 'r+');
		fputcsv($buffer, $fields, $delimiter, $enclosure, $escape_char);
		rewind($buffer);
		$csv = fgets($buffer);
		fclose($buffer);
		return $csv;
		*/

		//make sure input file Header is valid
		$fin  = fopen($doctemp,"r");
		$line = fgets($fin);
		$patient_id = trim(preg_replace('/"|\r|\n|\t|\n\r|,/', '', $line));
		if ($patient_id != "Patient_ID") { //missing header
			$output['status'] 	= "error";
			$output['message'] 	= "Missing 'Patient_ID' header in selected Document! ";
			return $output;
		}

		//create Header
		$header = $this->getDemographicHeader();
		if (is_array($header) && !empty($header)) {
			$fout = fopen($docdemo,"w");
			fputcsv($fout,$header);
		} else {
			$this->disconnectDemographicDB();		
			$output['status'] 	= "error";
			$output['message'] 	= "Unable to create Header in demographic Document! ".$header;
			return $output;
		}
		
		//create Records
		$output['status'] = "success";
		$output['message'] = "";
		$errors = array();
		
		while ($line = fgets($fin)) {
			$patient_id = trim(preg_replace('/"|\r|\n|\t|\n\r|,/', '', $line));
			
			$row = $this->getDemographicRecord($patient_id, $header);
			if (is_array($row)) {
				if (!empty($row)) {
					//if record found in errorids list then clear the field
					foreach($errorids as $errorid) {
						if ( $errorid[0] == $patient_id ) {
							$row[$errorid[1]] = "";
						}
					}
					fputcsv($fout,$row);
				} else {	
					//create an array of patient_ids not found
					$errors[] = ['patient_id' => $patient_id, 'message' => "Invalid Patient_Id or not found in Demographic DB."];
				}
			} else {
				$errors[] = ['patient_id' => $patient_id, 'message' => "Error: ".$row];
			}
		}
        fclose($fout);
		fclose($fin);

		if ($output['status'] != "error") { 
			$docdemo_size = count(file($docdemo));
			if (!empty($errors)) {
				$alt = false;
				$usermsg = '<br>
					<div class="red">
						<img src="'.APP_PATH_IMAGES.'exclamation.png">
						<b>Errors were detected in the import file that prevented it from being loaded.</b><br>
						<br>Correct any errors and upload the file again or click <b>Ignore&Continue</b> to ignore errors and import data.
					</div><br>
					<table id="errortable"><tbody>
								<tr><th scope="row" class="comp_fieldname" bgcolor="black" colspan="4"><font color="white">ERROR DISPLAY TABLE</font></th></tr>
								<tr><th scope="col">Patient_ID</th><th scope="col">Error Message</th></tr>';
				foreach ($errors as $error) {
					$usermsg .= '<tr '.($alt ? 'class="alt"' : '').'><td class="comp_new_error">'.$error['patient_id'].'</td><td class="comp_new">'.$error['message'].'</td></tr>';
					$alt = $alt ? false : true;
					$output['errorids'] .= $error['patient_id'].",Patient_ID;"; //"Patient_ID,fieldname;"
				}
				$usermsg .= '</tbody></table>';
				$output['status'] 	= "invalidids";
				$output['message']	= $usermsg;
			} else {
				if ($docdemo_size <= 1) {
					$output['status'] 	= "error";
					$output['message'] .= "Demographic file is empty!";
				} else {
					$output['status'] 	= "converted";
					$output['message']	= "";
				}
			}
		}
		//disconnect to Demographic DB
		$this->disconnectDemographicDB();
		
		return $output;
	}
	
	private function connectDemographicDB($settings)
	{
		$username 		= $settings["cdct-username"]; 
		$password 		= $settings["cdct-password"]; 
		$hostname 		= $settings["cdct-host"]; 
		$port	 		= $settings["cdct-port"]; 
		$sid			= $settings["cdct-sid"]; 
		$database 		= $settings["cdct-sid"]; 
		$this->dbdriver	= $settings["cdct-driver"];

		if ($this->dbdriver == "sqlsrv") 
		{
			//sqlsrv_configure("WarningsReturnAsErrors", 0);
			$servername = $hostname.",".$port;
			$connection = array( "Database"=>$database, "UID"=>$username, "PWD"=>$password); //, "CharacterSet"=>"UTF-8");

		try {
			$this->dbconn = sqlsrv_connect( $servername, $connection);
			if($this->dbconn == false) {
				$dberror = sqlsrv_errors();
				return htmlentities($dberror[0][message], ENT_QUOTES);
			}
		} catch (Error $e) {
			return $e->getMessage(); 
		}
	
		} 
		else //"oci"
		{ 
			$connection = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = ".$hostname.")(PORT = ".$port.")) (CONNECT_DATA = (SID = ".$sid.")))";
			$charset	= "AL32UTF8";

			// Connect to the Oracle database
			$this->dbconn = oci_connect($username, $password, $connection);

			// check for any errors
			if (!$this->dbconn) { 
				$dberror = oci_error();
				return htmlentities($dberror['message'], ENT_QUOTES);
			} 
		}
	}
	
	private function getDemographicHeader()
	{
		//get headers from demographic db
		$header_db = array();
		$sql = "SELECT * FROM REDCAP_DEMOGRAPHICS";

		if ($this->dbdriver == "sqlsrv") 
		{
			$this->dbstid = sqlsrv_prepare( $this->dbconn, $sql );
			if ($this->dbstid === false){
				$dberror = sqlsrv_errors();
				return 'Query Error '.htmlentities($dberror[0][message], ENT_QUOTES);
			}
			foreach( sqlsrv_field_metadata( $this->dbstid ) as $fieldMetadata ) {
			   $header_db[] = $fieldMetadata['Name'];
			}
			sqlsrv_free_stmt($this->dbstid);
		} 
		else //"oci"
		{
			$this->dbstid = oci_parse($this->dbconn, $sql);
			if ($this->dbstid === false) {
				$dberror = oci_error($this->dbconn); 
				return 'Parse Error '.htmlentities($dberror['message'], ENT_QUOTES);
			}
			$response = oci_execute($this->dbstid, OCI_DESCRIBE_ONLY);
			if ($response === false) {
				$dberror = oci_error($this->dbstid);
				return 'Execute Error '.htmlentities($dberror['message'], ENT_QUOTES);
			}
			$ncols = oci_num_fields($this->dbstid);
			for ($i = 1; $i <= $ncols; $i++) {
				$header_db[]  = oci_field_name($this->dbstid, $i);
			}
			oci_free_statement($this->dbstid);
		}			
		//get headers from project
		$sql = "SELECT * FROM redcap_metadata WHERE project_id = ".$this->projectId." AND (element_type != 'calc' AND element_type != 'file') ORDER BY field_order";
		$result = db_query($sql);

		$header_proj = array();
		while ($row = db_fetch_assoc($result)) {
			$header_proj[] = $row['field_name'];
		}

		//return headers that are in both places
		$header = array_intersect($header_db, $header_proj);
		return (empty($header) ? "" : $header);
	}
	
	private function getDemographicRecord($patient_id, $header)
	{
		$patient_id = trim($patient_id,"-"); //patient ids with "-" are old patient's that might not exist in db

		// Read demographic data by record_id
		
		if ($this->dbdriver == "sqlsrv") 
		{
			$sql = 'SELECT * FROM REDCAP_DEMOGRAPHICS WHERE "record_id" = '.$patient_id;
			$this->dbstid = sqlsrv_query($this->dbconn, $sql);
			if ($this->dbstid === false){
				$dberror = sqlsrv_errors();
				return 'Query Error '.htmlentities($dberror[0][message], ENT_QUOTES);
			}
			
			$record = array();
			// Fetch the results of the query
			while ($row = sqlsrv_fetch_array($this->dbstid, SQLSRV_FETCH_ASSOC)) {
				foreach ($row as $key => $value) {
					if (in_array($key,$header)) {
						$record[$key] = is_null($value) ? "" : $value;
					}
				}
			}
			sqlsrv_free_stmt($this->dbstid);
		} 
		else //"oci"
		{
			$sql = 'SELECT * FROM REDCAP_DEMOGRAPHICS WHERE "record_id" = :id';
			$this->dbstid = oci_parse($this->dbconn, $sql);
			if ($this->dbstid === false) {
				$dberror = oci_error($this->dbconn); 
				return 'Parse Error '.$dberror['message']; 
			}
			oci_bind_by_name($this->dbstid, ':id', $patient_id, -1, SQLT_INT);
			
			$response = oci_execute($this->dbstid, OCI_DEFAULT);
			if ($response === false) {
				$dberror = oci_error($this->dbstid);
				return 'Execute Error '.htmlentities($dberror['message'], ENT_QUOTES);
			}

			$record = array();
			// Fetch the results of the query
			while (($row = oci_fetch_array($this->dbstid, OCI_ASSOC+OCI_RETURN_NULLS))) {
				foreach ($row as $key => $value) {
					if (in_array($key,$header)) {
						$record[$key] = is_null($value) ? "" : $value;
					}
				}
			}
			oci_free_statement($this->dbstid);
		}
		return $record;
	}
	
	private function disconnectDemographicDB()
	{
		// Close DB connection
		if ($this->dbdriver == "sqlsrv") 
		{
			sqlsrv_close($this->dbconn);
		} 
		else //"oci"
		{ 
			oci_close($this->dbconn);
		}
	}
	

// *****************************************************************************
// * Demographic Data Import
// *****************************************************************************

//------------------------------------------------------------------------------
// Import Demographic data without updating records
//------------------------------------------------------------------------------	
	public function importDemographicFile($importDataFile)
	{
		extract($GLOBALS);
		
		// Increase memory limit in case needed for intensive processing
		System::increaseMemory(2048);

		$output = array (
			'status' 	=> "error",
			'message' 	=> "Unkown error! Please call System Support.",
			'errorids'	=> ""
		);
		
		$uploadedfile_name = $importDataFile; 
			
		// If filename is blank, error out
		if ($uploadedfile_name == "") {
			$output['status'] 	= "error";
			$output['message'] 	= "Unable to import selected file! Try later.";
			return $output;
		}

		// Process uploaded csv file
		// Set parameters for saveData()
		$forceAutoNumber = false;
		$overwriteBehavior = 'normal'; //or 'overwrite'
		$saveDataFormat = 'array';
		$dateFormat = 'MDY'; //or 'DMY'
		$format = 'rows'; //or 'cols'
		$event_string = ""; //???
		$require_change_reason = ($Proj->project['require_change_reason'] == null) ? $GLOBALS['require_change_reason'] : $Proj->project['require_change_reason']; //???
		$userRights = $user_rights['group_id']; 

		$importData = $this->csvToArray($uploadedfile_name, $format);
		
		// Do test import to check for any errors/warnings
		$result = Records::saveData($saveDataFormat, $importData, $overwriteBehavior, $dateFormat, 'flat',
									$userRights, true, true, false, false, true, array(), true, true, false, $forceAutoNumber);
									
		// Check if error occurred
		$output["loaded"] = $result['item_count'];
		$warningcount = count($result['warnings']);
		$errorcount = count($result['errors']);
		$warnings = $errors = array();
		if ($errorcount > 0) {
			foreach ($result['errors'] as $key => $value) {
				$errors[] = str_getcsv($value);
			}
		} elseif ($warningcount > 0) {
			foreach ($result['warnings'] as $key => $value) {
				$warnings[] = str_getcsv($value);
			}
		}

		// If there is errors but error array is empty, display error message
		if ($errorcount > 0 && empty($errors)) { 
			$usermsg = "<br>
				<div class='red'>
					<img src='".APP_PATH_IMAGES."exclamation.png'>
					<b>{$lang['data_import_tool_51']}<!--Errors were detected in the import file that prevented it from being loaded.--></b>
					<br><br>".$result['errors']."<br>
					<br>{$lang['data_import_tool_62']}<!--Correct any errors and upload the file again.-->
				</div><br>";
			$output["status"] = "invaliddata";
		} else 
		// If there are any errors or warnings, display the table and message
		if (($errorcount + $warningcount) > 0)
		{
			$usermsg = "<br>
						<div class='".($errorcount > 0 ? 'red' : 'yellow')."'>
							<img src='".APP_PATH_IMAGES."".($errorcount > 0 ? 'exclamation.png' : 'exclamation_orange.png')."'>
							<b>".($errorcount > 0 ? $lang['data_import_tool_51'] : $lang['data_import_tool_237'])."</b>";
							//($errorcount > 0 ? "Errors were detected in the import file that prevented it from being loaded." : "Warnings were detected in the file that was uploaded.")

			if ($errorcount + $warningcount > 1){
				$usermsg .= "<br><br>{$lang['data_import_tool_52']} "; //"There are ";
			} else {
				$usermsg .= "<br><br>{$lang['data_import_tool_53']} "; //"There is ";
			}

			if ($errorcount > 1){
				$usermsg .= $errorcount . " {$lang['data_import_tool_54']} {$lang['data_import_tool_56']} "; //" errors (shown in red in the error table below) ";
			}else if ($errorcount == 1){
				$usermsg .= $errorcount . " {$lang['data_import_tool_41']} {$lang['data_import_tool_56']} "; //" error (shown in red in the error table below) ";
			}

			if (($errorcount > 0)&&($warningcount > 0)){
				$usermsg .= " {$lang['global_43']} "; //" and ";
			}

			if ($warningcount > 1){
				$usermsg .= $warningcount . " {$lang['data_import_tool_58']} {$lang['data_import_tool_60']} "; //"  warnings (shown in orange) ";
			}else if ($warningcount == 1){
				$usermsg .= $warningcount . " {$lang['data_import_tool_43']} {$lang['data_import_tool_60']} "; //" warning (shown in orange) ";
			}
			
			$usermsg .= " {$lang['data_import_tool_61']} "; //" in the Demographic DB. Please "; 

			if ($errorcount > 0){
				$usermsg .= " {$lang['data_import_tool_62']}"; //" correct any errors and upload the file again.";
			} else {
				$usermsg .= " {$lang['data_import_tool_63']}"; //" review the warnings below before importing the data.";
			}

			$usermsg .= "</div><br>";

			// Create the error/warning table to display (if any errors/warnings exist)
			$usermsg .= $this->displayErrorTable($errors, $warnings);
			
			foreach($errors as $error) {
				$output['errorids'] .= $error[0].",".$error[1].";"; //"record_id,fieldname;"
			}
			$output["status"] = "invaliddata";

		} else {
			
			//Display confirmation that file was uploaded successfully
			$usermsg = "<br>
					<div class='green' style='padding:10px 10px 13px;'>
						<img src='".APP_PATH_IMAGES."accept.png'>
						<b>{$lang['data_import_tool_24']}<!--Your document was uploaded successfully and is ready for review.--></b><br>
						{$lang['data_import_tool_24b']}<!--You are now required to view the Data Display Table below to approve all the data before it is officially imported into the project. Follow the instructions below.--><br>
					</div>";
					
			$output["status"] = "uploaded";
		}

		//### Instructions and Key for Data Display Table
		if ($errorcount == 0)
		{
			$usermsg .= "<div class='blue' style='font-size:12px;margin:25px 0;'>
						<b style='font-size:15px;'>{$lang['data_import_tool_102']}<!--Instructions for Data Review--></b><br><br>
						{$lang['data_import_tool_25']}<!--The data you uploaded from the file is displayed in the Data Display Table below. Please inspect it carefully
						to ensure that it is all correct. After reviewing it, <b>click the 'Import Data' button at the bottom of this page</b>
						to import this data into the project.--><br><br>
						<table style='background-color:#FFF;color:#000;font-size:11px;border:1px;'>
							<tr><th scope='row' class='comp_fieldname' style='background-color:#000;color:#FFF;font-size:11px;'>
								{$lang['data_import_tool_33']}<!--KEY for Data Display Table below-->
							</th></tr>
							<tr><td class='comp_update' style='background-color:#FFF;font-size:11px;'>
								{$lang['data_import_tool_35']} = {$lang['data_import_tool_36']}<!--Black text = New Data-->
							</td></tr>
							<tr><td class='comp_old' style='background-color:#FFF;font-size:11px;'>
								{$lang['data_import_tool_37']} = {$lang['data_import_tool_38']}<!--Gray text = Existing data (will not change)-->
							</td></tr>
							<tr><td class='comp_old' style='font-size:11px;'>
								<span class='comp_oldval'>{$lang['data_import_tool_27']} = {$lang['data_import_tool_39']}<!--(Red text) = Data that will be overwritten--></span>
							</td></tr>
							<tr><td class='comp_new_error' style='font-size:11px;'>
								{$lang['data_import_tool_40']} = {$lang['data_import_tool_41']}<!--Red box = error-->
							</td></tr>
							<tr><td class='comp_new_warning' style='font-size:11px;'>
								{$lang['data_import_tool_42']} = {$lang['data_import_tool_43']}<!--Orange box = warning-->
							</td></tr>
						</table>
					</div>";
			// Render Data Disply table
			$usermsg .= $this->displayComparisonTable($result['values'], $format);

			// If ALL fields are old, then there's no need to update anything
			$field_counter = 0;
			$old_counter = 0;
			foreach ($result['values'] as $studyid => $studyrecord) {
				foreach ($studyrecord as $fieldname => $datapoint){
					if (isset($Proj->metadata[$fieldname]) && ($Proj->metadata[$fieldname]['element_type'] == 'calc' || $Proj->metadata[$fieldname]['element_type'] == 'file')) {
						continue;
					}
					if ($datapoint['status'] == 'keep') {
						$old_counter++;
					}
					$field_counter++;
				}
			}
			if ($field_counter == $old_counter) 
			{
				$usermsg .= "<br><br>";

				//Message saying that there are no new records (i.e. all the uploaded records already exist in project)
				$usermsg .= "<div id='commit_import_div' class='red' style='padding:20px;'>
								<img src='" . APP_PATH_IMAGES . "exclamation.png'>
								<b>{$lang['data_import_tool_68']}<!--NOTHING TO IMPORT:--></b><br>
								{$lang['data_import_tool_69']}<!--All the data from the uploaded file already exists in the project.-->
							</div>";
				$output["status"] = "loadeddata";

			}
		}
		$usermsg .= "<br><br>";
		
		$output["message"] = $usermsg;
		return $output;
	}
	
//-----------------------------------------------------------------------------------------------------------	
// Import Demographic data and update records
//-----------------------------------------------------------------------------------------------------------	
	public function importDemographicData($importDataFile)
	{
		extract($GLOBALS);
		
		// Increase memory limit in case needed for intensive processing
		System::increaseMemory(2048);

		$output = array (
			'status' 	=> "error",
			'message' 	=> "Unkown error! Please call System Support."
		);
		// Process uploaded csv file
		$uploadedfile_name = $importDataFile; 
			
		// If filename is blank, error out
		if ($uploadedfile_name == "") {
			$output['status'] 	= "error";
			$output['message'] 	= "Unable to import selected file! Try later.";
			return $output;
		}

		// Set parameters for saveData()
		$forceAutoNumber = false;
		$overwriteBehavior = 'normal'; //or 'overwrite'
		$saveDataFormat = 'array';
		$dateFormat = 'MDY'; //or 'DMY'
		$format = 'rows'; //or 'cols'
		$userRights = $user_rights['group_id']; 
		
		$importData = $this->csvToArray($uploadedfile_name, $format);
		
		// Do test import to check for any errors/warnings
		$result = Records::saveData($saveDataFormat, $importData, $overwriteBehavior, $dateFormat, 'flat',
									$userRights, true, true, true, false, true, array(), false, true, false, $forceAutoNumber); 
		
		// Count records added/updated
		$numRecordsImported =  count($result['ids']); //$result['item_count'];
		
		if ($numRecordsImported < 1) {//if not greater than 0 then something went wrong
			$output['status'] 	= "error";
			$output['message'] 	= "Error, imported 0 records.";
			return $output; 
		}

		// Give user message of successful import
		$usermsg = "<br><br>
				<div class='green' style='padding-top:10px;'>
					<img src='".APP_PATH_IMAGES."accept.png'> <b>{$lang['data_import_tool_133']}<!--Import Successful!--></b>
					<span style='font-size:16px;color:#800000;margin-left:8px;margin-right:1px;font-weight:bold;'>".User::number_format_user($numRecordsImported)."</span>
					<span style='color:#800000;'>".($numRecordsImported == '1' ? $lang['data_import_tool_183'] : $lang['data_import_tool_184']). //"record was created or modified during the import." : "records were created or modified during the import.").
					"</span>
					<br><br>".str_replace("above","below",$lang['data_import_tool_70'])."
					<!--The data you uploaded from the file was successfully imported into the project. If you wish to import more data, you may use the box below to select another file on your computer.-->";
		if ($forceAutoNumber && !empty($result['ids'])) {
			$usermsg .= "<br><br>{$lang['data_import_tool_270']}
						<!--NOTE: Since you elected to force record auto-numbering, listed below are the record names you submitted
						and also what they were renamed to during the import process. Since the uploaded record names are not stored anywhere in REDCap, 
						it is not possible to download the table below, but instead <b>you may copy and paste the table below</b> to Excel or other program to keep for reference.--><br>";
			$usermsg .= "<table id='comptable' style='background-color:#fff;margin-top:10px;'>";
			$usermsg .= "<tr><td class='comp_new'><b>{$lang['data_import_tool_271']}<!--Uploaded record name--></b></th><td class='comp_new'><b>{$lang['data_import_tool_272']}<!--Saved record name--></b></th></tr>";
			foreach ($result['ids'] as $origId=>$savedId) {
				$usermsg .= "<tr><td class='comp_new'>$origId</td><td class='comp_new'>$savedId</td></tr>";
			}
			$usermsg .= "</table>";
		}
		$usermsg .= "</div><br><br>";
		
		$output["status"]	= "success";
		$output["message"]	= $usermsg;
		$output["loaded"]	= $numRecordsImported;

		return $output;
	}
	
//---------------------------------------------------------------------------------------------------------------------
// Process uploaded Excel file, return references to (1) an array of fieldnames and (2) an array of items to be updated
//---------------------------------------------------------------------------------------------------------------------
	public static function csvToArray($csv_filepath, $format='rows')
	{
		global $lang, $table_pk, $longitudinal, $Proj, $user_rights, $project_encoding;

		// Extract data from CSV file and rearrange it in a temp array
		$newdata_temp = array();
		$found_pk = false;
		$i = 0;
		// Set commas as default delimiter (if can't find comma, it will revert to tab delimited)
		$delimiter 	  = ",";
		$removeQuotes = false;
		$resetKeys = false; // Set flag to reset array keys if any headers are blank

		// CHECKBOXES: Create new arrays with all checkbox fields and the translated checkbox field names
		$fullCheckboxFields = array();
		foreach (MetaData::getCheckboxFields(PROJECT_ID) as $field=>$value) {
			foreach ($value as $code=>$label) {
				$code = (Project::getExtendedCheckboxCodeFormatted($code));
				$fullCheckboxFields[$field . "___" . $code] = array('field'=>$field, 'code'=>$code);
			}
		}
		if (($handle = fopen($csv_filepath, "rb")) !== false)
		{
			// Loop through each row
			while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
			{
				// Detect if all values are blank in row (so we can ignore it)
				$numRowValuesBlank = 0;

				if ($i == 0)
				{
					## CHECK DELIMITER
					// Determine if comma- or tab-delimited (if can't find comma, it will revert to tab delimited)
					$firstLine = implode(",", $row);
					// If we find X number of tab characters, then we can safely assume the file is tab delimited
					$numTabs = 0;
					if (substr_count($firstLine, "\t") > $numTabs)
					{
						// Set new delimiter
						$delimiter = "\t";
						// Fix the $row array with new delimiter
						$row = explode($delimiter, $firstLine);
						// Check if quotes need to be replaced (added via CSV convention) by checking for quotes in the first line
						// If quotes exist in the first line, then remove surrounding quotes and convert double double quotes with just a double quote
						$removeQuotes = (substr_count($firstLine, '"') > 0);
					}
				}

				// Find record identifier field
				if (!$found_pk)
				{
					if ($i == 0 && preg_replace("/[^a-z_0-9]/", "", $row[0]) == $table_pk) {
						$found_pk = true;
					} elseif ($i == 1 && preg_replace("/[^a-z_0-9]/", "", $row[0]) == $table_pk && $format == 'cols') {
						$found_pk = true;
						$newdata_temp = array(); // Wipe out the headers that already got added to array
						$i = 0; // Reset
					}
				}
				// Loop through each column in this row
				for ($j = 0; $j < count($row); $j++)
				{
					// If tab delimited, compensate sightly
					if ($delimiter == "\t")
					{
						// Replace characters
						$row[$j] = str_replace("\0", "", $row[$j]);
						// If first column, remove new line character from beginning
						if ($j == 0) {
							$row[$j] = str_replace("\n", "", ($row[$j]));
						}
						// If the string is UTF-8, force convert it to UTF-8 anyway, which will fix some of the characters
						if (function_exists('mb_detect_encoding') && mb_detect_encoding($row[$j]) == "UTF-8")
						{
							$row[$j] = utf8_encode($row[$j]);
						}
						// Check if any double quotes need to be removed due to CSV convention
						if ($removeQuotes)
						{
							// Remove surrounding quotes, if exist
							if (substr($row[$j], 0, 1) == '"' && substr($row[$j], -1) == '"') {
								$row[$j] = substr($row[$j], 1, -1);
							}
							// Remove any double double quotes
							$row[$j] = str_replace("\"\"", "\"", $row[$j]);
						}
					}
					// Reads as records in rows (default)
					if ($format == 'rows')
					{
						// Santize the variable name
						if ($i == 0) {
							$row[$j] = preg_replace("/[^a-zA-Z_0-9]/", "", $row[$j]);
							if ($row[$j] == '') {
								$resetKeys = true;
								continue;
							}
						} elseif (!isset($newdata_temp[0][$j]) || $newdata_temp[0][$j] == '') {
							continue;
						}
						// If value is blank, then increment counter
						if ($row[$j] == '') $numRowValuesBlank++;
						// Add to array
						$newdata_temp[$i][$j] = $row[$j];
						if ($project_encoding == 'japanese_sjis')
						{ // Use only for Japanese SJIS encoding
							$newdata_temp[$i][$j] = mb_convert_encoding($newdata_temp[$i][$j], 'UTF-8',  'sjis');
						}
					}
					// Reads as records in columns
					else
					{
						// Santize the variable name
						if ($j == 0) {
							$row[$j] = preg_replace("/[^a-zA-Z_0-9]/", "", $row[$j]);
							if ($row[$j] == '') {
								$resetKeys = true;
								continue;
							}
						} elseif ($newdata_temp[0][$i] == '') {
							continue;
						}
						$newdata_temp[$j][$i] = $row[$j];
						if ($project_encoding == 'japanese_sjis')
						{ // Use only for Japanese SJIS encoding
							$newdata_temp[$j][$i] = mb_convert_encoding($newdata_temp[$j][$i], 'UTF-8',  'sjis');
						}
					}
				}
				// If whole row is blank, then skip it
				if ($numRowValuesBlank == count($row)) {
					$resetKeys = true;
					unset($newdata_temp[$i]);
				}
				// Increment col counter
				$i++;
			}
			unset($row);
			fclose($handle);
		} else {
			// ERROR: File is missing
			$fileMissingText = (!SUPER_USER) ? $lang['period'] : " (".APP_PATH_TEMP."){$lang['period']}<br><br>{$lang['file_download_13']}";
			return 	RCView::div(array('class'=>'red'),
						RCView::b($lang['global_01'].$lang['colon'])." {$lang['file_download_08']} <b>\"".htmlspecialchars(basename($csv_filepath), ENT_QUOTES)."\"</b>
						{$lang['file_download_12']}{$fileMissingText}"
					);
		}
		
		// If importing records as columns, remove any columns that are completely empty
		if ($format == 'cols') {
			$recCount = count($newdata_temp);
			for ($i=1; $i<$recCount; $i++) {
				// Set default for each record
				$recordEmpty = true;
				if (!isset($newdata_temp[$i])) continue;
				foreach ($newdata_temp[$i] as $val) {
					// If found a value, then skip to next record
					if ($val != '') {
						$recordEmpty = false;
						break;
					}
				}
				// Remove record
				if ($recordEmpty) {
					unset($newdata_temp[$i]);
				}
			}
			// If record count is now different, then re-index the array
			if ($recCount > count($newdata_temp)) {
				$newdata_temp = array_values($newdata_temp);
			}
		}

		// Give error message if record identifier variable name could not be found in expected places
		if (!$found_pk)
		{
			if ($format == 'rows') {
				$found_pk_msg = "{$lang['data_import_tool_134']} (\"$table_pk\") {$lang['data_import_tool_135']}";
			} else {
				$found_pk_msg = "{$lang['data_import_tool_134']} (\"$table_pk\") {$lang['data_import_tool_136']}";
			}
			return  "<div class='red' style='margin-bottom:15px;'>
						<b>{$lang['global_01']}:</b><br>
						$found_pk_msg<br><br>
						{$lang['data_import_tool_76']}
					</div>";
		}

		// Shift the fieldnames  into a separate array called $fieldnames_new
		$fieldnames_new = array_shift($newdata_temp);

		//	Ensure that all record names are in proper UTF-8 format, if UTF-8 (no black diamond characters)
		if (function_exists('mb_detect_encoding')) {
			foreach ($newdata_temp as $key=>$row) {
				$this_record = $row[0];
				if (mb_detect_encoding($this_record) == 'UTF-8' && $this_record."" !== mb_convert_encoding($this_record, 'UTF-8', 'UTF-8')."") {
					// Convert to true UTF-8 to remove black diamond characters
					$newdata_temp[$key][0] = utf8_encode($this_record);
				}
			}
			unset($row);
		}

		// If any columns were removed, reindex the arrays so that none are missing
		if ($resetKeys) {
			// Reindex the header array
			$fieldnames_new = array_values($fieldnames_new);
			// Loop through ALL records and reindex each
			foreach ($newdata_temp as $key=>&$vals) {
				$vals = array_values($vals);
			}
		}

		// If longitudinal, get array key of redcap_event_name field
		if ($longitudinal) {
			$eventNameKey = array_search('redcap_event_name', $fieldnames_new);
		}

		// Check if DAGs exist
		$groups = $Proj->getGroups();

		// If has DAGs, try to find DAG field
		if (!empty($groups)) {
			$groupNameKey = array_search('redcap_data_access_group', $fieldnames_new);
		}
		
		// Determine if using repeating instances
		$hasRepeatingFormsEvents = $Proj->hasRepeatingFormsEvents();
		$repeat_instance_index = $repeat_instrument_index = $importHasRepeatingFormsEvents = false;
		if ($hasRepeatingFormsEvents) {
			$repeat_instrument_index = array_search('redcap_repeat_instrument', $fieldnames_new);
			$repeat_instance_index = array_search('redcap_repeat_instance', $fieldnames_new);
			$importHasRepeatingFormsEvents = ($repeat_instance_index !== false);
		}

		## PUT ALL UPLOADED DATA INTO $updateitems
		$updateitems = $invalid_eventids = array();
		foreach ($newdata_temp as $i => $element)
		{
			// Trim the record name, just in case
			$newdata_temp[$i][0] = $element[0] = trim($element[0]);
			// Get event_id to add as subkey for record
			$event_id = ($longitudinal) ? $Proj->getEventIdUsingUniqueEventName($element[$eventNameKey]) : $Proj->firstEventId;
			if ($longitudinal && $event_id === false) {
				// Invalid unique event name was used.
				$invalid_eventids[] = $element[$eventNameKey];
				continue;
			}
			// Loop through data array and add each record values to $updateitems
			for ($j = 0; $j < count($fieldnames_new); $j++) {
				// Get this field and value
				$this_field = trim($fieldnames_new[$j]);
				$this_value = trim($element[$j]);
				// Skip if field is blank
				if ($this_field == "") continue;
				elseif ($this_field == "redcap_repeat_instance" || $this_field == "redcap_repeat_instrument") {
					if ($hasRepeatingFormsEvents) continue;
					else {
						// Stop if uploading repeating fields when project is not set to repeat forms/events
						return  "<div class='red' style='margin-bottom:15px;'>
									<b>{$lang['global_01']}{$lang['colon']} {$lang['data_import_tool_252']}</b><br>
									{$lang['data_import_tool_253']}
								</div>";
					}
				}
				// Is this row a repeating instance?
				$rowIsRepeatingInstance = false;
				if ($importHasRepeatingFormsEvents) {					
					$repeat_instance = $element[$repeat_instance_index];
					$repeat_instrument = $repeat_instrument_index ? $element[$repeat_instrument_index] : "";
					$rowIsRepeatingInstance = ($repeat_instance.$repeat_instrument."" != "");
				}
				if ($rowIsRepeatingInstance) {
					// Repeating instance
					if (isset($fullCheckboxFields[$this_field])) {
						// Checkbox
						$updateitems[$element[0]]['repeat_instances'][$event_id][$repeat_instrument][$repeat_instance][$fullCheckboxFields[$this_field]['field']][$fullCheckboxFields[$this_field]['code']] = $this_value;
					} else {
						// Non-checkbox
						$updateitems[$element[0]]['repeat_instances'][$event_id][$repeat_instrument][$repeat_instance][$this_field] = $this_value;
					}
				} else {
					// Regular non-repeating instance
					if (isset($fullCheckboxFields[$this_field])) {
						// Checkbox
						$updateitems[$element[0]][$event_id][$fullCheckboxFields[$this_field]['field']][$fullCheckboxFields[$this_field]['code']] = $this_value;
					} else {
						// Non-checkbox
						$updateitems[$element[0]][$event_id][$this_field] = $this_value;
					}
				}
			}
		}
		
		// Invalid unique event name was used.
		if (!empty($invalid_eventids)) 
		{
			return  "<div class='red' style='margin-bottom:15px;'>
						<b>{$lang['global_01']}{$lang['colon']}</b> {$lang['data_import_tool_254']}
						\"<b>".implode("</b>\", \"<b>", $invalid_eventids)."</b>\"
					</div>";
		}

		// If project has DAGs and redcap_data_access_group column is included and user is IN a DAG, then tell them they must remove the column
		if ($user_rights['group_id'] != '' && !empty($groups) && in_array('redcap_data_access_group', $fieldnames_new))
		{
			return  "<div class='red' style='margin-bottom:15px;'>
						<b>{$lang['global_01']}{$lang['colon']} {$lang['data_import_tool_171']}</b><br>
						{$lang['data_import_tool_172']}
					</div>";
		}
		// DAG check to make sure that a single record doesn't have multiple values for 'redcap_data_access_group'
		elseif ($user_rights['group_id'] == '' && !empty($groups) && $groupNameKey !== false)
		{
			// Creat array to collect all DAG designations for each record (each should only have one DAG listed)
			$dagPerRecord = array();
			foreach ($newdata_temp as $thisrow) {
				// Get record name
				$record = $thisrow[0];
				// Get DAG name for this row/record
				$dag = $thisrow[$groupNameKey];
				// Add to array
				$dagPerRecord[$record][$dag] = true;
			}
			unset($thisrow);
			// Now loop through all records and remove all BUT those with duplicates
			foreach ($dagPerRecord as $record=>$dags) {
				if (count($dags) <= 1) {
					unset($dagPerRecord[$record]);
				}
			}
			// If there records with multiple DAG designations, then stop here and throw error.
			if (!empty($dagPerRecord))
			{
				return  "<div class='red' style='margin-bottom:15px;'>
							<b>{$lang['global_01']}{$lang['colon']} {$lang['data_import_tool_173']}</b><br>
							{$lang['data_import_tool_174']} <b>".implode("</b>, <b>", array_keys($dagPerRecord))."</b>{$lang['period']}
						</div>";
			}
		}

		return $updateitems;
	}

	// Display errors/warnings in table format. Return HTML string.
	public static function displayErrorTable($errors, $warnings)
	{
		global $lang;
		$altrow = 1;
		$errortable =  "<br><table id='errortable'><tr><th scope=\"row\" class=\"comp_fieldname\" bgcolor=\"black\" colspan=4>
						<font color=\"white\">ERROR DISPLAY TABLE</th></tr>
						<tr><th scope='col'>Record</th><th scope='col'>Field Name</th>
						<th scope='col'>Value</th><th scope='col'>Error Message</th></tr>";
		foreach ($errors as $item) {
			$altrow = $altrow ? 0 : 1;
			$errortable .= $altrow ? "<tr class='alt'>" : "<tr>";
			$errortable .= "<th>".RCView::escape($item[0])."</th>";
			$errortable .= "<td class='comp_new'>".RCView::escape($item[1])."</td>";
			$errortable .= "<td class='comp_new_error'>".RCView::escape($item[2])."</td>";
			$errortable .= "<td class='comp_new'>".RCView::escape($item[3])."</td>";
		}
		foreach ($warnings as $item) {
			$altrow = $altrow ? 0 : 1;
			$errortable .= $altrow ? "<tr class='alt'>" : "<tr>";
			$errortable .= "<th>".RCView::escape($item[0])."</th>";
			$errortable .= "<td class='comp_new'>".RCView::escape($item[1])."</td>";
			$errortable .= "<td class='comp_new_warning'>".RCView::escape($item[2])."</td>";
			$errortable .= "<td class='comp_new'>".RCView::escape($item[3])."</td>";
		}
		$errortable .= "</table>";
		return $errortable;
	}

	// Display data comparison table
	public static function displayComparisonTable($updateitems, $format='rows')
	{
		global $lang, $table_pk, $user_rights, $longitudinal, $Proj;
		
		// Get record names being imported (longitudinal will not have true record name as array key
		$record_names = array();
		foreach (array_keys($updateitems) as $key) {
			list ($this_record, $nothing, $nothing, $nothing) = explode_right("-", $key, 4);
			$record_names[] = $this_record;
		}
		$record_names = array_values(array_unique($record_names));

		// Determine if imported values are a new or existing record by gathering all existing records into an array for reference
		$existing_records = array();
		foreach (Records::getData('array', $record_names, $table_pk, array(), $user_rights['group_id']) as $this_record=>$these_fields) {
			$existing_records[$this_record.""] = true;
		}

		$comparisontable = array();
		$rowcounter = 0;
		$columncounter = 0;

		//make "header" column (leftmost column) with fieldnames
		foreach ($updateitems as $studyevent) {
			foreach (array_keys($studyevent) as $fieldname) {
				if (isset($Proj->metadata[$fieldname]) && ($Proj->metadata[$fieldname]['element_type'] == 'calc' || $Proj->metadata[$fieldname]['element_type'] == 'file')) {
					continue;
				}
				$comparisontable[$rowcounter++][$columncounter] = "<th scope='row' class='comp_fieldname'>$fieldname</th>";
			}
			$columncounter++;
			break;
		}
		
		// If "Require Reason for Change" is enabled, then check which forms have data
		if ($GLOBALS['require_change_reason']) {
			$formStatusValues = Records::getFormStatus(PROJECT_ID, $record_names);
		}

		// Create array of all new records
		$newRecords = array();
		// Loop through all values
		foreach ($updateitems as $key=>$studyevent)
		{
			if (!isset($studyevent[$table_pk]['new'])) continue;
			list ($this_record, $this_event, $this_repeat_instrument, $this_instance) = explode_right("-", $key, 4);
			$this_event_id = $Proj->getEventIdUsingUniqueEventName($this_event);
			
			$rowcounter = 0;
			// Get record and evenet_id
			$studyid = $studyevent[$table_pk]['new'];
			$event_id = ($longitudinal) ? $Proj->getEventIdUsingUniqueEventName($studyevent['redcap_event_name']['new']) : $Proj->firstEventId;
			// Check if a new record or not
			$newrecord = !isset($existing_records[$studyid.""]);
			// Increment new record count
			if ($newrecord) $newRecords[] = $studyid;
			// Loop through fields/values
			foreach ($studyevent as $fieldname=>$studyrecord)
			{
				if (isset($Proj->metadata[$fieldname]) && ($Proj->metadata[$fieldname]['element_type'] == 'calc' || $Proj->metadata[$fieldname]['element_type'] == 'file')) {
					continue;
				}
				$this_form = $Proj->metadata[$fieldname]['form_name'];
				$this_form_instance = is_numeric($this_instance) ? $this_instance : 1;
				
				if ($rowcounter == 0){ //case of column header (cells contain the record id)
					// Check if a new record or not
					$existing_status_class = '';
					if (isset($_REQUEST['forceAutoNumber']) && $_REQUEST['forceAutoNumber'] == '1') {
						$existing_status = "<div class='new_impt_rec'>({$lang['data_import_tool_268']})</div>";
					} elseif (!$newrecord) {
						$existing_status_class = 'exist_impt_rec';
						$existing_status = "<div class='$existing_status_class'>({$lang['data_import_tool_144']})</div>";
					} else {
						$existing_status = "<div class='new_impt_rec'>({$lang['data_import_tool_145']})</div>";
					}
					// Render record number as table header
					$comparisontable[$rowcounter][$columncounter] = "<th scope='col' class='comp_recid'><span id='record-{$columncounter}' class='$existing_status_class'>$studyid</span>
																	 <span style='display:none;' id='event-{$columncounter}'>$event_id</span>$existing_status</th>";
				} else {
				//3 cases: new (+ errors or warnings), old, and update (+ errors or warnings)
					// Display redcap event name normally
					if (!(isset($updateitems[$key][$fieldname]))){
						$comparisontable[$rowcounter][$columncounter] = "<td class='comp_old'>&nbsp;</td>";
					} else {
						if ($updateitems[$key][$fieldname]['status'] == 'add'){
							if (isset($updateitems[$key][$fieldname]['validation'])){
								//if error
								if ($updateitems[$key][$fieldname]['validation'] == 'error'){
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new_error'>" . $updateitems[$key][$fieldname]['new'] . "</td>";
								}
								elseif ($updateitems[$key][$fieldname]['validation'] == 'warning'){ //if warning
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new_warning'>" . $updateitems[$key][$fieldname]['new'] . "</td>";
								}
								else {
									//shouldn't be a case of this
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new'>problem!</td>";
								}
							}
							else{
								// If requiring reason for change, check if form has any data
								$formHasDataClass = "";
								if ($GLOBALS['require_change_reason']) {
									$formHasData = (isset($formStatusValues[$this_record][$this_event_id][$this_form][$this_form_instance]) && is_numeric($formStatusValues[$this_record][$this_event_id][$this_form][$this_form_instance]));
									if ($formHasData) $formHasDataClass = "form_has_data";
								}
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new $formHasDataClass'>" . $updateitems[$key][$fieldname]['new'] . "</td>";
							}
						}
						elseif ($updateitems[$key][$fieldname]['status'] == 'keep'){
							if ($updateitems[$key][$fieldname]['old'] != ""){
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_old'>" . $updateitems[$key][$fieldname]['old'] . "</td>";
							} else {
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_old'>&nbsp;</td>";
							}
						}
						elseif ($updateitems[$key][$fieldname]['status'] == 'update' || $updateitems[$key][$fieldname]['status'] == 'delete'){
							if (isset($updateitems[$key][$fieldname]['validation'])){
								//if error
								if ($updateitems[$key][$fieldname]['validation'] == 'error'){
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_update_error'>" . $updateitems[$key][$fieldname]['new'] . "</td>";
								} elseif ($updateitems[$key][$fieldname]['validation'] == 'warning'){ //if warning
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_update_warning'>" . $updateitems[$key][$fieldname]['new'] . "</td>";
								} else {
									//shouldn't be a case of this
									$comparisontable[$rowcounter][$columncounter] = "<td class='comp_new'>problem!</td>";
								}
							} else {
								// If requiring reason for change, check if form has any data
								$formHasDataClass = "";
								if ($GLOBALS['require_change_reason']) {
									$formHasData = (isset($formStatusValues[$this_record][$this_event_id][$this_form][$this_form_instance]) && is_numeric($formStatusValues[$this_record][$this_event_id][$this_form][$this_form_instance]));
									if ($formHasData) $formHasDataClass = "form_has_data";
								}
								// Show new and old value
								$comparisontable[$rowcounter][$columncounter] = "<td class='comp_update $formHasDataClass'>"
																			  . $updateitems[$key][$fieldname]['new'];
								if (!$newrecord) {
									$comparisontable[$rowcounter][$columncounter] .= "<br><span class='comp_oldval'>("
																				  . $updateitems[$key][$fieldname]['old']
																				  . ")</span>";
								}
								$comparisontable[$rowcounter][$columncounter] .= "</td>";
							}
						}
					}
				}
				$rowcounter++;
			}
			$columncounter++;
		}

		// Build table (format as ROWS)
		if ($format == 'rows')
		{
			$comparisonstring = "<table id='comptable'><tr><th scope='row' class='comp_fieldname' colspan='$rowcounter' bgcolor='black'><font color='white'><b>{$lang['data_import_tool_28']}</b></font></th></tr>";
			for ($rowi = 0; $rowi <= $columncounter; $rowi++)
			{
				$comparisonstring .= "<tr>";
				for ($colj = 0; $colj < $rowcounter; $colj++)
				{
					$comparisonstring .= isset($comparisontable[$colj][$rowi]) ? $comparisontable[$colj][$rowi] : '';
				}
				$comparisonstring .= "</tr>";
			}
			$comparisonstring .= "</table>";
		}
		// Build table (format as COLUMNS)
		else
		{
			$comparisonstring = "<table id='comptable'><tr><th scope='row' class='comp_fieldname' colspan='" . ($columncounter+1) . "' bgcolor='black'><font color='white'><b>{$lang['data_import_tool_28']}</b></font></th></tr>";
			foreach ($comparisontable as $rowi => $rowrecord)
			{
				$comparisonstring .= "<tr>";
				foreach ($rowrecord as $colj =>$cellpoint)
				{
					$comparisonstring .= $comparisontable[$rowi][$colj];
				}
				$comparisonstring .= "</tr>";
			}
			$comparisonstring .= "</table>";
		}
		// If user is not allowed to create new records, then stop here if new records exist in uploaded file
		if (!$user_rights['record_create'] && !empty($newRecords))
		{
			return  "<div class='red' style='margin-bottom:15px;'>
						<b>{$lang['global_01']}{$lang['colon']}</b><br>
						{$lang['data_import_tool_159']} <b>
						".implode("</b>, <b>", $newRecords)."</b>{$lang['period']}
					</div>";
		}
		return $comparisonstring;
	}

//-----------------------------------------------------------------------------------------------------------	
// Download Audit records csv file
//-----------------------------------------------------------------------------------------------------------	
    public function download_audit_csv()
    {
        $errors = array();

        $ts = date('YmdHis');
        
        $cdct_audit = $this->get_audit_trail_details();

        if (empty($cdct_audit))
        {
            $errors[] = "Nothing was found in the audit log.";
        }
        else
        {
            $filename = "metamorphosis_audit_$ts.csv";

            header('Content-Description: File Transfer');
            header('Content-Type: application/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="'.basename($filename).'"');

            $file = fopen("php://output", "w");

            if ($file === FALSE)
            {
                $errors[] = "Error opening $filename to write.";
            }
            else
            {
                $separator = ",";
                $headers = array ("Date","Project Id","User","Name/Label","IRB Number","Request #","PI Name","Document","Records Loaded",);

                // Write headers to file
                if (fputcsv($file, $headers, $separator) === FALSE)
                {
                    $errors[] = "Error exporting audit headers.";
                }
                else
                {
                    // Loop through each audit and write it to CSV file
                    foreach($cdct_audit as $audit)
                    {
                        $row = array();
                        foreach($audit as $key => $value)
                        {
                            $row[] = $value;
                        }

                        if (!empty($row))
                        {
                            if (fputcsv($file, $row, $separator) === FALSE)
                            {
                                $errors[] = "Error exporting row (" . implode(", ", $row). ")";
                            }
                        }
                    }

                    if (fclose($file) === FALSE)
                    {
                        $errors[] = "Error closing $filename.";
                    }
                }
            }
        }
        return $errors;
    }

    public function get_audit_trail_details()
    {
        $details = array();
        $sql = "SELECT * FROM redcap_log_event WHERE description = 'MetaMorphosis Audit' ORDER BY ts DESC";
		$result = db_query($sql);
		
        while($row = db_fetch_assoc($result))
        {
			$audit = json_decode($row['data_values'], true);
			if ( is_array($audit) ) {
				$datestamp = DateTimeRC::format_ts_from_ymd(DateTimeRC::format_ts_from_int_to_ymd($row['ts']));
				$audit_trail = [
					"ts" => $datestamp,
					"pid" => $row['project_id'],
					"user" => $row['user'],
					"label" => $audit['label'],
					"irb" => $audit['irb'],
					"request" => $audit['request'],
					"pi" => $audit['pi'],
					"document" => $audit['document'],
					"loaded" => $audit['loaded']
				];
			} else {
				$audit_trail = [
					"ts" => $datestamp,
					"pid" => $row['project_id'],
					"user" => $row['user'],
					"label" => $row['data_values'],
					"irb" => "",
					"request" => "",
					"pi" => "",
					"document" => "",
					"loaded" => ""
				];
			}
            $details[] = $audit_trail;
        }
        return $details;

    }
	
    public function get_audit_trail_report($dateFrom, $dateTo)
    {
		$audit_list = '';

		if ($dateFrom == "" && $dateTo == "") {
			$sql = "SELECT * FROM redcap_log_event WHERE description = 'MetaMorphosis Audit' ORDER BY ts DESC";
		} else {
			$dateFrom	= ($dateFrom == "" ? "20190101000000" : str_replace("-","",$dateFrom))."000000";
			$dateTo		= ($dateTo == "" ? "32391231999999" : str_replace("-","",$dateTo))."999999";
			$sql = "SELECT * FROM redcap_log_event WHERE description = 'MetaMorphosis Audit' AND ts >= $dateFrom AND ts <= $dateTo ORDER BY ts DESC";
		}
		$result = db_query($sql);

		while ($row = db_fetch_assoc($result)) {
			$audit = json_decode($row['data_values'], true);
			if ( is_array($audit) ) {
				$datestamp = DateTimeRC::format_ts_from_ymd(DateTimeRC::format_ts_from_int_to_ymd($row['ts']));
				$projectid = '<a href="http://localhost/redcap/redcap_v8.11.8/ProjectSetup/index.php?pid='.$row['project_id'].'"><u>'.$row['project_id'].'</u></a>';
				$audit_list .= '<tr class="table_standard"><td>'.
					$datestamp.'</td><td>'.
					$projectid.'</td><td>'.
					$row['user'].'</td><td>'.
					$audit['label'].'</td><td>'.
					$audit['irb'].'</td><td>'.
					$audit['request'].'</td><td>'.
					$audit['pi'].'</td><td>'.
					$audit['document'].'</td><td>'.
					$audit['loaded'].'</td></tr>';
			} else {
				$audit_list .=	'<tr class="table_standard"><td>'.$datestamp.'</td><td>'.$projectid.'</td><td>'.$row['user'].
								'</td><td colspan="6">'.$row['data_values'].'</td></tr>';
			}
		}
		if ($audit_list == "") {
			$audit_list = '<tr class="table_standard"><td colspan="9">No audit records found</td></tr>';
		}
		return $audit_list;
	}
}
