<?php
require_once(__DIR__.'/guldap_login.php');

function checkLoggedInUser($allowLogin = true)
{
	global $CFG;
    $uinfo = false;
	if(($allowLogin )&&(isset($_REQUEST['uname']))&&(isset($_REQUEST['pwd'])))
    {
        $uinfo = checkLogin($_REQUEST['uname'], $_REQUEST['pwd']);
    }
    elseif(isset($_REQUEST['logout']))
    {
        setcookie($CFG['appname'].'_login', false, time() + 36000, '/');
    }
    elseif((isset($_COOKIE[$CFG['appname'].'_login']))&&($uinfo==false))
    {
        $uinfo = CheckValidLoginCookie($_COOKIE[$CFG['appname'].'_login']);
    }
    if($uinfo)
    {
      	setcookie($CFG['appname'].'_login',CreateLoginCookie($uinfo), time() + 36000, '/');
        return $uinfo;
    }
    else
    {  
        setcookie($CFG['appname'].'_login', '');
    	return false;
    }
}

function CreateLoginCookie($uinfo)
{
	global $CFG;
    $cookieinfo = base64_encode(serialize($uinfo));
    $cookie = implode('@', array($cookieinfo,time()+$CFG['cookietimelimit']));
    $cookie = $cookie .'::'.md5($cookie.$CFG['cookiehash']);
    return $cookie;
}

function CheckValidLoginCookie($cookie)
{
	global $CFG;
  	list($cookie,$hash) = explode('::',$cookie,2);
    if(trim(md5($cookie.$CFG['cookiehash']))==trim($hash))
    {
      	list($cookieinfo, $t) = explode('@',$cookie,2);
      	if(intval($t) > time())
        {
            return unserialize(base64_decode($cookieinfo));
        }
    }
   	return false;
}

// function loginBox($uinfo)
//     {
// 	$out ='<div class="loginBox">';
//     $out .= '';
//     if($_SERVER['HTTPS']=='on')
//         $protocol = 'https';
//     else
//         $protocol = 'http';
//     if($uinfo==false)
//         {
// 		$out .= "<form method='POST' action='$protocol://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."'>";
// 	    $out .= "<table><tr><td><label for='uname'>GUID</label>:</td><td><input type='text' name='uname' id='uname'/></td></tr>";
// 	    $out .= "<tr><td><label for='pwd'>Password</label>:</td><td><input type='password' name='pwd' id='pwd'/></td></tr>";
// 	    $out .= "<tr><td colspan='2' align='center'><input type='submit' name='submit' value='Log-in'/></td></tr></table></form>";
//         }
//         else
//     	$out .= "You are logged in as {$uinfo['gn']} {$uinfo['sn']} (<a href='{$_SERVER['PHP_SELF']}?logout=1'>Log-out</a>)";
//     $out .= '</div>';
//     return $out;
// }

function loginBox($uinfo) {
    
    
    if($_SERVER['HTTPS']=='on')
        $protocol = 'https';
    else
        $protocol = 'http';
    if($uinfo==false) {
        $out ='<div class="card login-card col-sm-10 col-xs-12 col-sm-offset-1">';
        $out .= '<div class="row">';
        $out .= '<div class="gustto-login-img-wrapper col-sm-6 col-xs-12 hidden-xs">';
        $out .= '<div class="gustto-login-img">';
        $out .= '
        <div id="login-images" class="carousel slide carousel-wrapper" data-ride="carousel">
          <!-- Indicators -->

          <ol class="carousel-indicators">
            <li data-target="#login-images" data-slide-to="0" class="active"></li>
            <li data-target="#login-images" data-slide-to="1"></li>
            <li data-target="#login-images" data-slide-to="2"></li>
            <li data-target="#login-images" data-slide-to="3"></li>
            <li data-target="#login-images" data-slide-to="4"></li>
            <li data-target="#login-images" data-slide-to="5"></li>
          </ol>

          <!-- Wrapper for slides -->
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

        </div>';

        $out .= '</div>';
        $out .= '</div>';

        $out .= '<div class="form-wrapper col-sm-6 col-xs-12">';
        $out .= "<form class='form-horizontal' method='POST' action='$protocol://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']."'>";
        $out .= '<h3>Log in</h3>';

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
        }
        else
        $out = "You are logged in as {$uinfo['gn']} {$uinfo['sn']} (<a class='footer-logout-link' href='login.php?logout=1'>Log-out</a>)";
    $out .= '</div>';
    return $out;
}


?>
