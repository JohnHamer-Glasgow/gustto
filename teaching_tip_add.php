<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
require_once(__DIR__.'/lib/formfunctions.php');
require_once(__DIR__.'/lib/constants.php');

$template = new templateMerge($TEMPLATE);

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';

$template->pageData['customCSS'] = '<script src="http://cdn.tinymce.com/4/tinymce.min.js"></script>
                                    <script>tinymce.init({ 
                                        selector:"textarea#inputTTPractice",
                                        theme:"modern",
                                        plugins: "link autolink image",
                                         
                                    });</script>';

$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

$_SESSION['url'] = $_SERVER['REQUEST_URI']; // current location (used for redirecting after login if user is not logged in)

if($uinfo==false)
{
	header("Location: login.php");
	exit();
}
else
{

	$username = $uinfo['uname'];
	$givenname = $uinfo['gn'];
	$surname = $uinfo['sn'];

    $edit = false;

	//User drop down
	$template->pageData['userLoggedIn'] = $givenname.' '.$surname ;
	$template->pageData['profileLink'] = "profile.php?usrID=".$loggedUserID;

    //Notifications
      if (notification::getNotifications($loggedUserID,false,0) == false) $notificationNo = 0;
      else $notificationNo = sizeof(notification::getNotifications($loggedUserID,false,0));
      $template->pageData['notificationNo'] = $notificationNo;
      $template->pageData['notifications'] = notifications($dbUser);

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
    }

    
    
	// Form validation and submission
	$errors = array();
	$title = $rationale = $description = $practice = $cond1 = $cond2 = $essence = $kwsString = ''; 
    $class_size = $environment = $suitable_ol = $it_competency = array(); 
	$keywords = array();

    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        // if ttID provided and logged user is the author of the teaching tip then set $edit = true (enable editing)
        if (isset($_GET['ttID']) && is_numeric($_GET['ttID']) && $_GET['ttID'] >= 0){
            $ttID = $_GET['ttID'];
            $tt = teachingtip::retrieve_teachingtip($ttID);
            $author = $tt->get_author();

            // check if archived
            if ($tt->archived == 1) {
                $template->pageData['content'] .= pageNotFound();
                echo $template->render();
                exit();
            }


            //contributor check

            $contrTTs = $user->get_contr_teaching_tips();
            $contr = false; // check if the logged user is contributor to display
            if (!empty($contrTTs)) {
                foreach($contrTTs as $cTT){
                    if($ttID==$cTT->id){
                      $contr = true; // contributed
                    }
                }
            }
            
            
            $isAuthor = $loggedUserID == $author->id;
            if ($isAuthor || $contr || $user->isadmin) {
                $edit = true;
                                

                $tt = teachingtip::retrieve_teachingtip($ttID);
                $title = $tt->title;
                $rationale = $tt->rationale;
                $description = $tt->description;
                $practice = $tt->practice;
                $cond1 = $tt->worksbetter;
                $cond2 = $tt->doesntworkunless;
                $essence = $tt->essence;
                $kws = $tt->get_keywords();
                $class_size = $tt->get_filters("class_size");
                $environment = $tt->get_filters("environment");
                $suitable_ol = $tt->get_filters("suitable_ol");
                $it_competency = $tt->get_filters("it_competency");
                
                if ($kws) {
                    foreach ($kws as $kw) $keywords[] = trim($kw->keyword);
                    $kwsString = implode(',', $keywords);
                }
                
                $saved_as_draft = ($tt->draft == 1) ? true : false;

                if ($saved_as_draft) {
                    $ttcontributors = $tt->get_contributors();
                    if ($ttcontributors) {
                        $contributorsEmail = array();
                        foreach ($ttcontributors as $ttcontr) $contributorsEmail[] = $ttcontr->email;
                    }
                }

            }
            else {
                header("Location: teaching_tip_add.php");
                exit();
            }
        }

        
    }

	if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();

        $update = false;
        $draft = false;           // true if it is a new TT that will be saved as a draft
        $saved_as_draft = false;  // true if TT is already saved as a draft and user wants to edit it

        // if a ttID is provided in the POST request then set $update = true
        if (isset($_POST['ttID']) && is_numeric($_POST['ttID']) && $_POST['ttID'] >= 0) {
            $ttID = $_POST['ttID'];
            $update = true;
        }

        // check if Draft
        if (isset($_POST['draft'])) $draft = true;

        // check if TT is saved as Draft
        if (isset($_POST['savedAsDraft'])) {
            $saved_as_draft = true;
            $tt = teachingtip::retrieve_teachingtip($ttID);
            if ($tt->author_id == $loggedUserID) $isAuthor = true;
        }

        if (!empty($_POST['contributors'])) {
            $contributorsEmail = $_POST['contributors'];
            $contributorsIDs = array();
            $contributorsEmails = array();  // to store email addresses for contributors that are not registered in the system

            // check every contributor email against the database
            foreach ($contributorsEmail as $index=>$contributorEmail) {
                $contributorEmail = sanitize_input($contributorEmail);
                if (!empty($contributorEmail)) {
                    $contributor = user::retrieve_user_matching('email', $contributorEmail)[0];

                    // if contributor email is in database add the contributor id (user id) in contributorsIDs array
                    // else add only the email address of the contributor
                    if ($contributor) {
                        // if contributor id is the same as the loggedUserID -> error
                        if ($loggedUserID == $contributor->id) $errors['contributor'][$index] = 'You cannot add yourself as a co-author';
                        else {
                            if (!in_array($contributor->id, $contributorsIDs)) $contributorsIDs[] = $contributor->id;
                            else $errors['contributor'][$index] = 'You entered the same co-author multiple times';
                        }
                    }
                    else {
                        // $errors['contributor'][$index] = 'The email address that you provided for this contributor does not exist in our database';

                        // Maybe check if the email address is a valid glasgow uni address and maybe send an email notification

                        if (!in_array($contributorEmail, $contributorsEmails)) $contributorsEmails[] = $contributorEmail;
                        else $errors['contributor'][$index] = 'You entered the same co-author multiple times';

                    }
                } else {
                    $errors['contributor'][$index] = 'Please provide an email address for this co-author';
                }
                
            }
        }

        // form validation
    	if (empty($_POST['title'])) {
    		$errors['name'] = 'Please provide a name for the Teaching Tip';
    	} else {
    		$title = sanitize_input($_POST['title']);
    		if (strlen($title) > 128) $errors['name'] = 'The name cannot be longer than 128 characters';
    	}

    	if (empty($_POST['rationale']) && !$draft) $errors['rationale'] = 'Please provide a rationale for this Teaching Tip';
    	else {
    		$rationale = sanitize_input($_POST['rationale']);
    		
    	}

    	if (empty($_POST['description']) && !$draft) $errors['description'] = 'Please provide a description for this Teaching Tip';
    	else {
    		$description = sanitize_input($_POST['description']);
    	}

    	if (empty($_POST['practice']) && !$draft) $errors['practice'] = 'Please provide the practice for this Teaching Tip';
    	else {
    		$practice = sanitize_input($_POST['practice']);
    	}

    	if (!empty($_POST['cond1'])) $cond1 = sanitize_input($_POST['cond1']);

    	if (!empty($_POST['cond2'])) $cond2 = sanitize_input($_POST['cond2']);

    	if (empty($_POST['essence']) && !$draft) $errors['essence'] = 'Please provide the essence statement for this Teaching Tip';
    	else {
    		$essence = sanitize_input($_POST['essence']);
    	}

    	if (empty($_POST['keywords']) && !$draft) { 
            $errors['keywords'] = 'Please provide at least one keyword for this Teaching Tip. This will facilitate searching and will make your Teaching Tip easier to find';
        } else if (!empty($_POST['keywords'])) {
            $kwsString = sanitize_input(rtrim($_POST['keywords'], ";, "));
            $kwsString = str_replace(';', ',', $kwsString);
    		$keywords = explode(',', $kwsString);
            foreach ($keywords as $index=>$keyword) {
                if (strlen(trim($keyword)) > 0) $keywords[$index] = trim($keyword);
            }
    	}

        if (empty($_POST['class_size']) && !$draft) $errors['class_size'] = 'Please select an option/multiple options for the class size';
        else {
            $post_class_size = $_POST['class_size'];
            $class_size = array();
            foreach ($post_class_size as $key=>$val) {
                $cs = sanitize_input($val);
                if (array_key_exists($cs, $CLASS_SIZES)) $class_size[] = $cs;
            }
            
        }

        if (empty($_POST['environment']) && !$draft) $errors['environment'] = 'Please select an option for the environment';
        else {
            $post_environment = $_POST['environment'];
            $environment = array();
            foreach ($post_environment as $key=>$val) {
                $e = sanitize_input($val);
                if (array_key_exists($e, $ENVS)) $environment[] = $e;
            }

        }

        if (empty($_POST['suitable_ol']) && !$draft) $errors['suitable_ol'] = 'Please specify if this Teaching Tip is suitable for online learning';
        else {
            $suitable_ol = sanitize_input($_POST['suitable_ol']);
            if ($suitable_ol != "yes" && $suitable_ol != "no") $suitable_ol = "";
        }

        if (empty($_POST['it_competency']) && !$draft) $errors['it_competency'] = 'Please select an option for the IT competency required';
        else {
            $post_it_competency = $_POST['it_competency'];
            $it_competency = array();
            foreach ($post_it_competency as $key=>$val) {
                $itc = sanitize_input($val);
                if (array_key_exists($itc, $ITC)) $it_competency[] = $itc;
            }
            
        }

        

        if (!$errors) {
            $ttdata = array(
                'title' => $title,
                'rationale' => $rationale,
                'description' => $description,
                'practice' => $practice,
                'cond1' => $cond1,
                'cond2' => $cond2,
                'essence' => $essence,
                'keywords' => $keywords,
                'contributors' => $contributorsIDs,
                'contributorsEmails' => $contributorsEmails,
                'class_size' => $class_size,
                'environment' => $environment,
                'suitable_ol' => $suitable_ol,
                'it_competency' => $it_competency,
                );
            if ($update) {
                // if user is on Edit page (editing an existing TT)
                if ($saved_as_draft) {
                    // if TT is a Draft
                    if ($draft) {
                        // user clicks 'Save as Draft' button
                        // update TT but keep it as Draft
                        if (teachingtip_update($ttdata, $ttID, $loggedUserID, true)) header("Location: myteachingtips.php");
                    }
                    else {
                        // user clicks 'Publish Teaching Tip' button
                        // update TT and publish it
                        if (teachingtip_update($ttdata, $ttID, $loggedUserID)){
                            
                            //Adding Notification For School
                            
                            $school = $user->school;
                            $usersIdSchool = getSameSchoolUsers($school,$loggedUserID);

                            foreach ($usersIdSchool as $userId)
                                createNotification($userId['id'], $ttID, 'post', 'school_posts');
                              
                            // Notification to the followers
                            $followers = getFollowers($loggedUserID);
                            $userSchool = $user->school;
                            if($followers){
                                foreach ($followers as $follower) {

                                    //avoid notifying duplicates as the user is notified from the school

                                    if($follower->school!=$userSchool)
                                        createNotification($follower->id, $ttID, 'post', 'followers_posts');
                                    
                                }
                            }

                            header("Location: index.php");
                           


                        } 
                    }
                } else {
                    // if TT is published - user clicks 'Save changes' button
                    // update the TT
                    if (teachingtip_update($ttdata, $ttID, $loggedUserID)) header("Location: index.php");
                }
            } else {
                // if user is on Create page (adding a new TT)
                if ($draft) {
                    // user clicks 'Save as Draft' button
                    // save TT as Draft
                    if (teachingtip_add($ttdata, $loggedUserID, true)) header("Location: myteachingtips.php"); 
                }
                else {
                    // user clicks 'Publish Teaching Tip' button
                    // publish TT
                    $tt = teachingtip_add($ttdata, $loggedUserID);
                    if ($tt){
                        
                        //Adding Notification For School
                        $school = $user->school;
                        $usersIdSchool = getSameSchoolUsers($school,$loggedUserID);

                        foreach ($usersIdSchool as $userId) 
                            createNotification($userId['id'], $tt->id, 'post', 'school_posts');
                        
                        // Notification to the followers
                        $followers = getFollowers($loggedUserID);
                        if($followers){
                            $userSchool = $user->school;
                            foreach ($followers as $follower) {

                                //avoid notifying duplicates as the user is notified from the school
                                if($follower->school!=$userSchool) 
                                    createNotification($follower->id, $tt->id, 'post', 'followers_posts');
                                
                            }
                        }

                        header("Location: index.php");


                    }


                }
                

            }

        }

	}					

	//Content

	$template->pageData['content'] .= 
		'<div class="col-sm-9 col-xs-12">
			<div class="card teachingtip-add">
			

				<!-- ADD TT FORM -->
				<form class="tt-add-form" action="" method="post">

				<div class="tt-add-form-header-wrapper">
					<div class="tt-add-form-header">';

    if($edit || $update) $template->pageData['content'] .= '<h3>Edit Teaching Tip</h3>';
    else $template->pageData['content'] .= '<h3>Create a new Teaching Tip</h3>';

    $template->pageData['content'] .= '</div>
                </div>';

    if ($saved_as_draft) $template->pageData['content'] .= 
                '<div class="alert alert-info alert-dismissible alert-tt-draft" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                  <strong>This Teaching Tip is a Draft!</strong> Only the author and the co-authors can edit it.
                </div>';
					
	// if there are any errors show error alert		
    if ($errors) $template->pageData['content'] .= 
                '<div class="tt-add-form-alert alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    <strong>Your Teaching Tip was not submitted. Please check the errors below!</strong>
                </div>';

                

	$template->pageData['content'] .= '<input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] .'" />';

    // if edit is enabled provide the ttID in the form
    if ($edit || $update) $template->pageData['content'] .= '<input type="hidden" name="ttID" value="' . $ttID .'" />';
    // if it is a draft add a hidden input to state this
    if ($saved_as_draft) $template->pageData['content'] .= '<input type="hidden" name="savedAsDraft" value="1" />';


    
    
    

    // tt name
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTName" class="control-label"><span class="mandatory">*</span>Name of Teaching Tip (Representing the essence of the Teaching Tip)</label>';
    if ($errors['name']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['name'] .'</span>';
    $template->pageData['content'] .= '<input type="text" class="form-control" id="inputTTName" name="title" placeholder="Name of the Teaching Tip..." value="'. $title .'">';
    $template->pageData['content'] .= '</div>';

    // tt rationale
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTRationale" class="control-label"><span class="mandatory">*</span>Rationale statement (The reason for adopting the Teaching Tip)</label>';
    if ($errors['rationale']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['rationale'] .'</span>';
    $template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTRatioanle" name="rationale" placeholder="Rationale...">'. $rationale .'</textarea>';
    $template->pageData['content'] .= '</div>';

    // tt description
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTDescription" class="control-label"><span class="mandatory">*</span>Description (One sentence description of the essence of the Teaching Tip)</label>';
    if ($errors['description']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['description'] .'</span>';
    $template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTDescription" name="description" placeholder="This Teaching Tip...">'. $description .'</textarea>';
    $template->pageData['content'] .= '</div>';
				
	// tt practice
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTPractice" class="control-label"><span class="mandatory">*</span>Practice of Teaching Tip (Description of the actual practice)</label>
                    <button type="button" class="tt-add-form-tooltip" data-toggle="tooltip" data-placement="right" title="Here you may include references or links to other material if appropriate. If you want to include any images, they must first be uploaded to an online image hosting service (such as imgur.com)."><span class="glyphicon glyphicon-question-sign"></span></button>';
    if ($errors['practice']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['practice'] .'</span>';
    $template->pageData['content'] .= '<textarea rows="8" cols="50" class="form-control" id="inputTTPractice" name="practice" placeholder="What we did...">'. $practice .'</textarea>';
    $template->pageData['content'] .= '</div>';		

	// tt conditional 1
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTConditional1" class="control-label">Conditional statement (This tends to work better if)</label>
                    <button type="button" class="tt-add-form-tooltip" data-toggle="tooltip" data-placement="right" title="Write about the necessary and optimum conditions for practicing the TT"><span class="glyphicon glyphicon-question-sign"></span></button>';
    if ($errors['cond1']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['cond1'] .'</span>';
    $template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTConditional1" name="cond1" placeholder="This tends to work better if...">'. $cond1 .'</textarea>';	
    $template->pageData['content'] .= '</div>';     			
				
    // tt conditional 2
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTConditional2" class="control-label">Conditional statement (This doesn\'t work unless)</label>
                    <button type="button" class="tt-add-form-tooltip" data-toggle="tooltip" data-placement="right" title="Write about the necessary and optimum conditions for practicing the TT"><span class="glyphicon glyphicon-question-sign"></span></button>';
    if ($errors['cond2']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['cond2'] .'</span>';
    $template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTConditional2" name="cond2" placeholder="This doesn\'t work unless...">'. $cond2 .'</textarea>';
    $template->pageData['content'] .= '</div>';     
				
	// tt essence
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTEssence" class="control-label"><span class="mandatory">*</span>Essence statement (Sum up the nature of the problem and the solution)</label>';
    if ($errors['essence']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['essence'] .'</span>';
    $template->pageData['content'] .= '<textarea rows="4" cols="50" class="form-control" id="inputTTEssence" name="essence" placeholder="So...">'. $essence .'</textarea>';
    $template->pageData['content'] .= '</div>'; 	


    /* KEYWORDS AND FILTERS */

    $template->pageData['content'] .= '<h4 class="tt-add-form-subtitle">Keywords and Filters (Used for effective searching)</h4>';
				
	// keywords
    $template->pageData['content'] .= 
                '<div class="form-group form-group-keywords">
                    <label for="inputTTKeywords" class="control-label"><span class="mandatory">*</span>Keywords</label>';
    if ($errors['keywords']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['keywords'] .'</span>';
    $template->pageData['content'] .= '<input type="text" class="form-control" id="inputTTKeywords" name="keywords" placeholder="Comma separated keywords..." value="'. $kwsString .'" autocomplete="off">
                <div id="tt-keyword-results">
                    <ul>
                      
                    </ul>
                </div>
            </div>';

    // class size
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTClassSize" class="control-label"><span class="mandatory">*</span>Class size</label>';
    if ($errors['class_size']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['class_size'] .'</span>';
    // $template->pageData['content'] .= 
    //                             '<select class="form-control" id="inputTTClassSize" name="class_size">
    //                               <option value="" disabled selected>Select the class size</option>
    //                               <option value="small" '. ($class_size == "small" ? "selected" : "") .'>Small (<25)</option>
    //                               <option value="medium" '. ($class_size == "medium" ? "selected" : "") .'>Medium (25-150)</option>
    //                               <option value="large" '. ($class_size == "large" ? "selected" : "") .'>Large (>150)</option>
    //                             </select>';
    // $template->pageData['content'] .= '</div>';

    foreach ($CLASS_SIZES as $key=>$val) {
        $template->pageData['content'] .= 
                                '<div class="checkbox">
                                  <label>
                                    <input type="checkbox" value="'.$key.'" name="class_size[]" '. (in_array($key, $class_size) ? "checked" : "") .'>
                                    '.$val.'
                                  </label>
                                </div>';
    }
    $template->pageData['content'] .= '</div>';

    // environment
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTEnvironment" class="control-label"><span class="mandatory">*</span>Environment</label>';
    if ($errors['environment']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['environment'] .'</span>';
    // $template->pageData['content'] .= 
    //                             '<select class="form-control" id="inputTTEnvironment" name="environment">
    //                               <option value="" disabled selected>Select the environment</option>
    //                               <option value="lecture" '. ($environment == "lecture" ? "selected" : "") .'>Lecture</option>
    //                               <option value="seminar" '. ($environment == "seminar" ? "selected" : "") .'>Seminar/Tutorial</option>
    //                               <option value="lab" '. ($environment == "lab" ? "selected" : "") .'>Lab</option>
    //                               <option value="field" '. ($environment == "field" ? "selected" : "") .'>Field</option>
    //                               <option value="other" '. ($environment == "other" ? "selected" : "") .'>Other</option>
    //                             </select>';

    foreach ($ENVS as $key=>$val) {
        $template->pageData['content'] .= 
                                '<div class="checkbox">
                                  <label>
                                    <input type="checkbox" value="'.$key.'" name="environment[]" '. (in_array($key, $environment) ? "checked" : "") .'>
                                    '.$val.'
                                  </label>
                                </div>';
    }
    $template->pageData['content'] .= '</div>';

    // suitable for online learning
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTSuitableOnlineLearning" class="control-label"><span class="mandatory">*</span>Suitable for online learning</label>';
    if ($errors['suitable_ol']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['suitable_ol'] .'</span>';
    $template->pageData['content'] .= 
                                '<select class="form-control" id="inputTTSuitableOnlineLearning" name="suitable_ol">
                                  <option value="" disabled selected>Select an option</option>
                                  <option value="yes" '. ($suitable_ol[0] == "yes" ? "selected" : "") .'>Yes</option>
                                  <option value="no" '. ($suitable_ol[0] == "no" ? "selected" : "") .'>No</option>
                                </select>';

    // foreach ($SOL as $key=>$val) {
    //     $template->pageData['content'] .= 
    //                             '<div class="checkbox">
    //                               <label>
    //                                 <input type="checkbox" value="'.$key.'" name="suitable_ol[]" '. ($suitable_ol == $key ? "checked" : "") .'>
    //                                 '.$val.'
    //                               </label>
    //                             </div>';
    // }
    $template->pageData['content'] .= '</div>';

    // IT competency required
    $template->pageData['content'] .= 
                '<div class="form-group">
                    <label for="inputTTITCompetency" class="control-label"><span class="mandatory">*</span>IT competency required</label>';
    if ($errors['it_competency']) $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['it_competency'] .'</span>';
    // $template->pageData['content'] .= 
    //                             '<select class="form-control" id="inputTTITCompetency" name="it_competency">
    //                               <option value="" disabled selected>Select the IT competency required</option>
    //                               <option value="basic" '. ($it_competency == "basic" ? "selected" : "") .'>Basic</option>
    //                               <option value="moderate" '. ($it_competency == "moderate" ? "selected" : "") .'>Moderate</option>
    //                               <option value="advanced" '. ($it_competency == "advanced" ? "selected" : "") .'>Advanced</option>
    //                             </select>';

    foreach ($ITC as $key=>$val) {
        $template->pageData['content'] .= 
                                '<div class="checkbox">
                                  <label>
                                    <input type="checkbox" value="'.$key.'" name="it_competency[]" '. (in_array($key, $it_competency) ? "checked" : "") .'>
                                    '.$val.'
                                  </label>
                                </div>';
    }
    $template->pageData['content'] .= '</div>';


    /* CONTRIBUTORS */

    $template->pageData['content'] .= '<h4 class="tt-add-form-subtitle">Co-authors</h4>';

    if ((!$edit && !$update) || ($saved_as_draft && $isAuthor)) {
        $template->pageData['content'] .= '<div class="tt-add-form-contributors">';
        if ($contributorsEmail) {
            foreach ($contributorsEmail as $index=>$contributorEmail) {
                $cindex = $index + 1;
                $template->pageData['content'] .= 
                '<div class="form-group fg-contributor" id="fg-contributor-'.$cindex.'">
                     <label for="contributor'. $cindex .'" class="add-contributor-form-label">Co-author '. $cindex .': </label>
                     <input type="text" class="form-control add-contributor-form-input" id="contributor'. $cindex .'" name="contributors[]" placeholder="Co-author\'s email address" value="'. $contributorEmail .'">
                     <button type="button" class="glyphicon glyphicon-remove btn-remove-contributor" data-target="'. $cindex .'"></button>';

                if ($errors['contributor'][$index]) $template->pageData['content'] .= '<span class="tt-add-form-error" style="padding-left: 115px; padding-top: 3px;"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> '. $errors['contributor'][$index] .'</span>';
                $template->pageData['content'] .= '</div>';
            }
            
        }
        $template->pageData['content'] .= '</div>';
        // add contributors
        $template->pageData['content'] .= 
                    '<div class="form-group form-group-contributors">
                        <a role="button" class="btn btn-default btn-add-contributor">
                            <span class="glyphicon glyphicon-plus"></span>
                            <span class="add-contributor-label">Add co-author</span>
                        </a>
                        <button type="button" id="btn-add-contributor-tooltip" data-toggle="tooltip" data-placement="right" title="Here you can add Co-authors for your Teaching Tip. Co-authors will be able to view and edit the Teaching Tip when it is in Draft stage but they won\'t be able to modify it after you publish it."><span class="glyphicon glyphicon-question-sign"></span></button>
                     </div>';
    }
                
    if ($edit || $update)
        if ($saved_as_draft) {
            if ($isAuthor) $template->pageData['content'] .= '<button type="submit" id="addTTFormSubmit" name="publish" class="btn btn-default">Publish Teaching Tip</button>';
            $template->pageData['content'] .= '<button type="submit" id="draftTTFormSubmit" name="draft" class="btn btn-default">Save as Draft</button>';}
        else $template->pageData['content'] .= '<button type="submit" name="publish" id="addTTFormSubmit" class="btn btn-default">Save Changes</button>';
    else {
        $template->pageData['content'] .= 
                    '<button type="submit" id="addTTFormSubmit" name="publish" class="btn btn-default">Publish Teaching Tip</button>
                     <button type="submit" id="draftTTFormSubmit" name="draft" class="btn btn-default">Save as Draft</button>';
    }


                
    $template->pageData['content'] .= 
                '<a href="index.php" id="cancelTTForm" class="btn btn-default">Cancel</a>';

    $template->pageData['content'] .= '</form>
                <br />
                <span class="mandatory">*Mandatory Fields</span>
            </div>
        </div>';              
                			
					
	

	$template->pageData['logoutLink'] = loginBox($uinfo);
}


//if(error_get_last()==null)
	echo $template->render();
//else
//    echo "<p>Not rendering template to avoid hiding error messages.</p>";

?>