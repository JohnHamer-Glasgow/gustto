<?php

// teaching tip form functions
function teachingtip_add($ttdata, $userID, $draft=false) {
	$tt = new teachingtip();
	$tt->author_id = $userID;
	$tt->number_likes = 0;
	$tt->number_comments = 0;
	$tt->number_shares = 0;
	$tt->title = $ttdata['title'];
	$tt->rationale = $ttdata['rationale'];
	$tt->description = $ttdata['description'];
	$tt->practice = $ttdata['practice'];
	$tt->worksbetter = $ttdata['cond1'];
	$tt->doesntworkunless = $ttdata['cond2'];
	$tt->essence = $ttdata['essence'];

	if ($draft) $tt->draft = 1;
	else $tt->draft = 0;

	$ttID = $tt->insert();
	if (!$ttID) return false;

	if (!empty($ttdata['keywords'])) {
		foreach ($ttdata['keywords'] as $keyword) {
			$kw = new ttkeyword();
			$kw->ttid_id = $ttID;
			$kw->keyword = $keyword;
			$kw->insert();
		}
	}

	if (!empty($ttdata['contributors']) && $tt->draft == 1) {
		foreach	($ttdata['contributors'] as $contributor) {
			$contr = new contributors();
			$contr->teachingtip_id = $ttID;
			$contr->user_id = $contributor;
			$contr->insert();
		}
	}

	return true;

}

function teachingtip_update($ttdata, $ttID, $userID, $draft=false) {
	$tt = teachingtip::retrieve_teachingtip($ttID);
	$tt->title = $ttdata['title'];
	$tt->rationale = $ttdata['rationale'];
	$tt->description = $ttdata['description'];
	$tt->practice = $ttdata['practice'];
	$tt->worksbetter = $ttdata['cond1'];
	$tt->doesntworkunless = $ttdata['cond2'];
	$tt->essence = $ttdata['essence'];

	if ($draft) $tt->draft = 1;
	else { 
		$tt->draft = 0;
		$tt->time = time();
	}

	$success = $tt->update();
	if (!$success) return false;

	// update the keywords
	$keywordsNew = $ttdata['keywords'];
	$keywordsOld = array();
	$keywords = $tt->get_keywords();

	if (!empty($keywords)) {
		foreach ($keywords as $keyword) $keywordsOld[] = $keyword->keyword;
	}

	$addedKeywords = array_diff($keywordsNew, $keywordsOld);
	$removedKeywords = array_diff($keywordsOld, $keywordsNew);

	if (!empty($addedKeywords)) {
		foreach ($addedKeywords as $addKw) {
			$kw = new ttkeyword();
			$kw->ttid_id = $ttID;
			$kw->keyword = $addKw;
			$kw->insert();
		}
	}

	if (!empty($removedKeywords)) {
		foreach ($removedKeywords as $rmKw) {
			$query = "DELETE FROM ttkeyword WHERE ttid_id = $ttID AND keyword = '$rmKw'";
	 		dataConnection::runQuery($query);
		}
	}

	// if (!empty($keywords)) {
	// 	foreach ($keywords as $keyword) {
	// 		$query = "DELETE FROM ttkeyword WHERE id ='" . $keyword->id . "'";
	// 		dataConnection::runQuery($query);
	// 	}
	// }

	// if (!empty($ttdata['keywords'])) {
	// 	foreach ($ttdata['keywords'] as $keyword) {
	// 		$kw = new ttkeyword();
	// 		$kw->ttid_id = $ttID;
	// 		$kw->keyword = $keyword;
	// 		$kw->insert();
	// 	}
	// }

	// update contributors (if needed)
	$contributorsNew = $ttdata['contributors'];
	$contributorsOld = array();
	$ttcontributors = $tt->get_contributors();
	if (!empty($ttcontributors)) {
		foreach ($ttcontributors as $ttcontributor) $contributorsOld[] = $ttcontributor->id;
	}

	$addedContributors = array_diff($contributorsNew, $contributorsOld);
	$removedContributors = array_diff($contributorsOld, $contributorsNew);

	if (!empty($addedContributors)) {
		foreach ($addedContributors as $addC) {
			$contr = new contributors();
			$contr->teachingtip_id = $ttID;
			$contr->user_id = $addC;
			$contr->insert();
		}
	}

	if (!empty($removedContributors)) {
		foreach ($removedContributors as $rmC) {
			$query = "DELETE FROM contributors WHERE user_id=$rmC AND teachingtip_id=$ttID";
			dataConnection::runQuery($query);
		}
	}
	
	return true;

}
