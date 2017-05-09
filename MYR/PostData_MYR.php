<?php
/* This is a subclass which estends Postdata used to sending email reports*/

require '../controllers/PostData.php';
include_once  '../controllers/Email.php';

class PostData_MYR extends PostData{
    
    private $jsonpost;
   	private $recipients;
	/*
	  The constructor is used for initializing the data.
	*/
	
	function __construct(){
		parent::__construct();
	} 
    
    /*
    Calls parent class setter
    */
    
    public function setter($property, $value) {
       parent::__set($property, $value);
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
    
     /* This function is used for setting recipients of the email from the json received*/

      public function setRecipients()
      {
         // $this->recipients = "alfiaa@thinkpowersolutions.com,vinodr@thinkpowersolutions.com";
              
         /*  if(!empty($this->jsonarray))
	        {
			    $this->recipients = $this->jsonarray['email_reports'];
                unset($this->jsonarray['email_reports']);

          	}
           */
          
          
          
      }
    
    /* This method is for getting the header of the email body
        which is stored in a file. 
        @return $header returns the header from the file
      */
   public function createBaseHeader()
   {
       $file = '../template/header.html';
       $header = file_get_contents($file);
       $headerlogo = "<a href='http://thinkpowersolutions.com/' class='aligncenter' style='color:#ed1c24;clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'>
                      <img src='https://logs-reports.thinkpowersolutions.com/clients/tps-lemyers-big.jpg' width='600' alt='' style='border:0;margin:0 auto;display: inline-block; max-width: 550px; margin: 4px auto;'/></a>";
       $this->setter("headerlogo", $headerlogo);
       return $header;
   }
    
       /*
    This method is used for constructing the body of the email
    @return emailbody returns the entire html email
    */
   public function createBody()
   {
          $header = $this->createBaseHeader();
	      $footer = parent::createBaseFooter();
          $body = parent::createBaseBody(2);
          $emailbody = $header.$body.$footer;
          $print_base_body = parent::__get("print_base_body");
          $pdfbody = $header.$print_base_body.$footer;
      
          $this->setter("print_base_body", $pdfbody);
          return $emailbody;

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
        $record_id = $data['ID'];
        parent::__set("record_id", $record_id); 
        parent::__set("profile", "MYR");
        parent::setErrorMessage($data);
        parent::parseJson($data);
	    parent::createSubject();
	    parent::setRecipients();
        parent::setfileUploadPath();
        parent::prepareReportPhotos();
        $sbody =  parent::createSlider();
        parent::uploadSlider($sbody);  
	    $email_body =  $this->createBody();
        $email = new Email();
        $this->recipients = parent::__get("recipients");
	    $email->to = $this->recipients;
        $email->subject = parent::__get("subject");
	    $email->body = $email_body;
        $sent = $email->sendEmailapi();
        $record_info = parent::__get("record_info");
   
        if($sent!==200){
	       $email->SendErrorEmail($record_info." Error in sending email. Error code $sent. Error Message is ".$email->failure_message);
	   }
        parent::createPDF(); 
          
              
        $email_trigger =  parent::__get("email_trigger");
        $upload_status =  parent::__get("upload_status");
        $upload_file = parent::__get("upload_file");
                 // trigger email after file upload
       if($email_trigger&&$upload_status=='OK'&&strpos($upload_file, 'Daily') === false)
       {
             parent::emailAlertOnUpload(); 
          
         } 
        }
}
