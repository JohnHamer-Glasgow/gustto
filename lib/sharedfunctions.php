<?php

// Get the user object for the database user record of a logged in user,
// and create it if it doesn't exist.
function getUserRecord($uinfo)
{
    $dbUser = user::retrieve_by_username($uinfo['uname']);
    if($dbUser == false && !empty($uinfo['uname']) && !empty($uinfo['gn']) && !empty($uinfo['sn']) && !empty($uinfo['email'])) // not found in database, so create a new record
    {
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

    } elseif($dbUser) {
        $dbUser->lastaccess = time();
        $dbUser->update();
    }
    return $dbUser;
}


// NEW DATABASE FUNCTIONS

function getLatestTeachingTips($limit=false,$lowerL=0){
    $query = "SELECT * FROM teachingtip WHERE archived='0' AND draft='0' ORDER BY id DESC";
    if($limit) $query.= " LIMIT " .dataConnection::safe($limit);
    $query .= " OFFSET " . dataConnection::safe($lowerL);
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
            $tts = array();
            foreach($result as $r){
                $tt = new teachingtip($r);
                array_push($tts, $tt);
            }
            return $tts;
        } else return false;
}


function getTeachingTip($ttID){
    $teachingTip = dataConnection::runQuery("SELECT u.id, u.name, u.lastname, u.username, tt.* FROM user AS u
        INNER JOIN teachingtip AS tt ON u.id = tt.author_id WHERE tt.id = $ttID");
    return $teachingTip;
}

function checkUserLikesTT($ttID, $userID) {
    $query = "SELECT COUNT(*) as count FROM user_likes_tt as ultt WHERE ultt.user_id = '{$userID}' AND ultt.teachingtip_id = '{$ttID}'";
    $result = dataConnection::runQuery($query);
    $count = $result[0]['count'];
    return ($count > 0) ? true : false;

}

function userLikesTT($ttID, $userID) {
    $liked = checkUserLikesTT($ttID, $userID);
    if ($liked) return false;

    $ultt = new user_likes_tt();
    $ultt->user_id = $userID;
    $ultt->teachingtip_id = $ttID;
    $success = $ultt->insert();
    if(is_null($success)) return false;
    //Notification
    return $ultt;
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
    if($followers){
        foreach ($followers as $follower) {
            $query = "SELECT * FROM user_likes_tt WHERE user_id='{$loggedUserId}' AND teachingtip_id='{$ttID}' ";
            $uultt = dataConnection::runQuery($query);
            
            deleteNotification($follower->id,$uultt[0]['id'],'like');
        }
    }

    // Remove it from user like tt

    $query = "DELETE FROM user_likes_tt WHERE user_id='{$loggedUserId}' AND teachingtip_id='{$ttID}'";
    $result = dataConnection::runQuery($query);

    if ($result < 1) return false;

    return true;
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

    if(is_null($success)) return false;
    return true;
    

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

// get tts which have a particular list of filters in a particular category
function getTTsWithFilters($filter_string) {
    $query = "SELECT DISTINCT tt.* FROM ttfilter as f INNER JOIN teachingtip as tt ON f.teachingtip_id = tt.id WHERE archived = 0 AND draft = 0 AND " . $filter_string . "ORDER BY time DESC";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $tts = array();
        foreach($result as $r){
            $tt = new teachingtip($r);
            array_push($tts, $tt);
        }
        return $tts;
    } else return array();
}

// get the ttkeywords that contain $keyword
function searchTTKeywordsByKeyword($keyword) {
    $k = '%' . $keyword . '%';
    $query = "SELECT DISTINCT * FROM ttkeyword WHERE keyword LIKE '". dataConnection::safe($k) ."'";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $kws = array();
        foreach ($result as $r)
        {
            $kw = new ttkeyword($r);
            array_push($kws, $kw);
             
        }
        return array_unique($kws);
    } else return false;
}

