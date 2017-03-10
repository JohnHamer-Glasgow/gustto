<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
require_once(__DIR__.'/lib/constants.php');
$template = new templateMerge($TEMPLATE);

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

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

  //User drop down
  $template->pageData['userLoggedIn'] = $givenname.' '.$surname ;
  $template->pageData['profileLink'] = "profile.php?usrID=".$loggedUserID;

  $template->pageData['navHome'] = 'sidebar-current-page';

  //Notifications
  if (notification::getNotifications($loggedUserID,false,0) == false) $notificationNo = 0;
  else $notificationNo = sizeof(notification::getNotifications($loggedUserID,false,0));
  
  $template->pageData['notificationNo'] = $notificationNo;
  $template->pageData['notifications'] = notifications($dbUser);

	//Content
 
	$template->pageData['content'].= 
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

  //Change of message in drop down
  
  $template->pageData['content'].= '<div class="col-xs-1 glyphicon glyphicon-blackboard options-icon"></div><span class="feed-dropdown-text">Most Teaching Tips</span>';
  
                  
  $template->pageData['content'].= 
                '<span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu main-feed-dropdown">
                    <li class="col-xs-12 options-wrapper" id="filter-recent">
                      <div class="col-xs-1 glyphicon glyphicon-refresh options-icon options-icon-refresh"></div>
                      <div class="col-xs-9 options-option"> <a href="index.php">Most Recent</a></div>';
  
  
    $template->pageData['content'].= '
                    </li>
                    <li class="col-xs-12 options-wrapper" id="filter-top-liked">
                      <div class="col-xs-1 glyphicon glyphicon-fire options-icon options-icon-fire"></div>
                      <div class="col-xs-9 options-option"> <a href="index.php?filterType=like&period=alltime">Most Liked</a></div>';
  
    $template->pageData['content'].= '                          
                    </li>
                    <li class="col-xs-12 options-wrapper" id="filter-top-commented">
                      <div class="col-xs-1 glyphicon glyphicon-pencil options-icon options-icon-pencil"></div>
                      <div class="col-xs-9 options-option"> <a href="index.php?filterType=comment&period=alltime">Most Commented</a></div>';

    $template->pageData['content'] .= '
                    <li class="col-xs-12 options-wrapper" id="filter-top-users">
                      <div class="col-xs-1 glyphicon glyphicon-blackboard options-icon"></div>
                      <div class="col-xs-9 options-option"> <a href="top_users.php">Most Teaching Tips</a></div>
                      <div class="col-xs-2 glyphicon glyphicon-ok options-icon ok-recent"></div>
                    </li>';
  
    $template->pageData['content'].= '
                    </li>
                  </ul>
                </div>
              </div> <!-- End Dropdown Options -->';

  $template->pageData['content'].= '          
            <div class="row feed-row">
              <div class="col-xs-12 feed-tts">';
  


  $top_users = user::get_most_tts();
  
  
  foreach($top_users as $tu) {
    $u = $tu[0];
    $tts_number = $tu[1];

    foreach ($COLLEGES as $key=>$college) {
      if ($u->college == 'College of ' . $college) {$user_college = $key; break;}
        
    }
    if ($u->school != 'Adam Smith Business School') {
      $school = explode(' ', $u->school, 3);
      $user_school = $school[2];
    } else $user_school = $u->school;

    $tts_string = "Teaching Tips";
    if ($tts_number == 1) $tts_string = "Teaching Tip";

    $template->pageData['content'] .= 
              "<div class='feed-tt'>
                <div class='row'>
                  
                  <div class='col-sm-2 feed-profile hidden-xs'>
                    <img class='img-circle' src='{$u->profile_picture}' alt='profile picture'>
                  </div><!-- end tt-profile -->

                  <div class='col-xs-12 col-sm-10 feed-info'>
                    <div class='row visible-xs'>
                      <div class='col-xs-2 feed-profile'>
                        <img class='img-circle img-responsive' src='{$u->profile_picture}' alt='profile picture'>
                        
                      </div>
                      <div class='col-xs-10'>
                        <h4 class='feed-tt-title'><a href='profile.php?usrID={$u->id}'>{$u->name} {$u->lastname}</a></h4>
                        <a href='search.php?college={$user_college}&school={$user_school}' class='feed-tt-profile-school'>{$u->school}</a>
                      </div>
                    </div>
                    <div class='feed-title hidden-xs'>
                      <h4 class='feed-tt-title'><a href='profile.php?usrID={$u->id}'>{$u->name} {$u->lastname}</a></h4>
                      <a href='search.php?college={$user_college}&school={$user_school}' class='feed-tt-profile-school'>{$u->school}</a>
                    </div>
                    <div class='feed-text-wrapper'>
                      <strong>{$tts_number} {$tts_string}</strong>
                    </div>";

    
    
    

    $template->pageData['content'] .= 
                      "</div>
                </div>
              </div>";
                 
  }
  
                    
  $template->pageData['content'] .= 
              '
              </div><!-- End col-xs-12 -->

              <div class="col-xs-12 homepage-view-more" value="10" data-filtertype="'.$_GET['filterType'].'" data-period="'.$_GET['period'].'">
                  <div class="glyphicon glyphicon-triangle-bottom"></div> View more
              </div>
            
            </div> <!-- END ROW -->
          </div>
        </div>';	

    $template->pageData['logoutLink'] = loginBox($uinfo);

}


//if(error_get_last()==null)
    echo $template->render();
//else
//    echo "<p>Not rendering template to avoid hiding error messages.</p>";

?>
