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

$uinfo = checkLoggedInUser(false, $error);
if (!$uinfo) {
  header("Location: ../login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);
if($user->isadmin != '1') {
  header("Location: ../index.php");
  exit();
}

session_start();
if (!isset($_SESSION['csrf_token']))
  $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();
  if (isset($_POST['uID']) && is_numeric($_POST['uID']) && $_POST['uID'] >= 0)
    $uID = sanitize_input($_POST['uID']);
  else
    exit();

  if (isset($_POST['uAction']) && ($_POST['uAction'] == 'promote' || $_POST['uAction'] == 'demote'))
    $uAction = sanitize_input($_POST['uAction']);
  else
    exit();

  $u = user::retrieve_user($uID);
  if ($u) {
    if ($uAction == 'promote') $u->isadmin = true;
    elseif ($uAction == 'demote') $u->isadmin = false;
    $u->update();
  }
}

$users = user::get_all_users();

require_once(__DIR__ . '/Scores.php');

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
              <li role="presentation" class=""><a href=".">Settings</a></li>
              <li role="presentation" class=""><a href="teachingtips.php">Teaching Tips</a></li>
              <li role="presentation" class="active"><a href="users.php">Users</a></li>
              <li role="presentation"><a href="statistics.php">Statistics</a></li>
            </ul>';

$template->pageData['content'] .= 
            '<table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th data-sorter="shortDate" data-date-format="yyyy-mm-dd">Join date</th>
                        <th>Name</th>
                        <th>Engagement</th>
                        <th>Esteem</th>
                        <th>#Tips</th>
                        <th>Last authored</th>
                        <th class="filter-select filter-exact" data-placeholder="All">Status</th>
                        <th data-sorter="false" data-filter="false">Actions</th>
                </thead>
<!--
                <tfoot>
                    <tr>
                        <th>ID</th>
                        <th>Join date</th>
                        <th>Name</th>
                        <th>Engagement</th>
                        <th>Esteem</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    <tr>
                        <th colspan="7" class="ts-pager">
                          <div class="form-inline">
                            <button type="button" class="btn first"><i class="icon-step-backward glyphicon glyphicon-step-backward"></i></button>
                            <button type="button" class="btn prev"><i class="icon-arrow-left glyphicon glyphicon-backward"></i></button>
                            <span class="pagedisplay"></span>
                            <button type="button" class="btn next"><i class="icon-arrow-right glyphicon glyphicon-forward"></i></button>
                            <button type="button" class="btn last"><i class="icon-step-forward glyphicon glyphicon-step-forward"></i></button>
                            <div class="form-group">
                              <label for="page-size">Users per page:</label>
                              <select id="page-size" class="pagesize input-mini form-control" title="Select page size">
                                <option selected="selected" value="1000">1,000</option>
                                <option value="2000">2,000</option>
                                <option value="4000">4,000</option>
                                <option value="10000">10,000</option>
                              </select>
                            </div>
                            <div class="form-group">
                              <label for="page-num">Page:</label>
                              <select id="page-num" class="pagenum input-mini form-control" title="Select page number"></select>
                            </div>
                          </div>
                        </th>
                    </tr>
                </tfoot>
-->
                <tbody>';

$scores = new Scores();
foreach ($users as $u) {
  $status = ($u->isadmin == 1) ? 'Admin' : 'User';
  $action = ($u->isadmin == 1) ? 'Demote' : 'Promote';
  $joined = date('Y-m-d', $u->joindate);
  $w = $u->get_most_recent_tip_date();
  $latest = $w == 0 ? '' : date('Y-m-d', $w);
  $actualEsteem = $scores->esteemScore($u->id);
  $actualEngagement = $scores->engagementScore($u->id);
  $template->pageData['content'] .= 
                '<tr>
                    <td>' . $u->id .'</td>
                    <td>' . $joined .'</td>
                    <td title="' . $u->school . '"><a href="../profile.php?usrID=' . $u->id . '">'. $u->name . ' ' . $u->lastname . '</a></td>
                    <td title="' . $scores->showEngagement($u->id) . '">' . $u->engagement . ($actualEngagement == $u->engagement ? '' : ' *') . '</td>
                    <td title="' . $scores->showEsteem($u->id) . '">' . $u->esteem     . ($actualEsteem == $u->esteem ? '' : ' *') . '</td>
                    <td>' . $u->get_number_tts() . '</td>
                    <td>' . $latest . '</td>
                    <td>' . $status . '</td>
                    <td>
                        <form onsubmit="return confirm(\'Are you sure you want to '.$action.' this user?\');" class="admin-panel-promote-user-form" id="appromu-'.$u->id.'" action="" method="post">
                            <input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] . '" />
                            <input type="hidden" name="uID" value="' . $u->id . '" />
                            <input type="hidden" name="uAction" value="' . strtolower($action) .'" />
                            <a href="#" type="submit" onclick="$(\'#appromu-'.$u->id.'\').submit()">' . $action . '</a>
                        </form>
                    </td>
                </tr>';
}
                    
                    
$template->pageData['content'] .= '
  </tbody>
</table>
</div>
</div>';    

$template->pageData['logoutLink'] = loginBox($uinfo);

// have to include these here again since they can't be loaded from the template
// because this page is in a different folder and the paths in the template are hardocded
$template->pageData['customJS'] .= '<script src="../js/bootstrap.min.js"></script>';

$template->pageData['customJS'] .= '<script src="../js/jquery.tablesorter.min.js"></script>
                                    <script src="../js/jquery.tablesorter.widgets.min.js"></script>
                                    <script src="../js/jquery.tablesorter.pager.min.js"></script>';

$template->pageData['customJS'] .= 
  "<script>$(function() {

    $.tablesorter.themes.bootstrap = {
        table        : 'table table-bordered table-striped',
        caption      : 'caption',
        header       : 'bootstrap-header',
        sortNone     : '',
        sortAsc      : '',
        sortDesc     : '',
        active       : '',
        hover        : '',
        // icon class names
        icons        : '',
        iconSortNone : 'bootstrap-icon-unsorted',
        iconSortAsc  : 'glyphicon glyphicon-chevron-up',
        iconSortDesc : 'glyphicon glyphicon-chevron-down',
        filterRow    : '',
        footerRow    : '',
        footerCells  : '',
        even         : '',
        odd          : ''
    };

    $(\"table\").tablesorter({
        theme : \"bootstrap\",
        widthFixed: true,
        headerTemplate : '{content} {icon}',
        widgets : [ \"uitheme\", \"filter\", \"zebra\" ],
        widgetOptions : {
            zebra : [\"even\", \"odd\"],
            filter_reset : \".reset\",
            filter_cssFilter: \"form-control\"
        }
    })
    .tablesorterPager({
        container: $(\".ts-pager\"),
        cssGoto  : \".pagenum\",
        removeRows: false,
        size: 1000,
        output: '{startRow} - {endRow} of {filteredRows} ({totalRows} total)'
    });
});</script>

<script>
$(document).ready(function () {
    $('.logout-dropdown-item a').attr('href', '../login.php?logout=1');
    $('.footer-logout-link').attr('href', '../login.php?logout=1');
    $('#homePage-link').attr('href', '../index.php');
    $('#aboutPage-link').attr('href', '../about.php');
    $('#ethicsPage-link').attr('href', '../ethics.php');
});
</script>";

echo $template->render();
