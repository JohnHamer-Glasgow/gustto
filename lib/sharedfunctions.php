<?php
// Get the user object for the database user record of a logged in
// user, and create it if it doesn't exist.
function getUserRecord($uinfo) {
  $dbUser = user::retrieve_by_username($uinfo['uname']);
  if ($dbUser == false && !empty($uinfo['uname']) && !empty($uinfo['gn']) && !empty($uinfo['sn']) && !empty($uinfo['email'])) {
    $dbUser = new user();
    $dbUser->username = $uinfo['uname'];
    $dbUser->name = $uinfo['gn'];
    $dbUser->lastname = $uinfo['sn'];
    $dbUser->email = $uinfo['email'];
    $dbUser->profile_picture = 'images/profile-placeholder.jpg';
    $dbUser->esteem = 0;
    $dbUser->engagement = 0;
    $dbUser->joindate = time();
    $dbUser->lastaccess = time();
    $dbUser->last_visit = time();
    $userID = $dbUser->insert();

    // create an entry for the new user in user_settings table
    // weekly digest for all by default
    $user_settings = new user_settings();
    $user_settings->user_id = $userID;
    $user_settings->school_posts = 2;
    $user_settings->tts_activity = 2;
    $user_settings->followers_posts = 2;
    $user_settings->awards = 2;
    $user_settings->insert();

    // check if the new user is a contributor to any tts (added by email)
    // if so, update the corresponding rows in the contributors table to include the user id
    $contr = contributors::retrieve_contributors_matching('email', $uinfo['email']);
    foreach ($contr as $c) {
      $c->user_id = $userID;
      $c->email = "";
      $c->update();
    }
  } elseif ($dbUser) {
    $dbUser->lastaccess = time();
    $dbUser->update();
  }

  return $dbUser;
}


// NEW DATABASE FUNCTIONS

function getRandomTeachingTips($limit = false, $lowerL = 0) {
  $query = "select * from teachingtip where status = 'active' order by rand() desc";
  if ($limit) $query.= " limit " .dataConnection::safe($limit);
  $query .= " offset " . dataConnection::safe($lowerL);
  $result = dataConnection::runQuery($query);
  $tts = array();
  foreach($result as $r)
    array_push($tts, new teachingtip($r));
  return $tts;
}

function getLatestTeachingTips($limit = false, $lowerL = 0) {
  $query = "select * from teachingtip where status = 'active' order by id desc";
  if ($limit) $query.= " limit " .dataConnection::safe($limit);
  $query .= " offset " . dataConnection::safe($lowerL);
  $result = dataConnection::runQuery($query);
  $tts = array();
  foreach($result as $r)
    array_push($tts, new teachingtip($r));
  return $tts;
}


function getTeachingTip($ttID) {
  return dataConnection::runQuery("select u.id, u.name, u.lastname, u.username, tt.* from user as u inner join teachingtip as tt on u.id = tt.author_id where tt.id = $ttID");
}

function checkUserLikesTT($ttID, $userID) {
  $result = dataConnection::runQuery("select count(*) as count from user_likes_tt as ultt where ultt.user_id = '{$userID}' and ultt.teachingtip_id = '{$ttID}'");
  return $result[0]['count'] > 0;
}

function userLikesTT($ttID, $userID) {
  $liked = checkUserLikesTT($ttID, $userID);
  if ($liked) return false;
  
  $ultt = new user_likes_tt();
  $ultt->user_id = $userID;
  $ultt->teachingtip_id = $ttID;
  $success = $ultt->insert();
  return !is_null($success);
}

function userUnlikesTT($ttID, $loggedUserId) {
  $liked = checkUserLikesTT($ttID, $loggedUserId);
  if (!$liked) return false;

  // Notification to the author removed
  $tt = teachingtip::retrieve_teachingtip($ttID);
  if($loggedUserId != $tt->author_id){
    $query = "SELECT * FROM user_likes_tt WHERE user_id='{$loggedUserId}' AND teachingtip_id='{$ttID}' ";
    $uultt = dataConnection::runQuery($query);
    deleteNotification($tt->author_id,$uultt[0]['id'],'like');
  }

  // Notification to the followers removed
  $followers = getFollowers($loggedUserId);
  foreach ($followers as $follower) {
    $query = "SELECT * FROM user_likes_tt WHERE user_id='{$loggedUserId}' AND teachingtip_id='{$ttID}' ";
    $uultt = dataConnection::runQuery($query);
    deleteNotification($follower->id,$uultt[0]['id'],'like');
  }

  // Remove it from user like tt
  $query = "DELETE FROM user_likes_tt WHERE user_id='{$loggedUserId}' AND teachingtip_id='{$ttID}'";
  $result = dataConnection::runQuery($query);
  return $result > 0;
}


