<?php
defined('enter') or die();


//На этой странице происходит DEMO пополнение счета заказчика
$data = $_POST;

//Пополнение счета только для заказчиков
if (!$data || !$auth || $auth['type'] != 1){
  echo json_encode([
    "auth" => $auth,
    "_csrf" => $_csrf,
    "action" => "back",
    "title" => "Вам сюда нельзя",
    "content" => 'Эта страница не предназначена для просмотра.'
  ]);
die();
}

  $addBalance = floatval(preg_replace('/,/', '.', $data['up_balance'], 1));

  if ($addBalance && mysqlAction($addBalance, 'up-balance', 'users')){

    redisAction(false, $auth_token, 'del');

    echo json_encode([
      "auth" => $auth,
      "action" => "up-balance-true"
    ]);

  }else{

    echo json_encode([
      "action" => "up-balance-false"
    ]);
  }


?>
