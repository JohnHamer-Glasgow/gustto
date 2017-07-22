<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../lib/database.php');
require_once(__DIR__ . '/../lib/sharedfunctions.php');
require_once(__DIR__ . '/../corelib/dataaccess.php');
require_once(__DIR__ . '/../lib/formfunctions.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) exit();

$data = array();
if (!empty($_GET['filterType']) && !empty($_GET['period']) && isset($_GET['offset']) ) {
  $filterType = sanitize_input($_GET['filterType']);
  $period = sanitize_input($_GET['period']);
  $offset = intval($_GET['offset']);

  if ($filterType == 'likes') {
    if ($period == 'alltime')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'likes', 0);
    elseif ($period == 'lastthree')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'likes', time() - (60 * 60 * 24 * 90));
    elseif ($period == 'lastmonth')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'likes', time() - (60 * 60 * 24 * 30));
    elseif ($period == 'lastweek')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'likes', time() - (60 * 60 * 24 * 7));
  } elseif ($filterType == 'comments') {
    if ($period == 'alltime')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'comments', 0);
    elseif ($period == 'lastthree')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'comments', time() - (60 * 60 * 24 * 90));
    elseif ($period == 'lastmonth')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'comments', time() - (60 * 60 * 24 * 30));
    elseif ($period == 'lastweek')
      $data = teachingtip::getPopularTeachingTips(10, $offset, 'comments', time() - (60 * 60 * 24 * 7));
  } elseif (isset($_GET['offset']))
    $data = getLatestTeachingTips(10, intval($_GET['offset']), 'likes', 0);
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

foreach ($data as $tt) {	
  $tt->tt_time = date('d M Y', $tt->whencreated);
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
