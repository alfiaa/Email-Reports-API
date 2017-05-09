<?php
/*
This php script is called after the iformbuilder form is submitted.
It receives the json post as input, parses it and creates 
an array to send as an input during the api call.It calls the appropriate 
functions in the class to save the project entry data received in the 
project table and to update the option list.

@author  alfiaa
@version 1
*/

require '../controllers/SubstationProjects.php';
require  '../controllers/OptionLists.php';
require_once('../vardefs.php');


// getting the post sent from iformbuilder
$json_post = file_get_contents('php://input');

$err = false;
$err_msg = '';
$project_status = "On-going";

if(!empty($json_post))
{
	$result = json_decode($json_post,true);
	 if(!empty($result)){
          foreach($result as $row) {
            foreach($row['record'] as $sub) {
               if(is_array($sub)) {
                foreach($sub as $data) {
               	$project_name = $data['record']['project_name'];
				 $created_by=$row['record']['CREATED_BY'];
				 $modified_by =$row['record']['MODIFIED_BY'];
				 $completion_percentage=$data['record']['completion_percentage'];

				 // prepare json to update the lookup table
                   	$json_data = "{
	  			\"fields\": [
	    			{
	      				\"element_name\": \"projectname\",
	     				 \"value\": \"".$data['record']['project_name']."\"
	    			},
	   			{
	     				 \"element_name\": \"sitepad_construction_and_fence\",
	      				  \"value\": \"".$data['record']['sitepad_construction_and_fence']."\"
	    			},
	      			{
	      				\"element_name\": \"conduit_installed\",
	      				\"value\": \"".$data['record']['conduit_installed']."\"
	    			},
	    			{
	      				\"element_name\": \"switch_house\",
	      				\"value\": \"".$data['record']['switch_house']."\"
	    			},
	    			{
	      				\"element_name\": \"grounding\",
	      				\"value\": \"".$data['record']['grounding']."\"
	    			},
	    			{
	      				\"element_name\": \"steel_set\",
	      				\"value\": \"".$data['record']['steel_set']."\"
	    			},
	    			{
	      				\"element_name\": \"equipment_installed\",
	      				\"value\": \"".$data['record']['equipment_installed']."\"
	    			},
	    			{
	     				\"element_name\": \"bus_work_completed\",
	      				\"value\": \"".$data['record']['bus_work_completed']."\"
	    			},
	    			{
	      				\"element_name\": \"control_cables_pulled\",
	      				\"value\": \"".$data['record']['control_cables_pulled']."\"
	    			},
	    			{
	      				\"element_name\": \"yard_rocked\",
	      				\"value\": \"".$data['record']['yard_rocked']."\"
	    			},
                                {
	      				\"element_name\": \"concrete_foundations\",
	      				\"value\": \"".$data['record']['concrete_foundations']."\"
	    			},
	    			{
	      				\"element_name\": \"completion_percentage\",
	      				\"value\": \"".$data['record']['completion_percentage']."\"
	    			}
	  			]
				}";


           		}

        	}
          }
      	}
	}
		
		 
		$profile_id = $vardef['Oncor']['profile_id'];
		$form_id = $vardef['Oncor']['substation_progress_lookup'];
		$optionlist_id = $vardef['Oncor']['project_list'];
		
		// saving in the lookup table		 
		$sub_project = new SubstationProjects($profile_id, $form_id);
		$sub_project->json_data=$json_data;
		$sub_project->project_name=$project_name;
	    $project_field_name = "projectname";
     	$sub_project->saveSubstationProgressLookup($project_field_name); 
		
		//updating project status
		$form_id = $vardef['Oncor']['substation_open_projects'];
		$project = new Projects($profile_id, $form_id);
		
		if($completion_percentage<100){
               $project_status="On-going";
		}elseif($completion_percentage==100){
			 $project_status="Completed";
		}
		 $project->project_status=$project_status;
		 $project->project_name=$project_name;
         $json_data = "{
	  	  	  \"fields\": [
	  	    	{
	  	      	\"element_name\": \"project_status\",
	  	      	\"value\": \"".$project_status."\"
	  	     	}
	  	 	   ]
          }"; 
		 $project->json_data=$json_data; 
		 $project->updateProjectStatus();
        
		 // updating option list
		 
	    if($project_status=="Completed") 
        {    
		 $optionlist = new OptionLists($profile_id,$form_id);
		 $optionlist->optionlist_id=$optionlist_id;
		 $optionlist->option_label=$project_name;
		 $optionlist->deleteOption();
        }
		  
		
}
else{
	$err = true;
    $err_msg .=" No post data";

}

if($err){
	    mail("alfiaa@thinkpowersolutions.com","Error in API updating Progress Data/Status for TNMP",$err_msg);
        $today = date("Y-m-d H:i:s");
		$file = '../errorlogs/update_progress_and_status_log.txt'; //tmp file
        file_put_contents($file, "\n $today TNMP Error in API updating Progress Data/Status $err_msg\n", FILE_APPEND | LOCK_EX);

}
?>
