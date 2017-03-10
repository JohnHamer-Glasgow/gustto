<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/constants.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$userID = $dbUser->id;

if($uinfo==false)
{
	header("Location: ../login.php");
	exit();
}

if (isset($_GET['college']) && !empty($_GET['college'])) {
	$college = sanitize_input($_GET['college']);

    $schools = array();

    foreach($COLLEGES_SCHOOLS[$college] as $s) {
        if ($s != 'Adam Smith Business School') {
            $school = explode(' ', $s, 3);
            $schools[] = $school[2];
        } else $schools[] = $s;
    }


	echo json_encode($schools);
	
} else exit();

?>