<?php
class ASPFdraw {

	
	
	//This checks the page to see if its a feedback project and renders the grid etc if so
	public static function drawProjectPage( $theContent )
	{
		global $post;
		

		

		$projectID = get_the_ID();
		$thisPostType = get_post_type( $projectID );
		
		
		// Only modify if front end is a feedback project
		if($thisPostType=="peer_projects") 
		{
			$preview=false;
			if(isset($_GET['preview']))
			{
				$preview=true;
			}
			
			
			$currentFeedbackUserID = $_GET['userID'];	
			$currentPassword = $_GET['password'];
			
	
			// Get the user info and check against the password
			$thisUserInfo = peerFeedback_Queries::getUserFromID($currentFeedbackUserID);
			$checkPassword = $thisUserInfo['password'];
			$thisGroupID= $thisUserInfo['groupID'];		
			
			if($checkPassword<>$currentPassword)
			{
				return 'You don\'t have access to this page';	
			}			
			
			
			// First add the CSS regardlress of if they can view it
			// This prevents the 'next project' link being shown
			?>
			<style>
			.entry-meta, .post-navigation {
				display:none;
			}
			</style>                
			<?php

			
			$project_status = get_post_meta($projectID,'project_status',true);
			$feedback_status= get_post_meta($projectID,'feedback_status',true);				
			$allow_self_review= get_post_meta($projectID,'allow_self_review',true);				
			$feedbackType = get_post_meta($projectID,'feedbackType',true);
			$distributionPoints= get_post_meta($projectID,'distributionPoints',true);
			$endDate= get_post_meta($projectID,'endDate',true);
			$anon_feedback= get_post_meta($projectID,'anon_feedback',true);			
			
			
			// Check if its available
			if($project_status<>1 && $preview==false)
			{
				$theContent='This feedback project is not available';
				return $theContent;
			}
			
			
			// Now check if they are a student attached to a group								
			// Get the group array of students for this user
			$args = array
			(
				'userID' =>$currentFeedbackUserID,
				'projectID' =>$projectID,					
				
			);
			$myStudents = peerFeedback_Queries::getMyGroupStudents ($args);
			
			// If they are not in the group then the this count will be zero.
			
			$studentCount = count($myStudents);
			
			// Add some javascript to the total number of expected students
			if($allow_self_review=="on")
			{
				$expectedSubmissions = $studentCount;
			}
			else
			{
				$expectedSubmissions = $studentCount-1;

			}
			$theContent.= '<script>
			var expectedResponses = '.$expectedSubmissions.';			
			</script>';			
			
			if($studentCount==0)
			{
				$theContent.= 'No students found';
			}
			else
			{
				
				// Check if they can view the feedback yet.					
				if($feedback_status==1)
				{
					
					$args=array
					(
						"projectID"		=>$projectID,
						"targetUserID"	=> $currentFeedbackUserID,
						"feedbackType"	=> $feedbackType,
						"anon_feedback"	=> $anon_feedback,
						"groupID"		=> $thisGroupID
					);
					return ASPFdraw::drawMyFeedback($args);	
				}
				
				
				if($endDate)
				{
					$endDate = new DateTime($endDate);
					$current_date = new DateTime();
					
					if ($endDate < $current_date)
					{
					  return 'Feedback is now closed';
					}
				}
				
				
				if ($feedbackType=="distribution")
				{
					$theContent.= 'Please distribute <b>'.$distributionPoints.'</b> across your group<br/>';
					$theContent.= '<div id="remainingPoints"></div>';
				}

				$currentStudent=1;
				// Go through the ordeded array and spit out results
				foreach($myStudents as $userInfo)
				{
					$firstName = $userInfo['firstName'];
					$lastName = $userInfo['lastName'];
					$userID = $userInfo['userID'];
					$email= $userInfo['email'];
					
					$showStudent=true;
					
					if($userID==$currentFeedbackUserID)
					{		
						$showStudent = false;					
						if($allow_self_review=="on")
						{
							$showStudent=true;	
						}
					}
					
					if($showStudent==true)
					{
						
						$theContent.='<div class="peerFeedbackStudentDiv"><h3>'.$currentStudent.'. '.$firstName.' '.$lastName.'</h3>';
						switch ($feedbackType)
						{
							case "textOnly":
								include( PFEEDBACK_PATH . 'front_forms/text-only.php' );
							break;
							
							case "distribution":
								include( PFEEDBACK_PATH . 'front_forms/distribution.php' );
							break;
							
							case "rubric":
								include( PFEEDBACK_PATH . 'front_forms/rubric.php' );
							break;						
						}

						$currentStudent++;
						
						$theContent.='</div>'; // Close the peer feedback student div
					}
				} // End of students loop
				
				
				// Add Custom JS for type of feedback
				
				switch ($feedbackType)
				{
					case "distribution":
						// JS for showing the feedback of submission
						echo '<script>';
						echo '
							jQuery(document).ready(
							
								function(){
									jQuery( "#submitButtonDistribution" ).click(function() {
									  jQuery( "#distributeSubmitButtonDiv" ).hide( "fast", function() {
										// Animation complete.
									  });
									});
									
									jQuery( "#submitButtonDistribution" ).click(function() {
											  jQuery( "#distributeSubmitFeedbackDiv" ).show( "fast", function() {
												// Animation complete.
											  });
											});								
								});	
							';							
						
						echo '</script>';					
						
						// Single FEedback button for ALL users
						$clickAction='ajaxFeedbackDistributionUpdate(\''.$currentFeedbackUserID.'\', \''.$projectID.'\')';						
						$theContent.='<div id="distributeSubmitButtonDiv" ';
						if(!is_array($myData))
						{
							$theContent.='style="display:none"';

						}
						$theContent.=' >';
						$theContent.='<a class="pure-button pure-button-primary" onclick="javascript:'.$clickAction.'" id="submitButtonDistribution" >Submit Feedback</a>';					
						$theContent.= '</div>';
						$theContent.='<div id="distributeSubmitFeedbackDiv" class="feedbackSuccess" style="display:none">';
						$theContent.='Feedback Saved';
						$theContent.='</div>';	


					break;		
					
					case "rubric":
						echo '<script>';
						echo "		
						jQuery( document ).ready( function () {
							jQuery('.td-toggle').on( 'click', function ( e ) {
								
								jQuery( this ).find('input:radio').prop('checked', true); // Allow the whole TD to be clicked	
								
								var parent_tr	= jQuery( this ).parent();
								var tds 		= jQuery( parent_tr ).children();
								
								jQuery( tds ).removeClass( 'td-highlight' ); // Remove ALL highlights from tds in this row
								jQuery( tds ).removeClass( 'td-green-highlight' ); // Remove ALL green highlights from tds in this row
								jQuery( this ).addClass( 'td-highlight' ); // Add the highglight just to this cell

								// HIDE the 'feedback saved' message if they select something else after saving
								var thisElementID = jQuery( this ).find('input:radio').attr('id');
								// Get the targetUserID
								var tempArray = thisElementID.split('_');
								// The target User ID is the first element of the temp array
								var targetUserID = tempArray[1];
								

								
								jQuery( '#feedbackResponse_'+targetUserID ).hide( 'fast');		
								
								
								
							});
						});
						
						";
						echo '</script>';						
					break;		
				}// End of additional JS custom switch case
				
				
			}
			

		}
			
			
		// Finally add the complete poopup
		$theContent.=ASPFdraw::drawPeerFeedbackCompletePopup();
		
		return $theContent;
	}
	
	
	
	
	// Draws the main Rubric Table either as edit field, or read only
	public static function drawRubricTable($args)
	{
		$str=""; // Craete blank str to return
		
		$myData = ""; // Define blank var for data they have stored about other people
		
		// Create blank response option lookup array for allocating correct descriptors	
		$tempResponseOptionArray = array();
		
		// Create blank response Array
		$masterUserDataArray = array();
		
		// Interpret the args
		$projectID = $args['projectID'];
		$readOnly = $args['readOnly'];
		$userFeedbackID = $args['userFeedbackID'];
		$userID = $args['userID'];	
		
		

		if($readOnly==false)
		{
			// See if they've already stored data for this individual
			$args = array
			(
				"projectID" => $projectID,
				"userID" => $userID,
				"targetUserID" => $userFeedbackID				
			);
			$myData = peerFeedback_Queries::getUserFeedbackForTargetUser ($args); // Check against this for individual data entries
			
			// Create array using target user ID as the key and option IDs as second key
			$masterUserDataArray = array();
			
			if(is_array($myData))
			{
				foreach ($myData as $tempData)
				{
					$targetUserID =  $tempData->targetUserID;
					$criteriaID = $tempData->feedbackText;
					$userResponse= $tempData->feedbackValue;	
					$masterUserDataArray[$targetUserID][$criteriaID] = $userResponse;
				}
			}
		}
	
		
		// Get all project descriptors and put into an array
		$args = array('projectID'=> $projectID);
		$projectDescriptors = peerFeedback_Queries::getProjectCriteriaDescriptors ($args);	
		
		
		// Get the Response Options
		$args = array(
			'projectID' => $projectID
		);
		$myOptions = peerFeedback_Queries::getProjectResponseOptions ($args);
		
		
		// Get the Criteria
		$args = array(
			'projectID' => $projectID
		);
		$myCriteria = peerFeedback_Queries::getProjectCriteria ($args);
		
		if(!$myCriteria)
		{
			return 'No Criteria Found';	
		}
		
		
		
		$str.='<div id="submitTableDiv_'.$userFeedbackID.'">';	
		
			
		$str.= '<table class="rubricTable" id="rubricTable_'.$userFeedbackID.'">';
		
		if($myOptions<>false)
		{
		
			$responseOptionCount = count($myOptions);
			$str.= '<tr><th></th>';
			foreach($myOptions as $optionInfo)
			{
				$responseOption = $optionInfo->responseOption;
				$optionID= $optionInfo->optionID;
				$str.= '<th>'.$responseOption.'</th>';
				$tempResponseOptionArray[] = $optionID;
			}
			
			$str.= '</tr>';
		}
		
		if($myCriteria<>false)
		{
			foreach($myCriteria as $criteriaInfo)
			{
				$str.= '<tr>';		
				$criteria = $criteriaInfo->criteria;
				$criteriaID= $criteriaInfo->criteriaID;
				$str.= '<td>'.$criteria.'</td>';
				
				$currentRO=0; // Set the current Response Option ticker to 0 = use to lookup in the descriptors array
				
				// Get the current saved response if it exsits
				$checkResponse = "";
				if (array_key_exists($userFeedbackID, $masterUserDataArray))
				{				
					$checkResponse = $masterUserDataArray[$userFeedbackID][$criteriaID];
				}




				while ($currentRO<$responseOptionCount)
				{
					$tempOptionID = $tempResponseOptionArray[$currentRO];
					$str.='<td class="td-toggle rubricTableClickable';
					if(is_array($myData))
					{
						if($checkResponse==$tempOptionID){$str.= ' td-green-highlight ';} // Add a green highlight if its saved
					}	
					
					$str.='">';
					
					
					$str.= '<input type="radio" id="option_'.$userFeedbackID.'_'.$criteriaID.'_'.$tempOptionID.'" name="feedback_'.$criteriaID.'_'.$userFeedbackID.'" value="'.$criteriaID.'_'.$tempOptionID.'"';
					if($checkResponse==$tempOptionID){$str.= ' checked ';} // Check the radio button if its been saved
					$str.='><br/>';
		
					$descriptor = $projectDescriptors[$criteriaID][$tempOptionID];
					$str.= '<label for="option_'.$userFeedbackID.'_'.$criteriaID.'_'.$tempOptionID.'"  class="descriptor">'.$descriptor.'</label>';
					$str.= '</td>';
					$currentRO++;
				}
				$str.= '</tr>';		
			}
		}
		$str.= '</table>';
		
		// Add hidden input value for checking if all responses have ben given		
		$str.= '<input type="hidden" value="" id="checkFinished'.$userFeedbackID.'" name="check'.$userFeedbackID.'">';
		
		
		
		if($readOnly==false)
		{
		
			// Add the ajax call		
			$clickAction='ajaxFeedbackRubricUpdate(\''.$userFeedbackID.'\', \''.$userID.'\', \''.$projectID.'\')';
			
			$str.='<br/><a class="pure-button pure-button-primary"/ onclick="javascript:'.$clickAction.'">Submit Feedback</a>';
			$str.='</div>'; // End of div for entire table form wrap
			
			$str.='<div id="notCompleteMessage_'.$userFeedbackID.'" class="feedbackFail" style="display:none">You have not rated all criteria for this student</div>';
			
			$str.='<div id="feedbackResponse_'.$userFeedbackID.'" class="feedbackSuccess" ';
			
			// If data has already been saved show the feedback notice by default
			if(!is_array($myData))
			{	
				$str.='style="display:none"';
			}

			
			$str.='>Feedback Saved!</div>';
			
			
			$str.='<br/><hr/>';		
		}
		
		
		
		return $str;
		
	}	
	
	
	
