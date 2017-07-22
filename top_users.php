<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');
require_once(__DIR__ . '/lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) {
  header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$template->pageData['userLoggedIn'] = $givenname . ' ' . $surname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['navHome'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0)); 
$template->pageData['notifications'] = notifications($dbUser);

$template->pageData['content'] .= 
  '<div class="col-xs-12 col-sm-9">
 <div class="card home-page">
   <div class=" col-xs-9 main-header">
     <h4>Teaching Tip Feed</h4>
   </div>
   <div class="col-xs-3 activity-options main-header">
       <div class="btn-group">
         <button type="button" class="btn btn-default dropdown-toggle visible-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
           Feed
           <span class="caret" />
         </button>
         <button type="button" class="btn btn-default dropdown-toggle hidden-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
           <div class="col-xs-1 glyphicon glyphicon-blackboard options-icon"></div><span class="feed-dropdown-text">Authors</span>
           <span class="caret" />
         </button>
         <ul class="dropdown-menu main-feed-dropdown">
           <li class="col-xs-12 options-wrapper" id="filter-recent">
             <div class="col-xs-1 glyphicon glyphicon-refresh options-icon options-icon-refresh"></div>
             <div class="col-xs-9 options-option"> <a href="index.php">Most Recent</a></div>
           </li>
           <li class="col-xs-12 options-wrapper" id="filter-top-liked">
             <div class="col-xs-1 glyphicon glyphicon-fire options-icon options-icon-fire"></div>
             <div class="col-xs-9 options-option"> <a href="index.php?filterType=like&period=alltime">Most Liked</a></div>
           </li>
           <li class="col-xs-12 options-wrapper" id="filter-top-commented">
             <div class="col-xs-1 glyphicon glyphicon-pencil options-icon options-icon-pencil"></div>
             <div class="col-xs-9 options-option"> <a href="index.php?filterType=comment&period=alltime">Most Commented</a></div>
           </li>
           <li class="col-xs-12 options-wrapper" id="filter-top-users">
             <div class="col-xs-1 glyphicon glyphicon-blackboard options-icon"></div>
             <div class="col-xs-9 options-option"> <a href="top_users.php">Authors</a></div>
             <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-recent"></div>
           </li>
         </ul>
       </div>
     </div>
   <div class="row feed-row">
     <div class="col-xs-12 feed-tts">';

foreach (user::get_most_tts(10, 0) as $tu) {
  $u = $tu['user'];
  $tts_number = $tu['n'];
  $tts_string = $tts_number == 1 ? "Teaching Tip" : "Teaching Tips";
  $template->pageData['content'] .= 
    "<div class='feed-tt'>
       <div class='row'>
         <div class='col-sm-2 feed-profile hidden-xs'>
           <img class='img-circle' src='{$u->profile_picture}' alt='profile picture'>
         </div>
         <div class='col-xs-12 col-sm-10 feed-info'>
           <div class='row visible-xs'>
             <div class='col-xs-2 feed-profile'>
               <img class='img-circle img-responsive' src='{$u->profile_picture}' alt='profile picture'>
             </div>
             <div class='col-xs-10'>
               <h4 class='feed-tt-title'><a href='profile.php?usrID={$u->id}'>{$u->name} {$u->lastname}</a></h4>
               <div class='feed-tt-profile-school'>{$u->school}</div>
             </div>
           </div>
           <div class='feed-title hidden-xs'>
             <h4 class='feed-tt-title'><a href='profile.php?usrID={$u->id}'>{$u->name} {$u->lastname}</a></h4>
             <div class='feed-tt-profile-school'>{$u->school}</div>
           </div>
           <div class='feed-text-wrapper'>
             <strong>{$tts_number} {$tts_string}</strong>
           </div>
        </div>
      </div>
    </div>";
}

$template->pageData['content'] .=
  "  </div>
     <div class='col-xs-12 homepage-view-more-contrib' value='10'>
       <div class='glyphicon glyphicon-triangle-bottom'></div>
       View more
     </div>            
   </div>
  </div>
</div>";

$template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
