<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');
require_once(__DIR__ . '/lib/formfunctions.php');
require_once(__DIR__ . '/lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) {
  header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';

$template->pageData['customCSS'] = '
<script src="tinymce/js/tinymce/tinymce.min.js"></script>
<script src="js/placeholder.js"></script>
<script>
tinymce.init({ 
  selector: "textarea#inputTTPractice",
  theme: "modern",
  plugins: "link autolink image placeholder"
  });
</script>';

$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$edit = false;

$template->pageData['userLoggedIn'] = $givenname . ' ' . $surname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

session_start();
if (!isset($_SESSION['csrf_token']))
  $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));

$errors = array();
$title = $rationale = $description = $practice = $cond1 = $cond2 = $essence = $kwsString = ''; 
$suitable_ol = array();
$it_competency = array(); 
$keywords = array();
$class_size = array();
$contributorsIDs = array();
$contributorsEmails = array();
$environment = array();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  if (isset($_GET['ttID']) && is_numeric($_GET['ttID']) && $_GET['ttID'] >= 0){
    $ttID = $_GET['ttID'];
    $tt = teachingtip::retrieve_teachingtip($ttID);
    $author = $tt->get_author();

    if ($tt->status == 'deleted' && $user->isadmin == 0) {
      $template->pageData['content'] .= pageNotFound();
      echo $template->render();
      exit();
    }
    
    $contrTTs = $user->get_contr_teaching_tips();
    $contr = false; // check if the logged user is contributor to display
    foreach($contrTTs as $cTT)
      if ($ttID == $cTT->id) {
	$contr = true;
	break;
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
	$ttSchool = $tt->school;
	$kws = $tt->get_keywords();
	$class_size = $tt->get_filters("class_size");
	$environment = $tt->get_filters("environment");
	$suitable_ol = $tt->get_filters("suitable_ol");
	$it_competency = $tt->get_filters("it_competency");
                
	foreach ($kws as $kw)
	  $keywords[] = trim($kw->keyword);
	$kwsString = implode(',', $keywords);
                
	$saved_as_draft = true; //*** CHECK THIS
	$tt->status = 'draft';
	if ($saved_as_draft) {
	  $ttcontributors = $tt->get_contributors();
	  $contributorsEmail = array();
	  foreach ($ttcontributors as $ttcontr)
	    $contributorsEmail[] = $ttcontr->email;
	}
      } else {
	header("Location: teaching_tip_add.php");
	exit();
      }
  } else
    $ttSchool = $user->get_school_for_tt($SCHOOLS);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])
    exit();

  $update = false;
  $draft = false;
  $saved_as_draft = false;

  if (isset($_POST['ttID']) && is_numeric($_POST['ttID']) && $_POST['ttID'] >= 0) {
    $ttID = $_POST['ttID'];
    $update = true;
  }

  if (isset($_POST['draft']))
    $draft = true;

  if (isset($_POST['savedAsDraft'])) {
    $saved_as_draft = true;
    $tt = teachingtip::retrieve_teachingtip($ttID);
    if ($tt->author_id == $loggedUserID)
      $isAuthor = true;
  }

  if (!empty($_POST['contributors'])) {
    $contributorsEmail = $_POST['contributors'];

    foreach ($contributorsEmail as $index=>$contributorEmail) {
      $contributorEmail = sanitize_input($contributorEmail);
      if (!empty($contributorEmail)) {
	$contributor = user::retrieve_user_matching('email', $contributorEmail)[0];

	if ($contributor) {
	  if ($loggedUserID == $contributor->id)
	    $errors['contributor'][$index] = 'You cannot add yourself as a co-author';
	  else {
	    if (!in_array($contributor->id, $contributorsIDs))
	      $contributorsIDs[] = $contributor->id;
	    else
	      $errors['contributor'][$index] = 'You entered the same co-author multiple times';
	  }
	} else {
	  if (!in_array($contributorEmail, $contributorsEmails))
	    $contributorsEmails[] = $contributorEmail;
	  else
	    $errors['contributor'][$index] = 'You entered the same co-author multiple times';
	}
      } else
	$errors['contributor'][$index] = 'Please provide an email address for this co-author';                
    }
  }

  if (empty($_POST['title']))
      $errors['name'] = 'Please provide a name for the Teaching Tip';
  else {
    $title = sanitize_input($_POST['title']);
    if (strlen($title) > 128)
      $errors['name'] = 'The name cannot be longer than 128 characters';
  }

  if (empty($_POST['rationale']) && !$draft)
    $errors['rationale'] = 'Please provide a rationale for this Teaching Tip';
  else
    $rationale = sanitize_input($_POST['rationale']);

  if (empty($_POST['description']) && !$draft)
    $errors['description'] = 'Please provide a description for this Teaching Tip';
  else
    $description = sanitize_input($_POST['description']);

  if (empty($_POST['practice']) && !$draft)
    $errors['practice'] = 'Please provide the practice for this Teaching Tip';
  else
    $practice = sanitize_input($_POST['practice']);

  if (!empty($_POST['cond1'])) $cond1 = sanitize_input($_POST['cond1']);
  if (!empty($_POST['cond2'])) $cond2 = sanitize_input($_POST['cond2']);
  if (empty($_POST['essence']) && !$draft)
    $errors['essence'] = 'Please provide the essence statement for this Teaching Tip';
  else
    $essence = sanitize_input($_POST['essence']);

  $ttSchool = sanitize_input($_POST['school']);
  
  if (empty($_POST['keywords']) && !$draft) { 
    $errors['keywords'] = 'Please provide at least one keyword for this Teaching Tip. This will facilitate searching and will make your Teaching Tip easier to find';
  } else if (!empty($_POST['keywords'])) {
    $kwsString = sanitize_input(rtrim($_POST['keywords'], ";, "));
    $kwsString = str_replace(';', ',', $kwsString);
    $keywords = explode(',', $kwsString);
    foreach ($keywords as $index => $keyword) {
      if (strlen(trim($keyword)) > 0)
	$keywords[$index] = trim($keyword);
    }
  }

  if (empty($_POST['class_size'])) {
    if(!$draft)
      $errors['class_size'] = 'Please select an option/multiple options for the class size';
  } else {
    $post_class_size = $_POST['class_size'];
    foreach ($post_class_size as $key => $val) {
      $cs = sanitize_input($val);
      if (array_key_exists($cs, $CLASS_SIZES))
	$class_size[] = $cs;
    }
  }

  if (empty($_POST['environment'])) {
    if (!$draft)
      $errors['environment'] = 'Please select an option for the environment';
  } else {
    foreach ($_POST['environment'] as $key => $val) {
      $e = sanitize_input($val);
      if (array_key_exists($e, $ENVS))
	$environment[] = $e;
    }
  }
  
  if (empty($_POST['suitable_ol'])) {
    if (!$draft)
      $errors['suitable_ol'] = 'Please specify if this Teaching Tip is suitable for online learning';
  } else {
    $sol = sanitize_input($_POST['suitable_ol']);
    if (in_array($sol, array('yes', 'no')))
      $suitable_ol[] = $sol;
  }
  
  if (empty($_POST['it_competency'])) {
    if (!$draft)
      $errors['it_competency'] = 'Please select an option for the IT competency required';
  } else {
    foreach ($_POST['it_competency'] as $key => $val) {
      $itc = sanitize_input($val);
      if (array_key_exists($itc, $ITC))
	$it_competency[] = $itc;
    }
  }

  if (!$errors) {
    $ttdata = array('title' => $title,
		    'rationale' => $rationale,
		    'description' => $description,
		    'practice' => $practice,
		    'cond1' => $cond1,
		    'cond2' => $cond2,
		    'essence' => $essence,
		    'school' => $ttSchool,
		    'keywords' => $keywords,
		    'contributors' => $contributorsIDs,
		    'contributorsEmails' => $contributorsEmails,
		    'class_size' => $class_size,
		    'environment' => $environment,
		    'suitable_ol' => $suitable_ol,
		    'it_competency' => $it_competency);
    if ($update) {
      if ($saved_as_draft) {
	if ($draft) {
	  if (teachingtip_update($ttdata, $ttID, $loggedUserID, true)) {
	    header("Location: myteachingtips.php");
	    exit();
	  }
	} else {
	  if (teachingtip_update($ttdata, $ttID, $loggedUserID)) {
	    $school = $user->school;
	    $usersIdSchool = getSameSchoolUsers($school, $loggedUserID);
	    foreach ($usersIdSchool as $userId)
	      createNotification($userId['id'], $ttID, 'post', 'school_posts');
                              
	    $followers = getFollowers($loggedUserID);
	    $userSchool = $user->school;
	    foreach ($followers as $follower) {
	      if ($follower->school != $userSchool)
		createNotification($follower->id, $ttID, 'post', 'followers_posts');
	    }

	    header("Location: index.php");
	    exit();
	  } 
	}
      } else {
	if (teachingtip_update($ttdata, $ttID, $loggedUserID)) {
	  header("Location: index.php");
	  exit();
	}
      }
    } else {
      if ($draft) {
	if (teachingtip_add($ttdata, $loggedUserID, true)) {
	  header("Location: myteachingtips.php");
	  exit();
	}
      } else {
	$tt = teachingtip_add($ttdata, $loggedUserID);
	if ($tt) {
	  $school = $user->school;
	  $usersIdSchool = getSameSchoolUsers($school, $loggedUserID);
	  foreach ($usersIdSchool as $userId) 
	    createNotification($userId['id'], $tt->id, 'post', 'school_posts');
                        
	  $followers = getFollowers($loggedUserID);
	    $userSchool = $user->school;
	    foreach ($followers as $follower) {
	      if ($follower->school != $userSchool) 
		createNotification($follower->id, $tt->id, 'post', 'followers_posts');
	  }

	  header("Location: index.php");
	  exit();
	}
      }
    }
  }
}					

