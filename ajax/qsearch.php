<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$userID = $dbUser->id;

if($uinfo==false)
{
	header("Location: ../login.php");
	exit();
}

if (isset($_GET['keyword']) ) {
	$keyword = sanitize_input($_GET['keyword']);
	
} else exit();

// // allow only letters and spaces for keyword
// if (!preg_match('/^[a-z\s]*$/i', $keyword)) exit();
$data = array();
$tts_matching = searchTitlesByKeyword($keyword);
$usrs_matching =  searchUsersByKeyword($keyword);
$tts_keywords = searchKeywordsByKeyword($keyword);

//Removing Dublicates
if(!empty($tts_keywords)){
	$dublicates = array_intersect($tts_keywords, $tts_matching);
	$tts_keywords = array_diff($tts_keywords, $dublicates);

	// Use a new array as it is recognized as an object from js if not :) 
	$tts_keywords_new = array();

	foreach ($tts_keywords as $tt){

	    $tt_new = teachingtip::retrieve_teachingtip($tt->id);
	    array_push($tts_keywords_new, $tt_new);
	             
	}

	$tts_keywords = $tts_keywords_new;
}

$data['tts'] = $tts_matching;
$data['users'] = $usrs_matching;
$data['keywords'] = $tts_keywords;

echo json_encode($data);


?>