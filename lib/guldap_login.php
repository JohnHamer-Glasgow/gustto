<?php
function checkLogin($username, $password, &$error = false) {
  global $CFG;
  if (strlen(trim($password)) == 0)
    return false;
  $error = false;
  $clrtime = time() + 5; // For paranoid prevention of timing to narrow username/password guesses
  $cookiehash = $CFG['cookiehash'];
  $ldap_host = $CFG['ldaphost'];
  $ds = @ldap_connect($ldap_host);
  if (isset($CFG['ldapbinduser']))
    ldap_bind($ds, $CFG['ldapbinduser'], $CFG['ldapbindpass']);
  
  if (!$ds) {
    $error = 'failed to contact LDAP server';
    return false;
  }
  
  $sr = @ldap_search($ds, $CFG['ldapcontext'], "cn=$username");
  if(!$sr) {
    $error = 'failed to contact LDAP server';
    return false;
  }
  
  $entry = ldap_first_entry($ds, $sr);
  if ($entry) {
    $user_dn = ldap_get_dn($ds, $entry);
    $ok = @ldap_bind( $ds, $user_dn, $password);
    if ($ok) {
      $sr = ldap_search($ds, $CFG['ldapcontext'], "cn=$username");
      $count = ldap_count_entries( $ds, $sr);
      if ($count > 0) {
	$records = ldap_get_entries($ds, $sr);
	$record = $records[0];
	return uinfoFromGULDAP($record);
      } else
	$error = "No Identity vault entry found.<br/>";
      ldap_free_result($sr);
    } else {
      while ($clrtime < time()) sleep(1);
      $error = 'Incorrect password';
      return false;
    }
  } else {
    while ($clrtime < time()) sleep(1);
    $error = 'Incorrect username';
    return false;
  }
}

function uinfoFromGULDAP($record) {
  $uinfo = array();
  $uinfo['uname'] = $record['uid'][0];
  $uinfo['gn'] = $record['givenname'][0];
  $uinfo['sn'] = $record['sn'][0];
  $uinfo['email'] = $record['mail'][0];
  $uinfo['isAdmin'] = false;
  if(isset($record['homezipcode'][0]))
    $uinfo['category'] = $record['homezipcode'][0];
  elseif(strpos($record['dn'], 'ou=staff') !== false)
    $uinfo['category'] = 'staff';
  else
    $uinfo['category'] = 'guest';
  if (isset($record['city'][0]))
    $uinfo['college'] = $record['city'][0];
  if (isset($record['costcenterdescription'][0])) {
    if (strpos($record['costcenterdescription'][0], '-')) {
      list($cd, $nm) = explode('-', $record['costcenterdescription'][0]);
      $uinfo['school'] = trim($nm);
    } else
      $uinfo['school'] = $record['costcenterdescription'][0];
  }
    
  return $uinfo;
}
