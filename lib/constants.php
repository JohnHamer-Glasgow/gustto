<?php

/* USED FOR FILTERING ON SEARCH PAGE */
$COLLEGES = array(
  'arts' => 'Arts',
  'mvls' => 'Medical, Veterinary and Life Sciences',
  'se'   => 'Science and Engineering',
  'ss'   => 'Social Sciences'
  );

$SCHOOLS = array(
  'Critical Studies' => 'arts',
  'Culture and Creative Arts' => 'arts',
  'Humanities | Sgoil nan Daonnachdan' => 'arts',
  'Modern Languages and Cultures' => 'arts',

  'Life Sciences' => 'mvls',
  'Medicine, Dentistry and Nursing' => 'mvls',
  'Veterinary Medicine' => 'mvls',

  'Chemistry' => 'se',
  'Computing Science' => 'se',
  'Engineering' => 'se',
  'Geographical and Earth Sciences' => 'se',
  'Mathematics and Statistics' => 'se',
  'Physics and Astronomy' => 'se',
  'Psychology' => 'se',

  'Adam Smith Business School' => 'ss',
  'Education' => 'ss',
  'Interdisciplinary Studies' => 'ss',
  'Law' => 'ss',
  'Social and Political Sciences' => 'ss',
  );

$COLLEGES_SCHOOLS = array(
  'arts' => array(
    'School of Critical Studies',
    'School of Culture and Creative Arts',
    'School of Humanities | Sgoil nan Daonnachdan',
    'School of Modern Languages and Cultures',
    ),
  'mvls' => array(
    'School of Life Sciences',
    'School of Medicine, Dentistry and Nursing',
    'School of Veterinary Medicine',
    ),
  'se' => array(
    'School of Chemistry',
    'School of Computing Science',
    'School of Engineering',
    'School of Geographical and Earth Sciences',
    'School of Mathematics and Statistics',
    'School of Physics and Astronomy',
    'School of Psychology',
    ),
  'ss' => array(
    'Adam Smith Business School',
    'School of Education',
    'School of Interdisciplinary Studies',
    'School of Law',
    'School of Social and Political Sciences',
    ),
  );

$CLASS_SIZES = array(
  'small' => 'Small (<25)',
  'medium' => 'Medium (25-150)',
  'large' => 'Large (>150)'
  );

$ENVS = array(
  'lecture' => 'Lecture',
  'seminar' => 'Seminar/Tutorial',
  'lab' => 'Lab',
  'field' => 'Field',
  'other' => 'Other',
  );

$SOL = array(
  'yes' => 'Yes',
  'no' => 'No',
  );

$ITC = array(
  'none' => 'None',
  'basic' => 'Basic',
  'moderate' => 'Moderate',
  'advanced' => 'Advanced',
  );

$GLOBALS['CLASS_SIZES'] = $CLASS_SIZES;
$GLOBALS['ENVS'] = $ENVS;
$GLOBALS['SOL'] = $SOL;
$GLOBALS['ITS'] = $ITC;

/* REPUTATION points */
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

?>
