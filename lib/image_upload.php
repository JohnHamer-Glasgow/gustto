<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__.'/../config.php');
require_once(__DIR__.'/../lib/database.php');
require_once(__DIR__.'/../lib/sharedfunctions.php');
require_once(__DIR__.'/../corelib/dataaccess.php');
require_once(__DIR__.'/../lib/formfunctions.php');
require_once(__DIR__.'/../lib/constants.php');

$uinfo = checkLoggedInUser(false, $error);
if ($uinfo == false) {
  header("Location: ../login.php");
  exit();
}

$dbUser = getUserRecord($uinfo);
$loggedUserID = $dbUser->id;
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_FILES["profilePicture"])) {
  if (!(isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']))
    exit();

  $profilePicture = $_FILES["profilePicture"];
  if ($profilePicture["error"] !== UPLOAD_ERR_OK) {
    echo "<p>An error occurred.</p>";
    exit;
  }
  
  $fileType = exif_imagetype($_FILES["profilePicture"]["tmp_name"]);
  $allowed = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);
  if (!in_array($fileType, $allowed)) {
    echo "<p>File type is not permitted.</p>";
    exit;
  }
  
  $name = preg_replace("/[^A-Z0-9._-]/i", "_", $profilePicture["name"]);
  $i = 0;
  $parts = pathinfo($name);
  while (file_exists(UPLOAD_DIR . $name)) {
    $i++;
    $name = $parts["filename"] . "-" . $i . "." . $parts["extension"];
  }

  $success = move_uploaded_file($profilePicture["tmp_name"], UPLOAD_DIR . $name);
  if (!$success) {
    echo "<p>Unable to save file.</p>";
    exit;
  }

  chmod(UPLOAD_DIR . $name, 0644);

  $user = user::retrieve_user($loggedUserID);
  $user->profile_picture = 'images/profile/' . $name;
  $user->update();
  
  header("Location: " . '../profile.php?usrID=' . $loggedUserID);
}
