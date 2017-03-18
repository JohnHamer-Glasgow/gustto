<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');
require_once(__DIR__ . '/lib/constants.php');

$uinfo = checkLoggedInUser();
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

if (notification::getNotifications($loggedUserID, false, 0) == false)
  $notificationNo = 0;
else
  $notificationNo = sizeof(notification::getNotifications($loggedUserID, false, 0));

$template->pageData['notificationNo'] = $notificationNo;
$template->pageData['notifications'] = notifications($dbUser);

$user = user::retrieve_user($loggedUserID);

session_start();
$new_tts_number = $user->get_number_new_tts(isset($_SESSION['last_visit']) ? $_SESSION['last_visit'] : $user->last_visit);

$most_liked = false;
$most_commented = false;
$latestTeachingTips = false;
$checkedAll = $checkedThree = $checkedMonth = $checkedWeek = ''; //Used for radio buttons as each time page is refreshed

if (isset($_GET['filterType']) &&  $_GET['filterType'] == "like"){
  if (isset($_GET['period']) &&  $_GET['period'] == "alltime"){
    $most_liked = teachingtip::getPopularTeachingTips(5);
    $checkedAll = "checked";
  } elseif (isset($_GET['period']) &&  $_GET['period'] == "lastthree"){
    $most_liked = teachingtip::getPopularTeachingTips(5, 0, true, '>', time() - (60 * 60 * 24 * 90));
    $checkedThree = "checked";
  } elseif (isset($_GET['period']) &&  $_GET['period'] == "lastmonth") {
    $most_liked = teachingtip::getPopularTeachingTips(5, 0, true, '>', time() - (60 * 60 * 24 * 30));
    $checkedMonth = "checked";
  } elseif (isset($_GET['period']) &&  $_GET['period'] == "lastweek") {
    $most_liked = teachingtip::getPopularTeachingTips(5, 0, true, '>', time() - (60 * 60 * 24 * 7));
    $checkedWeek = "checked";
  }
} elseif (isset($_GET['filterType']) &&  $_GET['filterType'] == "comment"){
    // PERIOD FILTERING
  if (isset($_GET['period']) &&  $_GET['period'] == "alltime"){
    $most_commented = teachingtip::getPopularTeachingTips(5, 0, false);
    $checkedAll = "checked";
  } elseif(isset($_GET['period']) &&  $_GET['period'] == "lastthree"){
    $most_commented = teachingtip::getPopularTeachingTips(5, 0, false, '>', time() - (60 * 60 * 24 * 90));
    $checkedThree = "checked";
  } elseif (isset($_GET['period']) &&  $_GET['period'] == "lastmonth") {
    $most_commented = teachingtip::getPopularTeachingTips(5, 0, false, '>', time() - (60 * 60 * 24 * 30));
    $checkedMonth = "checked";
  } elseif (isset($_GET['period']) &&  $_GET['period'] == "lastweek") {
    $most_commented = teachingtip::getPopularTeachingTips(5,0, false, '>', time() - (60 * 60 * 24 * 7));
    $checkedWeek = "checked";
  }
} else
  $latestTeachingTips = getLatestTeachingTips(10);

if ($most_liked)
  $teachingTipsListToUse = $most_liked;
elseif ($most_commented)
  $teachingTipsListToUse = $most_commented;
else
  $teachingTipsListToUse = $latestTeachingTips;
 
$template->pageData['content'] .= 
  '<div class="col-xs-12 col-sm-9">
          <div class="card home-page">
            <div class=" col-xs-9 main-header">
              <h4>Teaching Tip Feed</h4>
            </div>

            <!-- Dropdown options -->
            <div class="col-xs-3 activity-options main-header">
              <!-- Single button -->
                <div class="btn-group">
                  <button type="button" class="btn btn-default dropdown-toggle visible-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Feed
                    <span class="caret"></span>
                  </button>
                  <button type="button" class="btn btn-default dropdown-toggle hidden-xs" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
if ($latestTeachingTips)
  $template->pageData['content'] .= '<div class="col-xs-1 glyphicon glyphicon-refresh options-icon options-icon-refresh"></div><span class="feed-dropdown-text">Most Recent</span>';
elseif ($most_liked)
  $template->pageData['content'] .= '<div class="col-xs-1 glyphicon glyphicon-fire options-icon options-icon-fire"></div><span class="feed-dropdown-text">Most Liked</span>';