	public static function drawMyFeedback($args)
	{
		$str="";
		$targetUserID	= $args['targetUserID'];
		
		// GEt the User Info
		$userInfo  = peerFeedback_Queries::getUserFromID($targetUserID);
		$groupID = $userInfo['groupID'];
		
		// Get the Group Info		
		$args = array("groupID" => $groupID);
		$groupInfo = peerFeedback_Queries::getGroupInfo($args);		
		$projectID = $groupInfo->projectID;
		
		// Get the Project Info
		$feedbackType = get_post_meta($projectID,'feedbackType',true);

		$anon_feedback = get_post_meta($projectID,'anon_feedback',true);		
		
		
		$str.='<h2>'.$userInfo['firstName'].' '.$userInfo['lastName'].'</h2>';		
		
		$dataArgs = array
		(	
			"targetUserID" => $targetUserID
		);
		$myFeedback = peerFeedback_Queries::getAllUserFeedback($dataArgs);
		
		if(!$myFeedback)
		{
			return 'Nobody has given you any feedback';
		}		
		
		switch ($feedbackType)
		{
			
			
			case "distribution":
			
				// Get total number of marks that they distributed
				$distributionPoints = get_post_meta($projectID,'distributionPoints',true);
				
				// GEt all the usersin the group and get their totla points for each				
				$args = array
				(
					"groupID" => $groupID,
				);
				$studentsInGroup = peerFeedback_Queries::getUsersInGroup($args);
				
				// Create blank array to store the totals with the userID as key
				$tempDataCountArray = array();
				$tempDataMarkArray = array();
				
				$studentCount = count($studentsInGroup);				
				
				foreach($studentsInGroup as $tempUserInfo)
				{
					$tempUserID = $tempUserInfo['userID'];
					$firstName= $tempUserInfo['firstName'];	
					$lastName= $tempUserInfo['lastName'];										
					$dataArgs = array
					(	
						"targetUserID" => $tempUserID
					);					
					$tempFeedback = peerFeedback_Queries::getAllUserFeedback($dataArgs);
					
					$feedbackCountPerStudent = count($tempFeedback);
					$fudgeFactor = $studentCount/$feedbackCountPerStudent; ;// Fudge Factor PA style
					
					
					// Got through this array and count up total of distriubtion points					
					$tempTotal=0;
					$tempMark = 0; // This is the WEIGHTING PA style
					$str.= '<b>'.$firstName.' '.$lastName.'</b><br/>';

					foreach($tempFeedback as $feedbackData)
					{
						$thisCount = $feedbackData->feedbackText;
						
						$thisMark = $thisCount/$distributionPoints;
						$tempMark = $thisMark + $tempMark;
						
						$tempTotal = $tempTotal + $thisCount;
					}
					
					
					
					$tempDataCountArray[$tempUserID] = $tempTotal;
					$PA_style_weighting = round(($tempMark * $fudgeFactor), 2);
					$tempDataMarkArray[$tempUserID] = $PA_style_weighting;
					
					$str.= 'Weighting  = '.($PA_style_weighting * 100).'%<hr/>';
					
				}
				
				

			
			
			break;
			
			
			
			
			
			case "textOnly":
			
				if($anon_feedback=="on")
				{
					// Shuffle the array
					shuffle($myFeedback);
						
				}


				$currentFeedbackNo=1;
				foreach($myFeedback as $feedbackInfo)
				{
					$thisResponse = $feedbackInfo->feedbackText;
					$thisResponse = peerFeedback_Utils::processDatabaseText($thisResponse);
					
					
					$thisUserID = $feedbackInfo->userID;
					
					$str.='<div class="feedbackTextResponse">';
					
					if($anon_feedback<>"on")
					{
						// Get this user details
						$thisUserInfo = peerFeedback_Queries::getUserFromID($thisUserID);
						$firstName = $thisUserInfo['firstName'];
						$lastName = $thisUserInfo['lastName'];
						
						$str.= '<h3>Feedback from '.$firstName.' '.$lastName.'</h3>';
					}
					else
					{
						$str.='<h3>Feedback '.$currentFeedbackNo.'</h3>';
					}
					
					$str.='<blockquote><p>'.$thisResponse.'</p></blockquote>';
					
					$str.='</div>';
					$currentFeedbackNo++;					

				}
			
			break;
			
			
			
			case "rubric":
			
				// Loadup the scripts for google charts
				global $ASPF_gCharts;
				$ASPF_gCharts->enqueueScripts();
				
				
				// Now go through all the feedback and tally up the totals		
				$feedbackTotalsArray = array();


				foreach($myFeedback as $feedbackInfo)
				{
					$thisCriteriaID = $feedbackInfo->feedbackText;
					$thisResponse = $feedbackInfo->feedbackValue;
					$thisUserID = $feedbackInfo->userID;			
					$feedbackTotalsArray[$thisCriteriaID][$thisResponse][]=$thisUserID;
				}
				
				// Get the response Options and add to array
				$args = array
				(
					"projectID" => $projectID
				);
				$responseOptions = 	peerFeedback_Queries::getProjectResponseOptions ($args);
				
				// Get the crtieria
				$args = array
				(
					"projectID" => $projectID
				);	
				
					
				$projectCriteria = 	peerFeedback_Queries::getProjectCriteria ($args);
				$maxValue=0; // This will determine how big the chart is. Just keep a record of the max response
				
				foreach($projectCriteria as $criteriaInfo)
				{
					$chartData = array(); // Create blank Array for the data
					
					$criteriaID = $criteriaInfo->criteriaID;
					$criteria = $criteriaInfo->criteria;
					
					$str.= '<h3>'.$criteria.'</h3>';
					
					foreach($responseOptions as $optionInfo)
					{
						$optionID = $optionInfo->optionID;
						$responseOption = $optionInfo->responseOption;
						$optionLookupArray[$optionID] = $responseOption;
						
						// Get the number of response options in the master feedback array
						
						
						$tempFeedbackCheckCount=0;
						if (array_key_exists($optionID, $feedbackTotalsArray[$criteriaID]))
						{
							$tempFeedbackCheckCount = count($feedbackTotalsArray[$criteriaID][$optionID]);						
						}
						
						
						if($tempFeedbackCheckCount>=$maxValue){$maxValue=$tempFeedbackCheckCount;}
//						$str.= 	'['.$tempFeedbackCheckArray.'] '.$responseOption.'<br/>';
						$chartData[] = array($responseOption, $tempFeedbackCheckCount);						
					}
					
					
					
					$chartsArgs = array
					(
						"chartType" => 'bar',
						"data"		=> $chartData,
						"keyName"	=> 'Criteria',
						"valName"	=> 'Number of Votes',
						"title"		=> 'Peer Feedback',
						"elementID"	=> 'crtieriaChart'.$criteriaID,
						"width"		=> '80%',
						"height"	=> '200px',
						"maxValue"	=> $maxValue
					
					);
					
					$str.= $ASPF_gCharts->draw( $chartsArgs );
					
					
				}				
			
			break;
		}
		
		return $str;
		
	}
	
	
	//---
	public static function drawPeerFeedbackCompletePopup () 
	{
		echo '<!-- POPUP FOOTER -->';
		
		
		$html = '';
		
		
		$html .= '<div id="peerFeedbackCompletePopup" style="display:none;">';

		$html .= 	'<h3>Thank you!</h3>';
		$html .= 	'You have now completed this peer feedback task and can close this browser window';
		$html .= 	'<br><br><button class="pure-button" id="pfPopupCloseButton">Close</button>';
		$html .= '</div>';
		
		return $html;
		
		?>
		<script>

		
		</script>
		<?php
	}	
	
	
	
	
}
?>