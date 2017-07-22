<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/constants.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) {
  header("Location: ../login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);

if ($user->isadmin != '1') {
  header("Location: ../index.php");
  exit();
}

session_start();
if (!isset($_SESSION['csrf_token']))
  $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$template = new templateMerge('../html/template.html');
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';

// have to include these here again since they can't be loaded from the template
// because this page is in a different folder and the paths in the template are hardocded
$template->pageData['customCSS'] = '
<link href="../css/bootstrap.min.css" rel="stylesheet">
<link href="../css/style.css" rel="stylesheet">'; 

$template->pageData['homeURL'] = '../index.php';
$template->pageData['logoURL'] = '../images/logo/logo.png';

$template->pageData['userLoggedIn'] = $givenname.' '.$surname ;
$template->pageData['profileLink'] = "../profile.php?usrID=".$loggedUserID;
$template->pageData['navHome'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

$esteem = array();
$engagement = array();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
  $as = admin_settings::get_settings();
  $esteem[0] = $as->esteem_like; 
  $esteem[1] = $as->esteem_comment; 
  $esteem[2] = $as->esteem_share; 
  $esteem[3] = $as->esteem_view; 
  $esteem[4] = $as->esteem_follow;

  $engagement[0] = $as->engagement_like;
  $engagement[1] = $as->engagement_comment;
  $engagement[2] = $as->engagement_share;
  $engagement[3] = $as->engagement_view;
  $engagement[4] = $as->engagement_follow;

  $log_actions = $as->log_actions;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!(isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) exit();

  $error = false;

  if (isset($_POST['esteem'])) {
    foreach ($_POST['esteem'] as $esp) {
      if (isset($esp) && is_numeric($esp) && $esp >= 0) $esteem[] = sanitize_input($esp); 
      else {
	$error = true;
	break;
      }
    }
  }

  if (isset($_POST['engagement'])) {
    foreach ($_POST['engagement'] as $enp) {
      if (isset($enp) && is_numeric($enp) && $enp >= 0) $engagement[] = sanitize_input($enp); 
      else {
	$error = true;
	break;
      }
    }
  }

  if (isset($_POST['logActions']) && is_numeric($_POST['logActions']) && ($_POST['logActions'] == 0 || $_POST['logActions'] == 1))
    $log_actions = sanitize_input($_POST['logActions']);
  else
    $error = true;

  if (!$error) {
    $as = admin_settings::get_settings();
    $as->esteem_like = $esteem[0];
    $as->esteem_comment = $esteem[1];
    $as->esteem_share = $esteem[2];
    $as->esteem_view = $esteem[3];
    $as->esteem_follow = $esteem[4];

    $as->engagement_like = $engagement[0];
    $as->engagement_comment = $engagement[1];
    $as->engagement_share = $engagement[2];
    $as->engagement_view = $engagement[3];
    $as->engagement_follow = $engagement[4];

    $as->log_actions = $log_actions;
	    
    $as->update();

    require_once(__DIR__ . '/Scores.php');
    $scores = new Scores();
    $scoreByUser = array();
    foreach ($scores->esteem as $user_id => $data) {
      if (!isset($scoreByUser[$user_id])) $scoreByUser[$user_id] = array('esteem' => 0, 'engagement' => 0);
      $scoreByUser[$user_id]['esteem'] = Scores::score($scores->esteem, $user_id, $scores->esteemScores);
    }
    
    foreach ($scores->engagement as $user_id => $data) {
      if (!isset($scoreByUser[$user_id])) $scoreByUser[$user_id] = array('esteem' => 0, 'engagement' => 0);
      $scoreByUser[$user_id]['engagement'] = Scores::score($scores->engagement, $user_id, $scores->engagementScores);
    }

    foreach ($scoreByUser as $user_id => $ee)
      dataConnection::runQuery("update user set esteem = " . $ee['esteem'] . ", engagement = " . $ee['engagement'] . " where id = $user_id");
  }
}

$template->pageData['content'] = '
<style>
  .nav-bar-xs {display: none !important;} 
  .sidebar-wrapper {display: none !important;}
  .notification-button-group {display: none !important;}
  .settings-dropdown-item, .profile-dropdown-item {display: none !important;}
</style>';

$template->pageData['content'] .= '
<div class="col-xs-12">
  <div class="card home-page">
    <div class="main-header">
      <h4>Admin Panel</h4>
    </div>

    <ul class="nav nav-tabs admin-panel-nav-tabs" role="tablist">
      <li role="presentation" class="active"><a href=".">Settings</a></li>
      <li role="presentation" class=""><a href="teachingtips.php">Teaching Tips</a></li>
      <li role="presentation" class=""><a href="users.php">Users</a></li>
      <li role="presentation"><a href="statistics.php">Statistics</a></li>
    </ul>

    <div class="row">
      <form class="admin-panel-settings-form form-horizontal" action="" method="post">
	<input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] .'" />
	<div class="admin-panel-cat-header-wrapper col-xs-12">
	  <h4 class="admin-panel-cat-header">Reputation</h4>
	</div>

	<div class="form-group">
	  <div class="col-md-2 col-md-offset-2"><h4>Esteem</h4></div>
	  <div class="col-md-2"><h4>Engagement</h4></div>
	</div>

	<div class="form-group">
	  <label for="esteemLike" class="col-sm-2 control-label">Like points:</label>
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="esteem[]" id="esteemLike" value="' . $esteem[0] . '">
	  </div>
	  
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="engagement[]" id="engagementLike" value="' . $engagement[0] . '">
	  </div>
	</div>

	<div class="form-group">
	  <label for="esteemComment" class="col-sm-2 control-label">Comment points:</label>
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="esteem[]" id="esteemComment" value="' . $esteem[1] . '">
	  </div>
	  
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="engagement[]" id="engagementComment" value="' . $engagement[1] . '">
	  </div>
	</div>

	<div class="form-group">
	  <label for="esteemShare" class="col-sm-2 control-label">Share points:</label>
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="esteem[]" id="esteemShare" value="' . $esteem[2] . '">
	  </div>
	  
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="engagement[]" id="engagementShare" value="' . $engagement[2] . '">
	  </div>
	</div>

	<div class="form-group">
	  <label for="esteemView" class="col-sm-2 control-label">View points:</label>
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="esteem[]" id="esteemView" value="' . $esteem[3] . '">
	  </div>
	  
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="engagement[]" id="engagementView" value="' . $engagement[3] . '">
	  </div>
	</div>

	<div class="form-group">
	  <label for="esteemFollow" class="col-sm-2 control-label">Follow points:</label>
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="esteem[]" id="esteemFollow" value="' . $esteem[4] . '">
	  </div>
	  
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="engagement[]" id="engagementFollow" value="' . $engagement[4] . '">
	  </div>
	</div>

	<div class="admin-panel-cat-header-wrapper col-xs-12">
	  <h4 class="admin-panel-cat-header">Awards</h4>
	</div>

	<div class="form-group">
	  <label for="logActions" class="col-sm-2 control-label">Log users\' actions</label>
	  <div class="col-sm-2">
	    <input type="text" class="form-control" name="logActions" id="logActions" value="' . $log_actions . '">
	  </div>
	</div>

	<div class="col-xs-12">
	  <button type="submit" class="btn btn-default btn-settings-form" style="margin-top: 15px;">Save Changes</button>
	</div>
      </form>
    </div>
  </div>
</div>';	

$template->pageData['logoutLink'] = loginBox($uinfo);

// have to include these here again since they can't be loaded from the template
// because this page is in a different folder and the paths in the template are hardcoded
$template->pageData['customJS'] .= '
<script src="../js/bootstrap.min.js"></script>
<script>
  $(document).ready(function () {
    $(".logout-dropdown-item a").attr("href", "../login.php?logout=1");
    $(".footer-logout-link").attr("href", "../login.php?logout=1");
    $("#homePage-link").attr("href", "../index.php");
    $("#aboutPage-link").attr("href", "../about.php");
    $("#ethicsPage-link").attr("href", "../ethics.php");
  });
</script>';

echo $template->render();