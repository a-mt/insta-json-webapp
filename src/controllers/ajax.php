<?php
if(!isLoggedin()) {
  header('HTTP/1.0 403 Forbidden');
  echo 'Forbidden';
  exit;
}

header('Content-Type: application/json');
$user = $_SESSION['user'];

switch($PATH) {
    case "":
      print json_encode([
        "feed"        => "/ajax/feed?{cursor}",
        "account"     => "/ajax/account",
        "followers"   => "/ajax/followers",
        "following"   => "/ajax/following",
        "medias"      => "/ajax/medias",
        "medias_user" => "/ajax/medias?{user}",
      ]); break;

    case "account":
      print json_encode(modelToArray($ig->getAccount($user['username'])));
      break;

    case "medias":
      if(!$username = @$_GET['user']) {
        $username = $user['username'];
      }
      print toJson($ig->getMedias($username));
      break;

    case "followers":
      print json_encode($ig->getFollowers($user['id']));
      break;

    case "following":
      print json_encode($ig->getFollowing($user['id']));
      break;

    case "feed":
      print json_encode($ig->getFeed(@$_GET['cursor']));
      break;

    case "collections":
      $ig->getCollections();
}