// get users whose first name or last name starts with $keyword
function searchUsersByKeyword($keyword) {
    $kw = $keyword . '%';
    $concat_kw = '%' . $keyword . '%';
    $query = "SELECT * FROM user WHERE CONCAT(name ,' ', lastname) LIKE '".dataConnection::safe($concat_kw)."'";

    if (!preg_match('/\s/',$keyword)) $query .= " AND (name LIKE '".dataConnection::safe($kw)."' OR lastname LIKE '".dataConnection::safe($kw)."')";
    
    $result = dataConnection::runQuery($query);

    if (sizeof($result) != 0) {
        $users = array();
        foreach ($result as $r)
        {
            $user = new user($r);
            array_push($users, $user);
             
        }

        return $users;
    } else return false;
}


function searchUsersByEmailKeyword($keyword) {
    $keyword = $keyword . '%';
    $query = "SELECT * FROM user WHERE email LIKE '" .dataConnection::safe($keyword). "'";
    $result = dataConnection::runQuery($query);

    if (sizeof($result) != 0) {
        $users = array();
        foreach ($result as $r)
        {
            $user = new user($r);
            array_push($users, $user);
             
        }

        return $users;
    } else return false;
}

// get tts based on the word provided - search in titles
function searchTitlesByKeyword($keyword){
    $kw = '%'.$keyword.'%';
    $query = "SELECT * FROM teachingtip WHERE title LIKE '".dataConnection::safe($kw)."' AND archived = 0 AND draft = 0";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $tts = array();
        foreach ($result as $r)
        {
            $tt = new teachingtip($r);
            array_push($tts, $tt);
             
        }
        return $tts;

    } else return false;
}


// get tts based on the word provided - search in keywords
function searchKeywordsByKeyword($keyword){
    $kw = '%'.$keyword.'%';
    $query = "SELECT * FROM teachingtip AS tts,ttkeyword AS ttkws WHERE ttkws.keyword LIKE '".dataConnection::safe($kw)."' AND ttkws.ttid_id = tts.id";
    $result = dataConnection::runQuery($query);

    if (sizeof($result) != 0) {
        $tts = array();
        foreach ($result as $r)
        {
            $tt = teachingtip::retrieve_teachingtip($r['ttid_id']);
            array_push($tts, $tt);
             
        }
        return $tts;

    } else return false;
}

// get teaching tips by user id
function myTeachingTips($userId){
    $query = "SELECT * FROM teachingtip WHERE author_id= $userId AND archived='0'";
    $result = dataConnection::runQuery($query);
    return $result;
}

function deleteComment($cID, $loggedUserId) {
    $query = "DELETE FROM ttcomment WHERE id = {$cID}";
    $result = dataConnection::runQuery($query);
    if (!$result) return false;

    // Notification to the author removed
    $query = "SELECT * FROM user_comments_tt WHERE comment_id='{$cID}' AND user_id = '{$loggedUserId}'";
    $uuctt = dataConnection::runQuery($query);
    $tt = teachingtip::retrieve_teachingtip($uuctt[0]['teachingtip_id']);
    if ($loggedUserId!=$tt->author_id) deleteNotification($tt->author_id,$uuctt[0]['id'],'comment');

    // Notification to the followers removed

    $followers = getFollowers($loggedUserId);
    if($followers){
        foreach ($followers as $follower) deleteNotification($follower->id,$uuctt[0]['id'],'comment');                 
    }

    // Notification to the commenters removed
    $commenters = getTtCommentUsers ($tt,$loggedUserId);
    if ($commenters){
        foreach ($commenters as $commenter) deleteNotification($commenter->id,$uuctt[0]['id'],'comment');
    }

    // Delete User Comment TT

    $query = "DELETE FROM user_comments_tt WHERE comment_id={$cID} AND user_id = {$loggedUserId}";
    $result = dataConnection::runQuery($query);
    if (!$result) return false;
    return true;
}

