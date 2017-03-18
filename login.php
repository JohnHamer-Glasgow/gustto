<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');

$uinfo = checkLoggedInUser();
if ($uinfo) {
  session_start();

  if (isset($_SESSION['url'])) {
    $path = $_SESSION['url'];
    $path = explode('/', $path);
    $url = $path[sizeof($path) - 1];
  } else
    $url = "index.php"; 
  
  $dbUser = getUserRecord($uinfo, false);

  $_SESSION['last_visit'] = $dbUser->last_visit;
  if ($dbUser->college == '' && isset($uinfo['college']))
    $dbUser->college = $uinfo['college'];
  if ($dbUser->school == '' && isset($uinfo['school']))
    $dbUser->school = $uinfo['school'];
  $dbUser->update();
  header("Location: $url");
  exit();
}

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online'; 
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';
$template->pageData['loginBox'] = loginBox(false);
$template->pageData['content'] = 
  '<style>
	.nav-bar-xs { display: none !important; } 
	.sidebar-wrapper { display: none !important; }
	.main-nav .btn-group { display: none !important; }
	.footer-separator { display: none !important; }
	#homePage-link { display: none !important; }
   </style>';

echo $template->render();
