<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>Instagram<?= !empty($page_title) ? ' | ' . $page_title : '' ?></title>
    <link rel="stylesheet" href="/static/style.css">
  </head>
  <body>
    <?= $content ?>
  </body>
</html>