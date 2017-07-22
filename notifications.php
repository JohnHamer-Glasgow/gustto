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

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$dbUser = getUserRecord($uinfo);
$loggedUserId = $dbUser->id;

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';
$template->pageData['userLoggedIn'] = $givenname . ' ' . $surname;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserId;
$template->pageData['navHome'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserId, false, 0));
$template->pageData['notifications'] = notifications($dbUser);
$notifications = notification::getNotifications($loggedUserId, 15);

$template->pageData['content'] = '
    <div class="col-xs-12 col-sm-9">
    <div class="card notifications-card">
    <div class="main-header notifications-header">
    <div class="col-md-10 col-xs-7 main-header-notifications main-header">
    <h4>My Notifications</h4>
    </div>
    <div class="mark-all-read col-md-2 col-xs-5">
    <a href="notifications.php" id="allRead" class="btn btn-default" value="1">Mark all as read</a>
    </div>
    </div>';

if (count($notifications) > 0) {
  $template->pageData['content'] .= '
    <div class="notifications-wrapper">' . notificationsPrinting($notifications, $loggedUserId) . '</div>';
  $allNotifNo = sizeof(notification::getNotifications($loggedUserId, false));
  if ($allNotifNo > 15)
    $template->pageData['content'] .= '<div class="notifications-view-more" value="15"><div class="glyphicon glyphicon-triangle-bottom "></div> View more </div>';
} else
    $template->pageData['content'] .= ' <div class="no-notifications"><strong>You do not have any notifications.</strong></div>';

$template->pageData['content'] .= '</div></div>';
$template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
