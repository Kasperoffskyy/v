<?php
defined('enter') or die();

//Для дополнительной подгрузки списка заказов
if ($pageParam == 'my'){
  $pageParam = $auth['id'];
}else{
  $pageParam = intval($pageParam);
}

if ($pgSm){
  $pgSm = intval($pgSm);
}else{
  $pgSm = '';
}

//Попытка обойтись без mysql, через redis
if (!$orders = redisAction(false, 'show-more'.$pageParam.$pgSm, 'get')){
  $orders = mysqlAction(['param' => $pageParam, 'pg' => $pgSm], 'show-more', 'orders');
  redisAction($orders, 'show-more'.$pageParam.$pgSm, 'set');
}

echo json_encode([
  "content" => $orders
]);

?>