// FULL TEXT SEARCH
function searchTTs($search_string, $search_college, $search_school) {
    $search_string = dataConnection::safe($search_string);
    $query = "SELECT DISTINCT tt.*,
        MATCH (tt.title, tt.rationale, tt.description, tt.practice, tt.worksbetter, tt.doesntworkunless, tt.essence) AGAINST ('{$search_string}') as ttmatch,
        MATCH (u.name, u.lastname) AGAINST ('{$search_string}') as umatch,
        MATCH (k.keyword) AGAINST ('{$search_string}') as kmatch
        FROM ttkeyword as k
        LEFT JOIN teachingtip as tt ON tt.id = k.ttid_id
        LEFT JOIN user as u ON u.id = tt.author_id
        WHERE
        tt.archived = 0 AND tt.draft = 0 AND";
    if (!empty($search_college)) {
        $query .= " u.college = '" . dataConnection::safe($search_college) . "' AND";

        if (!empty($search_school)) $query .= " u.school = '" . dataConnection::safe($search_school) . "' AND";

    }
    $query .= " (MATCH (tt.title, tt.rationale, tt.description, tt.practice, tt.worksbetter, tt.doesntworkunless, tt.essence) AGAINST ('{$search_string}') OR
        MATCH (u.name, u.lastname) AGAINST ('{$search_string}') OR
        MATCH (k.keyword) AGAINST ('{$search_string}'))
        ORDER BY (kmatch + umatch*2.25 + ttmatch) DESC";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $tts = array();
        foreach($result as $r){
            $tt = new teachingtip($r);
            array_push($tts, $tt);
        }
        return array_unique($tts);
    } else return false;
    // if (sizeof($result) != 0) {
    //     $tts = array();
    //     $tts_kmatch = array();
    //     foreach ($result as $r) {
    //         if (array_key_exists($r['id'], $tts_kmatch)) $tts_kmatch[$r['id']] += $r['kmatch'];
    //         else $tts_kmatch[$r['id']] = $r['kmatch'];
    //     }
    //     arsort($tts_kmatch);
    //     foreach ($tts_kmatch as $ttid=>$km) {
    //         foreach ($result as $r) {
    //             if ($ttid == $r['id']) {
    //                 $tt = new teachingtip($r);
    //                 array_push($tts, $tt);
    //             }
    //         }
    //     }
    //     return array_unique($tts);
    // } else return false;

}

// Search TTs by Author
function searchTTsByAuthor($search_author, $search_college, $search_school){
    $search_author = dataConnection::safe($search_author);
    $query = "SELECT DISTINCT tt.*,
        MATCH (u.name, u.lastname) AGAINST ('{$search_author}') as umatch
        FROM teachingtip as tt
        LEFT JOIN user as u ON u.id = tt.author_id
        WHERE
        tt.archived = 0 AND tt.draft = 0 AND";
    if (!empty($search_college)) {
        $query .= " u.college = '" . dataConnection::safe($search_college) . "' AND";

        if (!empty($search_school)) $query .= " u.school = '" . dataConnection::safe($search_school) . "' AND";

    }
    $query .= " MATCH (u.name, u.lastname) AGAINST ('{$search_author}') 
        ORDER BY umatch DESC";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $tts = array();
        foreach($result as $r){
            $tt = new teachingtip($r);
            array_push($tts, $tt);
        }
        return $tts;
    } else return false;
}

