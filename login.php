<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
$template = new templateMerge($TEMPLATE);

session_start();

if(isset($_SESSION['url'])) {
	$path = $_SESSION['url'];
	$path = explode('/', $path);
	$url = $path[sizeof($path) - 1]; // holds url for last page visited.
} 
else $url = "index.php"; 

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo, false);

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online'; 
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

if($uinfo==false)
{
	$template->pageData['loginBox'] = loginBox($uinfo);
	$template->pageData['content'] = 
	'<style>
		.nav-bar-xs {display: none !important;} 
		.sidebar-wrapper {display: none !important;}
		.main-nav .btn-group {display: none !important;}
		.footer-separator {display: none !important;}
		#homePage-link {display: none !important;}
	</style>';
    
}
else
{
    $dbUser->last_visit = $dbUser->lastaccess;
    if(($dbUser->college == '')&&(isset($uinfo['college'])))
        $dbUser->college = $uinfo['college'];
    if(($dbUser->school == '')&&(isset($uinfo['school'])))
        $dbUser->school = $uinfo['school'];
    $dbUser->update();
 	header("Location: $url");
	exit();
}

echo $template->render();


?>
