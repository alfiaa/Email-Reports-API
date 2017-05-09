<?php
/* This is a subclass which estends Postdata used to sending email reports*/

require 'PostData.php';
include_once  '../controllers/Email.php';

class PostData_CNP extends PostData{

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
         /* $this->recipients = "alfiaa@thinkpowersolutions.com,vinodr@thinkpowersolutions.com";
		*/
         if(!empty($this->jsonarray))
	        {
		       $this->recipients = $this->jsonarray['email_reports'];
                unset($this->jsonarray['email_reports']);
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
       $headerlogo = "<a href='http://thinkpowersolutions.com/' class='aligncenter' style='color:#ed1c24;clear:both;display:block;margin-left:auto;margin-right:auto;text-align:center;padding:1% 3%;'>
                            <img src='https://logs-reports.thinkpowersolutions.com/logo-big.jpg' width='300' alt='' style='border:0;margin:0 auto;display: inline-block; max-width: 400px; margin: 4px auto;'/></a>";
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
	      $this->mainformbody();
          $body = parent::createBaseBody(2);
          $emailbody = $header.$body.$footer;
          $print_base_body = parent::__get("print_base_body");
          $pdfbody = $header.$print_base_body.$footer;
          
          $this->setter("print_base_body", $pdfbody);
          return $emailbody;

   }

        /*
    This method is used for parsing the data to separate the mainform, photos and subform data
    @return returns array that contains mainform, subform and photo data in separate arrays
    */
    public function prepareMainform()
    {
        $jsonarray = parent::__get("jsonarray");
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($jsonarray), RecursiveIteratorIterator::SELF_FIRST);
        $restofform =$mainform = $mainphotos = $mainothers= array();
        $main_form_keys = array("form_title","field_employee","report_date","project","project_id","master_technician","wo");

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

    /*Creating main form body for the subclass*/

    public function mainformbody()
    {
             list($mainform,$mainphotos,$mainothers,$restofform) = $this->prepareMainform();
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
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>Report Date</td>
											<td style='padding:2px 4px;width:70%;'> {$mainform['report_date']}</td>
										  </tr>
										</table>
									  </td>
									  <td class='spacer' width='4' style='padding:2px 4px;font-size: 1px;'>&nbsp;</td>
									  <td class='col inner' width='298' style='padding:2px 4px;padding: 10px;'>
										<table cellpadding='0' cellspacing='0' style='border-spacing:0;width:100%;display:block;'>

										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>
											  Project Id
															</td>
											<td style='padding:2px 4px;width:70%;'>
											  {$mainform['project_id']}
															</td>
										  </tr>
										  <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>
											  Master Technician
															</td>
											<td style='padding:2px 4px;width:70%;'>
											 {$mainform['master_technician']}
															</td>
										  </tr>
                                          <tr>
											<td class='strong' style='padding:2px 4px;font-weight:800;width:30%;'>
											  Project WO
															</td>
											<td style='padding:2px 4px;width:70%;'>
											 {$mainform['wo']}
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
       $this->setter("subclass_mainbody", $mainform_body);
       $this->setter("subclass_restofform", $restofform);
       $this->setter("subclass_mainphotos", $mainphotos);
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
        parent::__set("profile", "CNP"); 
        parent::__set("record_id", $record_id);
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
	      $email->SendErrorEmail($record_info." Error in sending email in CNP Substation. Error code $sent. Error Message is ".$email->failure_message);
	    }
     //   parent::createPDF();
        }
}
