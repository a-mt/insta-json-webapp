<?php
function isLoggedin() {
    if(!isset($_SESSION['user'])) {
      return false;
    }
    global $ig;

    // Get session from cache
    try {
      $ig = Instagram::withCredentials($_SESSION['user']['username'], "-");
      $ig->login();
    
    } catch(\InstagramScraper\Exception\InstagramAuthException $e) {
      return false;
    }

    return true;
}

/**
 * @param \InstagramScraper\Model\AbstractModel $obj
 * @return array
 */
function modelToArray($obj) {
  if(is_array($obj)) {
    return $obj;
  }
  $arr = array();
  $m   = array();

  foreach(get_class_methods($obj) as $method) {
    if($method == "getColumns" || !preg_match('/^(?:get|is)/', $method, $m)) {
      continue;
    }
    $name = lcfirst(substr($method, strlen($m[0])));
    $arr[$name] = $obj->$method();
  }
  return $arr;
}

/**
 * @param mixed $obj
 * @return string
 */
function toJson($obj) {
  return printr_source_to_json(print_r($obj, true));
}

/**
 * This function will convert output string of `print_r($array)` to `json string`
 * @note Exceptions are always there i tried myself best to get it done. Here $array can be array of arrays or arrays of objects or both
 * @param String $string This will contain the output of `print_r($array)` (which user will get from ctrl+u of browser),
 * @return String
 */
function printr_source_to_json($string)
{
    // `CLASSNAME Object (` => `{`
    $string = preg_replace("/[A-Za-z\\\]+ Object\s*\(/s", '{  ', $string);

    // `Array (` => `{`
    $string = preg_replace("/Array\s*\(/s", '{  ', $string);

    // )\n => }\n
    $string = preg_replace("/\)\n/", "},\n", $string);

    // `)` => `}`
    $string = preg_replace("/\)$/", '}', $string);

    // `[SOMEVALUE]` => `"SOMEVALUE"`
    $string = preg_replace_callback("/\[\s*([^\s\]]+)\s*\](?=\s*\=>)/", function($matches){
      return '"' . str_replace(':protected', '', $matches[1]) . '" ';
    }, $string);

    // `=> {` => `: {`
    $string = preg_replace("/=>\s*{/", ': {', $string);

    // `=> \n }` => `"" \n`
    $string = preg_replace("/=>\s*[\n\s]*\}/s", ":\"\"\n}", $string);

    // `=> SOMEVALUE` => `: "SOMEVALUE"`
    $string = preg_replace_callback("/=>\s*([^\[\"}]*)\n/", function($matches){
      return ':"' . str_replace("\n", '\n', $matches[1]) . '",' . "\n";
    }, $string);

    // `, }` => `}`
    $string = preg_replace("/,\s*}/s", '}', $string);

    // `} ,` => `}`
    return $string = preg_replace("/}\s*,$/s", '}', $string);
}