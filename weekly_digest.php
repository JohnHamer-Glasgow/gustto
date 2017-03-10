<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
require_once(__DIR__.'/lib/constants.php');

/* CRON JOB FILE
 * SEND WEEKLY EMAILS TO USERS WHO HAVE 
 * WEEKLY DIGEST OPTION ENABLED FOR ANY CATEGORY
 */

$users = user::get_all_users();

foreach ($users as $u) {
	$us = $u->get_settings();
	$cats = array();
	if ($us->school_posts == 2) $cats[] = 'school_posts';
	if ($us->tts_activity == 2) $cats[] = 'tts_activity';
	if ($us->followers_posts == 2) $cats[] = 'followers_posts';
	// if ($us->followers_activity == 2) $cats[] = 'followers_activity';
	if ($us->awards == 2) $cats[] = 'awards';

	// Send email notification containing $cat categories
	
	$notifications = $u->get_past_week_notifications($cats);

	//sendWeeklyDigest($notifications);
	if ($notifications) {
		// echo 'USER ' . $u->id . " --- ". sizeof($notifications) ."\n";
		// echo "======================================== <br>";
		sendWeeklyDigest($notifications);
		// foreach ($notifications as $n) {
		// 	echo "NOTIFICATION ID: " . $n->id . "<br>";
		// 	sendEmailNotification($n->id);
		// 	echo "<br><br>";
		// }
		
	}



	

	
}