function getTopLikedTeachingTips($limit,$userID){
  $query = "SELECT * FROM teachingtip WHERE author_id={$userID} ORDER BY number_likes DESC LIMIT {$limit}";
  $topLikedTeachingTips = dataConnection::runQuery($query);
  return $topLikedTeachingTips;
}

function commentTime($commentId){
  $query = "SELECT time FROM ttcomment WHERE id='{$commentId}'";
  $result = dataConnection::runQuery($query);
  return strtotime($result[0]['time']);
}

function userCommentsTT($ttID, $userID, $comment) {
  // create new ttcomment object and insert it into the db
  $c = new ttcomment();
  $c->comment = $comment;
  $cID = $c->insert();
  
  if (is_null($cID)) return false;
  
  // create new user_comments_tt object and insert it into the db
  $uctt = new user_comments_tt();
  $uctt->user_id = $userID;
  $uctt->teachingtip_id = $ttID;
  $uctt->comment_id = $cID;
  $success = $uctt->insert();

  return !is_null($success);
}

// check if a user ($senderEmail) shared $ttID woth $recipientEmail
// return true if shared, false otherwise
function userSharedTT($ttID, $senderEmail, $recipientEmail) {
  $query = "SELECT COUNT(*) AS share_count FROM user_shares_tt WHERE teachingtip_id = '". dataConnection::safe($ttID) ."' AND sender = '". dataConnection::safe($senderEmail) ."' AND recipient = '". dataConnection::safe($recipientEmail) ."'";
  $result = dataConnection::runQuery($query);
  return ($result[0]['share_count'] > 0);
}

// get the category for a filter option
function getFilterCategory($opt) {
  if (array_key_exists($opt, $GLOBALS['CLASS_SIZES'])) return 'class_size';
  else if (array_key_exists($opt, $GLOBALS['ENVS'])) return 'environment';
  else if (array_key_exists($opt, $GLOBALS['SOL'])) return 'suitable_ol';
  else if (array_key_exists($opt, $GLOBALS['ITC'])) return 'it_competency';
  else return false;
}

function getTTsWithFilters($filter_string) {
  $tts = array();
  $result = dataConnection::runQuery("select distinct tt.* from ttfilter as f inner join teachingtip as tt on f.teachingtip_id = tt.id where status = 'active' and " . $filter_string . " order by time desc");
  foreach($result as $r)
    array_push($tts, new teachingtip($r));
  return $tts;
}

function searchTTKeywordsByKeyword($keyword) {
  $k = '%' . $keyword . '%';
  $result = dataConnection::runQuery("select distinct * from ttkeyword where keyword like '" . dataConnection::safe($k) . "'");
  $kws = array();
  foreach ($result as $r)
    array_push($kws, new ttkeyword($r));
  return array_unique($kws);
}

function searchUsersByKeyword($keyword) {
  $kw = $keyword . '%';
  $query = "select id, name, lastname from user where concat(name ,' ', lastname) like '" . dataConnection::safe('%' . $keyword . '%') . "'";
  if (!preg_match('/\s/',$keyword)) $query .= " and (name like '" . dataConnection::safe($kw) . "' or lastname like '" . dataConnection::safe($kw) . "')";
  return dataConnection::runQuery($query);
}

function searchUsersByEmailKeyword($keyword) {
  $result = dataConnection::runQuery("select * from user where email like '" .dataConnection::safe($keyword . '%'). "'");
  $users = array();
  foreach ($result as $r)
    array_push($users, new user($r));
  return $users;
}

