<?php
defined('enter') or die();

//Обновить баланс исполнителя
if ($auth['type'] == 2){

  redisAction(false, $auth_token, 'del');

  echo json_encode([
    "action" => "ref-balance-true"
  ]);

}

?>
