<?php
if(!isLoggedin()) {
  unset($_SESSION['user']);

  header('Location: ' . REWRITE_BASE . 'login');
  exit;
}
try {
  $ig->login($_SESSION['user']['username'], $_SESSION['user']['password']);

} catch(\InstagramAPI\Exception\ChallengeRequiredException $e) {
  $challengeData = $ig->getChallengeData($e);
  $challenge     = $ig->getChallenge($challengeData['url'], $challengeData['headers']);

  $_SESSION['challenge'] = $challengeData;
  formChallenge($challenge['entry_data']['Challenge'][0]);
}

// Query
if(!empty($action = @$_GET['_']) && strpos($action, '_')) {
  list($path, $method) = explode('_', $action, 2);

  if(property_exists($ig, $path) && method_exists($ig->$path, $method)) {
    $args = [];

    // Retrieve parameters
    $reflect    = new ReflectionObject($ig->$path);
    $parameters = $reflect->getMethod($method)->getParameters();

    foreach($parameters as $parameter) {
      $name = $parameter->getName();

      if(isset($_GET[$name])) {
        $args[] = $_GET[$name];

      } else {
        if(!$parameter->isOptional()) {
          throw new Exception("Parameter $name wasn't provided");
        }
        $args[] = $parameter->getDefaultValue();
      }
    }

    // Call method
    $response = call_user_func_array(array($ig->$path, $method), $args);
    if(isset($_GET['_gallery'])) {
      print $tpl->gallery(['data' => $response->asArray()]);

    } else {
      header('Content-Type: application/json');
      echo (string) $response->getHttpResponse()->getBody();
    }
    die;
  }
}

// Display all available actions
$data  = [];
$paths = [
  'account', 'business', 'collection', 'creative', 'direct', 'discover',
  'hashtag', 'highlight', 'internal', 'live', 'location', 'media',
  'people', 'push', 'story', 'timeline', 'usertag'
];
foreach($paths as $path) {
  $methods = get_class_methods($ig->$path);
  $reflect = new ReflectionObject($ig->$path);

  // Get list of methods
  foreach($methods as $i => &$method) {
    if($method == "__construct") {
      unset($methods[$i]);
    }

    // Get list of parameters
    if($parameters = $reflect->getMethod($method)->getParameters()) {
      $q = '';
      foreach($parameters as $parameter) {
        $q .= $parameter->getName() . ($parameter->isOptional() ? '?' : '') . ',';
      }
      $method .= ' {' . substr($q, 0, -1) . '}';
    }
  }
  $data[$path] = $methods;
}

header('Content-Type: application/json');
echo json_encode($data);
