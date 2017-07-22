<?php
require_once(__DIR__ . '/guldap_login.php');
require_once(__DIR__ . '/../config.php');

function checkLoggedInUser($allowLogin = true, &$error) {
  session_start();

  if ($allowLogin && isset($_REQUEST['uname']) && isset($_REQUEST['pwd'])) {
    $uinfo = checkLogin($_REQUEST['uname'], $_REQUEST['pwd'], $error);
    Debug('checkLoggedInUser', $_REQUEST['uname'], $uinfo, "Message: '$error'");
  } elseif (!isset($_REQUEST['logout']) && isset($_SESSION['uinfo']))
    $uinfo = $_SESSION['uinfo'];
  else
    $uinfo = false;

  $_SESSION['uinfo'] = $uinfo;
  return $uinfo;
}

function loginBox($uinfo, $error = '') {
  if ($_SERVER['HTTPS'] == 'on')
    $protocol = 'https';
  else
    $protocol = 'http';

  if ($uinfo == false) {
    if (empty($error))
      $message = '';
    else
      $message = '<div class="alert alert-danger alert-dismissible" role="alert">
<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>' . $error . '</div>';
    
    $out = '
<div class="card login-card col-sm-10 col-xs-12 col-sm-offset-1">
  <div class="row">
    <div class="gustto-login-img-wrapper col-sm-6 col-xs-12 hidden-xs">
      <div class="gustto-login-img">
	<div id="login-images" class="carousel slide carousel-wrapper" data-ride="carousel">
	  <ol class="carousel-indicators">
	    <li data-target="#login-images" data-slide-to="0" class="active"></li>
	    <li data-target="#login-images" data-slide-to="1"></li>
	    <li data-target="#login-images" data-slide-to="2"></li>
	    <li data-target="#login-images" data-slide-to="3"></li>
	    <li data-target="#login-images" data-slide-to="4"></li>
	    <li data-target="#login-images" data-slide-to="5"></li>
	  </ol>
	  <div class="carousel-inner" role="listbox">
            <div id="login-image-0" class="item active">
              <img class="img-responsive" src="images/slider/1_1.jpg" alt="glasgow-tt-1">
            </div>
            <div id="login-image-1" class="item">
              <img class="img-responsive" src="images/slider/2_1.jpg" alt="glasgow-tt-2">
            </div>
            <div id="login-image-2" class="item">
              <img class="img-responsive" src="images/slider/3_1.jpg" alt="glasgow-tt-3">
            </div>
            <div id="login-image-3" class="item">
              <img class="img-responsive" src="images/slider/4_1.jpg" alt="glasgow-tt-4">
            </div>
            <div id="login-image-4" class="item">
              <img class="img-responsive" src="images/slider/5_1.jpg" alt="glasgow-tt-5">
            </div>
            <div id="login-image-5" class="item">
              <img class="img-responsive" src="images/slider/6_1.jpg" alt="glasgow-tt-1">
            </div>           
	  </div>
	</div>
      </div>
    </div>
<div class="form-wrapper col-sm-6 col-xs-12">';
    
    $out .= "<form class='form-horizontal' method='POST' action='$protocol://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . "'>";
    if (isset($_GET['redirect']))
      $out .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($_GET['redirect']) . '" />';
    $out .= '<h3>Log in</h3>';
    $out .= $message;
    $out .= '<div class="form-group">';
    $out .= '<input type="text" class="form-control" name="uname" id="inputUsername" placeholder="GUID" required autofocus>';
    $out .= '</div>';
    $out .= '<div class="form-group">';
    $out .= '<input type="password" class="form-control" name="pwd" id="inputPassword" placeholder="Password" required>';
    $out .= '</div>';
    $out .= '<div class="form-group">';
    $out .= '<button type="submit" id="loginButton" name="submit" class="btn btn-default btn-block">Log in</button>';
    $out .= '</div>';
    $out .= '</form>';
    $out .= '</div>';
    $out .= '</div>';
  } else
    $out = "You are logged in as {$uinfo['gn']} {$uinfo['sn']} (<a class='footer-logout-link' href='login.php?logout=1'>Log-out</a>)";
  $out .= '</div>';
  return $out;
}
