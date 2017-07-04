<?php
// Used for filtering on search page
$SCHOOLS =
    array('Adam Smith Business School',
	  'Chemistry',
	  'Computing Science',
	  'Critical Studies',
	  'Culture and Creative Arts',
	  'Education',
	  'Engineering',
	  'Geographical and Earth Sciences',
	  'Humanities | Sgoil nan Daonnachdan',
	  'Interdisciplinary Studies',
	  'Law',
	  'LEADS',
	  'Life Sciences',
	  'Mathematics and Statistics',
	  'Medicine, Dentistry and Nursing',
	  'Modern Languages and Cultures',
	  'Physics and Astronomy',
	  'Psychology',
	  'Social and Political Sciences',
	  'University Services',
	  'Veterinary Medicine',
	  'Other');

$CLASS_SIZES =
  array('small' => 'Small (<25)',
	'medium' => 'Medium (25-150)',
	'large' => 'Large (>150)');

$ENVS =
  array('lecture' => 'Lecture',
	'seminar' => 'Seminar/Tutorial',
	'lab' => 'Lab',
	'field' => 'Field',
	'other' => 'Other');

$SOL =
  array('yes' => 'Yes',
	'no' => 'No');

$ITC =
  array('none' => 'None',
	'basic' => 'Basic',
	'moderate' => 'Moderate',
	'advanced' => 'Advanced');

$GLOBALS['SCHOOLS'] = $SCHOOLS;
$GLOBALS['CLASS_SIZES'] = $CLASS_SIZES;
$GLOBALS['ENVS'] = $ENVS;
$GLOBALS['SOL'] = $SOL;
$GLOBALS['ITS'] = $ITC;

// REPUTATION points
$settings = admin_settings::get_settings();

// ESTEEM
$GLOBALS['ESTEEM_LIKE'] = $settings->esteem_like;                        // esteem points when user receives a like
$GLOBALS['ESTEEM_COMMENT'] = $settings->esteem_comment;                  // esteem points when user receives a comment
$GLOBALS['ESTEEM_SHARE'] = $settings->esteem_share;                      // esteem points when one of the user's TT is shared by other user
$GLOBALS['ESTEEM_VIEW'] = $settings->esteem_view;                        // esteem points when user receives a view
$GLOBALS['ESTEEM_FOLLOW'] = $settings->esteem_follow;                    // esteem points when user is followed by other user


// ENGAGEMENT
$GLOBALS['ENGAGEMENT_LIKE'] = $settings->engagement_like;               // engagement points when user receives a like
$GLOBALS['ENGAGEMENT_COMMENT'] = $settings->engagement_comment;         // engagement points when user receives a comment
$GLOBALS['ENGAGEMENT_SHARE'] = $settings->engagement_share;             // engagement points when one of the user's TT is shared by other user
$GLOBALS['ENGAGEMENT_VIEW'] = $settings->engagement_view;               // engagement points when user views another user's TT
$GLOBALS['ENGAGEMENT_FOLLOW'] = $settings->engagement_follow;           // engagement points when user follows another user
