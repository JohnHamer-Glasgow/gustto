<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE);

class dataConnection {
  private static $db = null;

  public static function connect() {
    if (self::$db == null) {
      global $DBCFG;
      self::$db = new mysqli($DBCFG['host'], $DBCFG['username'], $DBCFG['password'], $DBCFG['db_name']);
      if (self::$db->connect_errno) die("cannot connect");
    }
  }

  public static function runQuery($query) {
    dataConnection::connect();
    Debug($query);
    $result = self::$db->query($query);
    if (!$result) {
      $message  = 'Invalid query: ' . self::$db->error . "\n";
      $message .= 'Whole query: ' . $query;
      die($message);
    }

    if ($result === true)
      $output = true;
    else {
      $output = array();
      while ($row = $result->fetch_assoc())
	$output[] = $row;
    }
    
    return $output;
  }

  public static function close() {
    if (self::$db != null)
      self::$db->close();
    self::$db = null;
  }

  public static function safe($in) {
    dataConnection::connect();
    
    return self::$db->real_escape_string($in);
  }

  public static function db2date($in) {
    list($y, $m, $d) = explode("-", $in);
    return mktime(0, 0, 0, $m, $d, $y);
  }

  public static function date2db($in) {
    return strftime("%Y-%m-%d", $in);
  }

  public static function db2time($in) {
    list($dt, $ti) = explode(" ", $in);
    list($y,$m,$d) = explode("-", $dt);
    list($hh,$mm,$ss) = explode(":", $ti);
    return mktime(intval($hh), $mm, $ss, $m, $d, intval($y));
  }
  
  public static function time2db($in) {
    return strftime("%Y-%m-%d %H:%M:%S", $in);
  }
};
