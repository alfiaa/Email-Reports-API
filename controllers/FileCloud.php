<?php
/*
  This class is used for connecting and uploading datat to fileclou.
  @author  alfiaa
  @version 1

*/


require_once('/var/Auth/config.php');
require_once  'cloudapi.php';

class FileCloud extends CloudAPI{
	
	
		
	
	/*
	  The constructor is used for initializing the data.
	*/
	
	function __construct(){
		parent::__construct(SERVER_URL);
	}
	
	/*
	  The getter function used to read the given property
	
	  @param 	$property 		The name of the property to be read
	  @return 	$this->property The value of the property 
	*/
	
    public function __get($property) {
       if (property_exists($this, $property)) {
	       return $this->$property;
		
       }
     }

	 /*
	  The setter function is used for setting the value of the property
	 
	  @param $property  The name of the property to be set
	         $value     The value of the property
	 */
	 
     public function __set($property, $value) {
       if (property_exists($this, $property)) {
         $this->$property = $value;
	    }

       return $this;
     }
	 
	 /*
	   This function is used for getting the access token to 
	   be able to access the iformbuilder apis
	   
	   @param $pathvalue the path where the folder needs to be created
	          $folder_name name of the folder
	 */
	 
	 public function createFolderOnCloud($pathvalue, $folder_name){
	 	 $record = $this->loginGuest(USER, PASSWORD);
		 $record = $this->createFolder($pathvalue, $folder_name);
		 $err_msg = "";
		 if ($record->getResult() != '1'){
		 	$err_msg .="\n  Did not create an new folder $folder_name. The folder might already exists";
			$this->errorOccurred($err_msg);
		 }
 	    
	 }
	 
	 
	 public function saveFile($data_array, $local_path, $pathvalue)
	 {
		    $err = "";
			$file = '../errorlogs/save_images_log.txt'; //tmp file
			foreach($data_array as $key=>$val){
	 			$data_file = $val['url'];
	 			$data = @file_get_contents($data_file);
	 			$basename_file = basename($data_file);
	 			$filename = "";
		 		if ($data != '') {
	     		//$filename = $basename_file;
					$ext = pathinfo($basename_file, PATHINFO_EXTENSION);
	     			if(!empty($val['caption'])){
						$filename = $val['caption'].".$ext";
			    	}else{
			    		$filename= $basename_file;
			    	}
				    $ret=file_put_contents($local_path.$filename, $data);
		 					 
	 		}else{
			    $err_msg .="\n No or empty image";
				$this->errorOccurred($err_msg);
	 		}
		
			$pathvalue = urlencode($pathvalue);
		    $filename = $local_path.$filename;
            $this->uploadData($pathvalue,$filename);
		 
	
		 }
	 }
	 
  	/*
  	 This function is used for uploading data to filecloud
	
  	 @param    $pathvalue     path where the file will be uploaded
  	           $filename      the file to be uploaded
		
  	*/
	 public function uploadData($pathvalue,$filename){
	 	 
		$appnamevalue = "explorer";
		$err_msg = "";
 	    //Uploading the file
 	    $record = $this->upload($appnamevalue, $pathvalue, $filename);
 	   	if($record!='OK'){
 		    $err_msg .= "\n UPLOAD STATUS".$record." trying to upload file $filename to $pathvalue";
			$this->errorOccurred($err_msg);
 		}	 
 	    
	 }
	 
	 /*
	 This function is used for removing temp files/images stored locally
	 @param $dir path where temporary files are stored locally 
	 */
	 
	 public function removeTmpDir($dir){
 	      foreach (scandir($dir) as $item) {
   		 if ($item == '.' || $item == '..') continue;
   	   		unlink($dir.DIRECTORY_SEPARATOR.$item);
 	    }
	 }
	 
	 
   	/*
   	 This function is used for emailing and recording errors in the error file.
	
   	 @param    $err_msg  this explains what error has occured
   		
   	*/
	 
	 public function errorOccurred($err_msg){
     	   mail("alfiaa@thinkpowersolutions.com","Error in API",$err_msg);
           $today = date("Y-m-d H:i:s");
	       $file = '../errorlogs/save_images_log.txt'; //tmp file
           file_put_contents($file, "\n $today Error in API $err_msg\n", FILE_APPEND | LOCK_EX);
	 }
	
}
?>