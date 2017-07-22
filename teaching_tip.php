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

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

session_start();
if (!isset($_SESSION['csrf_token']))
  $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));

if (!isset($_GET['ttID']) || !is_numeric($_GET['ttID']) || $_GET['ttID'] < 0) {
  $template->pageData['content'] .= pageNotFound();
  echo $template->render();
  exit();
}

$ttID = $_GET['ttID'];
$tt = teachingtip::retrieve_teachingtip($ttID);
$isDraft = false;
    
if (!$tt || ($user->isadmin == 0 && $tt->status == 'deleted')) {
  $template->pageData['content'] .= pageNotFound();
  echo $template->render();
  exit();
} 

if ($tt->status == 'draft') {
  $contrTTs = $user->get_contr_teaching_tips();
  $contr = false;
    foreach($contrTTs as $cTT)
      if ($ttID == $cTT->id) {
	$contr = true;
	break;
      }
      
    if ($tt->author_id == $loggedUserID || $contr || $user->isadmin == 1)
      $isDraft = true;
    else {
      $template->pageData['content'] .= pageNotFound();
      echo $template->render();
      exit();
    }
} 

$author = $tt->get_author();

$isViewed = ttview::check_viewed($loggedUserID, $ttID);
if (!$isViewed && $author->id != $loggedUserID) {
  $new_view = new ttview();
  $new_view->user_id = $loggedUserID;
  $new_view->teachingtip_id = $ttID;
  $new_view->insert();

  $tt_number_views = $tt->get_number_views();
  $author->esteem_views_tt($tt_number_views);

  $author = $tt->get_author();
  $author_aid = $author->update_awards('views', 'esteem');
  $author_oaid = $author->update_overall_awards('esteem');

  $logged_user_number_views = $user->get_number_given_views();
  $user->engagement_views_tt($logged_user_number_views);

  $user = user::retrieve_user($loggedUserID);
  $user_aid = $user->update_awards('views', 'engagement');
  $user_oaid = $user->update_overall_awards('engagement'); 

  if ($author_aid) createNotification($author->id, $author_aid, 'award', 'awards');
  if ($author_oaid) createNotification($author->id, $author_oaid, 'award', 'awards');
  
  if ($user_aid) createNotification($user->id, $user_aid, 'award', 'awards');
  if ($user_oaid) createNotification($user->id, $user_oaid, 'award', 'awards');
}
  
$username = $uinfo['uname'];
  
$loggedUserName =  $uinfo['gn'];
$loggedUserLastname = $uinfo['sn'];
  
$template->pageData['userLoggedIn'] = $loggedUserName.' '.$loggedUserLastname ;
$template->pageData['profileLink'] = "profile.php?usrID=".$loggedUserID;
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID,false,0));
$template->pageData['notifications'] = notifications($dbUser);
	
$keywords = $tt->get_keywords();
$comments = $tt->get_comments();
$liked = checkUserLikesTT($tt->id, $loggedUserID);
$number_shares = $tt->get_number_shares();
$contributors = $tt->get_contributors();

$class_size_f = $tt->get_filters('class_size');
$environment_f = $tt->get_filters('environment');
$suitable_ol = $tt->get_filters('suitable_ol')[0];
$it_competency_f = $tt->get_filters('it_competency');

$class_size = array();
$environment = array();
$it_competency = array();

foreach ($class_size_f as $cs) $class_size[] = $CLASS_SIZES[$cs];
if ($environment_f) foreach ($environment_f as $env) $environment[] = $ENVS[$env];
if ($it_competency_f) foreach ($it_competency_f as $itc) $it_competency[] = $ITC[$itc];

$template->pageData['content'] .= 
  '<div class="col-sm-9 col-xs-12">
          <div class="card teaching-tip">';

