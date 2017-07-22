<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');
require_once(__DIR__ . '/lib/formfunctions.php');

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

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$dbUser = getUserRecord($uinfo);
$loggedUserId = $dbUser->id;

$template->pageData['userLoggedIn'] = $givenname . ' ' . $surname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserId;
$template->pageData['navHome'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserId, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

$loggedUser = user::retrieve_user($loggedUserId);
$user_settings = $loggedUser->get_settings();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])
    exit();

  $error = false;
  $update = true;

  if (isset($_POST['options1']) && is_numeric($_POST['options1']) && ($_POST['options1'] == 1 || $_POST['options1'] == 2))
    $opt1 = sanitize_input($_POST['options1']);
  
  if (isset($_POST['options2']) && is_numeric($_POST['options2']) && ($_POST['options2'] == 1 || $_POST['options2'] == 2))
    $opt2 = sanitize_input($_POST['options2']);

  if (isset($_POST['options3']) && is_numeric($_POST['options3']) && ($_POST['options3'] == 1 || $_POST['options3'] == 2))
    $opt3 = sanitize_input($_POST['options3']);

  if (isset($_POST['options5']) && is_numeric($_POST['options5']) && ($_POST['options5'] == 1 || $_POST['options5'] == 2))
    $opt5 = sanitize_input($_POST['options5']);

  if (isset($_POST['options6']) && is_numeric($_POST['options6']) && ($_POST['options6'] == 0 || $_POST['options6'] == 1))
    $opt6 = sanitize_input($_POST['options6']);
   
  if (!$error) {
    $user_settings->school_posts = $opt1 ?: 0;
    $user_settings->tts_activity = $opt2 ?: 0;
    $user_settings->followers_posts = $opt3 ?: 0;
    $user_settings->awards = $opt5 ?: 0;
    $user_settings->update();
  }
}

$options1 = array_fill(0, 2, '');
$options2 = array_fill(0, 2, '');
$options3 = array_fill(0, 1, '');
$options5 = array_fill(0, 2, '');
$options6 = array_fill(0, 2, '');

if ($user_settings->school_posts == 1)
  $options1[0] = 'checked';
elseif ($user_settings->school_posts == 2)
  $options1[1] = 'checked';

if ($user_settings->tts_activity == 1)
  $options2[0] = 'checked';
elseif ($user_settings->tts_activity == 2)
  $options2[1] = 'checked';

if ($user_settings->followers_posts == 1)
  $options3[0] = 'checked';
elseif ($user_settings->followers_posts == 2)
  $options3[1] = 'checked';

if ($user_settings->awards == 1)
  $options5[0] = 'checked';
elseif ($user_settings->awards == 2)
  $options5[1] = 'checked';

if ($options1[0] == '' && $options2[0] == '' && $options3[0] == '' && $options5[0] == '') 
  $options6[0] = 'checked';
if ($options1[1] == '' && $options2[1] == '' && $options3[1] == '' && $options5[1] == '')
  $options6[1] = 'checked';

$template->pageData['content'] = '
    <div class="col-xs-12 col-sm-9">
    <div class="card settings-card">
    <div class="main-header">
    <div class="col-xs-8 main-header-notifications main-header">
    <h4>Settings</h4>
    </div>
    </div>
  <div class="row settings-row">
    <div class="settings-notifications col-xs-12"><h5>Email Notifications</h5></div>';

if ($update && $error) 
  $template->pageData['content'] .= 
    '<div class="settings-form-alert-wrapper col-xs-12">
                  <div class="settings-form-alert alert alert-danger alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                    <strong>There was an error updating your settings!</strong> Please try again.
                  </div>
                </div>';
elseif ($update && !$error)
  $template->pageData['content'] .= 
  '<div class="settings-form-alert-wrapper col-xs-12">
                  <div class="settings-form-alert alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
                    <strong>Your settings have been updated successfully.</strong>
                  </div>
                </div>';

