<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser();
if ($uinfo == false) {
  header("Location: ../login.php");
  exit();
}

$data = array();
if (isset($_GET['filterType']) && !empty($_GET['filterType']) && isset($_GET['period']) && !empty($_GET['period']) && isset($_GET['offset']) ) {
  $filterType = dataConnection::safe(sanitize_input($_GET['filterType']));
  $period = dataConnection::safe(sanitize_input($_GET['period']));
  $offset = dataConnection::safe(sanitize_input($_GET['offset']));

  if ($filterType == 'like') {
    if ($period == 'alltime')
      $data = teachingtip::getPopularTeachingTips(5, $offset);
    elseif ($period == 'lastthree')
      $data = teachingtip::getPopularTeachingTips(5, $offset, true, '>', time() - (60 * 60 * 24 * 90));
    elseif ($period == 'lastmonth')
      $data = teachingtip::getPopularTeachingTips(5, $offset, true, '>', time() - (60 * 60 * 24 * 30));
    elseif ($period == 'lastweek')
      $data = teachingtip::getPopularTeachingTips(5, $offset, true, '>', time() - (60 * 60 * 24 * 7));
  } elseif ($filterType == 'comment') {
    if ($period == 'alltime')
      $data = teachingtip::getPopularTeachingTips(5, $offset, false);
    elseif ($period == 'lastthree')
      $data = teachingtip::getPopularTeachingTips(5, $offset, false, '>', time() - (60 * 60 * 24 * 90));
    elseif ($period == 'lastmonth')
      $data = teachingtip::getPopularTeachingTips(5, $offset, false, '>',time() - (60 * 60 * 24 * 30));
    elseif ($period == 'lastweek')
      $data = teachingtip::getPopularTeachingTips(5, $offset, false, '>',time() - (60 * 60 * 24 * 7));
  }
} elseif (isset($_GET['offset'])) {
  $offset = dataConnection::safe(sanitize_input($_GET['offset']));
  $data = getLatestTeachingTips(5, $offset);
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

foreach ($data as $tt) {	
  $tt->tt_time = date('d M y',$tt->whencreated);
  $tt->number_likes = $tt->get_number_likes();
  $tt->number_comments = $tt->get_number_comments();
  $tt->number_shares = $tt->get_number_shares();
  $tt->author = $tt->get_author();
  $tt->keywords = $tt->get_keywords();
  if (checkUserLikesTT($tt->id, $loggedUserID))
    $tt->user_likes = 1;
  else
    $tt->user_likes = 0;
}

echo json_encode($data);
