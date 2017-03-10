<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

if($uinfo==false){
	header("Location: ../index.php");
	exit();
}else{
	// Used to pass data to jquery
	$data = array();
	
	//Check if POST is valid
	// type with value of 1 is for likes
	// type with value of 2 is for comments
	// type with value of 3 is for shares  -- deleted?

	if(isset($_POST['csrf_token']) &&  $_POST['csrf_token'] === $_SESSION['csrf_token'] && isset($_POST['ttId']) && is_numeric($_POST['ttId']) && $_POST['ttId'] >= 0 ){
		

		$ttId = dataConnection::safe(sanitize_input($_POST['ttId']));
		$tt = teachingtip::retrieve_teachingtip($ttId);

		if(isset($_POST['likeId']) && is_numeric($_POST['likeId']) && $_POST['likeId'] >= 0 && isset($_POST['type']) && $_POST['type']==='1'){

			$likeId = dataConnection::safe($_POST['likeId']);
			// $query ="UPDATE user_likes_tt SET archived='1'  WHERE id=$likeId";
			$query ="DELETE FROM user_likes_tt  WHERE id=$likeId";
			$result = dataConnection::runquery($query); 

			// $tt->number_likes -= 1;
			// $tt->update();

		}
		
		if(isset($_POST['commentId']) && is_numeric($_POST['commentId']) && $_POST['commentId'] >= 0 && isset($_POST['type']) && $_POST['type']==='2'){

			$commentId = dataConnection::safe(sanitize_input($_POST['commentId']));
			//$query ="UPDATE user_comments_tt SET archived='1'  WHERE comment_id=$commentId";
			$query ="DELETE FROM user_comments_tt  WHERE id=$commentId";
			$result = dataConnection::runquery($query); 
			//$query ="UPDATE ttcomment SET archived='1'  WHERE id=$commentId";
			$query ="DELETE FROM ttcomment WHERE id=$commentId";
			$result = dataConnection::runquery($query);

			// $tt->number_comments -= 1;
			// $tt->update();

		}

		// Shares can not be deleted???

		// if(isset($_POST['shareId']) && is_numeric($_POST['shareId']) && $_POST['shareId'] >= 0 && isset($_POST['type']) && $_POST['type']==='3'){

		// 	$shareId = dataConnection::safe($_POST['shareId']);
		// 	//$query ="UPDATE user_shares_tt SET archived='1'  WHERE id=$shareId";
		// 	$query ="DELETE FROM user_shares_tt  WHERE id=$shareId";
		// 	$result = dataConnection::runquery($query); 

		// 	$tt->number_shares -= 1;
		// 	$tt->update();

		// }


		/*
		 *
		 *   HTML CODE FOR DELETE SHARE
		 *
		 *
		 */

		// <form class="deleteForm" action="ajax/deleteActivity.php" method="post">
		// <input type="hidden" name="csrf_token" value="'.$_SESSION["csrf_token"].'" />
		// <input type="hidden" name="shareId" value="'.$share_id.'" />
		// <input type="hidden" name="ttId" value="'.$act[1]->teachingtip_id.'" />
		// <input type="hidden" name="type" value="3" />
		// <button type="submit" class="glyphicon glyphicon-remove shared-remove-icon"></button>
		// </form>

		

	}

	$data['type'] = sanitize_input($_POST['type']);

	echo json_encode($data);

}


?>