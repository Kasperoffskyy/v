<?php
defined('enter') or die();

//Страница выхода пользователя через кнопку "выход"
$data = $_POST;

if ($auth && $data['logout']){

  //Удаляем токены и кеши пользователя
  mysqlAction($auth['id'], 'logout', 'users');
  redisAction(false, $auth_token, 'del');

  echo json_encode([
    "auth" => null,
    "_csrf" => $_csrf,
    "action" => 'logout'
  ]);

}



?>
