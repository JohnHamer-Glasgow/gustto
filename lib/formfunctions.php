<?php

function sanitize_input($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
}

// teaching tip form functions
function teachingtip_add($ttdata, $userID, $draft=false) {
	$tt = new teachingtip();
	$tt->author_id = $userID;
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

	if (!empty($ttdata['contributors'])) {
		foreach	($ttdata['contributors'] as $contributor) {
			$contr = new contributors();
			$contr->teachingtip_id = $ttID;
			$contr->user_id = $contributor;
			$contr->seen = 0;
			$contr->insert();
		}
	}

	if (!empty($ttdata['contributorsEmails'])) {
		foreach ($ttdata['contributorsEmails'] as $contr_email) {
			$contr = new contributors();
			$contr->teachingtip_id = $ttID;
			$contr->email = $contr_email;
			$contr->seen = 0;
			$contr->insert();
		}
	}

	// class size
	foreach ($ttdata['class_size'] as $opt) {
		$f = new ttfilter();
		$f->teachingtip_id = $ttID;
		$f->category = "class_size";
		$f->opt = $opt;
		$f->insert();
	}

	// environment
	foreach ($ttdata['environment'] as $opt) {
		$f = new ttfilter();
		$f->teachingtip_id = $ttID;
		$f->category = "environment";
		$f->opt = $opt;
		$f->insert();
	}

	// suitable for online learning
	$f = new ttfilter();
	$f->teachingtip_id = $ttID;
	$f->category = "suitable_ol";
	$f->opt = $ttdata['suitable_ol'];
	$f->insert();

	// it competency
	foreach ($ttdata['it_competency'] as $opt) {
		$f = new ttfilter();
		$f->teachingtip_id = $ttID;
		$f->category = "it_competency";
		$f->opt = $opt;
		$f->insert();
	}

	return $tt;

}

function teachingtip_update($ttdata, $ttID, $userID, $draft=false) {
	$tt = teachingtip::retrieve_teachingtip($ttID);
	$cids = $tt->get_contributors_ids();

	// extra security check
	if ($userID != $tt->author_id && ($cids && !in_array($userID, $cids))) return false;

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
			$contr->seen = 0;
			$contr->insert();
		}
	}

	if (!empty($removedContributors)) {
		foreach ($removedContributors as $rmC) {
			$query = "DELETE FROM contributors WHERE user_id=$rmC AND teachingtip_id=$ttID";
			dataConnection::runQuery($query);
		}
	}

	// update filters (if needed)
	$filtersNew = array_merge($ttdata['class_size'], $ttdata['environment'], array($ttdata['suitable_ol']), $ttdata['it_competency']);
	$filtersOld = array();
	$ttfilters = $tt->get_all_filters();

	if (!empty($ttfilters)) {
		foreach ($ttfilters as $ttf) $filtersOld[] = $ttf->opt;
	}

	$addedFilters = array_diff($filtersNew, $filtersOld);
	$removedFilters = array_diff($filtersOld, $filtersNew);

	if (!empty($addedFilters)) {
		foreach ($addedFilters as $addf) {
			$cat = getFilterCategory($addf);
			if ($cat) {
				$f = new ttfilter();
				$f->teachingtip_id = $ttID;
				$f->category = $cat;
				$f->opt = $addf;
				$f->insert();
			}
		}
	}

	if (!empty($removedFilters)) {
		foreach ($removedFilters as $rmf) {
			$query = "DELETE FROM ttfilter WHERE teachingtip_id = $ttID AND opt = '$rmf'";
	 		dataConnection::runQuery($query);
		}
	}

	return $tt;

}













