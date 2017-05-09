<?php
/*
This class is used for sending email using PostMark.

@author alfiaa
@version 1
*/


require_once('/var/Auth/config.php');
include_once  '../controllers/Email.php';
require_once '/var/www/html/tools/aws/aws-autoloader.php';
require_once '/var/www/html/Auth/aws_config.php';
require_once('../controllers/cloudapi.php');
use Aws\Common\Aws;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class PostData{

      private $jsonpost;
	  private $jsonarray=array() ;
	  private $template;
	  private $subject;
	  private $recipients;
	  private $remove_keys=array();
      private $report_photos = array();
      private $sliderurl;
      private $print_base_body;
      private $formtitle;
      private $subformtitle;
      private $file_upload_path;
      private $profile;
      private $record_info;
      private $record_id;

	/*
	 Constructor used for initializing the object.

	*/

	function __construct()
	{
	  require_once('../vardefs.php');
	  $this->remove_keys = $remove_keys;
	}

	/*
	 The setter method is used to set any property given its value.

	 @param $name    name of the property
	        $value   specifies the value of $name
	*/

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
          $this->$name = $value;
 	    }

        return $this;

     }

 	/*
 	 The getter method is used to read the value of any property.
 	 It calls the parent class getter.

 	 @param    $name     name of the property
 	 @return   $result   returns the value of $name
 	*/

	public function __get($name)
	{
        if (property_exists($this, $name)) {
 	       return $this->$name;

        }
		elseif (($result = parent::__get($name)) !== null)
	    {
	        return $result;
	    }

	       return;

	}

	  /* This function is used for setting the subject for the email from the json received*/

	  public function  createSubject()
	  {
	        if(!empty($this->jsonarray))
	        {
			    $this->subject = $this->jsonarray['email_subject'];
                unset($this->jsonarray['email_subject']);
          	}
	  }

      /* This function is used for setting recipients of the email from the json received*/
      public function setRecipients()
      {
          /*
		$this->recipients = "vinodr@thinkpowersolutions.com,hari@thinkpowersolutions.com,alfiaa@thinkpowersolutions.com";
              	unset($this->jsonarray['email_reports']);
	*/
          if(!empty($this->jsonarray))
	        {
			    $this->recipients = $this->jsonarray['email_reports'];
                unset($this->jsonarray['email_reports']);

          	}
            
          
      }
 
 
     /*
     This function is used for prepring the slider for the photos slideshow
     @return $sbody  is the html body for the slider.
     */
      public function createSlider()
      {
        $file = '../template/miniml.html';
        $sbody = file_get_contents($file);
        $sbody .= "<div class='content'>
                  <div class='galleria'>";
          
        if(isset($this->report_photos)) 
        {    
           foreach($this->report_photos as $key=>$value)
           {
             $sbody.="<a href='{$value['url']}'>
                      <img src='{$value['url']}'
                           data-title='{$value['caption']}'
                           data-description='{$value['caption']}'>
                    </a>";
           
           }
        }
        $sbody.="</div>
                </div>
                </body></html>";  

        return $sbody;
      }

      /* This function is used for uploading the slider page with photos and getting the slider url to view to photos slideshow on the report
      @param $body is the html slider body
      */
      public function uploadSlider($body)
      {
          

          $bucket = 'logs-reports.thinkpowersolutions.com/photo-reports';
          $keyname = time().'.html';
	      $filepath = '../tmp/'.$keyname;
          file_put_contents($filepath, $body, FILE_APPEND | LOCK_EX);
          
          $aws = Aws::factory('/var/www/html/Auth/aws_config.php');
          $s3 = $aws->get('s3');

          try {
             
            // Upload data.
            $result = $s3->putObject(array(
                'Bucket' => $bucket,
                'Key'    => $keyname,
                'SourceFile'   => $filepath,
                 'ContentType'  => 'text/html',
                'ACL'    => 'public-read'
            ));
              
           
            if(isset($result['ObjectURL']))
            {
               $this->sliderurl = "https://logs-reports.thinkpowersolutions.com/photo-reports/$keyname"; 
            }
            
          
            unlink($filepath);          

          } catch (S3Exception $e) {
               $email = new Email();
               $email->SendErrorEmail($this->record_info.' '.$e->getMessage() . "Error\n");
          }
 
      }
    
    
    
      /* This function us used for creating pdf report of the email sent
      */