$template->pageData['content'] .= 
  '<div class="col-sm-9 col-xs-12">
			<div class="card teachingtip-add">
				<form class="tt-add-form" action="" method="post">
				<div class="tt-add-form-header-wrapper">
					<div class="tt-add-form-header">';

if($edit || $update)
  $template->pageData['content'] .= '<h3>Edit Teaching Tip</h3>';
else
  $template->pageData['content'] .= '<h3>Create a new Teaching Tip</h3>';

$template->pageData['content'] .= '</div></div>';

if ($saved_as_draft)
  $template->pageData['content'] .= 
    '<div class="alert alert-info alert-dismissible alert-tt-draft" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                  <strong>This Teaching Tip is a Draft.</strong> Only the author and the co-authors can edit it.
                </div>';
					
if ($errors)
  $template->pageData['content'] .= 
    '<div class="tt-add-form-alert alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    <strong>Your Teaching Tip was not submitted. Please check the errors below!</strong>
                </div>';

$template->pageData['content'] .= '<input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] . '" />';
if ($edit || $update)
  $template->pageData['content'] .= '<input type="hidden" name="ttID" value="' . $ttID . '" />';
if ($saved_as_draft)
  $template->pageData['content'] .= '<input type="hidden" name="savedAsDraft" value="1" />';

