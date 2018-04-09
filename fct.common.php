<?php
$ig = new Instagram();

function isLoggedin() {
    if(!isset($_SESSION['user'])) {
      return false;
    }
    global $ig;
    return true;
}

// Render challenge template
function formChallenge($data) {
  global $tpl;
  $extra = [];

  if(isset($data['extraData']['content'])) {
    foreach($data['extraData']['content'] as $extraData) {
      $name = $extraData['__typename'];
      unset($extraData['__typename']);
      $extra[$name] = $extraData;
    }
  } else {
    $extra = $data['extraData'];
  }
  $tpl->assign('challengeType', $data['challengeType']);
  $tpl->assign('extraData', $extra);
  $tpl->assign('fields', $data['fields']);

  print $tpl->challenge();
  die;
}