/* KEEP THIS HERE - might need it later */
// Search TTs by Keyword
// function searchTTsByKeyword($search_keyword) {
//     $search_keyword = dataConnection::safe($search_keyword);
//     $query = "SELECT DISTINCT tt.*, k.keyword,
//         MATCH (k.keyword) AGAINST ('{$search_keyword}') as kmatch
//         FROM teachingtip as tt
//         LEFT JOIN ttkeyword as k ON tt.id = k.ttid_id
//         WHERE
//         tt.archived = 0 AND tt.draft = 0 AND
//         MATCH (k.keyword) AGAINST ('{$search_keyword}')
//         ORDER BY kmatch DESC";
//     $result = dataConnection::runQuery($query);
//     if (sizeof($result) != 0) {
//         $tts = array();
//         $tts_kmatch = array();
//         foreach ($result as $r) {
//             if (array_key_exists($r['id'], $tts_kmatch)) $tts_kmatch[$r['id']] += $r['kmatch'];
//             else $tts_kmatch[$r['id']] = $r['kmatch'];
//         }
//         arsort($tts_kmatch);
//         foreach ($tts_kmatch as $ttid=>$km) {
//             foreach ($result as $r) {
//                 if ($ttid == $r['id']) {
//                     $tt = new teachingtip($r);
//                     array_push($tts, $tt);
//                 }
//             }
//         }
//         return array_unique($tts);
//     } else return false;
// }

function searchTTsByKeyword($search_keyword, $search_college, $search_school) {
    $search_keyword = dataConnection::safe($search_keyword);
    $query = "SELECT DISTINCT tt.*
        FROM teachingtip as tt
        INNER JOIN ttkeyword as k ON tt.id = k.ttid_id
        INNER JOIN user as u ON tt.author_id = u.id
        WHERE tt.archived = 0 AND tt.draft = 0 AND";
    if (!empty($search_college)) {
        $query .= " u.college = '" . dataConnection::safe($search_college) . "' AND";

        if (!empty($search_school)) $query .= " u.school = '" . dataConnection::safe($search_school) . "' AND";

    }
    $query .= " k.keyword = '{$search_keyword}'";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $tts = array();
        foreach($result as $r){
            $tt = new teachingtip($r);
            array_push($tts, $tt);
        }
        return $tts;
    } else return false;
}



function get_number_tts_from_school($school) {
    $query = "SELECT COUNT(tt.id) as school_count FROM teachingtip as tt INNER JOIN user as u ON tt.author_id = u.id WHERE tt.archived = 0 AND tt.draft = 0 AND u.school = '{$school}'";
    $result = dataConnection::runQuery($query);
    return $result[0]['school_count'];
}

function checkUserFollowsUser($followerID, $userID) {
    $query = "SELECT * FROM user_follows_user WHERE follower_id = '". dataConnection::safe($followerID) ."' AND user_id = '" . dataConnection::safe($userID) . "'";
    $result = dataConnection::runQuery($query);
    if ($result) return true;
    else return false;
}

function userUnfollowsUser($followerID, $userID) {
    // remove notification
    $query = "SELECT * FROM user_follows_user WHERE follower_id = '{$followerID}' AND user_id = '{$userID}'";
    $result = dataConnection::runQuery($query);
    if(sizeof($result) != 0) deleteNotification($userID, $result[0]['id'], 'follow');

    // remove follow entry
    $query = "DELETE FROM user_follows_user WHERE follower_id = '". dataConnection::safe($followerID) ."' AND user_id = '" . dataConnection::safe($userID) . "'";
    $result = dataConnection::runQuery($query);
    return $query;
    if ($result < 1) return false;
    return true;
}

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// Notifications

function getFollowers($userID) {
    $query = "SELECT follower_id FROM user_follows_user WHERE user_id = {$userID}";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $followers = array();
        foreach($result as $r){
            $follower = user::retrieve_user($r['follower_id']);
            array_push($followers, $follower);
        }
        return $followers;
    } else return false;
}

function getFollowing($userID) {
    $query = "SELECT user_id FROM user_follows_user WHERE follower_id = {$userID}";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $followings = array();
        foreach($result as $r){
            $following = user::retrieve_user($r['user_id']);
            array_push($followings, $following);
        }
        return $followings;
    } else return false;
}

