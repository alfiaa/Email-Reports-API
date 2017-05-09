<?php
/*
This class is used for sending email using PostMark.

@author alfiaa
@version 1
*/


require_once('/var/Auth/config.php');


class Email{

      private $to;
      private $from;
      private $subject;
	  private $body;
      private $failure_message="";
      private $attachment=null;


	/*
	 Constructor used for initializing the object.

	*/

	function __construct()
	{
      $this->from = "logs@thinkpowersolutions.com";
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
      /*
      This function is used for calling the postmark api to send email
      @return status code after api call.
      */
      public function sendEmailapi(){
       if(isset($this->attachment)&&!empty($this->attachment))
        {
            $json = json_encode(array(
	           'From' => "$this->from",
	           'To' => "$this->to",
	           'Subject' => "$this->subject",
	           'HtmlBody' => "$this->body",
	           'TextBody' => "This will be shown to plain-text mail clients",
                'Attachments'=>$this->attachment
	       ));
          
            
        }else{
           $json = json_encode(array(
	           'From' => "$this->from",
	           'To' => "$this->to",
	           'Subject' => "$this->subject",
	           'HtmlBody' => "$this->body",
	           'TextBody' => "This will be shown to plain-text mail clients"
	       ));
        }
	       $ch = curl_init();
	       curl_setopt($ch, CURLOPT_URL, 'http://api.postmarkapp.com/email');
	       curl_setopt($ch, CURLOPT_POST, true);
	       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	       curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	           'Accept: application/json',
	           'Content-Type: application/json',
	           'X-Postmark-Server-Token: ' . POSTMARK_API_KEY
	       ));
	       curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
	       $response = json_decode(curl_exec($ch), true);
	       $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	       curl_close($ch);
           if($http_code!==200)
            {
                $this->failure_message = $response['Message'];
             }
           return $http_code;
      }

       public function SendErrorEmail($msg)
       {
            $this->from = "logs@thinkpowersolutions.com";
            $this->to = "alfiaa@thinkpowersolutions.com,vinodr@thinkpowersolutions.com";
            $this->subject = "Error in Email Report API";
           /* if($msg=="Error in sending email")
            {
              $this->to = "alfiaa@thinkpowersolutions.com,vinodr@thinkpowersolutions.com";
            }*/
            $this->body = $msg;
            $this->sendEmailapi();
	        $this->updateLog($msg);
	    }


       public function UpdateLog($err_msg){
            $file = '../errorlogs/email_log.txt'; //tmp file
		    file_put_contents($file, "\n Error in Email Report API  $err_msg\n", FILE_APPEND | LOCK_EX);


       }

    }