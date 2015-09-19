<?php //Swaroop Chakraborty:: PHP Code Block in CI Framework describing user authentication from Active Directory how to read and Upload  a Excel file in the server and insert the contents of the file into a database
 public function uploadReadInsertExcelData($token=''){
//Check if user is authenticated
error_reporting(0);$auth='';$logvalue=$this->security->xss_clean(trim(strtoupper($this->input->post('ICAUTH'))));$file_handle = fopen("auth/ActiveDirectoryListing.txt", "rb");
 while (!feof($file_handle) ) { //reading file
$line_of_text = fgets($file_handle);$parts = explode('=', $line_of_text);if($parts[0]==$logvalue){$auth="yes";break;}}fclose($file_handle);if($auth=='yes'){ //perform validation actions
 //Upload New Excel File into server currentVersion, move old file if any to history
$from=$this->security->xss_clean(trim(ucfirst($this->input->post('datePeriodfrom'))));$to=$this->security->xss_clean(trim(ucfirst($this->input->post('datePeriodto')))); $filepath="uploads/currentVersion/";$file = $_FILES['upload']['name'];$valid_formats = array("xls", "xlsx","XLSX","XLS");
if(strlen($_FILES['upload']['name'])>0) {$file=$_FILES['upload']['name'];
list($txt, $ext_pic) = explode(".", $file);if(in_array($ext_pic,$valid_formats)){
$actual_file_name ="_AbendRpt_".$from."_to_". $to.".".$ext_pic; $tmp_file = $_FILES['upload']['tmp_name'];$filesincurrent=scandir($filepath); ////naming the file,move and replace to history
$source="uploads/currentVersion/";$destination="uploads/history/";
foreach($filesincurrent as $file){if(in_array($file,array(".",".."))) continue;
	if(copy($source.$file,$destination.$file)){$delete[]=$source.$file;}
}foreach($delete as $file){unlink($file);}
//move and replace to history
if(move_uploaded_file($tmp_file, $filepath.$actual_file_name))
{$filepath=$filepath."/".$actual_file_name;}
else {$filepath='';  } } else { $filepath='';} }  
else {$filepath='';}
if($filepath!=''){ //after uploading the file into the server, check the pre defined settings of this file, as the highest column should always be K, this portion of code will be changeable
$file = $filepath; // the only new file in currentVersion
$this->load->library('excel');//read file from path
$objPHPExcel = PHPExcel_IOFactory::load($file);$sheet=$objPHPExcel->getSheet(0);$hc = $sheet->getHighestColumn();$hr=$sheet->getHighestRow();
if($hc=="K"){ // good data; this might change if highest column changes
$q00=$this->db->query("TRUNCATE TABLE RawAbendDataPushPoint"); //PURGE PREVIOUS UPLOAD IF ANY
for($row=2;$row<=$hr;$row++) {$rowData=$sheet->rangeToArray('A'.$row. ':'.$hc.$row,NULL,TRUE,FALSE);
$arrayData=array_shift($rowData);for($i=0;$i<=count($arrayData);$i++) {
//changeable region 0 to 10 means A to highest column=>K as of now
        $hhrr=$arrayData[0];if($hhrr=='' || empty($hhrr)){ $hhrr="NA";}$env=$arrayData[1];if($env=='' || empty($env)){ $env="NA";}$server=$arrayData[2];if($server=='' || empty($server)){ $server="NA";}$db=$arrayData[3];if($db=='' || empty($db)){ $db="NA";}$stream=$arrayData[4];if($stream=='' || empty($stream)){ $stream="NA";} $job=$arrayData[5];if($job=='' || empty($job)){ $job="NA";}$count=$arrayData[6]; if($count=='' || empty($count)){ $count=-1;}$evts=$arrayData[7];$evtsremark=$arrayData[8];$followup=$arrayData[9];$action=$arrayData[10];
$data_dump=array('HHRR'=>$hhrr,'EnvType'=>$env,'servername'=>$server, 'dbname'=>$db, 'stream'=>$stream,'job'=>$job,'count'=>$count,
 'EVTS'=>$evts,'EVTSRemark'=>$evtsremark,'FollowUpReqdYN'=>$followup,'ActionstakenYN'=>$action,'identityMetric'=>$token);
 $q1=$this->db->insert('RawAbendDataPushPoint',$data_dump);
 break;}} $flag="ok";if($flag=="ok"){redirect(base_url()."?msg=".base64_encode("FILE UPLOADED!! Abend Report File ". $actual_file_name. " has been successfully processed into server. You may now proceed to generate the associated EVTS of this report"),'refresh');
}else {redirect(base_url()."?err=".base64_encode("RUNTIME ERROR!! There occured an unexpected error. Please try again cleaning the browser cache"),'refresh');
}} // good data
else {@unlink($filepath);
redirect(base_url()."?err=".base64_encode("BAD DATA REQUEST!! Please upload a excel file as per specified format with column width ranging from A to K"),'refresh');
}}else { //if file not uploaded due to server problem
redirect(base_url()."?err=".base64_encode("FATAL ERROR! The file that you are trying to upload is already open in Microsoft Excel Application. Please close it first and try again."),'refresh');
}} //auth
else {redirect(base_url()."?err=".base64_encode("AUTHENTICATION FAILURE!! The IC ID with which you tried to upload the file is invalid. Please authenticate yourself properly"),'refresh');}
}?>
	
