<?php
defined('enter') or die();

//Выбор заказа из списка заказов
//Попытка обойтись без mysql через redis
if (!$order = redisAction(false, 'select_order_'.intval($pageParam), 'get')){
  $order = mysqlAction(['id' => intval($pageParam)], 'select-order', 'orders');
  redisAction($order, 'select_order_'.intval($pageParam), 'set');
}

if ($order){

  //Исполнители этого заказа (для заказчика, который создал этот заказ)
  if ($order['user_id'] == $auth['id'] && $auth['type'] = 1){
    $performers = mysqlAction($order['id'], 'performers', 'user_order');
  }

  //Брал и я (исполнитель) этот заказ
  //Для визуального отображения
  //(для исполнителей)
  if ($auth['type'] == 2){
      $exist = mysqlAction(['id' => $order['id']], 'select-order-performer-check', 'user_order');
  }

  //Избавляемся от ненужных html символов
  foreach ($order as &$a){
   $a=htmlspecialchars($a);
  }


  $order['performers'] = $performers;
  $order['exist'] = $exist;

  echo json_encode([
    "auth" => $auth,
    "title" => $order['title'],
    "content" => $order
  ]);

}else{

//Если нет заказа с указаным ID в $pageParam
@include_once __DIR__ . "/404.php";

}

?>
