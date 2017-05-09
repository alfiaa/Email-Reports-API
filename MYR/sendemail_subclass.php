<?php
/*
This php script is called after the iformbuilder form is submitted.
It receives the json post as input, parses it and creates
an array to send as an input during the api call.It calls the appropriate
functions.
@author  alfiaa
@version 1
*/

include_once '../controllers/Email.php';
require 'PostData_MYR.php';

// getting the post sent from iformbuilder

$json_post = file_get_contents('php://input');

$log = false;
$msg = '';
if(!empty($json_post))
{
      $post = new PostData_MYR();
      $post->jsonpost = $json_post;
      $post->setter("jsonpost", $json_post);
      $post->setter("template", 2);
      $post->setter("email_trigger",true);
      $post->setter("emailalert_recipients","vinod@thinkpowersolutions.com,LBorak@crosstexas.com");
     $post->sendEmail();
 
}

else{
	$log = true;
    $msg .=" No post data";

}

if($log){
	  $email = new Email();
	  $email->to = "alfiaa@thinkpowersolutions.com";
	  $email->subject = "Email Api Error!";
	  $email->body = $msg;
	  $email->SendErrorEmail();
}
?>
