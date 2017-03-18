<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/constants.php');
require_once(__DIR__.'/../lib/formfunctions.php');
$template = new templateMerge('../html/template.html');

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
$user = user::retrieve_user($loggedUserID);

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';

// have to include these here again since they can't be loaded from the template
// because this page is in a different folder and the paths in the template are hardocded
$template->pageData['customCSS'] = '<link href="../css/bootstrap.min.css" rel="stylesheet">
									<link href="../css/style.css" rel="stylesheet">';

// CSS for tablesorter
$template->pageData['customCSS'] .= '<link rel="stylesheet" href="../css/theme.bootstrap.min.css">
									<link rel="stylesheet" href="../css/jquery.tablesorter.pager.min.css">'; 


$template->pageData['homeURL'] = '../index.php';
$template->pageData['logoURL'] = '../images/logo/logo.png';

if($uinfo==false || $user->isadmin != '1')
{
    header("Location: ../index.php");
    exit();
}
else
{

  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
  }

  $username = $uinfo['uname'];
  $givenname = $uinfo['gn'];
  $surname = $uinfo['sn'];

  //User drop down
  $template->pageData['userLoggedIn'] = $givenname.' '.$surname ;
  $template->pageData['profileLink'] = "../profile.php?usrID=".$loggedUserID;

  $template->pageData['navHome'] = 'sidebar-current-page';

  //Notifications
  if (notification::getNotifications($loggedUserID,false,0) == false) $notificationNo = 0;
  else $notificationNo = sizeof(notification::getNotifications($loggedUserID,false,0));
  
  $template->pageData['notificationNo'] = $notificationNo;
  $template->pageData['notifications'] = notifications($dbUser);

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
  	if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token'])) exit();

  	if (!$user->isadmin) exit();

  	if (isset($_POST['ttID']) && is_numeric($_POST['ttID']) && $_POST['ttID'] >= 0) {
        $ttID = sanitize_input($_POST['ttID']);
    } else exit();

    if (isset($_POST['ttAction']) && ($_POST['ttAction'] == 'republish' || $_POST['ttAction'] == 'delete')) {
    	$ttAction = sanitize_input($_POST['ttAction']);
    } else exit();

    // delete/republish TT
  	$tt = teachingtip::retrieve_teachingtip($ttID);

  	if ($tt) {
  		if ($ttAction == 'delete') $tt->archived = 1;
  		elseif ($ttAction == 'republish') $tt->archived = 0;
  		$tt->update();
  	}
  }

  $tts = teachingtip::get_all_teaching_tips();
	
  //Content

  $template->pageData['content'] = 
	'<style>
		.nav-bar-xs {display: none !important;} 
		.sidebar-wrapper {display: none !important;}
		.notification-button-group {display: none !important;}
		.settings-dropdown-item, .profile-dropdown-item {display: none !important;}
		.tablesorter thead .disabled {display: none};
	</style>';

  $template->pageData['content'].= 
	'<div class="col-xs-12">
          <div class="card home-page">
            <div class="main-header">
              <h4>Admin Panel</h4>
            </div>

            <ul class="nav nav-tabs admin-panel-nav-tabs" role="tablist">
              <li role="presentation" class=""><a href=".">Settings</a></li>
              <li role="presentation" class="active"><a href="teachingtips.php">Teaching Tips</a></li>
              <li role="presentation" class=""><a href="users.php">Users</a></li>
            </ul>';

    if ($tts) {
    	$template->pageData['content'] .= 
    		'<table> <!-- bootstrap classes added by the uitheme widget -->
				<thead>
					<tr>
						<th>ID</th>
						<th data-sorter="shortDate" data-date-format="ddmmyyyy">Date</th>
						<th>Author</th>
						<th>Title</th>
						<th class="filter-select filter-exact" data-placeholder="All">Status</th>
						<th data-sorter="false" data-filter="false">Actions</th>
						
				</thead>
				<tfoot>
					<tr>
						<th>ID</th>
						<th>Date</th>
						<th>Author</th>
						<th>Title</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
					<tr>
						<th colspan="7" class="ts-pager form-horizontal">
							<button type="button" class="btn first"><i class="icon-step-backward glyphicon glyphicon-step-backward"></i></button>
							<button type="button" class="btn prev"><i class="icon-arrow-left glyphicon glyphicon-backward"></i></button>
							<span class="pagedisplay"></span> <!-- this can be any element, including an input -->
							<button type="button" class="btn next"><i class="icon-arrow-right glyphicon glyphicon-forward"></i></button>
							<button type="button" class="btn last"><i class="icon-step-forward glyphicon glyphicon-step-forward"></i></button>
							<select class="pagesize input-mini" title="Select page size">
								<option selected="selected" value="10">10</option>
								<option value="20">20</option>
								<option value="30">30</option>
								<option value="40">40</option>
								<option value="50">50</option>
							</select>
							<select class="pagenum input-mini" title="Select page number"></select>
						</th>
					</tr>
				</tfoot>
				<tbody>';

	foreach ($tts as $tt) {
		$author = $tt->get_author();
		$status = ($tt->archived == 1) ? 'Archived' : 'Active';
		$action = ($tt->archived == 1) ? 'Republish' : 'Delete';
		$time = date('y M d H:i', $tt->whencreated);
		$template->pageData['content'] .= 
				'<tr>
					<td>'. $tt->id .'</td>
					<td>'. $time .'</td>
					<td><a href="../profile.php?usrID='. $author->id .'">'. $author->name . ' ' . $author->lastname .'</a></td>
					<td><a href="../teaching_tip.php?ttID='. $tt->id .'">'. $tt->title .'</a></td>
					<td>'. $status .'</td>
					<td><a href="../teaching_tip_add.php?ttID='. $tt->id .'">Edit</a> | 
						<form onsubmit="return confirm(\'Are you sure you want to '.$action.' this Teaching Tip?\');" class="admin-panel-delete-tt-form" id="apdeltt-'.$tt->id.'" action="" method="post">
							<input type="hidden" name="csrf_token" value="' . $_SESSION["csrf_token"] .'" />
							<input type="hidden" name="ttID" value="' . $tt->id .'" />
							<input type="hidden" name="ttAction" value="' . strtolower($action) .'" />
							<a href="#" type="submit" onclick="$(\'#apdeltt-'.$tt->id.'\').submit()">'. $action .'</a>
						</form>
					</td>
					
				</tr>';
	}
					
					
	$template->pageData['content'] .= '</tbody>
			</table>';
    }

            


  $template->pageData['content'] .= 
  		 '</div>
        </div>';	

 
  $template->pageData['logoutLink'] = loginBox($uinfo);

  // have to include these here again since they can't be loaded from the template
  // because this page is in a different folder and the paths in the template are hardocded
  $template->pageData['customJS'] .= '<script src="../js/bootstrap.min.js"></script>';

  // JS for tablesorter
  $template->pageData['customJS'] .= '<script src="../js/jquery.tablesorter.min.js"></script>
									<script src="../js/jquery.tablesorter.widgets.min.js"></script>
									<script src="../js/jquery.tablesorter.pager.min.js"></script>';

  $template->pageData['customJS'] .= 
  "<script>$(function() {

	$.tablesorter.themes.bootstrap = {
		// these classes are added to the table. To see other table classes available,
		// look here: http://getbootstrap.com/css/#tables
		table        : 'table table-bordered table-striped',
		caption      : 'caption',
		// header class names
		header       : 'bootstrap-header', // give the header a gradient background (theme.bootstrap_2.css)
		sortNone     : '',
		sortAsc      : '',
		sortDesc     : '',
		active       : '', // applied when column is sorted
		hover        : '', // custom css required - a defined bootstrap style may not override other classes
		// icon class names
		icons        : '', // add icon-white to make them white; this icon class is added to the <i> in the header
		iconSortNone : 'bootstrap-icon-unsorted', // class name added to icon when column is not sorted
		iconSortAsc  : 'glyphicon glyphicon-chevron-up', // class name added to icon when column has ascending sort
		iconSortDesc : 'glyphicon glyphicon-chevron-down', // class name added to icon when column has descending sort
		filterRow    : '', // filter row class; use widgetOptions.filter_cssFilter for the input/select element
		footerRow    : '',
		footerCells  : '',
		even         : '', // even row zebra striping
		odd          : ''  // odd row zebra striping
	};

	// call the tablesorter plugin and apply the uitheme widget
	$(\"table\").tablesorter({
		// this will apply the bootstrap theme if \"uitheme\" widget is included
		// the widgetOptions.uitheme is no longer required to be set
		theme : \"bootstrap\",

		widthFixed: true,

		headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!

		// widget code contained in the jquery.tablesorter.widgets.js file
		// use the zebra stripe widget if you plan on hiding any rows (filter widget)
		widgets : [ \"uitheme\", \"filter\", \"zebra\" ],

		widgetOptions : {
			// using the default zebra striping class name, so it actually isn't included in the theme variable above
			// this is ONLY needed for bootstrap theming if you are using the filter widget, because rows are hidden
			zebra : [\"even\", \"odd\"],

			// reset filters button
			filter_reset : \".reset\",

			// extra css class name (string or array) added to the filter element (input or select)
			filter_cssFilter: \"form-control\",

			// set the uitheme widget to use the bootstrap theme class names
			// this is no longer required, if theme is set
			// ,uitheme : \"bootstrap\"

		}
	})
	.tablesorterPager({

		// target the pager markup - see the HTML block below
		container: $(\".ts-pager\"),

		// target the pager page select dropdown - choose a page
		cssGoto  : \".pagenum\",

		// remove rows from the table to speed up the sort of large tables.
		// setting this to false, only hides the non-visible rows; needed if you plan to add/remove rows with the pager enabled.
		removeRows: false,

		// output string - default is '{page}/{totalPages}';
		// possible variables: {page}, {totalPages}, {filteredPages}, {startRow}, {endRow}, {filteredRows} and {totalRows}
		output: '{startRow} - {endRow} / {filteredRows} ({totalRows})'

	});

});</script>

<script>
$(document).ready(function () {
	$('.logout-dropdown-item a').attr('href', '../login.php?logout=1');
	$('.footer-logout-link').attr('href', '../login.php?logout=1');
	$('#homePage-link').attr('href', '../index.php');
	$('#aboutPage-link').attr('href', '../about.php');
	$('#ethicsPage-link').attr('href', '../ethics.php');
});

</script>";
}


//if(error_get_last()==null)
    echo $template->render();
//else
//    echo "<p>Not rendering template to avoid hiding error messages.</p>";

?>
