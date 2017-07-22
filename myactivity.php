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
$loggedUserName = $uinfo['gn'];
$loggedUserLastname = $uinfo['sn'];

$template->pageData['userLoggedIn'] = $loggedUserName.' ' . $loggedUserLastname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['navActivity'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

$template->pageData['content'] .= '
<div class="col-xs-12 col-sm-9">
  <div class="card myactivity-card">
    <div class="main-header">
      <div class="col-xs-8 main-header-myactivity main-header">
    	<h4>My activity</h4>
      </div>
      <div class="col-xs-4 activity-options">
    	<div class="btn-group">
    	  <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	    <span class="activity-dropdown-text"> All </span>
	    <span class="caret"></span>
    	  </button>
    	  <ul class="dropdown-menu">
    	    <li class="col-xs-12 options-wrapper">
    	      <div class="col-xs-1 glyphicon glyphicon-star options-icon options-icon-star"></div>
    	      <div class="col-xs-9 options-option"><a role="button" id="filterAll">All</a></div>
              <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-all"></div>
    	    </li>
    	    <li class="col-xs-12 options-wrapper">
    	      <div class="col-xs-1 glyphicon glyphicon-thumbs-up options-icon options-icon-like"></div>
    	      <div class="col-xs-9 options-option"><a role="button" id="filterLikes">My Likes</a></div>
              <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-liked"></div>
    	    </li>
    	    <li class="col-xs-12 options-wrapper">
    	      <div class="col-xs-1 glyphicon glyphicon-comment options-icon options-icon-comment"></div>
    	      <div class="col-xs-9 options-option"><a role="button" id="filterComments">My Comments</a></div>
              <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-commented"></div>
    	    </li>
    	    <li class="col-xs-12 options-wrapper">
    	      <div class="col-xs-1 glyphicon glyphicon-share-alt options-icon options-icon-share"></div>
    	      <div class="col-xs-9 options-option"> <a role="button" id="filterShares">My Shares</a></div>
              <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-shared"></div>
    	    </li>
    	  </ul>
    	</div>
      </div>
    </div>';

$activity = array();
foreach ($user->getLikes() as $like)
  array_push($activity, [$like->time, $like]);

foreach ($user->getShares() as $share)
  array_push($activity, [$share->time, $share]);

foreach($user->getComments() as $comment)
  array_push($activity, [commentTime($comment->comment_id), $comment]);

function cmp($a, $b) { return $b[0] - $a[0]; }
usort($activity, "cmp");

$likeMin = true;
$commentMin = true;
$shareMin = true;
if (sizeof($activity) == 0)
  $template->pageData['content'] .= '<div class="no-activity"><strong>There is no recent activity!</strong></div>';
else {
  foreach ($activity as $act) {
    $activity_tt = teachingtip::retrieve_teachingtip($act[1]->teachingtip_id) ;
    $activity_author = user::retrieve_user($activity_tt->author_id);
    $activity_time = date('d M Y', $act[1]->time);  //conversion
    if ($act[1] instanceof user_likes_tt) {
      $likeMin = false; 
      $like_id = $act[1]->id;
      $template->pageData['content'] .= '
            <div class="row liked-row-wrapper activity like-activity">
              <div class="col-xs-12 activity-text-wrapper">
                
                  <span class="glyphicon glyphicon-thumbs-up liked-like"></span>
                  You liked 
                  <a class="liked-info-user" href="profile.php?usrID=' . $activity_author->id . '">' . $activity_author->name . ' ' . $activity_author->lastname . '</a>\'s post, 
                  <a class="liked-info-title" href="teaching_tip.php?ttID=' . $activity_tt->id . '">' . $activity_tt->title . '</a>
                  <span class="liked-info-time">' . $activity_time . '</span>
              </div>
            </div>';
    }

    if ($act[1] instanceof user_comments_tt) {
      $commentMin = false; 
      $activity_time = date('d M Y', commentTime($act[1]->comment_id));
      $comment_id = $act[1]->comment_id;
      $comment = ttcomment::retrieve_ttcomment($comment_id);
      $template->pageData['content'] .= '
            <div class="row commented-row-wrapper activity comment-activity">
              <div class="col-xs-12 activity-text-wrapper">
                  <span class="glyphicon glyphicon-comment liked-like"></span>
                  You commented 
                  "<div class="activity-comment-text">' . $comment->comment . '</div>"
                  on <a class="liked-info-user" href="profile.php?usrID=' . $activity_author->id . '">' . $activity_author->name . ' ' . $activity_author->lastname . '</a>\'s post, 
                  <a class="liked-info-title" href="teaching_tip.php?ttID=' . $activity_tt->id . '">' . $activity_tt->title . '</a>
                  <span class="liked-info-time">' . $activity_time . '</span>
                
              </div>
            </div>';
    }

    if ($act[1] instanceof user_shares_tt) {
      $shareMin = false; 
      $share_id = $act[1]->id;
      $template->pageData['content'] .= '
          <div class="row shared-row-wrapper activity share-activity">
                  <div class="col-xs-12 activity-text-wrapper">
                      <span class="glyphicon glyphicon-share-alt liked-like"></span>
                      You shared 
                      <a class="liked-info-user" href="profile.php?usrID=' . $activity_author->id . '">' . $activity_author->name . ' ' . $activity_author->lastname . '</a>\'s post, 
                      <a class="liked-info-title" href="teaching_tip.php?ttID=' . $activity_tt->id . '">' . $activity_tt->title . '</a>
                      <span class="liked-info-time">' . $activity_time . '</span>
                  </div>
                </div>';
    }
  }
}

if ($likeMin)
  $template->pageData['content'] .= '<div class="no-like-activity "><strong>There is no like activity!</strong></div>';

if ($commentMin)
  $template->pageData['content'] .= '<div class="no-comment-activity"><strong>There is no comment activity!</strong></div>';

if ($shareMin)
  $template->pageData['content'] .= '<div class="no-share-activity"><strong>There is no share activity!</strong></div>';

$template->pageData['content'] .= '
              </div>
          </div>';

$template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
