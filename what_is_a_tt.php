<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/lib/database.php');
require_once(__DIR__ . '/lib/sharedfunctions.php');
require_once(__DIR__ . '/corelib/dataaccess.php');

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
		        <h4>What is a Teaching Tip?</h4>
		    </div>
		    <div class="content-about col-xs-12">
		    	<p style="font-size: 16px;">A Teaching Tip (TT) is an example of your own teaching practice. It can take the form of an approach you adopted, a resource that you use, or a method you developed.</p>
 
				<p style="font-size: 16px;">The important thing is that <strong>it works for you</strong>, whether by making your life easier, or by improving the student experience.</p>
					 
				<p style="font-size: 16px;"><strong>TTs come in many shapes and sizes</strong>, but we have tried to make it easy for you to share yours. The GUSTTO system follows a simple format for TTs, based on feedback from University staff.</p>
					 
				<p style="font-size: 16px;">Sharing your TT is quick and easy: you don’t have to curate a repository of your materials to contribute a TT, just explain <strong>what you did and how it worked for you</strong>.</p>
					 
				<p style="font-size: 16px;"><strong>GUSTTO is a growing database of good ideas.</strong> Browse, share, or comment – there is a TT for everyone.</p>
		    	
		    </div>
		</div>
	</div>';

echo $template->render();
