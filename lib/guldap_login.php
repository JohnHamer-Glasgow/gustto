<?php
function checkLogin($username, $password, &$error) {
  global $CFG;
  if (strlen(trim($password)) == 0) {
    $error = 'Please enter your password';
    return false;
  }
  
  $ldap_host = $CFG['ldaphost'];

  $ds = @ldap_connect($ldap_host);
  if (isset($CFG['ldapbinduser']))
    @ldap_bind($ds, $CFG['ldapbinduser'], $CFG['ldapbindpass']);
  
  if (!$ds) {
    $error = 'Failed to contact the authentication server. Please try again later.';
    return false;
  }
  
  $sr = @ldap_search($ds, $CFG['ldapcontext'], "cn=$username");
  if (!$sr) {
    $error = 'Failed to contact the authentication server. Please try again later.';
    return false;
  }
  
  $entry = ldap_first_entry($ds, $sr);
  if (!$entry) {
    sleep(5);
    $error = 'Incorrect username or password.';
    return false;
  }

  $user_dn = ldap_get_dn($ds, $entry);
  $ok = @ldap_bind( $ds, $user_dn, $password);
  if (!$ok) {
    sleep(5);
    $error = 'Incorrect username or password.';
    return false;
  }

  $sr = ldap_search($ds, $CFG['ldapcontext'], "cn=$username");
  if (ldap_count_entries($ds, $sr) == 0) {
    $error = "No identity vault entry found.";
    ldap_free_result($sr);
    return false;
  }

  $records = ldap_get_entries($ds, $sr);
  ldap_free_result($sr);
  $record = $records[0];

  if (strpos($record['dn'], 'ou=staff') === false) {
    $error = 'You do not have permission to access this site.';
    return false;
  }

  if (isset($record['costcenterdescription'][0])) {
    if (strpos($record['costcenterdescription'][0], '-') !== false) {
      list($cd, $nm) = explode('-', $record['costcenterdescription'][0]);
      $school = trim($nm);
    } else
      $school = $record['costcenterdescription'][0];
  } else
    $school = '';

  $error = '';
  return array('uname' => $record['uid'][0],
               'gn' => $record['givenname'][0],
               'sn' => $record['sn'][0],
               'email' => $record['mail'][0],
               'school' => $school,
               'isAdmin' => false);
}
