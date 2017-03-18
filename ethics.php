<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
$template = new templateMerge($TEMPLATE);

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online'; 
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';


$template->pageData['content'] = 
'<style>
	.nav-bar-xs {display: none !important;} 
	.sidebar-wrapper {display: none !important;}
	.main-nav .btn-group {display: none !important;}
	
	
</style>';

$template->pageData['content'] .= 
   '<div class="card col-sm-10 col-xs-12 col-sm-offset-1">
		<div class="row">
			<div class="main-header">
		        <h4>Ethics</h4>
		    </div>
		    <div class="content-about col-xs-12">
		    	<p style="font-size: 16px;">Anonymised usage statistics are collected from GUSTTO, recording Teaching Tip creation, search and access actions. The purposes of this data collection is to measure and report on staff engagement with the system. Additionally, individual members of staff may be personally contacted for permission to publish select portions of the Teaching Tips they have contributed - staff will have the option of opting out of this portion of the research, and no information will be released about individual members of staff without their explicit permission.</p>
 
				<p style="font-size: 16px;">If you have any questions about this data collection, please contact Dr Helen Purchase, email: Helen.Purchase@glasgow.ac.uk.</p>
		    	
		    </div>
		</div>
	</div>';

echo $template->render();
