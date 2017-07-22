<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) {
  header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

session_start();

if (!isset($_SESSION['csrf_token']))
  $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$user = user::retrieve_user($loggedUserID);
$loggedUserName =  $uinfo['gn'];
$loggedUserLastname = $uinfo['sn'];

$template->pageData['userLoggedIn'] = $loggedUserName . ' ' . $loggedUserLastname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['navTTs'] = 'sidebar-current-page';

$notificationNo = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notificationNo'] = $notificationNo;
$template->pageData['notifications'] = notifications($dbUser);

$template->pageData['content'] .=
    	'<div class="col-sm-9 col-xs-12 my-tts-wrapper">
          <div class="my-tt-card">
            <div class="main-header"><h4>My Teaching Tips</h4></div>';

$myTTs =  $user->get_teaching_tips();
$contributedTTs = $user->get_contr_teaching_tips();

$existMyTT = 0;
$template->pageData['content'] .= '<div class="my-tt-mytt-header"><h5>Published Teaching Tips</h5></div>';

foreach($myTTs as $myTT) {
    $ttContributors = $myTT->get_contributors();
      
    if ($myTT->status == 'active') {
      $existMyTT = 1;
      $template->pageData['content'] .= '
<div class="row my-tt-wrapper">
  <div class="col-xs-12 my-tt-info" value="'.$myTT->id.'" >
    <div class="col-xs-12 feed-title">
      <h4 class="col-sm-8 col-xs-12 my-tt-title"><a href="teaching_tip.php?ttID='.$myTT->id.'">'.$myTT->title.'</a></h4>
      <span class="col-sm-4 col-xs-12 my-tt-time">'.date('d M Y', $myTT->whencreated).'</span>
    </div>';

      if (!empty($ttContributors)) {
        $tempContr = ' ';
        foreach ($ttContributors as $ttContr)
          $tempContr .= $ttContr->name . ' ' . $ttContr->lastname . ', ';
        $template->pageData['content'] .= '<div class="col-xs-12 my-tt-contributors">Co-authors:<span class=" my-tt-contributor">' . substr($tempContr, 0, -2) . '</span></div>';
      }

      $template->pageData['content'] .= '
    <div class="col-xs-12 feed-text-wrapper">
      <p class="feed-text">' . $myTT->description . ' </p>
    </div>
  </div> 

  <div class="col-xs-12 my-tt-button-wrapper">
    <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">
      <div class="feed-icons">
        <a class="glyphicon glyphicon-thumbs-up feed-likebutton"></a> ' . $myTT->get_number_likes() . '
      </div>
      <div class="feed-icons">
        <a class="glyphicon glyphicon-comment"></a> ' . $myTT->get_number_comments() . '
      </div>
      <div class="feed-icons">
        <a class="glyphicon glyphicon-share-alt"></a> ' . $myTT->get_number_shares() . '
      </div>
    </div>
    <div class="col-sm-6 col-xs-12 my-tt-buttons">
      <a href="teaching_tip.php?ttID=' . $myTT->id . '" class="btn btn-success">View</a>
      <a href="teaching_tip_add.php?ttID=' . $myTT->id . '" class="btn btn-info">Edit</a>
    </div>
  </div>
</div>'; 
    }
}
 
if ($existMyTT == 0)
  $template->pageData['content'] .= '<div class="no-tts"><strong>You do not have any Teaching Tips.</strong></div>';

$existDraftTT = 0;
$template->pageData['content'].='<div class="my-tt-draft-header"><h5>Draft Teaching Tips</h5></div>';
    
foreach ($myTTs as $myTT) {
  if ($myTT->status == 'draft') {
    $ttContributors = $myTT->get_contributors();
    $existDraftTT = 1;
    $template->pageData['content'].='
<div class="row my-tt-wrapper">
  <div class="col-xs-12 my-tt-info" value="' . $myTT->id . '" >
    <div class="col-xs-12 feed-title">
      <h4 class="col-sm-8 col-xs-12 my-tt-title"><a href="teaching_tip.php?ttID=' . $myTT->id . '">' . $myTT->title . '</a></h4>
      <span class="col-sm-4 col-xs-12 my-tt-time">' . date('d M Y', $myTT->whencreated) . '</span>
    </div>';
      
    if (!empty($ttContributors)) {
      $tempContr = ' ';
      foreach ($ttContributors as $ttContr)
	$tempContr .= $ttContr->name . ' ' . $ttContr->lastname . ', ';
      $template->pageData['content'] .= '<div class="col-xs-12 my-tt-contributors">Co-authors:<span class=" my-tt-contributor">' . substr($tempContr, 0, -2) . '</span></div>';
    }

    $template->pageData['content'] .= '
    <div class="col-xs-12 feed-text-wrapper">
      <p class="feed-text">' . $myTT->description . '</p>
    </div> 
  </div> 
  <div class="col-xs-12 my-tt-button-wrapper">
    <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper"></div>
    <div class="col-sm-6 col-xs-12 my-tt-buttons">
      <a href="teaching_tip.php?ttID=' . $myTT->id . '" class="btn btn-success">View</a>
      <a href="teaching_tip_add.php?ttID=' . $myTT->id . '" class="btn btn-info">Edit</a>
      <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#target-uniqueId' . $myTT->id . '">Delete</button>
      <div class="modal fade delete-modal-tt" id="target-uniqueId' . $myTT->id . '" id="deleteTTModal" tabindex="-1" role="dialog">
	<div class="modal-dialog">
	  <div class="modal-content">
	    <div class="modal-header">
	      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	      <h4 class="modal-title">Delete Teaching Tip</h4>
	    </div>
	    <div>
	      <h4 class="delete-modal-message">Are you sure you want to delete this Teaching Tip?</h4>
	    </div>
	    <div class="modal-footer delete-modal-buttons">
	      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
	      <form class="deleteFormTT">
		<input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] . '">
		<input type="hidden" name="ttId" value="' . $myTT->id . '">
		<button type="submit" class="btn btn-danger">Delete</button>
	      </form>
	    </div>
	  </div>
	</div>
      </div>
    </div>
  </div>
</div>'; 
  }
}

