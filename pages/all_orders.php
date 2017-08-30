<?php
defined('enter') or die();

//Все заказы
//Или все Мои заказы, в зависисмости от параметра
if ($pageParam == 'my'){
  $pageParam = $auth['id'];
}else{
  $pageParam = '';
}

//Попытка обойтись без mysql, через redis
if (!$orders = redisAction(false, 'all_orders'.$pageParam, 'get')){
  $orders = mysqlAction(['param' => $pageParam], 'all_orders', 'orders');

  if ($orders){
    redisAction($orders, 'all_orders'.$pageParam, 'set');
  }

}

if (!$orders){
    $orders = false;
}

echo json_encode([
  "auth" => $auth,
  "title" => "Заказы",
  "_csrf" => $_csrf,
  "content" => $orders
]);

?>
