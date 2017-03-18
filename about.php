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
		        <h4>About GUSTTO</h4>
		    </div>
		    <div class="content-about col-xs-12">
		    	<p style="font-size: 16px;"><strong>GUSTTO officially launched on Friday, 4<sup>th</sup> of November 2016. The current version is a Beta Release.</strong></p>
		    	<p>The GUSTTO system was developed during a two-year project funded by the Glasgow University Learning and Teaching Development Fund. It allows staff to share their teaching practices, and to access and discuss those of their colleagues.</p>

		    	<p>There are three main aims to the project:</p>

		    	<ol class="about-list">
		    		<li>To allow staff to share ideas they use in their day-to-day teaching, in the form of ‘teaching tips’</li>
		    		<li>To support the initiation and continuing development of a collegial academic community based on the sharing of good teaching practice, and</li>
		    		<li>To create an institutional repository of best practice teaching activities.</li>
		    	</ol>

		    	<p>GUSTTO was inspired by the work of Janet Finlay and Sally Fincher, who created the concept of ‘bundles’ as structured natural language narratives that ‘describe an effective solution to a recurrent problem’ (Finlay, 2012). They tested and refined the use of bundles for describing educational activities through workshops with Computing Science academic staff from several institutions across the UK. GUSTTO both extends this work (by extending its scope outside of Computing Science) and narrows it (by focussing on building a community of scholars within our own organisation).</p>

		    	<p>Using ideas from social media, GUSTTO has been built by students from the School of Computing Science and designed to be very user-friendly:  new teaching tips can be easily uploaded in a few minutes using the simple built-in template.</p>

		    	<p>The GUSTTO project started in August 2015, and is due to finish in June 2017, although the GUSTTO system itself will continue to be supported as an active system after the project concludes. The project has been led by Dr Helen Purchase (Computing Science), supported by the GUSTTO team:  Niall Barr (Learning Technology Unit), Dr Lisa Bradley (Politics), Dr Susan Deeley (Urban Studies), Kerr Gardiner (Learning Technology Unit),  Elina Koristashevskaya (Learning and Teaching Centre), Dr Chris Lindsay (Philosophy), Catherine Omand (Senate Office), and Dr Michelle Welsh (Life Sciences). The two students who developed the system are Adrian Musat and Panagiotis Antoniou.</p>

		    	<p>[1] Finlay, J. Representing Teaching Practice: A book of bundles. University of Kent Press, 2012.</p>
		    </div>
		</div>
	</div>';

echo $template->render();