else
  $template->pageData['content'] .= '<div class="col-xs-1 glyphicon glyphicon-pencil options-icon options-icon-pencil"></div><span class="feed-dropdown-text">Most Commented</span>';
                  
$template->pageData['content'] .= 
  '<span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu main-feed-dropdown">
                    <li class="col-xs-12 options-wrapper" id="filter-recent">
                      <div class="col-xs-1 glyphicon glyphicon-refresh options-icon options-icon-refresh"></div>
                      <div class="col-xs-9 options-option"> <a href="index.php">Most Recent</a></div>';
  
if ($latestTeachingTips)
  $template->pageData['content'] .= '<div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-recent"></div>';
$template->pageData['content'] .= '</li>
                    <li class="col-xs-12 options-wrapper" id="filter-top-liked">
                      <div class="col-xs-1 glyphicon glyphicon-fire options-icon options-icon-fire"></div>
                      <div class="col-xs-9 options-option"> <a href="index.php?filterType=like&period=alltime">Most Liked</a></div>';
if ($most_liked)
  $template->pageData['content'] .= '  
                      <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-recent"></div>';
$template->pageData['content'] .= '                          
                    </li>
                    <li class="col-xs-12 options-wrapper" id="filter-top-commented">
                      <div class="col-xs-1 glyphicon glyphicon-pencil options-icon options-icon-pencil"></div>
                      <div class="col-xs-9 options-option"> <a href="index.php?filterType=comment&period=alltime">Most Commented</a></div>';
if ($most_commented)
  $template->pageData['content'] .= '<div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-recent"></div>';

$template->pageData['content'] .= '
                    <li class="col-xs-12 options-wrapper" id="filter-top-users">
                      <div class="col-xs-1 glyphicon glyphicon-blackboard options-icon"></div>
                      <div class="col-xs-9 options-option"> <a href="top_users.php">Most Teaching Tips</a></div>
                    </li>';
$template->pageData['content'] .= '
                    </li>
                  </ul>
                </div>
              </div>';

if ($latestTeachingTips == false && $most_liked){
  $template->pageData['content'] .= '
              <div class="col-xs-12 feed-period-header">
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=like&period=alltime"><input type="radio" name="period" value="allTime" ' . $checkedAll . '>  All Time</a></div>
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=like&period=lastthree" class="home-filter-tooltip" data-toggle="tooltip" data-placement="top" title="Teaching Tips posted in the last three months"><input type="radio" name="period" value="lastThree"' . $checkedThree . '>  < 3 Months</a></div>
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=like&period=lastmonth" class="home-filter-tooltip" data-toggle="tooltip" data-placement="top" title="Teaching Tips posted in the last month"><input type="radio" name="period" value="lastOne"' . $checkedMonth . '>  < 1 Month</a></div>
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=like&period=lastweek" class="home-filter-tooltip" data-toggle="tooltip" data-placement="top" title="Teaching Tips posted in the last week"><input type="radio" name="period" value="lastWeek"' . $checkedWeek . '>  Last Week</a></div>
              </div>';
} elseif($latestTeachingTips==false && $most_commented) {
  $template->pageData['content'] .= '
              <div class="col-xs-12 feed-period-header">
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=comment&period=alltime"><input type="radio" name="period" value="allTime" ' . $checkedAll . '>  All Time</a></div>
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=comment&period=lastthree" class="home-filter-tooltip" data-toggle="tooltip" data-placement="top" title="Teaching Tips posted in the last three months"><input type="radio" name="period" value="lastThree"' . $checkedThree . '>  < 3 Months</a></div>
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=comment&period=lastmonth" class="home-filter-tooltip" data-toggle="tooltip" data-placement="top" title="Teaching Tips posted in the last month"><input type="radio" name="period" value="lastOne"' . $checkedMonth . '>  < 1 Month</a></div>
                <div class="feed-period-a col-xs-3"><a href="index.php?filterType=comment&period=lastweek" class="home-filter-tooltip" data-toggle="tooltip" data-placement="top" title="Teaching Tips posted in the last week"><input type="radio" name="period" value="lastWeek"' . $checkedWeek . '>  Last Week</a></div>
              </div>';
}

$nUsers = user::get_number_users();
$nUsersSpan = $nUsers == 1 ? 'There is one user' : "There are $nUsers users";
$nTTs = teachingtip::get_number_tts();
$nTTsSpan = $nTTs == 1 ? 'one Teaching Tip' : "$nTTs Teaching Tips"; 
$template->pageData['content'] .= '          
            <div class="row feed-row">
              <div class="col-xs-12 total-number-tts-users"><span>' . $nUsersSpan . '<span> and <span>' . $nTTsSpan . '</span> (' . $new_tts_number . ' new since your last visit)
              </div>              
              <div class="col-xs-12 feed-tts">';
  