$template->pageData['content'].= 
  "<div class='row'>
              <div class='modal fade' id='shareModal' tabindex='-1' role='dialog' aria-labelledby='shareModalLabel'>
                <div class='modal-dialog' role='document'>
                  <div class='modal-content'>
                    <div class='modal-header'>
                      <button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
                      <h4 class='modal-title' id='shareModalLabel'>Share this Teaching Tip</h4>
                    </div>
                    <div class='modal-body'>
                      <div class='tt-share-error'></div>
                      <form class='tt-share-form' action='ajax/tt_share.php' method='post'>
                        <div class='form-group'>
                          <label for='recipient-name' class='control-label'>Share with:</label>
                          <input type='text' class='form-control' id='tt-share-recipient' name='recipient' required autocomplete='off' placeholder='Email address...'>
                          <div id='tt-share-recipient-results'>
                            <ul>                              
                            </ul>
                          </div>
                        </div>
                        <input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}' />
                        <input type='hidden' name='ttId' value='{$tt->id}' />
                        
                        <div class='form-group'>
                          <label for='message-text' class='control-label'>Include a message (optional):</label>
                          <textarea class='form-control' id='tt-share-message' placeholder='Optional message...'></textarea>
                        </div>
                      </form>
                    </div>
                    <div class='modal-footer'>
                      <button type='button' class='btn btn-default' id='tt-share-close' data-dismiss='modal'>Close</button>
                      <button type='submit' class='btn btn-primary' id='tt-share-send'><span class='glyphicon glyphicon-share-alt'></span> Share</button>
                    </div>
                  </div>
                </div>
              </div>

              <div class='tt-profile col-sm-2 col-xs-12'>
                <div class='row'>
                  <div class='tt-profile-image col-sm-12 col-xs-1 hidden-print'>
                    <img class='img-circle' src='{$author->profile_picture}' alt='profile picture'>
                  </div>
                  <div class='tt-profile-details col-sm-12 col-xs-11'>
                    <h4 class='tt-profile-name'><a href='profile.php?usrID={$author->id}'>{$author->name} {$author->lastname}</a></h4>
                    <div class='tt-school'>$author->school</div>
                    <div class='tt-datetime'>
                      <p class='tt-date'>".date('d M Y',$tt->whencreated)."</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class='tt-content col-sm-10 col-xs-12'> ";


if ($isDraft)
  $template->pageData['content'] .= 
    '<div class="alert alert-info alert-dismissible alert-tt-draft" role="alert">
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                  <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                  <strong>This Teaching Tip is a draft</strong>. Only the author and the co-authors can see this page.
                </div>';


$template->pageData['content'] .= "<h3 class='tt-name'>{$tt->title}</h3>";
if (!empty($contributors)) {
  $i = 0;
  $template->pageData['content'] .= "<h4>Co-authors</h4>";
  foreach ($contributors as $contributor) {
    if ($i > 0) $template->pageData['content'] .= ", ";
    $template->pageData['content'] .= "<a href=profile.php?usrID=". $contributor->id ." class='tt-contributor'>". $contributor->name . " " . $contributor->lastname ."</a>";
    $i++;
  }
}

$template->pageData['content'] .= 
  "<div class='tt-rationale'>
                  <h4>Rationale</h4>
                  <p>{$tt->rationale}</p>
                </div>

                <div class='tt-description'>
                  <h4>Description</h4>
                  <p>{$tt->description}</p>
                </div>

                <div class='tt-practice'>
                  <h4>What we did</h4>
                  " . htmlspecialchars_decode($tt->practice) . "
                </div>";

if (!empty($tt->worksbetter)) 
  $template->pageData['content'] .= 
                "<div class='tt-conditional-1'>
                  <h4>This tends to work better if</h4>
                  <p>{$tt->worksbetter}</p>
                </div>";

if (!empty($tt->doesntworkunless)) 
  $template->pageData['content'] .= 
                "<div class='tt-conditional-2'>
                  <h4>This doesn't work unless</h4>
                  <p>{$tt->doesntworkunless}</p>
                </div>";

$template->pageData['content'] .= 
  "<div class='tt-conclusion'>
                  <h4>Conclusion</h4>
                  <p>{$tt->essence}</p>
                </div>";

$template->pageData['content'] .=
  "<div class='tt-filters'>
                  <div class='tt-filter'><h4>Class size </h4><div class='tt-filter-d'>". implode(', ', $class_size) ."</div></div>
                  <div class='tt-filter'><h4>Environment </h4><div class='tt-filter-d'>". implode(', ', $environment) ."</div></div>
                  <div class='tt-filter'><h4>Suitable for online learning </h4><div class='tt-filter-d'>". ucfirst($suitable_ol) ."</div></div>
                  <div class='tt-filter'><h4>IT competency required </h4><div class='tt-filter-d'>". implode(', ', $it_competency) ."</div></div>
                </div>";

$template->pageData['content'] .= "<div class='tt-keywords'>
                  <h4>Keywords</h4>";

foreach($keywords as $kw)
  $template->pageData['content'] .= "<a href='search.php?q=". $kw->keyword ."&o=keyword'><div class='tt-keyword'>".$kw->keyword."</div></a>";

$template->pageData['content'] .= "</div>";

function plural($n, $single, $plural) {
  return $n == 1 ? "$n $single" : "$n $plural";
}

