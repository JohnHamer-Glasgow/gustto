<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');
require_once(__DIR__ . '/lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo === false) {
  header("Location: login.php");
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$template = new templateMerge($TEMPLATE);

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$template->pageData['userLoggedIn'] = $givenname . ' ' . $surname;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['navHome'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

$user = user::retrieve_user($loggedUserID);

session_start();
$new_tts_number = $user->get_number_new_tts(isset($_SESSION['last_visit']) ? $_SESSION['last_visit'] : $user->last_visit);

$filterData = array('random'   => array('text' => 'Random',
					'id'   => 'random',
					'icon' => 'refresh'),
		    'recent'   => array('text' => 'Most Recent',
					'id'   => 'recent',
					'icon' => 'time'),
		    'likes'    => array('text' => 'Most Liked',
					'id'   => 'top-likes',
					'icon' => 'thumbs-up'),
		    'comments' => array('text' => 'Most Commented',
					'id'   => 'top-commented',
					'icon' => 'pencil'));

$periodData = array('alltime'   => array('time' => 0,
					 'text' => ' All Time',
					 'tooltip' => ''),
		    'lastthree' => array('time' => time() - 60 * 60 * 24 * 90,
					 'text' => '< 3 Months',
					 'tooltip' => 'three months'),
		    'lastmonth' => array('time' => time() - 60 * 60 * 24 * 30,
					 'text' => '< 1 Month',
					 'tooltip' => 'month'),
		    'lastweek'  => array('time' => time() - 60 * 60 * 24 * 7,
					 'text' => 'Last week',
					 'tooltip' => 'week'));

if (isset($_GET['filterType']) && isset($filterData[$_GET['filterType']]))
  $filterType = $_GET['filterType'];
else
  $filterType = 'random';

if (isset($_GET['period']) && isset($periodData[$_GET['period']]))
  $period = $_GET['period'];
else
  $period = 'alltime';

if ($filterType == 'random')
  $tips = getRandomTeachingTips(10);
else if ($filterType == 'recent')
  $tips = getLatestTeachingTips(10);
else
  $tips = teachingtip::getPopularTeachingTips(10, 0, $filterType, $periodData[$period]['time']);

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
                    <span class="caret"></span>
                  </button>
                  <button type="button" class="btn btn-default dropdown-toggle hidden-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
$icon = $filterData[$filterType]['icon'];
$filterText = $filterData[$filterType]['text'];
$template->pageData['content'] .= "<div class='col-xs-1 glyphicon glyphicon-$icon refresh options-icon options-icon-$icon'></div><span class='feed-dropdown-text'>$filterText</span>";
$template->pageData['content'] .= 
  '<span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu main-feed-dropdown">';

foreach ($filterData as $f => $data) {
  $template->pageData['content'] .= "
      <li class='col-xs-12 options-wrapper' id='filter-recent'>
        <div class='col-xs-1 glyphicon glyphicon-$data[icon] options-icon options-icon-$data[icon]'></div>
        <div class='col-xs-9 options-option'> <a href='index.php?filterType=$f&period=$period'>$data[text]</a></div>";
  if ($filterType == $f)
    $template->pageData['content'] .= '<div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-recent"></div>';
  $template->pageData['content'] .= '</li>';
}

$template->pageData['content'] .= '
                    <li class="col-xs-12 options-wrapper" id="filter-top-users">
                      <div class="col-xs-1 glyphicon glyphicon-blackboard options-icon"></div>
                      <div class="col-xs-9 options-option"> <a href="top_users.php">Authors</a></div>
                    </li>';

$template->pageData['content'] .= '
                  </ul>
                </div>
              </div>';

if ($filterType != 'recent' && $filterType != 'random') {
  $template->pageData['content'] .= "<div class='col-xs-12 feed-period-header'>";
  foreach ($periodData as $p => $data) {
    $sel = $period == $p ? "period-selected" : '';
    if ($p == 'alltime')
      $template->pageData['content'] .= "
 <div class='feed-period-a col-xs-3'><a href='index.php?filterType=$filterType&period=alltime' class='$sel'>$data[text]</a></div>";
    else
      $template->pageData['content'] .= "
 <div class='feed-period-a col-xs-3'><a href='index.php?filterType=$filterType&period=$p' class='home-filter-tooltip $sel' data-toggle='tooltip' data-placement='top' title='Teaching Tips posted in the last $data[tooltip]'>$data[text]</a></div>";
  }

  $template->pageData['content'] .= "</div>";
}

$nUsers = user::get_number_users();
$nUsersSpan = $nUsers == 1 ? 'There is one user' : "There are $nUsers users";
$nTTs = teachingtip::get_number_tts();
$nTTsSpan = $nTTs == 1 ? 'one Teaching Tip' : "$nTTs Teaching Tips"; 
$template->pageData['content'] .= "       
<div class='row feed-row'>
   <div class='col-xs-12 total-number-tts-users'><span>$nUsersSpan<span> and <span>$nTTsSpan</span> ($new_tts_number new since your last visit)</div>
   <div class='col-xs-12 feed-tts'>";
  
foreach($tips as $ltt) {
  $tt = teachingtip::retrieve_teachingtip($ltt->id);
  $tt_time = date('d M Y', $tt->whencreated);
  $number_likes = $tt->get_number_likes();
  $number_comments = $tt->get_number_comments();
  $number_shares = $tt->get_number_shares();
  $keywords = $tt->get_keywords();
  $author = $tt->get_author();

  $template->pageData['content'] .= "
<div class='feed-tt'>
 <div class='row'>                    
   <div class='col-sm-2 feed-profile hidden-xs'>
     <img class='img-circle' src='{$author->profile_picture}' alt='profile picture'>
     <a href='profile.php?usrID={$author->id}' class='col-xs-12 tt-profile-name '>{$author->name} {$author->lastname}</a>
     <div class='feed-tt-profile-school'>{$author->school}</div>
     <div class='clearfix'></div>
     <span class='feed-tt-time'>{$tt_time}</span>
   </div>
   <div class='col-xs-12 col-sm-10 feed-info'>
     <div class='row visible-xs'>
       <div class='col-xs-2 feed-profile'>
         <img class='img-circle img-responsive' src='{$author->profile_picture}' alt='profile picture'>                          
       </div>
       <div class='col-xs-10'>
         <h4 class='feed-tt-title'><a href='teaching_tip.php?ttID={$tt->id}'>{$tt->title}</a></h4>
         <span class='feed-tt-time'>{$tt_time}</span>
       </div>
     </div>
     <div class='feed-title hidden-xs'>
       <h4 class='feed-tt-title'><a href='teaching_tip.php?ttID={$tt->id}'>{$tt->title}</a></h4>
     </div>
     <div class='feed-text-wrapper'>
       <p class='feed-text'>{$tt->description}</p>
     </div>";

  $template->pageData['content'] .= "<div class='feed-tt-keywords'>";
  foreach($keywords as $kw)
    $template->pageData['content'] .= "<a href='search.php?q=" . $kw->keyword . "&o=keyword'><div class='tt-keyword'>" . $kw->keyword . "</div></a>";
  $template->pageData['content'] .= "</div>"; 
  
  $template->pageData['content'] .= "
      <div class='feed-icons-wrapper'>";
  
  if (checkUserLikesTT($tt->id, $loggedUserID))
    $template->pageData['content'] .= "
        <div class='feed-icons'><span class='glyphicon glyphicon-thumbs-up glyphicon-liked'></span> {$number_likes}</div>";
  else
    $template->pageData['content'] .= "
        <div class='feed-icons'><span class='glyphicon glyphicon-thumbs-up'></span> {$number_likes}</div>";
  
  $template->pageData['content'] .= "
              <div class='feed-icons'><span class='glyphicon glyphicon-comment'></span> {$number_comments}</div>
              <div class='feed-icons'><span class='glyphicon glyphicon-share-alt'></span> {$number_shares}</div>
            </div>        
          </div>
        </div>
      </div>";
}

if (count($tips) == 0)
  $template->pageData['content'] .= 'No Teaching Tips have been posted yet.';

$template->pageData['content'] .= "
      </div>
      <div class='col-xs-12 homepage-view-more' value='10' data-filtertype='$filterType' data-period='$period'>
         <div class='glyphicon glyphicon-triangle-bottom'></div>
         View more
      </div>            
    </div>
  </div>
</div>";
$template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
