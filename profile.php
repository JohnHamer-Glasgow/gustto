<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
require_once(__DIR__.'/lib/constants.php');

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

if (!isset($_GET['usrID']) || !is_numeric($_GET['usrID']) || $_GET['usrID'] < 0) {
  $template->pageData['content'] .= pageNotFound();
  echo $template->render();
  exit();
}

$usrID = $_GET['usrID'];

$user = user::retrieve_user($usrID);
if (!$user) {
  $template->pageData['content'] .= pageNotFound();
  echo $template->render();
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$topThreeTT = $user->get_top_teaching_tips(3);
$loggedUserName =  $uinfo['gn'];
$loggedUserLastname = $uinfo['sn'];
$template->pageData['userLoggedIn'] = $loggedUserName . ' ' . $loggedUserLastname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['navProfile'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

$awards = $user->get_awards();

$template->pageData['content'] .= '
      <div class="col-sm-9 col-xs-12">
          <div class="card profile">
            <div class="cover">
              <div class="profile-username">
                ' . $user->name . ' ' . $user->lastname . '
              </div>
            </div>
            <div class="profile-img-wrapper">
              <img class="img responsive img-circle" src="' . $user->profile_picture . '" alt="profile picture">
            </div>';

if ($loggedUserID != $user->id) {
  $template->pageData['content'] .= 
    '<form class="profile-follow-form" action="ajax/follow_user.php" method="post">
            	<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '" />';
  if (checkUserFollowsUser($loggedUserID, $user->id))
    $template->pageData['content'] .= '<button type="submit" id="follow-btn" class="btn btn-default btn-followed" data-uid=' . $user->id . ' data-followed="1"><span class="glyphicon glyphicon-signal"></span> Following</button>';
  else
    $template->pageData['content'] .= '<button type="submit" id="follow-btn" class="follow-btn btn btn-default" data-uid=' . $user->id . ' data-followed="0"><span class="glyphicon glyphicon-signal"></span> Follow</button>';
} else {
  $template->pageData['content'] .= 
       '<div class="modal fade" id="profilePicModal" tabindex="-1" role="dialog" aria-labelledby="changePicLabel">
          <div class="modal-dialog modal-sm">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="changePicLabel">Change profile picture</h4>
              </div>
              <div class="modal-body">
                <form class="change-profile-pic-form" action="lib/image_upload.php" method="post" enctype="multipart/form-data">
                  <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '" />
                  <div class="form-group">
                    <label for="pictureInput">Choose a profile picture</label>
                    <input type="file" id="pictureInput" name="profilePicture">
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary btn-change-profile-pic"><span class="glyphicon glyphicon-upload"></span> Upload picture</button>
              </div>
            </div>
          </div>
        </div>';
  $template->pageData['content'] .= 
    '<a role="button" class="profile-change-pic" data-toggle="modal" data-target="#profilePicModal"><span class="glyphicon glyphicon-edit"></span> Change profile picture</a>';
}

$template->pageData['content'] .=            	
           '</form>
            <div class="row profile-info">
              <div class="profile-details col-xs-12">
                <div class="profile-details-college"><span class="glyphicon glyphicon-education"></span> '.$user->school.'</div>
                <div class="profile-details-email"><span class="glyphicon glyphicon-envelope"></span> '.$user->email.'</div>
              </div>
              <div class="profile-stats-header-wrapper col-xs-12">
                <ul class="nav nav-tabs reputation-nav-tabs" role="tablist">
                  <li role="presentation" class="reputation-nav-tab active"><a href="#esteem" aria-controls="esteem" role="tab" data-toggle="tab">Esteem</a></li>
                  <li role="presentation" class="reputation-nav-tab"><a href="#engagement" aria-controls="engagement" role="tab" data-toggle="tab">Engagement</a></li>
                  <li class="reputation-help"><a role="button" class="reputation-help-btn" data-toggle="collapse" href="#collapseReputation" aria-expanded="false" aria-controls="collapseReputation"><span class="glyphicon glyphicon-question-sign"></span></a></li>
                </ul>
                <div class="collapse" id="collapseReputation">
                  <div class="well">
                    <p><strong>Reputation</strong> is a rough measurement of how much the community trusts you. It is earned by convincing your peers that you know what you’re talking about.</p> 
                    <p>In GUSTTO, reputation is split into two categories: <strong>Esteem</strong> and <strong>Engagement</strong></p>
                    <p>Esteem reflects the activity of your colleagues when they access your Teaching Tips</p>                     
                    <p>Engagement reflects your activity when accessing others’ Teaching Tips</p>
                    <p>Reputation points are gained by viewing, liking, sharing, commenting on Teaching Tips, and following colleagues.</p>                     
                    <p>10 points in a category gains a <strong>Bronze</strong> award, 20 points for <strong>Silver</strong>, and 30 for <strong>Gold</strong>. A <strong>Star</strong> is awarded at mid-points (5, 15, 25).</p>
                  </div>
                </div>
                <div class="tab-content">
                  <div role="tabpanel" class="tab-pane active" id="esteem">
                    <div class="profile-stats-section-wrapper">
                      <div class="profile-stats-section section-reputation col-sm-4 col-sm-offset-4 col-xs-6 col-xs-offset-3">
                        <div class="profile-stats-reputation">
                          <div class="profile-stats-number">' . $user->esteem . '</div>
                          <div class="profile-stats-label">Esteem</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'overall', 'esteem');

$template->pageData['content'] .= '
                        </div>
                      </div>
                    </div>
                    <div class="tree-line-wrap tlw-2">
                      <div class="tree-line tl-middle tl-bottom"></div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-likes">
                        <div class="profile-stats-number">' . $user->get_number_received_likes() . ' Likes</div>';
                        
$template->pageData['content'] .= displayUserAwards($awards, 'likes', 'esteem');

$template->pageData['content'] .= '
                      </div>                      
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-views">
                        <div class="profile-stats-number">' . $user->get_number_received_views_tts() . ' Views</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'views', 'esteem');