function searchTitlesByKeyword($keyword) {
  return dataConnection::runQuery("
select id, title
 from teachingtip
 where title like '" . dataConnection::safe('%' . $keyword . '%') . "' and status = 'active'");
}

function searchKeywordsByKeyword($keyword){
  return dataConnection::runQuery("
select t.id, t.title
 from teachingtip t left join ttkeyword k on k.ttid_id = t.id
 where k.keyword like '" . dataConnection::safe('%' . $keyword . '%') . "' and t.status = 'active'");
}

function myTeachingTips($userId) {
  return dataConnection::runQuery("select * from teachingtip where author_id = $userId and status <> 'deleted'");
}

function deleteComment($cID, $loggedUserId) {
  if (!dataConnection::runQuery("delete from ttcomment where id = {$cID}"))
    return false;

  $uuctt = dataConnection::runQuery("select * from user_comments_tt where comment_id='{$cID}' and user_id = '{$loggedUserId}'");
  $tt = teachingtip::retrieve_teachingtip($uuctt[0]['teachingtip_id']);
  if ($loggedUserId != $tt->author_id)
    deleteNotification($tt->author_id, $uuctt[0]['id'], 'comment');

  $followers = getFollowers($loggedUserId);
  foreach ($followers as $follower)
    deleteNotification($follower->id,$uuctt[0]['id'],'comment');                 
    
  $commenters = getTtCommentUsers($tt,$loggedUserId);
  if ($commenters)
    foreach ($commenters as $commenter) deleteNotification($commenter->id,$uuctt[0]['id'],'comment');
  
  return dataConnection::runQuery("delete from user_comments_tt where comment_id = {$cID} and user_id = {$loggedUserId}");
}

function searchTTs($search_string, $search_school) {
  $search_string = dataConnection::safe($search_string);
  $query = "select distinct tt.*,
        match (tt.title, tt.rationale, tt.description, tt.practice, tt.worksbetter, tt.doesntworkunless, tt.essence) against ('{$search_string}') as ttmatch,
        match (u.name, u.lastname) against ('{$search_string}') as umatch,
        match (k.keyword) against ('{$search_string}') as kmatch
        from ttkeyword as k
        left join teachingtip as tt ON tt.id = k.ttid_id
        left join user u on u.id = tt.author_id
        where
        tt.status = 'active' and";
  if (!empty($search_school))
    $query .= " tt.school like '" . dataConnection::safe($search_school) . "' and";
  $query .= " (match (tt.title, tt.rationale, tt.description, tt.practice, tt.worksbetter, tt.doesntworkunless, tt.essence) against ('{$search_string}')
           or match (u.name, u.lastname) against ('{$search_string}')
           or concat(' ', k.keyword, ' ') like '% {$search_string} %')
           order by (kmatch + umatch * 2.25 + ttmatch) desc";
  $tts = array();
  foreach (dataConnection::runQuery($query) as $r)
    array_push($tts, new teachingtip($r));
  return array_unique($tts);
}

function searchTTsByAuthor($search_author, $search_school){
  $search_author = dataConnection::safe($search_author);
  $query = "select distinct tt.*,
        match (u.name, u.lastname) against ('{$search_author}') as umatch
        from teachingtip as tt
        left join user as u ON u.id = tt.author_id
        where
        tt.status = 'active' and";
  if (!empty($search_school))
    $query .= " tt.school = '" . dataConnection::safe($search_school) . "' and";
  $query .= " match (u.name, u.lastname) against ('{$search_author}') order by umatch desc";
  $result = dataConnection::runQuery($query);
  $tts = array();
  foreach ($result as $r)
    array_push($tts, new teachingtip($r));
  return $tts;
}

function searchTTsByKeyword($search_keyword, $search_school) {
  $search_keyword = dataConnection::safe($search_keyword);
  $query = "select distinct tt.*
        from teachingtip as tt
        inner join ttkeyword as k on tt.id = k.ttid_id
        inner join user as u on tt.author_id = u.id
        where tt.status = 'active' and";
  if (!empty($search_school))
    $query .= " tt.school = '" . dataConnection::safe($search_school) . "' and";
  $query .= " concat(' ', k.keyword, ' ') like '% {$search_keyword} %'";
  $result = dataConnection::runQuery($query);
  $tts = array();
  foreach($result as $r)
    array_push($tts, new teachingtip($r));
  return $tts;
}

function checkUserFollowsUser($followerID, $userID) {
  return dataConnection::runQuery("select * from user_follows_user where follower_id = '". dataConnection::safe($followerID) ."' and user_id = '" . dataConnection::safe($userID) . "'");
}

function userUnfollowsUser($followerID, $userID) {
  $result = dataConnection::runQuery("select * from user_follows_user where follower_id = '{$followerID}' and user_id = '{$userID}'");
  if(sizeof($result) != 0)
    deleteNotification($userID, $result[0]['id'], 'follow');
  return dataConnection::runQuery("delete from user_follows_user where follower_id = '". dataConnection::safe($followerID) ."' and user_id = '" . dataConnection::safe($userID) . "'");
}

function getFollowers($userID) {
  $followers = array();
  foreach(dataConnection::runQuery("select follower_id from user_follows_user where user_id = {$userID}") as $r)
    array_push($followers, user::retrieve_user($r['follower_id']));
  return $followers;
}

function getFollowing($userID) {
  $followings = array();
  foreach (dataConnection::runQuery("select user_id from user_follows_user where follower_id = {$userID}") as $r)
    array_push($followings, user::retrieve_user($r['user_id']));
  return $followings;
}

function getSameSchoolUsers($school, $loggedUserId) {
  return dataConnection::runQuery("select id from user where school = '$school' and id <> '$loggedUserId'");
}

// Create a system notification for $userId and send email notification if user has enabled
// email notification for every activity in this category
function createNotification($userId,$activityId,$activityType,$category){
  $n = new notification();
  $n->user_id = $userId;
  $n->activity_id = $activityId;
  $n->activity_type = $activityType;
  $n->category = $category;
  $nID = $n->insert();

  // get the user settings and check if user has enabled email notification for every activity in this category
  $us = user_settings::retrieve_user_settings($userId);
  switch ($activityType) {
  case 'post':
    if ($category == 'school_posts') 
      if ($us->school_posts == '1') sendEmailNotification($nID);
    elseif ($category == 'followers_posts')
      if ($us->followers_posts == '1') sendEmailNotification($nID);
    
  case 'like':
  case 'comment':
    if ($category == 'tts_activity')
      if ($us->tts_activity == '1') sendEmailNotification($nID);
    // elseif ($category == 'followers_activity')
    //     if ($us->followers_activity == '1') sendEmailNotification($nID);
    break;
    
  case 'share':
    if ($us->tts_activity == '1') sendEmailNotification($nID);
    break;
    
  case 'award':
    if ($us->awards == '1') sendEmailNotification($nID);
  }
  
  return $nID;
}

function deleteNotification($userId,$activityId,$activityType){
  $query = "DELETE FROM notification WHERE user_id='$userId' AND activity_id = '$activityId'  AND activity_type='$activityType' ";
  $result = dataConnection::runQuery($query); 
}

function getTtCommentUsers ($tt,$loggedUserId){
  $result = dataConnection::runQuery("select distinct user_id from user_comments_tt where teachingtip_id = {$tt->id} and user_id <> {$tt->author_id} and user_id <> $loggedUserId");
  $users = array();
  foreach($result as $r)
    array_push($users, user::retrieve_user($r['user_id']));
  return $users;
}

// Used to print the notifications to the user Drop Down-- limited to 5 and only unseen 

function notifications($user) {
  $notificationsUnseen = notification::getNotifications($user->id, 5, 0);
  if ($notificationsUnseen > 0) {
    $out = '';
    foreach ($notificationsUnseen as $notification) {
      $activity_type = $notification->activity_type;
      $notification_id = $notification->id;
      if ($activity_type == 'like'){
	$activity = user_likes_tt::retrieve_user_likes_tt($notification->activity_id);
	$activity_type_print = 'likes';
	$activity_time = date('d M Y', $activity->time);
	$fromUser = user::retrieve_user($activity->user_id);
      }
      elseif($activity_type == 'comment'){
	$activity = user_comments_tt::retrieve_user_comments_tt($notification->activity_id);
	$activity_type_print = 'commented on';
	$activity_time = date('d M Y', ttcomment::retrieve_ttcomment($activity->comment_id)->time);
	$fromUser = user::retrieve_user($activity->user_id);
      }
      elseif($activity_type == 'share'){
	$activity = user_shares_tt::retrieve_user_shares_tt($notification->activity_id);
	$activity_type_print = 'shared';
	$activity_time = date('d M Y', $activity->time);
	// Temporary mod to allow older PHP
	$tmpusers = user::retrieve_user_matching('email', $activity->sender);
	$fromUser = $tmpusers[0];
	//$fromUser = user::retrieve_user_matching('email', $activity->sender)[0];
      }
      elseif($activity_type == 'post'){
	$tt = teachingtip::retrieve_teachingtip($notification->activity_id);
	$activity_type_print = 'posted';
	$activity_time = date('d M Y', $tt->time);
	$fromUser = user::retrieve_user($tt->author_id);
      }
      elseif($activity_type == 'award') {
	$activity = user_earns_award::retrieve_user_earns_award($notification->activity_id);
	$award = award::retrieve_award($activity->award_id);
	$activity_type_print = 'earned a new';
	$activity_name = $award->name;
	$activity_time = date('d M Y', $activity->time);
      }
      elseif($activity_type == 'follow') {
	$activity = user_follows_user::retrieve_user_follows_user($notification->activity_id);
	$activity_type_print = 'followed';
	$activity_time = date('d M Y', $activity->time);
	$fromUser = user::retrieve_user($activity->follower_id);
      }

            if($activity_type!='post'){
                $tt = teachingtip::retrieve_teachingtip($activity->teachingtip_id);
            }
               
            $out .='<div class="col-xs-12 notification-teachingtip" data-id="'.$notification_id.'">';
            if ($activity_type == 'award') $out .='<a href="profile.php?usrID='.$notification->user_id.'">';
            elseif ($activity_type == 'follow') $out .= '<a href="settings.php">';
            else $out .='<a href="teaching_tip.php?ttID='.$tt->id.'">';
            if($activity_type=='like') $out .= '<span class="glyphicon glyphicon-thumbs-up"></span>';
            elseif($activity_type=='comment') $out .= '<span class="glyphicon glyphicon-comment"></span>';
            elseif($activity_type=='share') $out .= '<span class="glyphicon glyphicon-share-alt"></span>';
            elseif($activity_type=='post') $out .= '<span class="glyphicon glyphicon-file"></span>';
            elseif($activity_type=='award') $out .= '<span class="glyphicon glyphicon-star"></span>';
            elseif($activity_type=='follow') $out .= '<span class="glyphicon glyphicon-signal"></span>';

            if($activity_type == 'award') $out .= '<span class="notification-user">You</span>';
            else $out .= '<span class="notification-user"> '.$fromUser->name.' '.$fromUser->lastname.'</span>';
            $out .= '<span class="notification-type"> '.$activity_type_print.'</span>';
            if ($activity_type == 'award') $out .= '<span class="notification-tt-title"> '.$activity_name.'</span>';
            elseif($activity_type=='follow') $out .= '<span class="notification-tt-title"> you</span>';
            else $out .= '<span class="notification-tt-title"> '.$tt->title.'</span>';
            $out .= '</a>';
            $out .= '<span class="notification-time">'. $activity_time .'</span>';
            $out .= '</div>';
        }
    } else {
        $out ='<div class="col-xs-12 notification-teachingtip notification-empty">';
        $out .='<strong>You do not have any new notifications.</strong>';
        $out .='</div>';   
    }

    return $out;

}

// Notifications Page


function notificationsPrinting($notifications, $loggedUserId) {
    $out = '';
    foreach ($notifications as $notification) {
      // Notification Details
      $notification_type = $notification->activity_type;
      $notificationId = $notification->id;
      if ($notification_type == 'like') {
        $activity =  user_likes_tt::retrieve_user_likes_tt($notification->activity_id);
        $notification_user = user::retrieve_user($activity->user_id);
        $notification_tt = teachingtip::retrieve_teachingtip($activity->teachingtip_id);
        $notification_action = 'likes';
        $icon = 'glyphicon-thumbs-up';
        $notification_time = date('d M Y', $activity->time);
      }
      elseif ($notification_type == 'comment'){ 
        $activity =  user_comments_tt::retrieve_user_comments_tt($notification->activity_id);
        $comment = ttcomment::retrieve_ttcomment($activity->comment_id);
        $notification_user = user::retrieve_user($activity->user_id);
        $notification_tt = teachingtip::retrieve_teachingtip($activity->teachingtip_id);
        $notification_action = "commented on";
        $icon = 'glyphicon-comment';
        $notification_time = date('d M Y', $comment->time);
      }
      elseif ($notification_type == 'share') {
        $activity =  user_shares_tt::retrieve_user_shares_tt($notification->activity_id);
        $notification_tt = teachingtip::retrieve_teachingtip($activity->teachingtip_id);
        //tmp mod for older PHP
        $nus = user::retrieve_user_matching('email', $activity->sender);
        $notification_user = $nus[0];
        //$notification_user = user::retrieve_user_matching('email', $activity->sender)[0];
        $notification_action = "shared";
        $icon = 'glyphicon-share-alt';
        $notification_time = date('d M Y', $activity->time);
      }
      elseif ($notification_type == 'post') {
        $activity =  teachingtip::retrieve_teachingtip($notification->activity_id); 
        $notification_tt = $activity;
        $notification_user = user::retrieve_user($activity->author_id);
        $notification_action = "posted";
        $icon = 'glyphicon-file';
        $notification_time = date('d M Y', $activity->time);
      }
      elseif($notification_type == 'award') {
        $activity = user_earns_award::retrieve_user_earns_award($notification->activity_id);
        $notification_user = user::retrieve_user($activity->user_id);
        $award = award::retrieve_award($activity->award_id);
        $activity_name = $award->name;
        $notification_action = 'earned a new';
        $icon = 'glyphicon-star';
        $notification_time = date('d M Y', $activity->time);
      }
      elseif($notification_type == 'follow') {
        $activity = user_follows_user::retrieve_user_follows_user($notification->activity_id);
        $notification_user = user::retrieve_user($activity->follower_id);
        $notification_action = 'followed';
        $icon = 'glyphicon-signal';
        $notification_time = date('d M Y', $activity->time);
        
      }
      
      $notification_seen = $notification->seen;
      if ($notification_seen) $notification_seen='';
      else $notification_seen = 'notification-wrapper-unseen';

      $out .= '<div class="row notification-wrapper '.$notification_seen.'">';

      if ($notification_type == 'award')
        $out .= '<a href="profile.php?usrID=' . $loggedUserId . '" class="col-xs-12 notification-update" value="' . $notificationId . '">';
      elseif ($notification_type == 'follow') 
        $out .= '<a href="profile.php?usrID=' . $notification_user->id . '" class="col-xs-12 notification-update" value="' . $notificationId . '">';
      else 
        $out .= '<a href="teaching_tip.php?ttID=' . $notification_tt->id . '" class="col-xs-12 notification-update" value="' . $notificationId.'">';

      $out .= '<div class="notification-text-wrapper" >
             <span class="glyphicon '.$icon.' notification-icon"></span>';
      if ($notification_type == 'award') 
        $out .= '<span class="notification-info-user">You</span>';
      else 
        $out .= '<span class="notification-info-user">'.$notification_user->name.' '.$notification_user->lastname.'</span>';

      $out .= '<span> '.$notification_action.' </span>';

      if ($notification_type == 'award') 
        $out .= '<span class="notification-info-title"> '.$activity_name.'</span>';
      elseif ($notification_type == 'follow')
        $out .= '<span class="notification-info-title"> you</span>';
      else 
        $out .= '<span class="notification-info-title">'.$notification_tt->title.'</span>';

      $out.= '
      <span class="notification-info-time">'.$notification_time.'</span>
      </div>
      </a>
      </div> <!-- END ROW -->
      ';
      
    }
return $out;    

}


// <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<

function pageNotFound() {
    $out = 
    '<div class="card page-not-found-card col-sm-10 col-xs-12 col-sm-offset-1">
        <div class="heading-wrapper">
            <span class="page-not-found-glyph glyphicon glyphicon-exclamation-sign hidden-xs"></span>
            <h3 class="page-not-found-heading">The page you requested was not found.</h3>
        </div>
        <div class="clearfix"></div>
        <h5 class="page-not-found-list-title">This could be because: </h5>
        <ul class="page-not-found-list">
            <li>You accessed a URL that doesn\'t exist.</li>
            <li>You are trying to access a Teaching Tip that doesn\'t exist or was removed.</li>
            <li>You are trying to access the Profile page of a user that doesn\'t exist.</li>
        </ul>
        <div class="clearfix"></div>
        <a class="page-not-found-back" href="index.php">< Back to My Homepage</a>
    </div>
    <style>
        .nav-bar-xs {display: none !important;} 
        .sidebar-wrapper {display: none !important;}
        .main-nav .btn-group {display: none !important;}
        #homePage-link {display: none !important;}
    </style>';

    return $out;
}

function displayUserAwards($awards, $cat, $type) {
    $out = '<div class="profile-stats-awards">';
    foreach ($awards as $a)
      if ($a->type == $type && $a->category == $cat) {
        $out .= '<img data-toggle="tooltip" data-placement="top" title="'. $a->name .'" class="profile-cat-award" src="'. $a->url .'" alt="'. $a->name .'"/>';
	$some = true;
	break;
      }
    
    return isset($some) ? ($out . '</div>') : '';
}

function updateAdminSettings($esteem, $engagement, $awards) {
    $as = admin_settings::get_settings();
    $as->esteem_like = $esteem[0];
    $as->esteem_comment = $esteem[1];
    $as->esteem_share = $esteem[2];
    $as->esteem_number_views_1 = $esteem[3];
    $as->esteem_number_views_2 = $esteem[4];
    $as->esteem_number_views_3 = $esteem[5];
    $as->esteem_views_1 = $esteem[6];
    $as->esteem_views_2 = $esteem[7];
    $as->esteem_views_3 = $esteem[8];

    $as->engagement_like = $engagement[0];
    $as->engagement_comment = $engagement[1];
    $as->engagement_share = $engagement[2];
    $as->engagement_number_views_1 = $engagement[3];
    $as->engagement_number_views_2 = $engagement[4];
    $as->engagement_number_views_3 = $engagement[5];
    $as->engagement_views_1 = $engagement[6];
    $as->engagement_views_2 = $engagement[7];
    $as->engagement_views_3 = $engagement[8];

    $as->award_activity = $awards[0];
    $as->award_promote = $awards[1];
    
    $success = $as->update();

    return $success;
}


/* EMAIL NOTIFICATIONS */

function thisUrl($path) {
  // From http://blog.lavoie.sl/2013/02/php-document-root-path-and-url-detection.html
  $base_dir  = dirname(__DIR__ . "..");
  $doc_root  = preg_replace("!${_SERVER['SCRIPT_NAME']}$!", '', $_SERVER['SCRIPT_FILENAME']);
  $base_url  = preg_replace("!^${doc_root}!", '', $base_dir);
  $protocol  = empty($_SERVER['HTTPS']) ? 'http' : 'https';
  $port      = $_SERVER['SERVER_PORT'];
  $disp_port = ($protocol == 'http' && $port == 80 || $protocol == 'https' && $port == 443) ? '' : ":$port";
  $domain    = $_SERVER['SERVER_NAME'];
  return  "${protocol}://${domain}${disp_port}${base_url}/$path";
}


function mailx($to, $subject, $body, $headers) {
  Debug(array('to' => $to, 'subject' => $subject, 'body' => $body, 'headers' => $headers));
  return true;
}


/*
 * Send email from the user that shared a TT (sender) to the recipient
 */
function sendEmailShare($usttID) {
    $ustt = user_shares_tt::retrieve_user_shares_tt($usttID);
    $tt = teachingtip::retrieve_teachingtip($ustt->teachingtip_id);
    $tt_author = $tt->get_author();
    $tmps = user::retrieve_user_matching('email', $ustt->sender);
    $s = $tmps[0];
    //$s = user::retrieve_user_matching('email', $ustt->sender)[0];

    $to = $ustt->recipient;
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $ustt->sender;

    $subject = "{$s->name} {$s->lastname} shared a Teaching Tip with you";

    $body = 
    "<html>
        <head>
            <title>{$s->name} {$s->lastname} shared a Teaching Tip with you</title>
        </head>
        <body>";

    $thisTt = thisUrl("teaching_tip.php?ttID={$tt->id}");
    $body .= "Hi there, <br><br>";
    $body .= "{$s->name} {$s->lastname} shared {$tt_author->name} {$tt_author->lastname}'s Teaching Tip, <a href='$thisTt'>{$tt->title}</a> with you. <br><br>";

    if (!empty($ustt->message)) {
        $body .= "{$s->name} {$s->lastname}'s message for you: <br><br>";
        $body .= "'{$ustt->message}' <br><br>";
    }

    $body .= "</body></html>";

    return mail($to, $subject, $body, $headers);
}

/*
 * Send email notification from the master email using an existing notification ($nID) ;
 * this function is used if the user has opted to receive an email notification for every activity
 * in a particular category (e.g. the user opted to receive email notification for every new TT in their school)
 */
function sendEmailNotification($nID) {
    $n = notification::retrieve_notification($nID);
    $u = user::retrieve_user($n->user_id);
    $cat = $n->category;
    $type = $n->activity_type;
    $aID = $n->activity_id;

    $masterEmaiL = 'GUSTTO@glasgow.ac.uk';
    $to = $u->email;

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $masterEmail;

    $subject = "";
    $body = "";

    $body .= 
    "<html>
        <head>
            <title>You have a new notification</title>
        </head>
        <body>";
    

    $body .= 'Hi ' . $u->name . ", <br><br>";

    switch ($type) {
        case 'post':
            $a = teachingtip::retrieve_teachingtip($aID);
            $a_user = $a->get_author();
            $subject = "{$a_user->name} {$a_user->lastname} posted a new Teaching Tip";
            $body .= "{$a_user->name} {$a_user->lastname} posted <a href='" . thisUrl("teaching_tip.php?ttID={$tt->id}") . "'>{$a->title}</a>. <br><br>";
            $notificationReason = "new post in your school";
            break;

        case 'like':
            $a = user_likes_tt::retrieve_user_likes_tt($aID);
            $a_user = user::retrieve_user($a->user_id);
            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
            $subject = "{$a_user->name} {$a_user->lastname} liked a Teaching Tip";
            $body .= "{$a_user->name} {$a_user->lastname} liked <a href='" . thisUrl("teaching_tip.php?ttID={$a_tt->id}") . "'>{$a_tt->title}</a>. <br><br>";
            if ($cat == 'tts_activity') 
                $notificationReason = 'like/comment/share on your Teaching Tips';
            elseif ($cat == 'followers_activity')
                $notificationReason = 'like/comment that people you follow post on any Teaching Tip';
            break;

        case 'comment':
            $a = user_comments_tt::retrieve_user_comments_tt($aID);
            $a_user = user::retrieve_user($a->user_id);
            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
            $subject = "{$a_user->name} {$a_user->lastname} commented on a Teaching Tip";
            $body .= "{$a_user->name} {$a_user->lastname} commented on <a href='" . thisUrl("teaching_tip.php?ttID={$a_tt->id}") . "'>{$a_tt->title}</a>. <br><br>";
            if ($cat == 'tts_activity') 
                $notificationReason = 'like/comment/share on your Teaching Tips';
            elseif ($cat == 'followers_activity')
                $notificationReason = 'like/comment that people you follow post on any Teaching Tip';
            break;

        case 'share':
            $a = user_shares_tt::retrieve_user_shares_tt($aID);
            $tmpa = user::retrieve_user_matching('email', $a->sender);
            $a_user = $tmpa[0];
            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
            $subject = "{$a_user->name} {$a_user->lastname} shared your Teaching Tip";
            $body .= "{$a_user->name} {$a_user->lastname} shared <a href='" . thisUrl("teaching_tip.php?ttID={$a_tt->id}") . "'>{$a_tt->title}</a>. <br><br>";
            $notificationReason = 'like/comment/share on your Teaching Tips';
            break;

        case 'award':
            $a = user_earns_award::retrieve_user_earns_award($aID);
            $award = award::retrieve_award($a->award_id);
            $subject = "You earned a new award";
            $body .= "Well done! You earned a {$award->name}. If you wish to see your awards you can do so by visiting your <a href='" . thisUrl("profile.php?usrID={$u->id}") . "'>Profile</a>. <br><br>";
            $notificationReason = "new award that you earn";
            break;
    }

    $body .= "You received this email because you opted to receive an email notification for every {$notificationReason}. If you want to stop receiving these emails, please review your <a href='" . thisUrl("settings.php") . "'>Email Notifications settings</a>. <br>";

    $body .= "</body></html>";

    if (!$a) return false;

    return mail($to, $subject, $body, $headers);
}

/*
 * Send a weekly digest with the notifications contained in $notifications array
 * the $notifications array contains the notifications of a user for the past week
 * for the categories they opted to receive a weekly digest;
 * it get all the unique dates during the week for which the user has notifications
 * and displays the notifications grouped by these dates;
 *
 * E.G. an email notification for the week between 3 Aug - 10 Aug 2016 would look like this:
 * Wednesday, 3 August 2016
 * - notification 1
 * - notification 2
 * Saturday, 6 Aug 2016
 * - notification 3
 * Sunday, 7 August 2016
 * - notification 4
 * Wednesday, 10 August 2016
 * - notification 5
 * - notification 6
 */
function sendWeeklyDigest($notifications) {
    $masterEmail = 'GUSTTO@glasgow.ac.uk';
    $to = $u->email;

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . $masterEmail;

    $subject = "GUSTTO Weekly Digest";
    $body = "";

    $body .= 
    "<html>
        <head>
            <title>Your GUSTTO weekly digest</title>
        </head>
        <body>";

    $body .= 'Hi ' . $u->name . ", <br><br>";
    $body .= "This is your GUSTTO weekly digest for the past week. <br><br>";
    
    $days = array();
        if ($notifications) {
        foreach($notifications as $i=>$n) {
            $days[] = date('l, d F Y',$n->time);
            $notifications[$i]->time = date('H:i d F Y', $n->time);
        }

        // get all unique dates during the week for which the user has notifications
        $days = array_unique($days);
        $days = array_values($days);
        
        foreach ($days as $i=>$d) {
            $body .= "<u>$d</u> <br><br>";
            foreach ($notifications as $k=>$n) {
                $u = user::retrieve_user($n->user_id);
                $cat = $n->category;
                $type = $n->activity_type;
                $aID = $n->activity_id; 
                
                $d_date = date_parse($days[$i]);
                $n_date = date_parse($notifications[$k]->time);
                
                if ($d_date['day'] == $n_date['day'] && $d_date['month'] == $n_date['month']) {
                    // print_r($n);

                    switch ($type) {
                        case 'post':
                            $a = teachingtip::retrieve_teachingtip($aID);
                            $a_user = $a->get_author();
                            $subject = "{$a_user->name} {$a_user->lastname} posted a new Teaching Tip";
                            $body .= "{$a_user->name} {$a_user->lastname} posted <a href='" . thisUrl("teaching_tip.php?ttID={$tt->id}") . "'>{$a->title}</a>. <br>";
                            break;

                        case 'like':
                            $a = user_likes_tt::retrieve_user_likes_tt($aID);
                            $a_user = user::retrieve_user($a->user_id);
                            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
                            $body .= "{$a_user->name} {$a_user->lastname} liked <a href='" . thisUrl("teaching_tip.php?ttID={$a_tt->id}") . "'>{$a_tt->title}</a>. <br>";
                            break;

                        case 'comment':
                            $a = user_comments_tt::retrieve_user_comments_tt($aID);
                            $a_user = user::retrieve_user($a->user_id);
                            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
                            
                            $body .= "{$a_user->name} {$a_user->lastname} commented on <a href='" . thisUrl("teaching_tip.php?ttID={$a_tt->id}") . "'>{$a_tt->title}</a>. <br>";
                            
                            break;

                        case 'share':
                            $a = user_shares_tt::retrieve_user_shares_tt($aID);
                            $tmpa = user::retrieve_user_matching('email', $a->sender);
                            $a_user = $tmpa[0];
                            //$a_user = user::retrieve_user_matching('email', $a->sender)[0];
                            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
                            
                            $body .= "{$a_user->name} {$a_user->lastname} shared <a href='" . thisUrl("teaching_tip.php?ttID={$a_tt->id}") . "'>{$a_tt->title}</a>. <br>";
                            
                            break;

                        case 'award':
                            $a = user_earns_award::retrieve_user_earns_award($aID);
                            $award = award::retrieve_award($a->award_id);
                            
                            $body .= "Well done! You earned a {$award->name}. If you wish to see your awards you can do so by visiting your <a href='" . thisUrl("profile.php?usrID={$u->id}") . "'>Profile</a>. <br>";
                           
                            break;
                    }
                }
            }
            $body .= "<br>";

        }

        $body .= "You received this email because you opted to receive a weekly digest. If you want to stop receiving these emails, please review your <a href='" . thisUrl("settings.php") . "'>Email Notifications settings</a>. <br>";

        $body .= "</body></html>";
    }

	return mail($to, $subject, $body, $headers);
}