public function createPDF()
      {
          try{
          list($upload_path,$filename) = $this->createUploadPath();
          if(empty($upload_path)||empty($filename))
          {
              throw new Exception('Upload path or filename is empty');
          }

          $origfilename=$filename =str_replace("/", "-", $filename);
          $tmp_filename = "email_".time(); ;
          $filename_html = $tmp_filename.".html";
          $filename_pdf = $tmp_filename.".pdf";
          $filepath_html = '../tmp/'.$filename_html;
          $filepath_pdf =  '../tmp/'.$filename_pdf;
          ob_clean(); 
          //creating pdf
          file_put_contents($filepath_html, $this->print_base_body, FILE_APPEND | LOCK_EX);
           exec("wkhtmltopdf --page-size a4 --dpi 300 --margin-left 25mm --zoom 1.25 --disable-external-links $filepath_html $filepath_pdf");
          $filepath_main = "../tmp/$filename.pdf";
          $ret = rename ($filepath_pdf, $filepath_main);
          
          
          //checking of file already exists
          
          
           $cloudAPI = new CloudAPI(SERVER_URL);
		   $record = $cloudAPI->loginGuest(USER, PASSWORD);
          
           $pathvalue = $upload_path."/".$origfilename.".pdf";
           $exists = $cloudAPI->fileexists($pathvalue);
           $i=1;
            
           while($exists->getMessage())
           {
            $filename = $origfilename.'-'.$i;
            $pathvalue = $upload_path."/".$filename.".pdf";
		    $i++;
            $exists = $cloudAPI->fileexists($pathvalue);
            
          }
            $filepath_new = "../tmp/$filename.pdf";
            $ret = rename ($filepath_main, $filepath_new);
            //uploading file
            try
          {
		          $cloudAPI = new CloudAPI(SERVER_URL);
                  $record = $cloudAPI->loginGuest(USER, PASSWORD);
                  $appnamevalue = "explorer";
	              $pathvalue = urlencode($upload_path);
	              $record = $cloudAPI->upload($appnamevalue, $pathvalue, $filepath_new);
                 
          } 
          catch (Exception $e) 
          {
	        $btrace = $e->getTraceAsString();
            $email = new Email();
            $err_msg ='Email Report file upload Caught exception: '. $e->getMessage(). "\n";
            $err_msg .='Trace: '. $btrace. "\n";
            $email->SendErrorEmail($this->record_info.' '.$err_msg);
          }
          }catch (Exception $e) 
          {
	        $btrace = $e->getTraceAsString();
            $email = new Email();
            $err_msg ='Email Report file upload Caught exception: '. $e->getMessage(). "\n";
            $err_msg .='Trace: '. $btrace. "\n";
            $email->SendErrorEmail($this->record_info.' '.$err_msg);
        }
        unlink($filepath_new);
        unlink($filepath_html);
      }
    
      /* This function is sued for setting the fileupload path variable. 
         This is value is received from the json
         */
    
      public function setfileUploadPath()
      {
          if(!empty($this->jsonarray))
	        {
			    
                $this->file_upload_path = $this->jsonarray['file_upload_path'];
                unset($this->jsonarray['file_upload_path']);
          	}
      }
    
      /* This function is used for creating upload path 
         based on the formtitle, where the pdf file will be uploaded
      */
    
      public function createUploadPath()
      {  
            
          if(!empty($this->jsonarray))
	      {
			   
                if(isset( $this->jsonarray['structure']))
                { 
                    $structure = $this->jsonarray['structure'];
                    $structure = str_replace("/", "-", $structure);
                }
          }
          if(!empty($this->file_upload_path))
          {      
             $filename = $folder_name = "";
            if(strpos($this->formtitle, 'Daily Field Report') !== false) 
            {
                $folder_name = "Daily Field Reports";   
                if(!empty($this->subject))
                {
                    $filename = substr($this->subject, 0, 120); 
                } else
                {
                    $filename = "report_".time(); 
                }
                
            }
            elseif(strpos($this->formtitle, 'Line Construction Inspection') !== false)
            {
                $filename = "$structure"."_".$this->subformtitle;
                if(strpos($this->subformtitle, 'Foundation Assessment')!== false)
                {
                   $folder_name ="Construction Inspections/$structure/Foundation";
                }
                elseif(strpos($this->subformtitle, 'Grounding Assessment')!== false|| strpos($this->subformtitle, 'Final Inspection Assessments')!== false)
                {
                   $folder_name ="Construction Inspections/$structure/Final Inspection";
                }
                elseif(strpos($this->subformtitle, 'Pole Jacking Assessment')!== false || strpos($this->subformtitle, 'Structure Erection Assessment')!== false)
                {
                   $folder_name ="Construction Inspections/$structure/Structure";
                }
                 elseif(strpos($this->subformtitle, 'Stringing/Pulling Operation Assessment')!== false 
                        || strpos($this->subformtitle, 'Sagging Operation Assessment')!== false
                        || strpos($this->subformtitle, 'Compression Sleeve and Terminal Assessment')!== false
                        || strpos($this->subformtitle, 'Clipping Assessment')!== false
                        || strpos($this->subformtitle, 'Fiber Splicing Assessment')!== false
                        )
                        
                {
                   $folder_name ="Construction Inspections/$structure/Wire";
                }
                elseif(strpos($this->subformtitle, 'Final')!== false || strpos($this->subformtitle, 'Ground Resistance Assessment')!== false)
                {
                   $folder_name ="Construction Inspections/$structure/Final";
                }
                
            }elseif(strpos($this->formtitle, 'ROW Assessment') !== false) 
            {
                $folder_name = "ROW Assessment";   
                if(!empty($this->subject))
                {
                    $filename = substr($this->subject, 0, 120); 
                } else
                {
                    $filename = "report_".time(); 
                }
                
            }
              $folders = explode("/",$folder_name);
              $cloudAPI = new CloudAPI(SERVER_URL);
	          $record = $cloudAPI->loginGuest(USER, PASSWORD);
	          $pathvalue= '/SHARED/thinkpower/'.$this->file_upload_path;
              $email = new Email();
              file_put_contents($file, "folder ".$folder_name."\n", FILE_APPEND | LOCK_EX);
              foreach($folders as $name)
              {
                   $record = $cloudAPI->createFolder($pathvalue, $name);
                   $pathvalue .= "/$name";
                    if ($record->getResult() != '1'){    
                         $exists = $cloudAPI->fileexists($pathvalue);
                         if($exists->getResult() != '1')
                         {
                             $email->SendErrorEmail($this->record_info.' '."Error creating folder in email api $pathvalue. The folder might exists. The folder name is $name
                             The message is".$record->getMessage()."\n");
                         }
	 	            
	               }
              }
            
          }
        
          return array($pathvalue,$filename);
          
      }
    
      /**
       This function gets all the photos on the form, arranges them in an array, the main form photos comes in the end. This is done for the slider
      **/
      public function prepareReportPhotos()
      {
          $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->jsonarray), RecursiveIteratorIterator::SELF_FIRST);
          $i = $j = 0;
          $main_report_photo = array();
          foreach($iterator as $key=>$value)
          {
    
            $d = $iterator->getDepth();
            if(!is_array($value))
            {
                if(strpos($key, 'take_and_annotate_photo') !== false)
                {
                    if($d==3) // getting main form photos to put them at the end of the array
                    {
                        $main_report_photo[$j]['url']=$value;
                    }
                    else{
                        $this->report_photos[$i]['url']=$value;
                    }
                                            
                }
                elseif(strpos($key, 'caption') !== false && strpos($key, 'photo') !== false)
                {
                    if($d==3)
                    {
                        $main_report_photo[$j]['caption']=$value;
                        $j++;  
                    }
                    else{
                        $this->report_photos[$i]['caption']=$value;  
                        $i++;
                    }
                }
            }
 
          }
          
          if(!empty($main_report_photo))
          {
            foreach($main_report_photo as $rowp){
                $this->report_photos[$i]['url']= $rowp['url'];
                $this->report_photos[$i]['caption']=$rowp['caption'];
                $i++;
                }
            }
      }
      
    /*This function is used to set the information that will give details in case of an error*/
      public function setErrorMessage($data)
      {
          
          $employee = $data['field_employee'];
          $report_date = $data['report_date'];
          $project = $data['project'];
          $form_title = $data['form_title'];
          $this->record_info = 'Profile: '.$this->profile.' Form Titile: '.$form_title.' Project: '.$project.' Employee: '. $employee. ' Record id : '.$this->record_id.' Report Date '.$report_date.'<br/>';
      }
    
      /* This function makes a call to other function for get the data for sending the email ready and then
         calls the sendemail function of the email class to send the email
        */
      public function sendEmail()
	  {
		$result = json_decode($this->jsonpost,true);
		$data = array();
	   if(!empty($result)){
		    foreach($result as $row) {
		  	   $data = $row['record'];
			 }
	     }
       
       $this->record_id = $data['ID'];
       $this->setErrorMessage($data);
       $this->parseJson($data);
	   $this->createSubject();
	   $this->setRecipients();
       $this->setfileUploadPath();
       $this->prepareReportPhotos();
       $sbody = $this->createSlider();
       $this->uploadSlider($sbody);  
	   $email_body = $this->createBody();
       $email = new Email();
	   $email->to = $this->recipients;
       $email->subject = $this->subject;
	   $email->body = $email_body;
       $sent = $email->sendEmailapi();
	   if($sent!==200){
	       $email->SendErrorEmail($this->record_info.' '."Error in sending email. Error code $sent. Error Message is ".$email->failure_message);
       }
       $this->createPDF();  
        }
	  

    /* This method is used for parsing the json data and taking the data out
       thats not required.

       @param $data is the array that was generated from the json received
       @return $jsonarray is an array with values for suform and mainform

       */

    public function parseJson($result)
    {
      foreach($this->remove_keys as $val)
      {
         $this->recursiveRemoval(&$result,$val);
      }
        $this->jsonarray = $result;//$data;
    }


  /* This method is used for removing the unwanted data from the input json, this 
     created an iterator to iterate through the multidimensional array recursively
     @param $array is the json input array
            $val is the value to be removed from the json array
  */
  public function recursiveRemoval(&$array, $val)
  {
    if(is_array($array))
    {
        foreach($array as $key=>&$arrayElement)
        {    if(is_array($arrayElement))
            {
                if(isset($arrayElement[$val])){
                     
                    unset($arrayElement[$val]);
                    $this->recursiveRemoval($arrayElement, $val);
                }
               
                 $this->recursiveRemoval($arrayElement, $val);
            }
            else
            {
                if($key === $val)
                {
                    unset($array[$key]);
                }
            }
        }
    }
}
    
     /* This method is for getting the header of the email body
        which is stored in a file. 
        @return $header returns the header from the file
      */
   public function createBaseHeader()
   {
       $file = '../template/header.html';
       $header = file_get_contents($file);
       return $header;
   }
    
    /* This method is for getting the footer of the email body
        which is stored in a file. 
        @return $footer returns the footer from the file
      */   
   public function createBaseFooter()
   {
          $file = '../template/footer.html';
          $footer = file_get_contents($file);
          return $footer;
   }

    /*
    This method is used for constructing the body of the email
    @return emailbody returns the entire html email
    */
   public function createBody()
   {
          $header = $this->createBaseHeader();
	      $footer = $this->createBaseFooter();
          $body = $this->createBaseBody($this->template);
          $emailbody = $header.$body.$footer;
          $pdfbody = $header.$this->print_base_body.$footer;
          $this->print_base_body = $pdfbody;
          return $emailbody;

   }

    /*
    This method is used for parsing the data to separate the mainform, photos and subform data
    @return returns array that contains mainform, subform and photo data in separate arrays
    */
    public function prepareMainform()
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->jsonarray), RecursiveIteratorIterator::SELF_FIRST);
        $restofform =$mainform = $mainphotos = $mainothers= array();
    
        $main_form_keys = array("form_title","field_employee","project","report_date","client","project_manager","electrical_engineer","gps_latitude","gps_longitude","project_wo","onsite_contractor","weather_conditions");
        
        foreach($iterator as $key=>$value)
        {  $depth = $iterator->getDepth();
            if($depth ==0){
                if(!is_array($value))
                 {
                     if(in_array($key,$main_form_keys,true))
                     {   
                         $mainform[$key]=$value;
                     }
                     else
                     {
                         if(!empty($value))
                         {     
                           $mainothers[$key]=$value;
                         }
                     }
                }
                elseif(strpos($key, 'photo') !== false){
                    $mainphotos[$key]=$value;
                }else{
                    $restofform[$key]=$value;
                }
                
        }
        }
        return array ($mainform, $mainphotos,$mainothers,$restofform);
    }
    
    /*
    This method is used for parsing the subform data at different levels
    @param $restofform is the subform data received from json
    @return returns the formatted subform data array
    */
    public function prepareSubform($restofform)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($restofform), RecursiveIteratorIterator::SELF_FIRST);
        $sub_arr = array();
        $top_level = 0;
        $toplevel_array = array();
        $last_depth=$last_key = "";
        foreach($iterator as $key=>$value)
        {
     
        $depth = $iterator->getDepth();
       
        // there is another form of same name
        if(!empty($toplevel_array))
        {
           if(in_array($top_level,$toplevel_array)&&$key>0&&is_array($value)&&$depth<$last_depth)
          {
           $top_level .= $key;
           $toplevel_array[] = $top_level; 
          }
       }
            
        /*if($depth==1&&$key>0)
        { // there is another form of same type
           $top_level .= $key;
        } */
        if(is_array($value))
        {
            if(!is_numeric($key) && $key != 'record' && strpos($key, 'photo') === false)
            {
                $top_level = $key;
                $toplevel_array[] = $top_level; 
            }
            if(strpos($key, 'photo') !== false)
            {
                $sub_arr[$top_level][$key]=$value;
                $last_key = $key;
                $last_depth = $depth;
                continue;
        
            }
        }
        else 
        {
           if(strpos($key, 'photo') === false) // if key is not photos
           { 
               if(!empty($value))
                {     
                     $sub_arr[$top_level][$key]=$value;
                     $last_key = $key;
                      $last_depth = $depth;
                }
                
           }
            elseif(strpos($key, 'photos') !== false)
           { //see if photos array is empty and has no photo
                $sub_arr[$top_level]['nophotos']=true;  
                $last_key = $key;
                $last_depth = $depth;
           }
          
        }
  
       }
      return $sub_arr;
    }
    
    /*
     This function is used for terating through all subform photos
     @return true if there are photo
             false no photos
    */
    public function  checkAllSubformphoto($subform)
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($subform), RecursiveIteratorIterator::SELF_FIRST);
       foreach($iterator as $key=>$value)
        {
            if(strpos($key, 'photo') !== false) //ifphoto
            {
               
                return true;     
            }
        }
        return false;
    }
    
    
    
    /*
    This function is used for preparing body for email
    @retun returns the email html body
    */
    public function createBaseBody($template){
        list($mainform,$mainphotos,$mainothers,$restofform) = $this->prepareMainform();
     		
                                  $basebody = "<body style='Margin:0;padding:0;min-width:100%;'>
      								<div class='email-body' style='max-width:600px;'>
        							<!--[if (gte mso 9)|(IE)]>
         								 <table width='600' align='center'>
            								<tr>
             								 <td>
        							<![endif]-->
        							<table class='outer' align='center' style='border-spacing:0;'>
         							 <tr>
									<td class='' style='text-align: center'>
									  <h2 class='header' style='color:white;font-size:16px;font-weight:bold;text-transform:uppercase;padding:1%;text-align:center;margin-top:0em;margin-bottom:0em;'>
										<a href='http://thinkpowersolutions.com/' class='aligncenter' style='color:#ed1c24;clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'>
                                        <img src='https://logs-reports.thinkpowersolutions.com/logo-big.jpg' width='300' alt='' style='border:0;margin:0 auto;display: inline-block; max-width: 400px; margin: 4px auto;'/></a>
									  </h2>
									</td>
								  </tr>
								  <tr>
									<td class='one-column paragraph' style='padding:.5% 2%;background-color:#ffffff;'>
								  <table width='100%' style='border-spacing:0;'>
									<tr>
									  <td style='padding:2px 4px;'>
										<p class='h1 text-center' style='text-align:center;font-size:18px;margin-top:0px;margin-bottom:0px;'>Field Services, Software Products, and Consulting</p>
									  </td>
									</tr>
								  </table>
								</td>
							  </tr>";
                        $this->formtitle = $mainform['form_title'];
						$mainform_body="<tr>
								<td class='one-column test drive' style='background-color:#ed1c24;color:white;padding:.5% 2%;'>
								  <table width='100%' style='border-spacing:0;'>
									<tr>
									  <td class='inner-content-two text-center' style='padding:2px 4px;text-align:center;'>
										<h2 class='teal' style='color:white;font-size:16px;font-weight:bold;text-transform:uppercase;padding:1%;margin: 2px;'>{$mainform['form_title']}</h2>
									  </td>
									</tr>
								  </table>
								</td>
							  </tr>
							  <tr>
								<td class='pattern' width='600' align='center' style='background-color: #f6f6f6; font-size: 14px;'>
								  <table cellpadding='0' cellspacing='0' style='border-spacing:0;'>
									<tr>
									  <td class='col inner' width='298' style='padding:2px 4px;padding: 10px;'>
										<table cellpadding='0' cellspacing='0' style='border-spacing:0;width:100%;display:block;'>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>Employee </td>
											<td style='padding:2px 4px;width:70%;'>{$mainform['field_employee']}</td>
										  </tr>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>Project</td>
											<td style='padding:2px 4px;width:70%;'>{$mainform['project']}</td>
										  </tr>
                                          <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>Project WO</td>
											<td style='padding:2px 4px;width:70%;'>{$mainform['project_wo']}</td>
										  </tr>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>Report Date</td>
											<td style='padding:2px 4px;width:70%;'> {$mainform['report_date']}</td>
										  </tr>
										</table>
									  </td>
									  <td class='spacer' width='4' style='padding:2px 4px;font-size: 1px;'>&nbsp;</td>
									  <td class='col inner' width='298' style='padding:2px 4px;padding: 10px;'>
										<table cellpadding='0' cellspacing='0' style='border-spacing:0;width:100%;display:block;'>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>Client	</td>
											<td style='padding:2px 4px;width:70%;'>{$mainform['client']}</td>
										  </tr>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>
											  Manager
															</td>
											<td style='padding:2px 4px;width:70%;'>
											  {$mainform['project_manager']}
															</td>
										  </tr>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>
											  Engineer
															</td>
											<td style='padding:2px 4px;width:70%;'>
											 {$mainform['electrical_engineer']}
															</td>
										  </tr>
                                          <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>
											  Contractor
															</td>
											<td style='padding:2px 4px;width:70%;'>
											 {$mainform['onsite_contractor']}
															</td>
										  </tr>
										</table>
									  </td>
									</tr>
								  </table>
								</td>
							  </tr>
							  <tr>
								<td class='one-column test drive' style='background-color:#ed1c24;color:white;padding:.5% 2%;'>
								  <table width='100%' style='border-spacing:0;'>
									<tr>
									  <td class='inner-content-two text-center' style='padding:2px 4px;text-align:center;'>
										<h2 class='teal' style='color:white;font-size:16px;font-weight:bold;text-transform:uppercase;padding:1%;margin: 2px;'>Location & Weather Conditions</h2>
									  </td>
									</tr>
								  </table>
								</td>
							  </tr>
							  <tr>
								<td class='pattern' width='600' align='center' style='background-color: #f6f6f6; font-size: 14px;'>
								  <table cellpadding='0' cellspacing='0' style='border-spacing:0;'>
									<tr>
									  <td class='col text-center' width='298' style='padding:2px 4px;text-align:center;'>
										<table cellpadding='0' cellspacing='0' style='border-spacing:0;'>
										  <tr class='aligncenter' style='clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'>
											<img src='http://maps.googleapis.com/maps/api/staticmap?center={$mainform['gps_latitude']},{$mainform['gps_longitude']}&zoom=13&scale=false&size=200x200&maptype=roadmap&format=png&visual_refresh=true&markers=size:mid|color:0xff0000|label:|{$mainform['gps_latitude']} {$mainform['gps_longitude']}' alt='Google Map' style='border:0;margin:0 auto;width:100%;height:auto;'/>
														</tr>
										</table>
									  </td>
									  <td class='spacer' width='4' style='padding:2px 4px;font-size: 1px;'>&nbsp;</td>
									  <td class='col' width='298' style='padding:2px 4px;'>
										<table cellpadding='0' cellspacing='0' style='border-spacing:0;'>
										  <tr>
											<td style='padding:2px 4px;'>
                                              <p class='strong' style='font-weight:800;'>
											  Weather Conditions
															</p>
											 {$mainform['weather_conditions']}
															</td>
										  </tr>
										</table>
									  </td>
									</tr>
								  </table>
								</td>
							  </tr>
							  ";
                
                $mainform_body.="<tr>
                <td class='one-column red-bg drive' style='background-color:#ed1c24;color:white;padding:.5% 2%;'>
              <table width='100%' style='border-spacing:0;'>
                <tr>
                  <td class='inner-content-two text-center' style='padding:2px 4px;text-align:center;'>
                    <h2 class='teal' style='color:white;font-size:16px;font-weight:bold;text-transform:uppercase;padding:1%;margin: 2px;'>Activity Description</h2>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class='one-column' style='padding:.5% 2%; background-color:#f6f6f6;'>
              <table width='100%' style='border-spacing:0;'>
                <tr>
                  <td class='inner-content-two' style='padding:2px 4px;'>
                    <table align='left' style='border-spacing:0;width:100%;display:block;'>";

        

        foreach($mainothers as $mkey=>$mval){
         $label = ucwords(str_replace("_", " ", $mkey));  
          $mainform_body.="<tr style='text-align: left'>
                   <td class='strong' style='padding:2px 4px;font-weight:800;width:40%;'>$label</td>
                    <td style='padding:2px 4px;width:60%;'>
                    $mval
                    </td>
                    </tr>";
        }

        $mainform_body.= "</table>
            </td>
          </tr>
        </table>
     </td>
    </tr>";
    $reportsildeshow_body="";
    if(!empty($this->report_photos)) 
    {                
        $reportsildeshow_body.="<tr><td style='background-color:#f6f6f6;'>
        <p style='padding: 0% 5%;font-size: 14px;text-align:center;'>
                    <a style='color:#ed1c24;display:block;' href='{$this->sliderurl}'>Report Photo Slideshow</a>
                  </p>
        </td></tr>";
    }
   
  
                
                $subform  = $this->prepareSubform($restofform);
                $is_photo_insubform = $this->checkAllSubformphoto($subform);
 	            $subform_body = "<tr class='page-breaker'></tr>";
                   if(!empty($subform)){
                    foreach($subform as $item){
                        $subform_photos = array();
                        $nophotos = false;
                 
                        if (array_key_exists('nophotos', $item)) { // if no photos
                                $nophotos = true;
                        }elseif(!$is_photo_insubform){
                             $nophotos = true;
                        }    
                                 $this->subformtitle = $item['form_title'];
                                 $subform_body.="<tr>
                                    <td class='one-column test drive' style='background-color:#ed1c24;color:white;padding:.5% 2%;'>
                                        <table width='100%' style='border-spacing:0;'>
                                        <tr>
                                            <td class='inner-content-two text-center' style='padding:2px 4px;text-align:center;'>
                                            <h2 class='teal' style='color:white;font-size:16px;font-weight:bold;text-transform:uppercase;padding:1%;margin: 2px;'>{$item['form_title']}</h2>
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>";
                            if($nophotos)
                            {
                             $subform_body .= "<tr>
                                        <td class='one-column' style='padding:.5% 2%;background-color:#f6f6f6;'>
                                        <table width='100%' style='border-spacing:0;'>
                                        <tr>
                                        <td class='inner-content-two' style='padding:2px 4px;'>
                                        <table align='left' style='border-spacing:0;width:100%;display:block;'>";
                                
                            }
                           else
                           {
                             $subform_body.="<tr>
                                    <td class='pattern' width='600' align='center' style='background-color: #f6f6f6;'>
                                    <table cellpadding='0' cellspacing='0' style='border-spacing:0;'>
                                    <tr>
                                    <td class='col' width='298' style='padding:2px 4px;'>
                                        <table cellpadding='0' cellspacing='0' style='border-spacing:0;width:100%;display:block;'>
                                        <tr>
                                            <td style='padding:2px 4px;padding: 0;'>
                                            </td>
                                    </tr>";
                           }
                          
                            
                            foreach($item as $key=>$value)
                            {
                                $label = ucwords(str_replace("_", " ", $key));
                                if(!is_array($value))
                                {  
                                    if(strpos($key, 'photo') === false&&$key!='form_title')
                                    {
                                        $subform_body.="<tr>
                                                <td class='strong' style='padding:2px 4px;font-weight:800;width:40%;'>
                                                $label
                                                </td>
                                                <td style='padding:2px 4px;width:60%;'>
                                                $value
                                                </td>
                                                </tr>";
                                        
                                    }
                                }
                                elseif(strpos($key, 'photo') !== false)
                                {
                                    $subform_photos[$key]=$value;
                                }
                            }
                        
                            $subform_body.="</table>
                                    </td>
                                     ";
                            
                            
                            if(isset($subform_photos))
                            {
                               
                                foreach($subform_photos as $pval)
                                {
                                     $i=0;
                                    foreach($pval as $key=>$value)
                                    {   
                                        $photoval = $value['record'];
                                        $awskey = 0;
                                        foreach($this->report_photos as $repkey => $repval)
                                        {
                                            if ( $repval['url'] === $photoval['take_and_annotate_photo'] )
                                            {
                                                 $awskey = $repkey; 
                                            }
                                       }  
                                        $aws_url = $this->sliderurl."#/$awskey";
                                        if($i==0)
                                        {
                                                $subform_body.=" <td class='spacer' width='4' style='padding:2px 4px;font-size: 1px;'>&nbsp;</td>
                                                         <td class='col text-center' width='298' style='padding:2px 4px;text-align:center;'>
                                                            <table cellpadding='0' cellspacing='0' style='border-spacing:0;'> 
                                                             <tr>
                                                                <td class='text-center' style='padding:2px 4px;text-align:center;'>
                                                                <a href='{$aws_url}' class='aligncenter' style='color:#ed1c24;clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'><img src='{$photoval['take_and_annotate_photo']}' width='225' height='auto' style='border:0;margin:0 auto;'/></a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td  style='padding:2px 4px;text-align:left;'>
                                                                <p>{$photoval['photo_caption']}
                                                                </p> 
                                                                </td>
                                                            </tr>
                                                            </table>
                                                        </td></tr>";
                                   
                                         }else{
                                            
                                            $subform_body.="<tr><td class='col' width='298' style='padding:2px 4px;'>&nbsp;</td>
                                            <td class='spacer' width='4' style='padding:2px 4px;font-size: 1px;'>&nbsp;</td>
                                                         <td class='col text-center' width='298' style='padding:2px 4px;text-align:center;'>
                                                            <table cellpadding='0' cellspacing='0' style='border-spacing:0;'> 
                                                             <tr>
                                                                <td class='text-center' style='padding:2px 4px;text-align:center;'>
                                                                <a href='{$aws_url}' class='aligncenter' style='color:#ed1c24;clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'><img src='{$photoval['take_and_annotate_photo']}' width='225' height='auto' style='border:0;margin:0 auto;'/></a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td  style='padding:2px 4px;text-align:left;'>
                                                                <p>{$photoval['photo_caption']}
                                                                </p> 
                                                                </td>
                                                            </tr>
                                                            </table>
                                                        </td></tr>";
                                        }
                                        $i++;
                                }
                                
                            }
                            }
                        
                        $subform_body.="
              </table>
            </td>
          </tr>"; 
                                
                    }
                          
                    
                }
         
     $mainphotos_body="";    
     if(!empty($mainphotos))
    {
       $main_key_photo = key($mainphotos);// to get the label
       if(!empty($main_key_photo))
       {
         $phlabel = key($mainphotos);
         $photo_label = ucwords(str_replace("_", " ", $phlabel)); 
       }
        else
        {
            $photo_label = "Activity Photos";
        }
       $mainphotos_body="<tr>
            <td class='one-column red-bg drive' style='background-color:#ed1c24;color:white;padding:.5% 2%;'>
              <table width='100%' style='border-spacing:0;'>
                <tr>
                  <td class='inner-content-two text-center' style='padding:2px 4px;text-align:center;'>
                    <h2 class='teal' style='color:white;font-size:16px;font-weight:bold;text-transform:uppercase;padding:1%;margin: 2px;'>$photo_label</h2>
                  </td>
                </tr>
              </table>
            </td>
          </tr>";
         foreach($mainphotos as $pval){
           $last_key =0;
          foreach($pval as $key=>$value){
              
      
           $photoval = $value['record'];
           $awskey = 0;
           foreach($this->report_photos as $repkey => $repval)
           {
                if ( $repval['url'] === $photoval['take_and_annotate_photo'] ){
                    $awskey = $repkey;
                }
                
            }  
            $aws_url = $this->sliderurl."#/$awskey";
              
              
             if($key%2==0){
            $mainphotos_body.="
                  <tr>
            <td class='pattern' width='600' align='center' style='background-color: #f6f6f6;'>
              <table cellpadding='0' cellspacing='0' style='border-spacing:0;'>
                  <tr>
                  <td class='col' width='100%' style='padding:2px 4px;'>
                    <table cellpadding='0' cellspacing='0' width='600' style='border-spacing:0;'>
                      <tr>
                        <td style='padding:2px 4px;padding: 0;'>
                        </td>
                      </tr>
                    <tr>";
            }
            $mainphotos_body.="<td class='col text-center' width='50%' style='padding:2px 4px;text-align:center;'>
                    <table cellpadding='0' cellspacing='0' width='100%' style='border-spacing:0;background-color:#eeeeee;'>
                      <tr>
                        <td class='text-center' style='padding:2px 4px;text-align:center;'>
                          <a href='{$aws_url}' class='aligncenter' style='color:#ed1c24;clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'><img src='{$photoval['take_and_annotate_photo']}'  width='225' height='auto' style='border:0;margin:0 auto;width:225px;height:auto;'/></a>
                        </td>
                      </tr>
                      <tr>
                        <td  style='padding:1% 3% 1% 5%;text-align:left;'>
                         <p>{$photoval['photo_caption']}
                            </p> 
                        </td>
                      </tr>
                    </table>
                  </td>";
               
                                 
           if($key%2==1){
               $mainphotos_body.="</tr>
                        </table>
                            </td>
                            </tr>
                            </table>
                            </td></tr>";
            }
              $last_key = $key;
        }
        if($last_key%2==0){
               $mainphotos_body.="</tr>
                        </table>
                            </td>
                            </tr></table>
                            </td></tr>";
            }
                
  
    }
    }
             
     $body = $basebody.$mainform_body.$reportsildeshow_body.$subform_body.$mainphotos_body;
     $this->print_base_body = $basebody.$mainform_body.$subform_body.$mainphotos_body;    
     return $body;
    


} //function
    
    

    
} //class


?>
