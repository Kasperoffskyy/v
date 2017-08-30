<?php
defined('enter') or die();


//Это ошибка 404
//Может появиться, если ввести несуществующий путь в $route Array
header("HTTP/1.0 404 Not Found");

echo json_encode([
  "auth" => $auth,
  "title" => "404 Not Found",
  "content" => '<p>Такая страница не существует.</p>'
]);

?>
