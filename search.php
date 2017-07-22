<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');
require_once(__DIR__ . '/lib/constants.php');
require_once(__DIR__ . '/lib/formfunctions.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) {
  header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$display_filter = false;

$template = new templateMerge($TEMPLATE);
$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

$schools_filter_string = '';
$schools_filter_array = array();

$class_size_filter_string = '';
$class_size_filter_array = array();

$env_filter_string = '';
$env_filter_array = array();

$sol_filter_string = '';
$sol_filter_array = array();

$itc_filter_string = '';
$itc_filter_array = array();

$school_filter = false;
$class_size_filter = false;
$env_filter = false;
$sol_filter = false;
$itc_filter = false;

$full_radio_checked = 'checked';
$author_radio_checked = '';
$keyword_radio_checked = '';

$display_filter = true;

if (isset($_GET['school']) && !empty($_GET['school'])) {
  $schools_filter_string = sanitize_input($_GET['school']);
  $schools_filter_array = explode('_', $schools_filter_string);

  $schools_for_query = array();
  foreach ($schools_filter_array as $s)
      $schools_for_query[] = $s;

  $school_filter = true;
}

if (isset($_GET['size']) && !empty($_GET['size'])) {
  $class_size_filter_string = sanitize_input($_GET['size']);
  $class_size_filter_array = explode('_', $_GET['size']);
  $sizes_for_query = $class_size_filter_array;
  $class_size_filter = true;
}

if (isset($_GET['env']) && !empty($_GET['env'])) {
  $env_filter_string = sanitize_input($_GET['env']);
  $env_filter_array = explode('_', $_GET['env']);
  $envs_for_query = $env_filter_array;
  $env_filter = true;
}

if (isset($_GET['sol']) && !empty($_GET['sol'])) {
  $sol_filter_string = sanitize_input($_GET['sol']);
  $sol_filter_array = explode('_', $_GET['sol']);
  $sol_for_query = $sol_filter_array;
  $sol_filter = true;
}

if (isset($_GET['itc']) && !empty($_GET['itc'])) {
  $itc_filter_string = sanitize_input($_GET['itc']);
  $itc_filter_array = explode('_', $_GET['itc']);
  $itc_for_query = $itc_filter_array;
  $itc_filter = true;
}
    
$search_school = '';
$search_input = '';

if (isset($_GET['q'])) {
  $search_input = sanitize_input($_GET['q']);
  $display_filter = false;
  $byAuthor = false;
  $byKeyword = false;

  if (isset($_GET['o']) && !empty($_GET['o'])) {
    $search_type = sanitize_input($_GET['o']);
    if ($search_type != 'full') {
      if ($search_type == 'author') {
	$byAuthor = true;
	$author_radio_checked = 'checked';
	$full_radio_checked = '';
      }
      else if ($search_type == 'keyword') {
	$byKeyword = true;
	$keyword_radio_checked = 'checked';
	$full_radio_checked = '';
      }
    }
  }
}

if ($display_filter) {
  $schools = dataConnection::runQuery("select school, count(*) as count from teachingtip where status = 'active' and school <> '' group by school");
  
  if ($school_filter || $class_size_filter || $env_filter || $sol_filter || $itc_filter)
    $results = teachingtip::get_tts_from_filters($schools_for_query, $sizes_for_query, $envs_for_query, $sol_for_query, $itc_for_query);
  else
    $results = teachingtip::get_latest_teaching_tips();
} else {
  if ($byAuthor)
    $results = searchTTsByAuthor($search_input, $search_school);
  elseif ($byKeyword)
    $results = searchTTsByKeyword($search_input, $search_school);
  else
    $results = searchTTs($search_input, $search_school);
}

$user = user::retrieve_user($loggedUserID);
$loggedUserName =  $uinfo['gn'];
$loggedUserLastname = $uinfo['sn'];

$template->pageData['userLoggedIn'] = $loggedUserName . ' ' . $loggedUserLastname ;
$template->pageData['profileLink'] = "profile.php?usrID=" . $loggedUserID;
$template->pageData['navSearch'] = 'sidebar-current-page';
$template->pageData['notificationNo'] = sizeof(notification::getNotifications($loggedUserID, false, 0));
$template->pageData['notifications'] = notifications($dbUser);

$template->pageData['content'] .= 
				'<div class="col-sm-9 col-xs-12">
					<div class="card advanced-search">
						<div class="main-header">
							<h4>Search</h4>
						</div>

						<div class="search-box-wrapper">
							
								<div class="search-box">
                  <form class="form-inline search-form" action="" method="get">
                    <div class="search-input-wrapper">
                      <input type="text" class="search-input form-control" aria-label="..." placeholder="Search..." name="q" value="' . $search_input . '">
                      <div class="search-options">
                        <div class="radio">
                          <label>
                            <input type="radio" name="o" value="full" ' . $full_radio_checked . '>
                            Full Search
                          </label>
                        </div>
                        <div class="radio">
                          <label>
                            <input type="radio" name="o" value="author" ' . $author_radio_checked . '>
                            Search by Author
                          </label>
                        </div>
                        <div class="radio">
                          <label>
                            <input type="radio" name="o" value="keyword" ' . $keyword_radio_checked . '>
                            Search by Keyword
                          </label>
                        </div>
                      </div>
                    </div>
                    <button type="submit" class="btn btn-default search-button"><span class="glyphicon glyphicon-search"></span></button>
                  </form>
                </div>
              </div>
              <div class="clearfix"></div>
              <div class="row search-results-row">';

if ($display_filter) {
  $template->pageData['content'] .= '<div class="search-results-filter-wrapper col-sm-3">
              <div class="search-results-filter">
                <h3 class="filter-header">Filter</h3>
                <h4 class="filter-sub-header">Class size</h4>';

  foreach ($CLASS_SIZES as $key=>$class_size) {
    $template->pageData['content'] .= 
      '<div class="checkbox">
                    <label>';
    
    $cfs = '';
    $cfa = array();
    if (!empty($schools_filter_string)) $cfa[] = 'school=' . $schools_filter_string;
    if (!empty($env_filter_string)) $cfa[] = 'env=' . $env_filter_string;
    if (!empty($sol_filter_string)) $cfa[] = 'sol=' . $sol_filter_string;
    if (!empty($itc_filter_string)) $cfa[] = 'itc=' . $itc_filter_string;

    $cfs = implode('&', $cfa);
    $schar = (empty($cfs) ? '':'&');

    if (!in_array($key, $class_size_filter_array)) {
      if (empty($class_size_filter_string))
	$filter_string = '?' . $cfs . $schar . 'size=' . $key;
      else
	$filter_string = '?' . $cfs . $schar . 'size=' . $class_size_filter_string . '_' . $key;
      $checked = '';
    } else {
      if (sizeof($class_size_filter_array) == 1)
	$filter_string = '?' . $cfs;
      else {
	$filter_string = str_replace('_' . $key, '', $class_size_filter_string); 
	$filter_string = str_replace($key . '_', '', $filter_string);
	$filter_string = '?' . $cfs . $schar . 'size=' . $filter_string;
      }

      $checked = 'checked';
    }
    
    $template->pageData['content'] .= 
      '<input type="checkbox" value="" onclick="window.location.href=' . "'search.php" . $filter_string . '\'"' . $checked . '>
                        <a href="search.php' . $filter_string . '">' . $class_size . '</a>';
    $template->pageData['content'] .= 
      '</label>
                    </div>';
  }

  $template->pageData['content'] .= '<h4 class="filter-sub-header">Environment</h4>';

  foreach ($ENVS as $key=>$env) {
    $template->pageData['content'] .= 
                  '<div class="checkbox">
                    <label>';
    $cfs = '';
    $cfa = array();
    if (!empty($class_size_filter_string)) $cfa[] = 'size=' . $class_size_filter_string;
    if (!empty($schools_filter_string)) $cfa[] = 'school=' . $schools_filter_string;
    if (!empty($sol_filter_string)) $cfa[] = 'sol=' . $sol_filter_string;
    if (!empty($itc_filter_string)) $cfa[] = 'itc=' . $itc_filter_string;

    $cfs = implode('&', $cfa);
    $schar = (empty($cfs) ? '':'&');

    if (!in_array($key, $env_filter_array)) {
      if (empty($env_filter_string))
	$filter_string = '?' . $cfs . $schar . 'env=' . $key;
      else
	$filter_string = '?' . $cfs . $schar . 'env=' . $env_filter_string . '_' . $key;
      $checked = '';
    } else {
      if (sizeof($env_filter_array) == 1)
	$filter_string = '?' . $cfs;
      else {
	$filter_string = str_replace('_' . $key, '', $env_filter_string); 
	$filter_string = str_replace($key . '_', '', $filter_string);
	$filter_string = '?' . $cfs . $schar . 'env=' . $filter_string;
      }
      
      $checked = 'checked';
    }

    $template->pageData['content'] .= 
                        '<input type="checkbox" value="" onclick="window.location.href=' . "'search.php" . $filter_string . '\'"' . $checked . '>
                        <a href="search.php' . $filter_string . '">' . $env . '</a>';
    $template->pageData['content'] .= 
                  '</label>
                    </div>';
  }

  $template->pageData['content'] .= '<h4 class="filter-sub-header">Suitable for online learning</h4>';
  foreach ($SOL as $key=>$sol) {
    $template->pageData['content'] .= 
                  '<div class="checkbox">
                    <label>';
    $cfs = '';
    $cfa = array();
        
    if (!empty($class_size_filter_string)) $cfa[] = 'size=' . $class_size_filter_string;
    if (!empty($schools_filter_string)) $cfa[] = 'school=' . $schools_filter_string;
    if (!empty($env_filter_string)) $cfa[] = 'env=' . $env_filter_string;
    if (!empty($itc_filter_string)) $cfa[] = 'itc=' . $itc_filter_string;
        
    $cfs = implode('&', $cfa);
    $schar = (empty($cfs) ? '':'&');

    if (!in_array($key, $sol_filter_array)) {
      if (empty($sol_filter_string))
	$filter_string = '?' . $cfs . $schar . 'sol=' . $key;
      else
	$filter_string = '?' . $cfs . $schar . 'sol=' . $sol_filter_string . '_' . $key;
      $checked = '';
    } else {
      if (sizeof($sol_filter_array) == 1)
	$filter_string = '?' . $cfs;
      else {
	$filter_string = str_replace('_' . $key, '', $sol_filter_string); 
	$filter_string = str_replace($key . '_', '', $filter_string);
	$filter_string = '?' . $cfs . $schar . 'sol=' . $filter_string;
      }
          
      $checked = 'checked';
    }

    $template->pageData['content'] .= 
                        '<input type="checkbox" value="" onclick="window.location.href=' . "'search.php" . $filter_string . '\'"' . $checked . '>
                        <a href="search.php' . $filter_string . '">' . $sol . '</a>';
    $template->pageData['content'] .= 
                  '</label>
                    </div>';
  }

  $template->pageData['content'] .= '<h4 class="filter-sub-header">IT competency required</h4>';
  foreach ($ITC as $key=>$itc) {
    $template->pageData['content'] .= 
                  '<div class="checkbox">
                    <label>';
    $cfs = '';
    $cfa = array();
        
    if (!empty($class_size_filter_string)) $cfa[] = 'size=' . $class_size_filter_string;
    if (!empty($schools_filter_string)) $cfa[] = 'school=' . $schools_filter_string;
    if (!empty($env_filter_string)) $cfa[] = 'env=' . $env_filter_string;
    if (!empty($sol_filter_string)) $cfa[] = 'sol=' . $sol_filter_string;
        
    $cfs = implode('&', $cfa);
    $schar = (empty($cfs) ? '':'&');

    if (!in_array($key, $itc_filter_array)) {
      if (empty($itc_filter_string))
	$filter_string =  $cfs . $schar . 'itc=' . $key;
      else
	$filter_string =  $cfs . $schar . 'itc=' . $itc_filter_string . '_' . $key;
      $checked = '';
    } else {
      if (sizeof($itc_filter_array) == 1)
	$filter_string = $cfs;
      else {
	$filter_string = str_replace('_' . $key, '', $itc_filter_string); 
	$filter_string = str_replace($key . '_', '', $filter_string);
	$filter_string = $cfs . $schar . 'itc=' . $filter_string;
      }

      $checked = 'checked';
    }

    $template->pageData['content'] .= 
      '<input type="checkbox" value="" onclick="window.location.href=\'search.php?'. $filter_string . '\'"'. $checked . '>
                        <a href="search.php?'. $filter_string . '">'. $itc . '</a>';
    $template->pageData['content'] .= '</label></div>';
  }
                    
  $template->pageData['content'] .= '<h4 class="filter-sub-header">School</h4>';
  foreach ($schools as $s) {
    $school = $s['school'];
    $template->pageData['content'] .= '<div class="checkbox"><label>';

    $cfs = '';
    $cfa = array();
    if (!empty($class_size_filter_string)) $cfa[] = 'size=' . $class_size_filter_string;
    if (!empty($env_filter_string)) $cfa[] = 'env=' . $env_filter_string;
    if (!empty($sol_filter_string)) $cfa[] = 'sol=' . $sol_filter_string;
    if (!empty($itc_filter_string)) $cfa[] = 'itc=' . $itc_filter_string;
    $cfs = implode('&', $cfa);
    $schar = empty($cfs) ? '': '&';
    
    if (!in_array($school, $schools_filter_array)) {
      if (empty($schools_filter_string))
	$filter_string = '?' . $cfs . $schar . 'school=' . $school;
      else
	$filter_string = '?' . $cfs . $schar . 'school=' . $schools_filter_string . '_' . $school;
      $checked = '';
    } else {
      if (sizeof($schools_filter_array) == 1)
	$filter_string = '?' . $cfs;
      else {
	$filter_string = str_replace('_' . $school, '', $schools_filter_string); 
	$filter_string = str_replace($school . '_', '', $filter_string);
	$filter_string = '?' . $cfs . $schar . 'school=' . $filter_string;
      }
      
      $checked = 'checked';
    }
    
    $template->pageData['content'] .= '<input type="checkbox" value="" onclick="window.location.href='. "'search.php". $filter_string . '\'"'. $checked . '>
                      <a href="search.php'. $filter_string . '">'. $school . ' (' . $s['count'] . ')</a>
                    </label>
                  </div>';
  }
  
  $template->pageData['content'] .= '</div></div>';
}

if ($display_filter) {
  $template->pageData['content'] .= '<div class="search-results-header-wrap col-sm-9 col-xs-12">
                <div class="search-results-header">';
  $active_filter_message = '';
  if (!$school_filter && !$class_size_filter && !$env_filter && !$sol_filter && !$itc_filter)
    $active_filter_message = '<span style="color: #90A4AE; font-weight: normal">No active filters <span>';
  $template->pageData['content'] .= '<h5>Active filters: '. $active_filter_message . '</h5>';

  if ($school_filter) {
    foreach ($schools_filter_array as $sfilter)
      $template->pageData['content'] .= '<div class="college-filter-badge">'. $sfilter . '</div>';
  } 

  if ($class_size_filter) 
    foreach ($class_size_filter_array as $csfilter)
      $template->pageData['content'] .= '<div class="college-filter-badge">'. 'Class size: ' . $CLASS_SIZES[$csfilter] . '</div>';
  
  if ($env_filter) 
    foreach ($env_filter_array as $efilter)
      $template->pageData['content'] .= '<div class="college-filter-badge">'. 'Environment: ' . $ENVS[$efilter] . '</div>';

  if ($sol_filter) 
    foreach ($sol_filter_array as $solfilter)
      $template->pageData['content'] .= '<div class="college-filter-badge">'. 'Suitable for online learning: ' . $SOL[$solfilter] . '</div>';

  if ($itc_filter) 
    foreach ($itc_filter_array as $itcfilter)
      $template->pageData['content'] .= '<div class="college-filter-badge">'. 'IT competency required: ' . $ITC[$itcfilter] . '</div>';

  $template->pageData['content'] .= '</div>
              </div>';   
  $template->pageData['content'] .= '<div class="search-results col-sm-9 col-xs-12">';
} else {
  $template->pageData['content'] .= '<div class="search-results-header-wrap col-xs-12">
                <div class="search-results-header">';
  $search_filter = '';
  if ($search_school) $search_filter .= ' > <span style="color:#607D8B">' . $search_school . '</span>';
  if ($byAuthor) $custom_search = 'by Author';
  if ($byKeyword) $custom_search = 'by Keyword';
  $template->pageData['content'] .= '<h5>Search '. $custom_search . ' results for \''. $search_input . '\''. $search_filter . '</h5>';
  $template->pageData['content'] .= '</div>
              </div>';   
  $template->pageData['content'] .= '<div class="search-results col-xs-12">';
}

foreach ($results as $tt) {
  $author = $tt->get_author();
  $tt_time = date('d M Y', $tt->whencreated);
  $template->pageData['content'] .= 
    '<div class="search-result">
                    <div class="row">
                      <div class="search-result-title col-sm-9 col-xs-12"><h4><a href="teaching_tip.php?ttID='. $tt->id . '">'. $tt->title . '</a></h4></div>
                      <div class="search-result-date col-sm-3 col-xs-12">'. $tt_time . '</div>
                      <div class="search-result-author col-xs-12"><span>by</span> <a href="profile.php?usrID='. $author->id . '">'. $author->name . ' ' . $author->lastname . '</a> <span>'. $author->school . '</span></div>
                      <div class="search-result-description col-xs-12">'. $tt->description . '</div>
                      <div class="search-result-icons col-xs-12">
                        <div class="feed-icons"><button class="glyphicon glyphicon-thumbs-up feed-likebutton"></button> '. $tt->get_number_likes() . '</div>
                        <div class="feed-icons"><span class="glyphicon glyphicon-comment"></span> '. $tt->get_number_comments() . '</div>
                        <div class="feed-icons"><span class="glyphicon glyphicon-share-alt"></span> '. $tt->get_number_shares() . '</div>
                      </div>
                    </div>                  
                </div>';
}

if (count($results) == 0)
  $template->pageData['content'] .= '<div class="no-search-results">There are no results matching your query.</div>';

if (!$display_filter)
  $template->pageData['content'] .= '<div class="back-to-search"><a href="search.php"><span class="glyphicon glyphicon-triangle-left"></span> Back to Search page</a></div>';
		
$template->pageData['content'] .= '</div></div></div></div>';
$template->pageData['logoutLink'] = loginBox($uinfo);

echo $template->render();
