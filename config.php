<?php

date_default_timezone_set('Europe/London');
//include_once('corelib/force_ssl.php');  // To allow non-SSL use, comment out this line


require_once(__DIR__.'/corelib/safeRequestFunctions.php');
require_once(__DIR__.'/corelib/templateMerge.php');
include(__DIR__.'/lib/login.php');
//include_once('lib/libfuncs.php');

$TEMPLATE = 'html/template.html';

// directory for uploading the users' profile images
define("UPLOAD_DIR", "../images/profile/");

$CFG['cookiehash'] = "apehdlywdosoqegjao";
$CFG['cookietimelimit'] =  10800; // seconds
$CFG['appname'] = 'GUIT';

// LDAP server IP
$CFG['ldaphost'] = 'taranis.campus.gla.ac.uk';
// LDAP context or list of contexts
$CFG['ldapcontext'] = 'o=Gla';
// LDAP Bind details
$CFG['ldapbinduser'] = 'CN=LDAPTTIPS,ou=service,o=gla';
$CFG['ldapbindpass'] = 'lkl198xgv434rgh';

$CFG['masterEmail'] = 'ltc-gustto@glasgow.ac.uk';

$DBCFG['type']='MySQL';
$DBCFG['host']="localhost"; // Host name
$DBCFG['username']="newguit"; // Mysql username
$DBCFG['password']="newguit"; // Mysql password
$DBCFG['db_name']="newguit"; // Database name

/*
// Database settings
$DBCFG['type']='MySQL';
$DBCFG['host']="localhost"; // Host name
$DBCFG['username']="guit"; // Mysql username
$DBCFG['password']="guit"; // Mysql password
$DBCFG['db_name']="guit"; // Database name
*/

?>