$template->pageData['content'] .= '
                      </div>                      
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-shares">
                        <div class="profile-stats-number">' . $user->get_number_shares_of_tts() . ' Shares</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'shares', 'esteem');

$template->pageData['content'] .= '
                      </div>                      
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-comments">
                        <div class="profile-stats-number">' . $user->get_number_received_comments() . ' Comments</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'comments', 'esteem');

$template->pageData['content'] .= '                    
                      </div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-followers">
                        <div class="profile-stats-number">' . $user->get_number_followers() . ' Followers</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'follows', 'esteem');

$template->pageData['content'] .= '                    
                      </div>
                    </div>
                  </div>
                  <div role="tabpanel" class="tab-pane" id="engagement">
                    <div class="profile-stats-section-wrapper">
                      <div class="profile-stats-section section-reputation col-sm-4 col-sm-offset-4 col-xs-6 col-xs-offset-3">
                        <div class="profile-stats-reputation">
                          <div class="profile-stats-number">' . $user->engagement . '</div>
                          <div class="profile-stats-label">Engagement</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'overall', 'engagement');

$template->pageData['content'] .= '
                        </div>
                      </div>
                    </div>
                    <div class="tree-line-wrap tlw-2">
                      <div class="tree-line tl-middle tl-bottom"></div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-likes">
                        <div class="profile-stats-number">' . $user->get_number_given_likes() . ' Likes</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'likes', 'engagement');

$template->pageData['content'] .= '                        
                      </div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-views">
                        <div class="profile-stats-number">' . $user->get_number_given_views() . ' Views</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'views', 'engagement');

$template->pageData['content'] .= '                       
                      </div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-shares">
                        <div class="profile-stats-number">' . $user->get_number_given_shares() . ' Shares</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'shares', 'engagement');

$template->pageData['content'] .= '                        
                      </div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-comments">
                        <div class="profile-stats-number">' . $user->get_number_given_comments() . ' Comments</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'comments', 'engagement');

$template->pageData['content'] .= '                       
                      </div>
                    </div>
                    <div class="profile-stats-section custom-col-md-fifth col-xs-6">
                      <div class="tree-line tl-middle"></div>
                      <div class="profile-stats-followers">
                        <div class="profile-stats-number">' . $user->get_number_following() . ' Following</div>';

$template->pageData['content'] .= displayUserAwards($awards, 'follows', 'engagement');

$template->pageData['content'] .= '                    
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="profile-tts-header-wrapper col-xs-12">
                <div class="profile-tts-header">
                  <h4>Top Teaching Tips posted by '.$user->name.' '.$user->lastname.'</h4>
                </div>
              </div>
              <div class="clearfix visible-xs-block"></div>';

if (!empty($topThreeTT)) {
  foreach ($topThreeTT as $tt) {
    $number_likes = $tt->get_number_likes();
    $number_comments = $tt->get_number_comments();
    $number_shares = $tt->get_number_shares();
    $template->pageData['content'] .= "
    			<div class='profile-tt col-sm-4'>
                 <h5><a href='teaching_tip.php?ttID={$tt->id}'>{$tt->title}</a></h5>
                 <p>{$tt->description}</p>
                 <div class='profile-tt-stats'>
                   <div class='profile-tt-thumbs-up'><span class='glyphicon glyphicon-thumbs-up'></span> {$number_likes}</div>
                   <div class='profile-tt-comments-number'><span class='glyphicon glyphicon-comment'></span> {$number_comments}
                   </div>
                   <div class='profile-tt-shares'><span class='glyphicon glyphicon-share-alt'></span> {$number_shares}
                   </div>
                 </div>
               </div>";
  }
} else
  $template->pageData['content'] .= ' <div class="col-xs-12"><br/>There are no Teaching Tips posted by this user.</div>';

$ttsSorted = $user->get_top_teaching_tips();
if (sizeof($ttsSorted) > 3)
  $template->pageData['content'] .= "
			<div class='col-xs-12 profile-view-more' data-user-id='{$usrID}'>
                <div class='glyphicon glyphicon-triangle-bottom'></div> View more
             </div>
             <div class='col-xs-12 profile-view-more-tts-wrapper'></div>";

$template->pageData['content'].='
    		</div>
          </div>
        </div>';

$template->pageData['logoutLink'] = loginBox($uinfo);

$template->pageData['customJS'] .= "
  <script>
  $(document).ready(function () {
    $('[data-toggle=\"tooltip\"]').tooltip();
  });
  </script>";

echo $template->render();