$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTName" class="control-label"><span class="mandatory">*</span>Name of Teaching Tip (representing the crux of the Teaching Tip)</label>';
if ($errors['name'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['name'] . '</span>';
$template->pageData['content'] .= '<input type="text" class="form-control" id="inputTTName" name="title" placeholder="Name..." value="' . $title . '">';
$template->pageData['content'] .= '</div>';

$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTRationale" class="control-label"><span class="mandatory">*</span>Rationale (the reason for adopting the Teaching Tip)</label>';
if ($errors['rationale'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['rationale'] . '</span>';
$template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTRatioanle" name="rationale" placeholder="Rationale...">' . $rationale . '</textarea>';
$template->pageData['content'] .= '</div>';

$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTDescription" class="control-label"><span class="mandatory">*</span>Description (one sentence description of the Teaching Tip)</label>';
if ($errors['description'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['description'] . '</span>';
$template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTDescription" name="description" placeholder="This Teaching Tip...">' . $description . '</textarea>';
$template->pageData['content'] .= '</div>';
				
$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTPractice" class="control-label"><span class="mandatory">*</span>What I did (the actual practice of the Teaching Tip)</label>
                    <button type="button"
                            class="tt-add-form-tooltip"
                            data-toggle="tooltip"
                            data-placement="right"
                            title="Here you may include references or links to other material if appropriate. If you want to include any images, they must first be uploaded to an online image hosting service (such as imgur.com).">
<span class="glyphicon glyphicon-question-sign"></span></button>';
if ($errors['practice'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['practice'] . '</span>';
$template->pageData['content'] .= '<textarea rows="8" cols="50" class="form-control" id="inputTTPractice" name="practice" placeholder="What I/we did...">' . $practice . '</textarea>';
$template->pageData['content'] .= '</div>';		

$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTConditional1" class="control-label">This tends to work better if...</label>
                    <button type="button" class="tt-add-form-tooltip" data-toggle="tooltip" data-placement="right" title="Write about the necessary and optimum conditions for practicing the TT"></button>';
if ($errors['cond1'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['cond1'] . '</span>';
$template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTConditional1" name="cond1">' . $cond1 . '</textarea>';	
$template->pageData['content'] .= '</div>';     			
				
$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTConditional2" class="control-label">This doesn\'t work unless...</label>
                    <button type="button" class="tt-add-form-tooltip" data-toggle="tooltip" data-placement="right" title="Write about the necessary and optimum conditions for practicing the TT"></button>';
if ($errors['cond2'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['cond2'] . '</span>';
$template->pageData['content'] .= '<textarea rows="2" cols="50" class="form-control" id="inputTTConditional2" name="cond2">' . $cond2 . '</textarea>';
$template->pageData['content'] .= '</div>';     
				
$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTEssence" class="control-label"><span class="mandatory">*</span>Essence statement (sum up the crux of the problem and the solution)</label>';
if ($errors['essence'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['essence'] . '</span>';
$template->pageData['content'] .= '<textarea rows="4" cols="50" class="form-control" id="inputTTEssence" name="essence" placeholder="So...">' . $essence . '</textarea>';
$template->pageData['content'] .= '</div>'; 	

$template->pageData['content'] .= '<h4 class="tt-add-form-subtitle">Keywords and filters (used for effective searching)</h4>';
				
$template->pageData['content'] .= 
  '<div class="form-group form-group-keywords">
                    <label for="inputTTKeywords" class="control-label"><span class="mandatory">*</span>Keywords</label>';
if ($errors['keywords'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['keywords'] . '</span>';
$template->pageData['content'] .= '<input type="text" class="form-control" id="inputTTKeywords" name="keywords" placeholder="Comma separated keywords..." value="' . $kwsString . '" autocomplete="off">
                <div id="tt-keyword-results">
                    <ul>                      
                    </ul>
                </div>
            </div>';

$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTClassSize" class="control-label"><span class="mandatory">*</span>Class size</label>';
if ($errors['class_size'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['class_size'] . '</span>';

foreach ($CLASS_SIZES as $key => $val)
  $template->pageData['content'] .= 
    '<div class="checkbox">
                                  <label>
                                    <input type="checkbox" value="' . $key . '" name="class_size[]" ' . (in_array($key, $class_size) ? "checked" : "") . '>
                                    ' . $val . '
                                  </label>
                                </div>';
    
$template->pageData['content'] .= '</div>';

$template->pageData['content'] .= 
  '<div class="form-group">
                    <label for="inputTTEnvironment" class="control-label"><span class="mandatory">*</span>Environment</label>';
if ($errors['environment'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['environment'] . '</span>';

foreach ($ENVS as $key => $val)
  $template->pageData['content'] .= 
                                '<div class="checkbox">
                                  <label>
                                    <input type="checkbox" value="' . $key . '" name="environment[]" ' . (in_array($key, $environment) ? "checked" : "") . '>
                                    ' . $val . '
                                  </label>
                                </div>';

$template->pageData['content'] .= '</div>';

$template->pageData['content'] .= 
  '<div class="form-group">
     <label for="inputTTSuitableOnlineLearning" class="control-label"><span class="mandatory">*</span>Suitable for online learning</label>';
if ($errors['suitable_ol'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['suitable_ol'] . '</span>';

$template->pageData['content'] .= 
  '<div class="radio">
     <label><input type="radio" value="yes" ' . (in_array('yes', $suitable_ol) ? 'checked="checked"' : '') . ' name="suitable_ol" />Yes</label>
     <label><input type="radio" value="no" '  . (in_array('no', $suitable_ol) ? 'checked="checked"' : '') . ' name="suitable_ol" />No</label>
   </div>';

$template->pageData['content'] .= '</div>';

$template->pageData['content'] .= 
  '<div class="form-group">
     <label for="inputTTITCompetency" class="control-label"><span class="mandatory">*</span>IT competency required</label>';
if ($errors['it_competency'])
  $template->pageData['content'] .= '<span class="tt-add-form-error"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['it_competency'] . '</span>';

foreach ($ITC as $key => $val)
  $template->pageData['content'] .=
  '<div class="radio">
     <label><input type="radio" value="' . $key . '" name="it_competency[]" ' . (in_array($key, $it_competency) ? "checked" : "") . '>' . $val . '</label>
   </div>';
$template->pageData['content'] .= '</div>';

$template->pageData['content'] .= '<div class="form-group"><label for="inputTTschool" class="control-label">Please indicate your school or unit, for easier search.</label>';
$template->pageData['content'] .= '<select class="form-control" id="inputTTschool" name="school">';
$template->pageData['content'] .= '<option value="Other"' . ('' == $ttSchool ? " selected='selected'" : '') . '></option>';
foreach ($SCHOOLS as $s)
  $template->pageData['content'] .= '<option value="' . $s . '"' . ($s == $ttSchool ? " selected='selected'" : '') . '>' . $s . '</option>';
$template->pageData['content'] .= '</select></div>';

$template->pageData['content'] .= '<h4 class="tt-add-form-subtitle">Co-authors</h4>';

if ((!$edit && !$update) || ($saved_as_draft && $isAuthor)) {
  $template->pageData['content'] .= '<div class="tt-add-form-contributors">';
  if ($contributorsEmail) {
    foreach ($contributorsEmail as $index=>$contributorEmail) {
      $cindex = $index + 1;
      $template->pageData['content'] .= 
	'<div class="form-group fg-contributor" id="fg-contributor-' . $cindex . '">
                     <label for="contributor' . $cindex . '" class="add-contributor-form-label">Co-author ' . $cindex . ': </label>
                     <input type="text" class="form-control add-contributor-form-input" id="contributor' . $cindex . '" name="contributors[]" placeholder="Co-author\'s email address" value="' . $contributorEmail . '">
                     <button type="button" class="glyphicon glyphicon-remove btn-remove-contributor" data-target="' . $cindex . '"></button>';

      if ($errors['contributor'][$index])
	$template->pageData['content'] .= '<span class="tt-add-form-error" style="padding-left: 115px; padding-top: 3px;"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> ' . $errors['contributor'][$index] . '</span>';
      $template->pageData['content'] .= '</div>';
    }
  }

  $template->pageData['content'] .= '</div>';
  $template->pageData['content'] .= 
    '<div class="form-group form-group-contributors">
                        <a role="button" class="btn btn-default btn-add-contributor">
                            <span class="glyphicon glyphicon-plus"></span>
                            <span class="add-contributor-label">Add co-author</span>
                        </a>
                        <button type="button" id="btn-add-contributor-tooltip" data-toggle="tooltip" data-placement="right" title="Here you can add co-authors for your Teaching Tip. Co-authors will be able to view and edit the Teaching Tip when it is in Draft stage but they won\'t be able to modify it after you publish it."><span class="glyphicon glyphicon-question-sign"></span></button>
                     </div>';
}
                
if ($edit || $update)
  if ($saved_as_draft) {
    if ($isAuthor || $user->isadmin)
      $template->pageData['content'] .= '<button type="submit" id="addTTFormSubmit" name="publish" class="btn btn-default">Publish Teaching Tip</button>';
    $template->pageData['content'] .= '<button type="submit" id="draftTTFormSubmit" name="draft" class="btn btn-default">Save as Draft</button>';
  } else
    $template->pageData['content'] .= '<button type="submit" name="publish" id="addTTFormSubmit" class="btn btn-default">Save Changes</button>';
else
  $template->pageData['content'] .= 
                    '<button type="submit" id="addTTFormSubmit" name="publish" class="btn btn-default">Publish Teaching Tip</button>
                     <button type="submit" id="draftTTFormSubmit" name="draft" class="btn btn-default">Save as Draft</button>';


$template->pageData['content'] .= '<a href="index.php" id="cancelTTForm" class="btn btn-default">Cancel</a>';

$template->pageData['content'] .= '</form>
                <br />
                <span class="mandatory">*Mandatory fields</span>
            </div>
        </div>';

$template->pageData['logoutLink'] = loginBox($uinfo);
echo $template->render();
