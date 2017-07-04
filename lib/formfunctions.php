<?php
function sanitize_input($input) {
  return htmlspecialchars(stripslashes(trim($input)));
}

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
  $tt->school = $ttdata['school'];

  if ($draft)
    $tt->status = 'draft';
  else
    $tt->status = 'active';

  $ttID = $tt->insert();
  if (!$ttID)
    return false;

  foreach ($ttdata['keywords'] as $keyword) {
    $kw = new ttkeyword();
    $kw->ttid_id = $ttID;
    $kw->keyword = $keyword;
    $kw->insert();
  }
  
  foreach ($ttdata['contributors'] as $contributor) {
    $contr = new contributors();
    $contr->teachingtip_id = $ttID;
    $contr->user_id = $contributor;
    $contr->seen = 0;
    $contr->insert();
  }

  foreach ($ttdata['contributorsEmails'] as $contr_email) {
    $contr = new contributors();
    $contr->teachingtip_id = $ttID;
    $contr->email = $contr_email;
    $contr->seen = 0;
    $contr->insert();
  }

  foreach ($ttdata['class_size'] as $opt) {
    $f = new ttfilter();
    $f->teachingtip_id = $ttID;
    $f->category = "class_size";
    $f->opt = $opt;
    $f->insert();
  }
  
  foreach ($ttdata['environment'] as $opt) {
    $f = new ttfilter();
    $f->teachingtip_id = $ttID;
    $f->category = "environment";
    $f->opt = $opt;
    $f->insert();
  }
  
  $f = new ttfilter();
  $f->teachingtip_id = $ttID;
  $f->category = "suitable_ol";
  $f->opt = $ttdata['suitable_ol'];
  $f->insert();

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
  $user = user::retrieve_user($userID);
  if ($userID != $tt->author_id && !in_array($userID, $cids) && !$user->isadmin)
    return false;

  $tt->title = $ttdata['title'];
  $tt->rationale = $ttdata['rationale'];
  $tt->description = $ttdata['description'];
  $tt->practice = $ttdata['practice'];
  $tt->worksbetter = $ttdata['cond1'];
  $tt->doesntworkunless = $ttdata['cond2'];
  $tt->essence = $ttdata['essence'];
  $tt->school = $ttdata['school'];

  if ($draft)
    $tt->status = 'draft';
  else { 
    $tt->status = 'active';
    $tt->time = time();
  }
  
  $success = $tt->update();
  if (!$success) return false;
  
  $keywordsNew = $ttdata['keywords'];
  $keywordsOld = array();
  $keywords = $tt->get_keywords();

  foreach ($keywords as $keyword)
    $keywordsOld[] = $keyword->keyword;

  $addedKeywords = array_diff($keywordsNew, $keywordsOld);
  $removedKeywords = array_diff($keywordsOld, $keywordsNew);

  foreach ($addedKeywords as $addKw) {
    $kw = new ttkeyword();
    $kw->ttid_id = $ttID;
    $kw->keyword = $addKw;
    $kw->insert();
  }

  foreach ($removedKeywords as $rmKw) {
    $query = "DELETE FROM ttkeyword WHERE ttid_id = $ttID AND keyword = '$rmKw'";
    dataConnection::runQuery($query);
  }

  $contributorsNew = $ttdata['contributors'];
  $contributorsOld = array();
  $ttcontributors = $tt->get_contributors();
  foreach ($ttcontributors as $ttcontributor)
    $contributorsOld[] = $ttcontributor->id;

  $addedContributors = array_diff($contributorsNew, $contributorsOld);
  $removedContributors = array_diff($contributorsOld, $contributorsNew);

  foreach ($addedContributors as $addC) {
    $contr = new contributors();
    $contr->teachingtip_id = $ttID;
    $contr->user_id = $addC;
    $contr->seen = 0;
    $contr->insert();
  }

  foreach ($removedContributors as $rmC) {
    $query = "DELETE FROM contributors WHERE user_id=$rmC AND teachingtip_id=$ttID";
    dataConnection::runQuery($query);
  }

  $filtersNew = array_merge($ttdata['class_size'], $ttdata['environment'], array($ttdata['suitable_ol']), $ttdata['it_competency']);
  $filtersOld = array();
  $ttfilters = $tt->get_all_filters();

  foreach ($ttfilters as $ttf)
    $filtersOld[] = $ttf->opt;

  $addedFilters = array_diff($filtersNew, $filtersOld);
  $removedFilters = array_diff($filtersOld, $filtersNew);

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

  foreach ($removedFilters as $rmf) {
    $query = "DELETE FROM ttfilter WHERE teachingtip_id = $ttID AND opt = '$rmf'";
    dataConnection::runQuery($query);
  }

  return $tt;
}
