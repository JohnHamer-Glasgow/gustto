<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/constants.php');
require_once(__DIR__.'/../lib/formfunctions.php');
$template = new templateMerge('../html/template.html');

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);
if ($uinfo == false || $user->isadmin != '1') {
  header("Location: ../index.php");
  exit();
}

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';

// have to include these here again since they can't be loaded from the template
// because this page is in a different folder and the paths in the template are hardocded
$template->pageData['customCSS'] = '<link href="../css/bootstrap.min.css" rel="stylesheet">
                                    <link href="../css/style.css" rel="stylesheet">';

$template->pageData['customCSS'] .= '<link rel="stylesheet" href="../css/theme.bootstrap.min.css">
                                    <link rel="stylesheet" href="../css/jquery.tablesorter.pager.min.css">'; 

$template->pageData['homeURL'] = '../index.php';
$template->pageData['logoURL'] = '../images/logo/logo.png';

$username = $uinfo['uname'];
$givenname = $uinfo['gn'];
$surname = $uinfo['sn'];

$template->pageData['userLoggedIn'] = $givenname.' '.$surname ;
$template->pageData['profileLink'] = "../profile.php?usrID=".$loggedUserID;
$template->pageData['navHome'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));  
$template->pageData['notifications'] = notifications($dbUser);

$template->pageData['content'] = 
    '<style>
        .nav-bar-xs {display: none !important;} 
        .sidebar-wrapper {display: none !important;}
        .notification-button-group {display: none !important;}
        .settings-dropdown-item, .profile-dropdown-item {display: none !important;}
        .tablesorter thead .disabled {display: none};
    </style>';

$template->pageData['content'].= 
    '<div class="col-xs-12">
          <div class="card home-page">
            <div class="main-header">
              <h4>Admin Panel</h4>
            </div>

            <ul class="nav nav-tabs admin-panel-nav-tabs" role="tablist">
              <li role="presentation"><a href="index.php">Settings</a></li>
              <li role="presentation"><a href="teachingtips.php">Teaching Tips</a></li>
              <li role="presentation"><a href="users.php">Users</a></li>
              <li role="presentation" class="active"><a href="statistics.php">Statistics</a></li>
            </ul>';

$template->pageData['content'] .= '
<table class="table table-striped">
  <thead>
    <tr>
      <th>Year-Week</th>
      <th>Created</th>
      <th>Views</th>
      <th>Follows</th>
      <th>Comments</th>
      <th>Likes</th>
      <th>Shares</th>
    <tr>
  </thead>';

$timeseries = array();
$fmt = '%Y-%u'; // day of the year: %j, month: %c
foreach (dataConnection::runQuery("select date_format(whencreated, '$fmt') as d, count(*) as c from teachingtip where draft = 0 and archived = 0 group by d") as $v)
  $timeseries[$v['d']]['tips'] = $v['c'];
foreach (dataConnection::runQuery("select date_format(time, '$fmt') as d, count(*) as c from ttview group by d") as $v)
  $timeseries[$v['d']]['views'] = $v['c'];
foreach (dataConnection::runQuery("select date_format(time, '$fmt') as d, count(*) as c from user_follows_user group by d") as $v)
  $timeseries[$v['d']]['follows'] = $v['c'];
foreach (dataConnection::runQuery("select date_format(time, '$fmt') as d, count(*) as c  from ttcomment group by d") as $v)
  $timeseries[$v['d']]['comments'] = $v['c'];
foreach (dataConnection::runQuery("select date_format(time, '$fmt') as d, count(*) as c  from user_likes_tt group by d") as $v)
  $timeseries[$v['d']]['likes'] = $v['c'];
foreach (dataConnection::runQuery("select date_format(time, '$fmt') as d, count(*) as c from user_shares_tt group by d") as $v)
  $timeseries[$v['d']]['shares'] = $v['c'];

$template->pageData['content'] .= '<tbody>';

ksort($timeseries);
foreach ($timeseries as $d => $t)
  $template->pageData['content'] .= '
<tr>
    <td>' . $d . '</td>
    <td>' . $t['tips'] .'</td>
    <td>' . $t['views'] .'</td>
    <td>' . $t['follows'] .'</td>
    <td>' . $t['comments'] .'</td>
    <td>' . $t['likes'] .'</td>
    <td>' . $t['shares'] .'</td>
</tr>';
                    
$template->pageData['content'] .= '
  </tbody>
</table>
</div>
</div>';    

$template->pageData['logoutLink'] = loginBox($uinfo);

// have to include these here again since they can't be loaded from the template
// because this page is in a different folder and the paths in the template are hardocded
$template->pageData['customJS'] .= '<script src="../js/bootstrap.min.js"></script>';
$template->pageData['customJS'] .= 
  "<script>
$(document).ready(function () {
    $('.logout-dropdown-item a').attr('href', '../login.php?logout=1');
    $('.footer-logout-link').attr('href', '../login.php?logout=1');
    $('#homePage-link').attr('href', '../index.php');
    $('#aboutPage-link').attr('href', '../about.php');
    $('#ethicsPage-link').attr('href', '../ethics.php');
});
</script>";

echo $template->render();