function getSameSchoolUsers($school,$loggedUserId){
    $query = "SELECT id FROM user WHERE school = '$school' AND id <> '$loggedUserId'";
    $usersIdSchool = dataConnection::runQuery($query);
    return $usersIdSchool; 
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
    $query = "SELECT DISTINCT user_id FROM user_comments_tt WHERE teachingtip_id = {$tt->id} AND user_id <> {$tt->author_id} AND user_id <> $loggedUserId";
    $result = dataConnection::runQuery($query);
    if (sizeof($result) != 0) {
        $users = array();
        foreach($result as $r){
            $user = user::retrieve_user($r['user_id']);
            array_push($users, $user);
        }
        return $users;
    } else return false;
}

// Used to print the notifications to the user Drop Down-- limited to 5 and only unseen 

function notifications($user) {
    $notificationsUnseen = notification::getNotifications($user->id,5,0); // notifications of the user specified-limited to 5 - 0 for unseen
    if($notificationsUnseen){
        $out='';
        foreach ($notificationsUnseen as $notification) {
            $activity_type = $notification->activity_type;
            $notification_id = $notification->id;
            if( $activity_type == 'like'){
                $activity = user_likes_tt::retrieve_user_likes_tt($notification->activity_id);
                $activity_type_print = 'likes';
                $activity_time = date('H:i d M y', $activity->time);
                $fromUser = user::retrieve_user($activity->user_id);
            }
            elseif($activity_type == 'comment'){
                $activity = user_comments_tt::retrieve_user_comments_tt($notification->activity_id);
                $activity_type_print = 'commented on';
                $activity_time = date('H:i d M y', ttcomment::retrieve_ttcomment($activity->comment_id)->time);
                $fromUser = user::retrieve_user($activity->user_id);

            }
            elseif($activity_type == 'share'){
                $activity = user_shares_tt::retrieve_user_shares_tt($notification->activity_id);
                $activity_type_print = 'shared';
                $activity_time = date('H:i d M y', $activity->time);
                // Temporary mod to allow older PHP
                $tmpusers = user::retrieve_user_matching('email', $activity->sender);
                $fromUser = $tmpusers[0];
                //$fromUser = user::retrieve_user_matching('email', $activity->sender)[0];
            }
            elseif($activity_type == 'post'){
                $tt = teachingtip::retrieve_teachingtip($notification->activity_id);
                $activity_type_print = 'posted';
                $activity_time = date('H:i d M y', $tt->time);
                $fromUser = user::retrieve_user($tt->author_id);
            }
            elseif($activity_type == 'award') {
                $activity = user_earns_award::retrieve_user_earns_award($notification->activity_id);
                $award = award::retrieve_award($activity->award_id);
                $activity_type_print = 'earned a new';
                $activity_name = $award->name;
                $activity_time = date('H:i d M y', $activity->time);
            }
            elseif($activity_type == 'follow') {
                $activity = user_follows_user::retrieve_user_follows_user($notification->activity_id);
                $activity_type_print = 'followed';
                $activity_time = date('H:i d M y', $activity->time);
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
    }

    else{
        $out ='<div class="col-xs-12 notification-teachingtip notification-empty">';
        $out .='<strong>You do not have any new notifications.</strong>';
        $out .='</div>';   
    }

    return $out;

}

// Notifications Page


function notificationsPrinting($notifications,$loggedUserId){
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
        $notification_time = date('H:i d M y',$activity->time);
      }
      elseif ($notification_type == 'comment'){ 
        $activity =  user_comments_tt::retrieve_user_comments_tt($notification->activity_id);
        $comment = ttcomment::retrieve_ttcomment($activity->comment_id);
        $notification_user = user::retrieve_user($activity->user_id);
        $notification_tt = teachingtip::retrieve_teachingtip($activity->teachingtip_id);
        $notification_action = "commented on";
        $icon = 'glyphicon-comment';
        $notification_time = date('H:i d M y',$comment->time);
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
        $notification_time = date('H:i d M y',$activity->time);
      }
      elseif ($notification_type == 'post') {
        $activity =  teachingtip::retrieve_teachingtip($notification->activity_id); 
        $notification_tt = $activity;
        $notification_user = user::retrieve_user($activity->author_id);
        $notification_action = "posted";
        $icon = 'glyphicon-file';
        $notification_time = date('H:i d M y',$activity->time);
      }
      elseif($notification_type == 'award') {
        $activity = user_earns_award::retrieve_user_earns_award($notification->activity_id);
        $notification_user = user::retrieve_user($activity->user_id);
        $award = award::retrieve_award($activity->award_id);
        $activity_name = $award->name;
        $notification_action = 'earned a new';
        $icon = 'glyphicon-star';
        $notification_time = date('H:i d M y',$activity->time);
      }
      elseif($notification_type == 'follow') {
        $activity = user_follows_user::retrieve_user_follows_user($notification->activity_id);
        $notification_user = user::retrieve_user($activity->follower_id);
        $notification_action = 'followed';
        $icon = 'glyphicon-signal';
        $notification_time = date('H:i d M y', $activity->time);
        
      }
      
      // Seen
      $notification_seen = $notification->seen;
      if ($notification_seen) $notification_seen='';
      else $notification_seen = 'notification-wrapper-unseen';

      $out .= '<div class="row notification-wrapper '.$notification_seen.'">';

      if ($notification_type == 'award') 
        $out .= '<a href="profile.php?usrID='.$notification_user->id.'" class="col-xs-12 notification-update" value="'.$notificationId.'">';
      elseif ($notification_type == 'follow') 
        $out.= '<a href="settings.php" class="col-xs-12 notification-update" value="'.$notificationId.'">';
      else 
        $out .= '<a href="teaching_tip.php?ttID='.$notification_tt->id.'" class="col-xs-12 notification-update" value="'.$notificationId.'">';

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

function displayUserAwards($awards) {
    $out = '<div class="profile-stats-awards">';
    foreach ($awards as $a) 
        $out .= '<img data-toggle="tooltip" data-placement="top" title="'. $a->name .'" class="profile-cat-award" src="'. $a->url .'" alt="'. $a->name .'"/>';
    $out .= '</div>';
    return $out;
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

    $body .= "Hi there, <br><br>";
    $body .= "{$s->name} {$s->lastname} shared {$tt_author->name} {$tt_author->lastname}'s Teaching Tip, <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$tt->id}'>{$tt->title}</a> with you. <br><br>";

    if (!empty($ustt->message)) {
        $body .= "{$s->name} {$s->lastname}'s message for you: <br><br>";
        $body .= "'{$ustt->message}' <br><br>";
    }

    $body .= "</body></html>";

    // echo "To:{$to} <br> {$headers} <br> {$subject} <br> {$body}";

    // SEND EMAIL
    if(mail($to, $subject, $body, $headers)) return true;
    return false;
    

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

    $masterEmail = 'Niall.Barr@glasgow.ac.uk';
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
            $body .= "{$a_user->name} {$a_user->lastname} posted <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$tt->id}'>{$a->title}</a>. <br><br>";
            $notificationReason = "new post in your school";
            break;

        case 'like':
            $a = user_likes_tt::retrieve_user_likes_tt($aID);
            $a_user = user::retrieve_user($a->user_id);
            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
            $subject = "{$a_user->name} {$a_user->lastname} liked a Teaching Tip";
            $body .= "{$a_user->name} {$a_user->lastname} liked <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$a_tt->id}'>{$a_tt->title}</a>. <br><br>";
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
            $body .= "{$a_user->name} {$a_user->lastname} commented on <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$a_tt->id}'>{$a_tt->title}</a>. <br><br>";
            if ($cat == 'tts_activity') 
                $notificationReason = 'like/comment/share on your Teaching Tips';
            elseif ($cat == 'followers_activity')
                $notificationReason = 'like/comment that people you follow post on any Teaching Tip';
            break;

        case 'share':
            $a = user_shares_tt::retrieve_user_shares_tt($aID);
            $tmpa = user::retrieve_user_matching('email', $a->sender);
            $a_user = $tmpa[0];
            //$a_user = user::retrieve_user_matching('email', $a->sender)[0];
            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
            $subject = "{$a_user->name} {$a_user->lastname} shared your Teaching Tip";
            $body .= "{$a_user->name} {$a_user->lastname} shared <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$a_tt->id}'>{$a_tt->title}</a>. <br><br>";
            $notificationReason = 'like/comment/share on your Teaching Tips';
            break;

        case 'award':
            $a = user_earns_award::retrieve_user_earns_award($aID);
            $award = award::retrieve_award($a->award_id);
            $subject = "You earned a new award";
            $body .= "Well done! You earned a {$award->name}. If you wish to see your awards you can do so by visiting your <a href='https://learn.gla.ac.uk/teachingtips/profile.php?usrID={$u->id}'>Profile</a>. <br><br>";
            $notificationReason = "new award that you earn";
            break;
    }

    $body .= "You received this email because you opted to receive an email notification for every {$notificationReason}. If you want to stop receiving these emails, please review your <a href='https://learn.gla.ac.uk/teachingtips/settings.php'>Email Notifications settings</a>. <br>";

    $body .= "</body></html>";

    if (!$a) return false;

    // echo "To:{$to} <br> {$headers} <br> {$subject} <br> {$body}";

    // SEND EMAIL
    if(mail($to, $subject, $body, $headers)) return true;
    return false;
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
    $masterEmail = 'Niall.Barr@glasgow.ac.uk';
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
                            $body .= "{$a_user->name} {$a_user->lastname} posted <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$tt->id}'>{$a->title}</a>. <br>";
                            break;

                        case 'like':
                            $a = user_likes_tt::retrieve_user_likes_tt($aID);
                            $a_user = user::retrieve_user($a->user_id);
                            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
                            $body .= "{$a_user->name} {$a_user->lastname} liked <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$a_tt->id}'>{$a_tt->title}</a>. <br>";
                            break;

                        case 'comment':
                            $a = user_comments_tt::retrieve_user_comments_tt($aID);
                            $a_user = user::retrieve_user($a->user_id);
                            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
                            
                            $body .= "{$a_user->name} {$a_user->lastname} commented on <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$a_tt->id}'>{$a_tt->title}</a>. <br>";
                            
                            break;

                        case 'share':
                            $a = user_shares_tt::retrieve_user_shares_tt($aID);
                            $tmpa = user::retrieve_user_matching('email', $a->sender);
                            $a_user = $tmpa[0];
                            //$a_user = user::retrieve_user_matching('email', $a->sender)[0];
                            $a_tt = teachingtip::retrieve_teachingtip($a->teachingtip_id);
                            
                            $body .= "{$a_user->name} {$a_user->lastname} shared <a href='https://learn.gla.ac.uk/teachingtips/teaching_tip.php?ttID={$a_tt->id}'>{$a_tt->title}</a>. <br>";
                            
                            break;

                        case 'award':
                            $a = user_earns_award::retrieve_user_earns_award($aID);
                            $award = award::retrieve_award($a->award_id);
                            
                            $body .= "Well done! You earned a {$award->name}. If you wish to see your awards you can do so by visiting your <a href='https://learn.gla.ac.uk/teachingtips/profile.php?usrID={$u->id}'>Profile</a>. <br>";
                           
                            break;
                    }
                }
            }
            $body .= "<br>";

        }

        $body .= "You received this email because you opted to receive a weekly digest. If you want to stop receiving these emails, please review your <a href='https://learn.gla.ac.uk/teachingtips/settings.php'>Email Notifications settings</a>. <br>";

        $body .= "</body></html>";


    }

    // echo "To:{$to} <br> {$headers} <br> {$subject} <br> {$body}";

    // SEND EMAIL
    if(mail($to, $subject, $body, $headers)) return true;
    return false;
}

?>



