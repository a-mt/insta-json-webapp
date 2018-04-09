<?php
if(isset($_SESSION['user'])) {
  try {
    $ig->settings->deleteUser($_SESSION['user']['username']);
  } catch(Exception $e) {
  }
}

session_destroy();
header('Location: /');
exit;