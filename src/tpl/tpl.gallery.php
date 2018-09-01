<?php
$items = false;

if(isset($data['items'])) {
  $items = $data['items'];
} else if(isset($data['feed_items'])) {
  $items = $data['feed_items'];
}
if($items !== false) {

  echo '<div class="items">';
  foreach($items as $item) {
     echo '<div class="item">';

     $media = false;
     if(isset($item['media'])) {
       $media = $item['media'];
     } else if(isset($item['media_or_ad'])) {
       $media = $item['media_or_ad'];
     }

     if($media !== false) {
       echo '<a target="_blank" href="https://www.instagram.com/p/' . $media['code'] . '/">';

       if(!empty($media['carousel_media'])) {
         foreach($media['carousel_media'] as $i_media) {
           echo '<img src="' . end($i_media['image_versions2']['candidates'])['url'] . '">';
         }
       } else {
         echo '<img src="' . end($media['image_versions2']['candidates'])['url'] . '">';
       }
       echo '</a>';

     } else {
       echo '<pre>' . json_encode($item, JSON_PRETTY_PRINT) . '</pre>';
     }
     echo '</div>';
  }
  
  if($data['more_available']) {
    $url = '?';
    $_GET['maxId'] = $data['next_max_id'];

    foreach($_GET as $param => $value) {
        $url .= $param . '=' . $value . '&';
    }
    echo '<div class="item"><a href="' . $url . '">Next</a></div>';
  }
  echo '</div>';

} else {
  echo '<div class="item alone">';
  echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';
  echo '</div>';
}