$existContrTT = 0;
foreach ($contributedTTs as $cTT) {
  if ($cTT->status == 'draft') {
    $author = user::retrieve_user($cTT->author_id);
    $existContrTT = 1;
    $template->pageData['content'] .= '
          <div class="row my-tt-wrapper">
          <div class="col-xs-12 my-tt-info" value="' . $cTT->id . '" >
          <div class="col-xs-12 feed-title">
          <div class="col-sm-10 col-xs-12 my-tt-title">
            <h4><a href="teaching_tip.php?ttID=' . $cTT->id . '">' . $cTT->title . '</a></h4>
            <span class="draft-tt-user">by <a href="profile.php?usrID=' . $author->id . '">' . $author->name . ' ' . $author->lastname . '</a></span>
          </div>
          <span class="col-sm-2 col-xs-12 my-tt-time">' . date('d M Y', $cTT->whencreated) . '</span>
          </div>
          <span class="col-xs-12">
          </span>
          <div class="col-xs-12 feed-text-wrapper">
          <p class="feed-text">' . $cTT->description . ' 
          </p>
          </div> 
          </div> 
          <div class="col-xs-12 my-tt-button-wrapper">
          <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">
          </div>
          <div class="col-sm-6 col-xs-12 my-tt-buttons">
          <a href="teaching_tip.php?ttID=' . $myTT->id . '" class="btn btn-success">View</a>
          <a href="teaching_tip_add.php?ttID=' . $cTT->id . '" class="btn btn-info">Edit</a>
          </div>
          </div>
          </div>';
  }
}

if ($existDraftTT == 0 && $existContrTT == 0)
  $template->pageData['content'] .= '<div class="no-tts"><strong>You do not have any draft Teaching Tips.</strong></div>';

$existContrTT = 0;
$template->pageData['content'].='<div class="my-tt-contr-header"><h5>Co-authored Teaching Tips</h5></div>';
foreach ($contributedTTs as $cTT){
  if ($cTT->status == 'active') {
    $author = user::retrieve_user($cTT->author_id);
    $existContrTT = 1;
    $template->pageData['content'] .= '
          <div class="row my-tt-wrapper">
          <div class="col-xs-12 my-tt-info" value="' . $cTT->id . '" >
          <div class="col-xs-12 feed-title">
          <div class="col-sm-10 col-xs-12 my-tt-title">
            <h4><a href="teaching_tip.php?ttID=' . $cTT->id . '">' . $cTT->title . '</a></h4>
            <span class="draft-tt-user">by <a href="profile.php?usrID=' . $author->id . '">' . $author->name . ' ' . $author->lastname . '</a></span>
          </div>
          <span class="col-sm-2 col-xs-12 my-tt-time">' . date('d M Y', $cTT->whencreated) . '</span>
          </div>
          <span class="col-xs-12">
          </span>
          <div class="col-xs-12 feed-text-wrapper">
          <p class="feed-text">' . $cTT->description . ' 
          </p>
          </div> 
          </div> 
          <div class="col-xs-12 my-tt-button-wrapper">
          <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">
          </div>
          <div class="col-sm-6 col-xs-12 my-tt-buttons">
          <a href="teaching_tip.php?ttID=' . $cTT->id . '" class="btn btn-success">View</a>
          </div>
          </div>
          </div>';
  }
}

if ($existContrTT == 0)
  $template->pageData['content'] .= '<div class="no-tts"><strong>You are not a co-author of any Teaching Tips.</strong></div>';

$template->pageData['content'] .= '</div></div>';
$template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
