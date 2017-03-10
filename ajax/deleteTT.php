<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');
require_once(__DIR__.'/../lib/constants.php');

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if($uinfo==false){
	header("Location: ../index.php");
	exit();
}
else{

	// Used to pass data to jquery
	$data = array();

	if(isset($_POST['csrf_token']) &&  $_POST['csrf_token'] === $_SESSION['csrf_token'] && isset($_POST['ttId']) && is_numeric($_POST['ttId']) && $_POST['ttId'] >= 0 ){


		$ttId = dataConnection::safe(sanitize_input($_POST['ttId']));

		$tt = teachingtip::retrieve_teachingtip($ttId);
		$author = $tt->get_author();

		// extra security check
		if ($loggedUserID != $author->id) exit();

		// if(isset($_POST['userId']) && is_numeric($_POST['userId']) && $_POST['userId'] >= 0){
		// 	$userId = dataConnection::safe($_POST['userId']);


		// 	$query = "DELETE FROM contributors WHERE user_id=$userId AND teachingtip_id=$ttId";
		// 	$result = dataConnection::runquery($query);
		// }else{

			

			// Archive Likes related to TT
			// $query = "UPDATE user_likes_tt SET archived='1' WHERE teachingtip_id=$ttId";
			// $result = dataConnection::runquery($query);

			// Archive Comments related to TT
			// $query = "UPDATE user_comments_tt SET archived='1' WHERE teachingtip_id=$ttId";
			// $result = dataConnection::runquery($query);

			// Archive Shares related to TT
			// $query = "UPDATE user_shares_tt SET archived='1' WHERE teachingtip_id=$ttId";
			// $result = dataConnection::runquery($query);

		// Archive TT
		$query = "UPDATE teachingtip SET archived='1' WHERE id=$ttId";
		$result = dataConnection::runquery($query);
			
		// Notifications Delete Same School
		$loggedUser = user::retrieve_user($loggedUserID);
		$school = $loggedUser->school;
       	$usersIdSchool = getSameSchoolUsers($school,$loggedUserID);

        foreach ($usersIdSchool as $userId) deleteNotification($userId['id'],$ttId,'post');

        // Notification to the followers Delete
        $followers = getFollowers($loggedUserID);
        $userSchool = $loggedUser->school;
        if($followers){
            foreach ($followers as $follower) {

                //avoid deleting twice as the user is deleted from the school loop
                if($follower->school!=$userSchool) deleteNotification($follower->id,$ttId,'post');
            }
        }

			// $currentTT = teachingtip::retrieve_teachingtip($ttId);
			// Archive comments
			// $ttComments = $currentTT->get_comments();
			// foreach($ttComments as $ttComment){
			// 	$ttComment->archived='1';
			// 	$ttComment->update();
			// }

			// // Archive keywords
			// $ttKeywords = $currentTT->get_keywords();
			// foreach($ttKeywords as $ttKeyword){
			// 	$ttKeyword->archived='1';
			// 	$ttKeyword->update();
			// }
		// }


	}

	$data['ttId'] = $ttId;


echo json_encode($data);

}


?>