$template->pageData['content'] .=
  '<div class="col-xs-12 settings-options">
      <form class="user-settings-form" action="" method="post">
        <input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] . '" />
        <div class="settings-options-category col-md-6 col-xs-12" style="padding-right: 15px">
          <h5>Send me emails for every</h5>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="options1" value="1" ' . $options1[0] . '>
              New Teaching Tip in my school.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="options2" value="1" ' . $options2[0] . '>
              Like/comment/share on my Teaching Tips.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="options3" value="1" ' . $options3[0] . '>
              New Teaching Tip from people I follow.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" name="options5" value="1" ' . $options5[0] . '>
              New award that I earn.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" class="no-notifications" name="options6" value="1" ' . $options6[0] . '>
              Never
            </label>
          </div>
        </div>
        <div class="settings-options-category col-md-6 col-xs-12">
          <h5>Send me weekly digests of</h5>
          <div class="checkbox">
            <label>
              <input type="checkbox" class="digest" name="options1" value="2" ' . $options1[1] . '>
              New Teaching Tips from colleagues in my School.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" class="digest" name="options2" value="2" ' . $options2[1] . '>
              Likes/comments/shares on my own Teaching Tips.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" class="digest" name="options3" value="2" ' . $options3[1] . '>
              New Teaching Tips from people I follow.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" class="digest" name="options5" value="2" ' . $options5[1] . '>
              New awards that I earn.
            </label>
          </div>
          <div class="checkbox">
            <label>
              <input type="checkbox" class="no-notifications" name="options6" value="2" ' . $options6[1] . '>
              Never
            </label>
          </div>
        </div>
        <button type="submit" class="btn btn-default btn-settings-form">Save Changes</button>
      </form>
    </div>
    </div>';

$template->pageData['content'] .= '
  <div class="row settings-row">
    <div class="settings-followers col-xs-12"><h5>Followers</h5></div>
    <div class="settings-ul-wrapper col-xs-12">';
 
$followers = getFollowers($loggedUserId);

if (!empty($followers)) {
  $template->pageData['content'] .= '<ul>';
  foreach ($followers as $f) {
    $name = $f->name;
    $surname = $f->lastname;
    $fId = $f->id;
    $template->pageData['content'] .= '<li class="col-xs-6"><a href="profile.php?usrID=' . $fId . '">' . $name . ' ' . $surname . '</a></li>';
  }
  $template->pageData['content'] .= '</ul>';
} else
  $template->pageData['content'] .= '
      <div class="no-followers col-xs-12"><strong>You are not being followed by anyone.</strong></div>';

$template->pageData['content'] .= '</div></div>';
    
$template->pageData['content'] .= '
  <div class="row settings-row">
    <div class="settings-following col-xs-12"><h5>Following <span>(By following someone you will receive notifications when they comment, like or post a Teaching Tip)</span></h5></div>
    <div class="settings-ul-wrapper col-xs-12">';

$following = getFollowing($loggedUserId);

if ($following) {
  $template->pageData['content'] .= '<ul>';
  foreach ($following as $f) {
    $name = $f->name;
    $surname = $f->lastname;
    $fId = $f->id;
    $template->pageData['content'] .= '<li class="col-xs-6"><a href="profile.php?usrID=' . $fId . '">' . $name . ' ' . $surname . '</a></li>';
  }
  $template->pageData['content'] .= '</ul>';
} else
  $template->pageData['content'] .= '
      <div class="no-followers col-xs-12"><strong>You do not follow anyone.</strong></div>';

$template->pageData['content'] .= '
  </div>
  </div>
    </div>
    </div>';

$template->pageData['logoutLink'] = loginBox($uinfo);

$template->pageData['customJS'] .= 
  "<script src='js/settings.js'></script>
  <script>
  $(document).ready(function () {
    $('.alert-success').delay(2000).slideUp();
  });
  </script>";

echo $template->render();