if (!$isDraft) {
  $number_comments = plural($tt->get_number_comments(), 'comment', 'comments');
  $number_likes = plural($tt->get_number_likes(), 'person likes this', 'people like this');
 
  $template->pageData['content'] .= "
<div class='tt-stats hidden-print'>
  <div class='tt-thumbs-up'>
   <span class='glyphicon glyphicon-thumbs-up'></span>
   <span class='tt-likes-number'>{$number_likes}</span>
  </div>
  <div class='tt-comments-number'>
    <span class='glyphicon glyphicon-comment'></span> {$number_comments}
  </div>
</div>
<div class='tt-options hidden-print'>";
  if ($liked)
    $template->pageData['content'] .= "<div class='tt-rate'><form class='tt-like-form' action='ajax/like_tt.php' method='post'><a role='button' type='submit' class='tt-liked' id='ttLikeButton' value='liked' data-ttid='{$tt->id}'><span class='glyphicon glyphicon-thumbs-up'></span> You like this</a></form></div>";
  else 
    $template->pageData['content'] .= "<div class='tt-rate'><form class='tt-like-form' action='ajax/like_tt.php' method='post'><a role='button' type='submit' id='ttLikeButton' value='not-liked' data-ttid='{$tt->id}'><span class='glyphicon glyphicon-thumbs-up'></span> Like</a></form></div>";
    
  $template->pageData['content'] .= 
    "<div class='tt-share'><a role='button' data-toggle='modal' data-target='#shareModal'><span class='glyphicon glyphicon-share-alt'></span> Share</a></div>
                    <div class='tt-print'><a onclick='window.print()' role='button'><span class='glyphicon glyphicon-print'></span> Print </a></div>
                  </div>";
}

$template->pageData['content'] .= "</div></div>";

if (!$isDraft) {
  $template->pageData['content'] .= " <div class='row tt-comments-row hidden-print'>
              <div class='tt-comments'>";

  $template->pageData['content'] .= 
    "<h3 class='tt-comments-header'>Comments</h3> ";

  foreach($comments as $c) {
    $comment_author = $c->get_author();
    $cTime = date('d M Y', $c->time);
    $template->pageData['content'] .= 
      "<div class='tt-comment'>
                    <div class='tt-comment-profile'>
                      <div class='tt-comment-profile-img col-md-1 col-sm-1 col-xs-1'>
                        <img class='img responsive img-circle' src='{$comment_author->profile_picture}' alt='profile picture'>
                      </div>

                      <div class='tt-comment-profile-details col-md-2 col-sm-11 col-xs-11'>
                        <div class='tt-comment-profile-name'><a href='profile.php?usrID={$comment_author->id}'>{$comment_author->name} {$comment_author->lastname}</a></div>
                        <div class='tt-comment-datetime'>{$cTime}</div>";

    if ($comment_author->id == $loggedUserID) 
      $template->pageData['content'] .= "
<div class='tt-comment-options'>
  <a class='tt-edit-comment-btn' role='button' data-target={$c->id}>Edit</a>
  <form class='tt-comment-delete-form' action='ajax/tt_delete_comment.php' method='post'>
    <a role='button' class='tt-comment-delete-btn' type='submit' data-cid='{$c->id}'>Delete</a>
  </form>
</div>";
    
    $template->pageData['content'] .= 
      "</div>
                      <div class='tt-comment-body tt-comment-body-{$c->id} col-md-9 col-sm-12 col-xs-12'>
                        <div class='arrow_box hidden-sm hidden-xs'></div>
                        <div class='arrow_box-up visible-sm visible-xs'></div>
                        <div class='tt-comment-body-text'>{$c->comment}</div>
                        <form class='tt-comment-edit-form' action='ajax/tt_edit_comment' method='post'>
                          <input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}' />
                          <textarea class='form-control tt-comment-edit-box' name='edit_comment_{$c->id}' required>{$c->comment}</textarea>
                          <button type='submit' class='btn btn-default btn-edit-comment-submit' data-cid='{$c->id}'>Save Changes</button>
                          <button type='button' class='btn btn-default btn-edit-comment-cancel'>Cancel</button>
                        </form>
                      </div>
                    </div>
                  </div>";                
  }
  
  $template->pageData['content'] .= '</div>';
  $template->pageData['content'] .= "<div class='clearfix'></div>";
  $template->pageData['content'] .= "
  <div class='tt-comment-form-wrapper'>
    <form class='tt-comment-form form-horizontal' action='ajax/tt_add_comment.php' method='post'>
      <input type='hidden' name='csrf_token' value='{$_SESSION['csrf_token']}' />
      <input type='hidden' name='ttID' value='{$tt->id}' />
      <div class='form-group'>
        <label for='inputComment' class='col-sm-2 control-label'>Add a comment</label>
        <div class='col-sm-10'>
          <textarea rows='4' cols='50' class='form-control' name='inputComment' id='inputComment' placeholder='Comment...' required></textarea>                      
        </div>
      </div>
      <div class='form-group'>
        <div class='col-sm-offset-2 col-sm-10'>
          <button type='submit' id='commentSubmit' class='btn btn-default'>Add comment</button>
        </div>
      </div>
     </form>
  </div>
</div>";
}

$template->pageData['content'] .= '</div></div>'; 
$template->pageData['logoutLink'] = loginBox($uinfo);
echo $template->render();
