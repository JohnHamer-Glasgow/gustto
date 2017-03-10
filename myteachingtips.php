<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/config.php');
require_once(__DIR__.'/lib/database.php');
require_once(__DIR__.'/lib/sharedfunctions.php');
require_once(__DIR__.'/corelib/dataaccess.php');
$template = new templateMerge($TEMPLATE);

session_start();

$uinfo = checkLoggedInUser();
$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;

$template->pageData['pagetitle'] = 'GUSTTO Teaching Tips Online';
$template->pageData['homeURL'] = 'index.php';
$template->pageData['logoURL'] = 'images/logo/logo.png';

$_SESSION['url'] = $_SERVER['REQUEST_URI']; // current location (used for redirecting after login if user is not logged in)

if($uinfo==false){
		header("Location: login.php");
		exit();
}else{
  if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
    }

  //Drop Down menu

  $user = user::retrieve_user($loggedUserID);
  $loggedUserName =  $uinfo['gn'];
  $loggedUserLastname = $uinfo['sn'];

  $template->pageData['userLoggedIn'] = $loggedUserName.' '.$loggedUserLastname ;
  $template->pageData['profileLink'] = "profile.php?usrID=".$loggedUserID;

  $template->pageData['navTTs'] = 'sidebar-current-page';

  //Notifications
  if (notification::getNotifications($loggedUserID,false,0) == false) $notificationNo = 0;
  else $notificationNo = sizeof(notification::getNotifications($loggedUserID,false,0));
  $template->pageData['notificationNo'] = $notificationNo;
  $template->pageData['notifications'] = notifications($dbUser);

    //Content
    $template->pageData['content'].=
    	'<div class="col-sm-9 col-xs-12 my-tts-wrapper">
          <div class="my-tt-card">
            <div class="main-header"><h4>My Teaching Tips</h4></div>';


    //Teaching tip
    $myTTs =  $user->get_teaching_tips();
    $contributedTTs = $user->get_contr_teaching_tips();

    // MY TTS
    $existMyTT = 0; //check if there are my tts
    $template->pageData['content'].='<div class="my-tt-mytt-header"><h5>Published Teaching Tips</h5></div>';

    // Check for error message -- ERROR MESSAGE START
    if(!empty($myTTs)){
      foreach($myTTs as $myTT){

      $ttContributors = $myTT->get_contributors();
      
      if($myTT->draft==0){
      $existMyTT = 1;
      $template->pageData['content'].='
      <!-- TT -->
      <div class="row my-tt-wrapper">
      <div class="col-xs-12 my-tt-info" value="'.$myTT->id.'" >
      <div class="col-xs-12 feed-title">
      <h4 class="col-sm-8 col-xs-12 my-tt-title"><a href="teaching_tip.php?ttID='.$myTT->id.'">'.$myTT->title.'</a></h4>
      <span class="col-sm-4 col-xs-12 my-tt-time">'.date('H:i d M y',$myTT->time).'</span>
      </div>';

      //Check if there are contributors to tt draft and add them
      if(!empty($ttContributors)){
        $tempContr=' ';
        foreach($ttContributors as $ttContr){
          $tempContr.= $ttContr->name . ' ' . $ttContr->lastname . ', ';
        }
        $template->pageData['content'].='<div class="col-xs-12 my-tt-contributors">Co-authors:<span class=" my-tt-contributor">'.substr($tempContr,0,-2).'</span></div>';
      }


      $template->pageData['content'].='
      <div class="col-xs-12 feed-text-wrapper">
      <p class="feed-text">'.$myTT->description.' 
      </p>
      </div> 
      </div> 

      <!-- ./TT-info -->

      <div class="col-xs-12 my-tt-button-wrapper">
      <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">
      <div class="feed-icons">
      <a class="glyphicon glyphicon-thumbs-up feed-likebutton"></a>'.' '.$myTT->get_number_likes().'
      </div>
      <div class="feed-icons">
      <a class="glyphicon glyphicon-comment"></a>'.' '.$myTT->get_number_comments().'
      </div>
      <div class="feed-icons">
      <a class="glyphicon glyphicon-share-alt"></a>'.' '.$myTT->get_number_shares().'
      </div>
      </div>
      <div class="col-sm-6 col-xs-12 my-tt-buttons">
      <a href="teaching_tip.php?ttID='.$myTT->id.'" class="btn btn-success">View</a>
      <a href="teaching_tip_add.php?ttID='. $myTT->id .'" class="btn btn-info">Edit</a>

      <!-- Small modal -->

      <button type="button" class="btn btn-danger" data-toggle="modal" data-target=".target-uniqueId'.$myTT->id.'">Delete</button>

      <div class="modal fade delete-modal-tt target-uniqueId'.$myTT->id.'" id="deleteTTModal" tabindex="-1" role="dialog">
      <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">Delete Teaching Tip</h4>
      </div>
      <div>
      <h4 class="delete-modal-message">Are you sure you want to delete this Teaching Tip?</h4>
      </div>
      <div class="modal-footer delete-modal-buttons">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      <form class="deleteFormTT" action="ajax/deleteTT.php" method="post">
      <input type="hidden" name="csrf_token" value="'.$_SESSION["csrf_token"].'">
      <input type="hidden" name="ttId" value="'.$myTT->id.'">
      <button type="submit" class="btn btn-danger">Delete</button>
      </form>
      </div>
      </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
      </div><!-- /.modal --> 

      </div>
      </div>
      </div> 

      '; 
      }
      } // end of myTTs
    } // END ERROR CHECKING
    //check if myTT is empty to display message
    if ($existMyTT==0){
      $template->pageData['content'].='<div class="no-tts"><strong>You do not have any Teaching Tips.</strong></div>';
    }

    // Draft TTS

    $existDraftTT = 0; //check if there are draft tts
    $template->pageData['content'].='<div class="my-tt-draft-header"><h5>Draft Teaching Tips</h5></div>';
    
    // MY DRAFT
    // Check for error message -- ERROR MESSAGE START
    if(!empty($myTTs)){
      foreach($myTTs as $myTT){
      
      $ttContributors = $myTT->get_contributors();
      
      if($myTT->draft==1){
      $existDraftTT = 1;
      $template->pageData['content'].='
      <!-- TT -->
      <div class="row my-tt-wrapper">
      <div class="col-xs-12 my-tt-info" value="'.$myTT->id.'" >
      <div class="col-xs-12 feed-title">
      <h4 class="col-sm-8 col-xs-12 my-tt-title"><a href="teaching_tip.php?ttID='.$myTT->id.'">'.$myTT->title.'</a></h4>
      <span class="col-sm-4 col-xs-12 my-tt-time">'.date('H:i d M y',$myTT->time).'</span>
      </div>';
      
      //Check if there are contributors to tt draft and add them
      if(!empty($ttContributors)){
        $tempContr=' ';
        foreach($ttContributors as $ttContr){
          $tempContr.= $ttContr->name . ' ' . $ttContr->lastname . ', ';
        }
        $template->pageData['content'].='<div class="col-xs-12 my-tt-contributors">Co-authors:<span class=" my-tt-contributor">'.substr($tempContr,0,-2).'</span></div>';
      }
      
      

      $template->pageData['content'].='<div class="col-xs-12 feed-text-wrapper">
      <p class="feed-text">'.$myTT->description.' 
      </p>
      </div> 
      </div> 

      <!-- ./TT-info -->

      <div class="col-xs-12 my-tt-button-wrapper">
      <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">

      </div>
      <div class="col-sm-6 col-xs-12 my-tt-buttons">
      <a href="teaching_tip.php?ttID='.$myTT->id.'" class="btn btn-success">View</a>
      <a href="teaching_tip_add.php?ttID='. $myTT->id .'" class="btn btn-info">Edit</a>

      <!-- Small modal -->

      <button type="button" class="btn btn-danger" data-toggle="modal" data-target=".target-uniqueId'.$myTT->id.'">Delete</button>

      <div class="modal fade delete-modal-tt target-uniqueId'.$myTT->id.'" id="deleteTTModal" tabindex="-1" role="dialog">
      <div class="modal-dialog">
      <div class="modal-content">
      <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">Delete Teaching Tip</h4>
      </div>
      <div>
      <h4 class="delete-modal-message">Are you sure you want to delete this Teaching Tip?</h4>
      </div>
      <div class="modal-footer delete-modal-buttons">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      <form class="deleteFormTT" action="ajax/deleteTT.php" method="post">
      <input type="hidden" name="csrf_token" value="'.$_SESSION["csrf_token"].'">
      <input type="hidden" name="ttId" value="'.$myTT->id.'">
      <button type="submit" class="btn btn-danger">Delete</button>
      </form>
      </div>
      </div><!-- /.modal-content -->
      </div><!-- /.modal-dialog -->
      </div><!-- /.modal --> 

      </div>
      </div>
      </div> 

      '; 
        } //end if draft
      } // end of foreach DraftTTs
    } // END ERROR CHECKING


    // CONTRIBUTED DRAFT
    $existContrTT = 0; //check if there are contr tts
    // CHECK ERROR MESSAGE
    if(!empty($contributedTTs)){
      foreach($contributedTTs as $cTT){
        if($cTT->draft==1){
          $author = user::retrieve_user($cTT->author_id);
          $existContrTT = 1;
          $template->pageData['content'].='
          <!-- TT -->
          <div class="row my-tt-wrapper">
          <div class="col-xs-12 my-tt-info" value="'.$cTT->id.'" >
          <div class="col-xs-12 feed-title">
          <div class="col-sm-10 col-xs-12 my-tt-title">
            <h4><a href="teaching_tip.php?ttID='.$cTT->id.'">'.$cTT->title.'</a></h4>
            <span class="draft-tt-user">by <a href="profile.php?usrID='.$author->id.'">'.$author->name.' '.$author->lastname.'</a></span>
          </div>
          <span class="col-sm-2 col-xs-12 my-tt-time">'.date('H:i d M y',$cTT->time).'</span>
          </div>
          <span class="col-xs-12">

          </span>
          <div class="col-xs-12 feed-text-wrapper">
          <p class="feed-text">'.$cTT->description.' 
          </p>
          </div> 
          </div> 

          <!-- ./TT-info -->

          <div class="col-xs-12 my-tt-button-wrapper">
          <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">

          </div>
          <div class="col-sm-6 col-xs-12 my-tt-buttons">
          <a href="teaching_tip.php?ttID='.$myTT->id.'" class="btn btn-success">View</a>
          <a href="teaching_tip_add.php?ttID='.$cTT->id .'" class="btn btn-info">Edit</a>

          </div>
          </div>
          </div> 

          ';
        }// end of draft check 
      } // end of foreach ContrTTs
    } // END ERROR

    //check if DraftTT is empty to display message
    if ($existDraftTT==0&&$existContrTT==0){
      $template->pageData['content'].='<div class="no-tts"><strong>You do not have any Draft Teaching Tips.</strong></div>';
    }

    
    // Contributed TTS

    $existContrTT = 0; //check if there are contr tts
    $template->pageData['content'].='<div class="my-tt-contr-header"><h5>Co-authored Teaching Tips</h5></div>';
    // CHECK ERROR MESSAGE
    if(!empty($contributedTTs)){
      foreach($contributedTTs as $cTT){
        if($cTT->draft==0){
          $author = user::retrieve_user($cTT->author_id);
          $existContrTT = 1;
          $template->pageData['content'].='
          <!-- TT -->
          <div class="row my-tt-wrapper">
          <div class="col-xs-12 my-tt-info" value="'.$cTT->id.'" >
          <div class="col-xs-12 feed-title">
          <div class="col-sm-10 col-xs-12 my-tt-title">
            <h4><a href="teaching_tip.php?ttID='.$cTT->id.'">'.$cTT->title.'</a></h4>
            <span class="draft-tt-user">by <a href="profile.php?usrID='.$author->id.'">'.$author->name.' '.$author->lastname.'</a></span>
          </div>
          <span class="col-sm-2 col-xs-12 my-tt-time">'.date('H:i d M y',$cTT->time).'</span>
          </div>
          <span class="col-xs-12">

          </span>
          <div class="col-xs-12 feed-text-wrapper">
          <p class="feed-text">'.$cTT->description.' 
          </p>
          </div> 
          </div> 

          <!-- ./TT-info -->

          <div class="col-xs-12 my-tt-button-wrapper">
          <div class="col-sm-6 col-xs-12 my-tt-icons-wrapper">

          </div>
          <div class="col-sm-6 col-xs-12 my-tt-buttons">
          <a href="teaching_tip.php?ttID='.$cTT->id.'" class="btn btn-success">View</a>
        
          </div>
          </div>
          </div> 

          ';
        }// end of draft check 
      } // end of foreach ContrTTs
    } // END ERROR
    //check if ContrTT is empty to display message
    if ($existContrTT==0){
    $template->pageData['content'].='<div class="no-tts"><strong>You are not a Co-author of any Teaching Tips.</strong></div>';
    }




    $template->pageData['content'] .= '</div>
                </div>';
    

    $template->pageData['logoutLink'] = loginBox($uinfo);

}

echo $template->render();

?>