if ($teachingTipsListToUse) {
  foreach($teachingTipsListToUse as $ltt) {
    $tt = teachingtip::retrieve_teachingtip($ltt->id);
    $tt_time = date('d M y', $tt->whencreated);
    $number_likes = $tt->get_number_likes();
    $number_comments = $tt->get_number_comments();
    $number_shares = $tt->get_number_shares();
    $keywords = $tt->get_keywords();
    $author = $tt->get_author();
    $author_college = '';
    $author_school = '';

    foreach ($COLLEGES as $key=>$college) {
      if ($author->college == 'College of ' . $college) {
	$author_college = $key;
	break;
      }
    }
    
    if ($author->school != 'Adam Smith Business School') {
      $school = explode(' ', $author->school, 3);
      $author_school = $school[2];
    } else
      $author_school = $author->school;

    $template->pageData['content'] .= 
                "<div class='feed-tt'>
                  <div class='row'>                    
                    <div class='col-sm-2 feed-profile hidden-xs'>
                      <img class='img-circle' src='{$author->profile_picture}' alt='profile picture'>
                      <a href='profile.php?usrID=" . $author->id . "' class='col-xs-12 tt-profile-name '>{$author->name} {$author->lastname}</a>
                      <div class='clearfix'></div>
                      <span class='feed-tt-time'>{$tt_time}</span>
                    </div><!-- end tt-profile -->
                    <div class='col-xs-12 col-sm-10 feed-info'>
                      <div class='row visible-xs'>
                        <div class='col-xs-2 feed-profile'>
                          <img class='img-circle img-responsive' src='{$author->profile_picture}' alt='profile picture'>                          
                        </div>
                        <div class='col-xs-10'>
                          <h4 class='feed-tt-title'><a href='teaching_tip.php?ttID={$tt->id}'>{$tt->title}</a></h4>
                          <span class='feed-tt-time'>{$tt_time}</span>. <a href='search.php?college={$author_college}&school={$author_school}' class='feed-tt-profile-school'>{$author->school}</a>
                        </div>
                      </div>
                      <div class='feed-title hidden-xs'>
                        <h4 class='feed-tt-title'><a href='teaching_tip.php?ttID={$tt->id}'>{$tt->title}</a></h4>
                        <a href='search.php?college={$author_college}&school={$author_school}' class='feed-tt-profile-school'>{$author->school}</a>
                      </div>
                      <div class='feed-text-wrapper'>
                        <p class='feed-text'>{$tt->description}</p>
                      </div>";

    if ($keywords) {
      $template->pageData['content'] .= "<div class='feed-tt-keywords'>";
      foreach($keywords as $kw)
	$template->pageData['content'] .= "<a href='search.php?q=" . $kw->keyword . "&o=keyword'><div class='tt-keyword'>" . $kw->keyword . "</div></a>";
      $template->pageData['content'] .= "</div>"; 
    }
      
    $template->pageData['content'] .= "<div class='feed-icons-wrapper'>";

    if (checkUserLikesTT($tt->id, $loggedUserID))
      $template->pageData['content'] .= 
                        "<div class='feed-icons'><span class='glyphicon glyphicon-thumbs-up glyphicon-liked'></span> {$number_likes}</div>";
    else
      $template->pageData['content'] .= 
	"<div class='feed-icons'><span class='glyphicon glyphicon-thumbs-up'></span> {$number_likes}</div>";

    $template->pageData['content'] .= 
                        "<div class='feed-icons'><span class='glyphicon glyphicon-comment'></span> {$number_comments}</div>
                        <div class='feed-icons'><span class='glyphicon glyphicon-share-alt'></span> {$number_shares}</div>
                      </div>
                      
                    </div>
                  </div>
                </div>";
  }
} else
  $template->pageData['content'] .= 'No Teaching Tips have been posted yet.';

$template->pageData['content'] .= '
              </div>
              <div class="col-xs-12 homepage-view-more" value="10" data-filtertype="' . $_GET['filterType'] . '" data-period="' . $_GET['period'].'">
                  <div class="glyphicon glyphicon-triangle-bottom"></div> View more
              </div>            
            </div>
          </div>
        </div>';
    $template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
