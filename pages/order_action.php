<?php
defined('enter') or die();

//Разные дейсвтия с заказами
$data = $_POST;

//Взять заказ (только для исполнителей)
if (intval($data['take-order']) && $auth['type'] == 2){

  $successful = false;

  if ($order = mysqlAction($data, 'order-check', 'orders')){

    $data['user_id'] = $order['user_id'];
    $data['price'] = $order['price'];

    if (mysqlAction($data, 'take-order', 'user_order')){

      $successful = true;

    }

  }

    if ($successful){
      echo json_encode([
        "action" => "take-order-true"
      ]);
    }else{
      echo json_encode([
        "action" => "take-order-false"
      ]);
    }

}

//Выбрать исполнителя (только для заказчиков)
if (intval($data['select-performer']) && intval($data['order_id']) && $auth['type'] == 1){

  $successful = false;

  //Подстрахуемся редисом, чтобы можно было повторить запрос в случае неудачи
  if (!$result = redisAction(false, "last-performer-{$auth['id']}-{$data['select-performer']}", 'get')){
    $result = mysqlAction($data, 'select-performer', 'user_order');
    redisAction($result, "last-performer-{$auth['id']}-{$data['select-performer']}", 'set');
  }

  //Выбираем исполнителя + узнаем цену заказа
  if ($result){

    $data['price'] = explode('/', $result['info'])[3];

    //Вычитаем коммиссию
    $data['price'] = $data['price'] * (100 - COMMISSION) / 100;

    //Переводим деньги исполнителю
    if (mysqlAction($data, 'performer-upbalance', 'users')){

      //Закрываем заказ
      if (mysqlAction($data, 'close-order', 'orders')){

        $successful = true;

        //Удалем ненужный кеш заказчика
        redisAction(false, "last-performer-{$auth['id']}-{$data['select-performer']}", 'del');

        //Обновляем кеш заказов
        redisAction(false, 'select_order_'.$data['order_id'], 'del');
        redisAction(false, 'all_orders',  'del');
        redisAction(false, 'all_orders'.$auth['id'],  'del');
        redisAction(false, 'all_orders'.$data['select-performer'],  'del');

      }

    }

  }

  if ($successful){
    echo json_encode([
      "action" => "select-performer-true"
    ]);
  }else{
    echo json_encode([
      "action" => "select-performer-false"
    ]);
  }

}


if (!$data){

  echo json_encode([
    "auth" => $auth,
    "action" => "back",
    "title" => "Вам сюда нельзя",
    "content" => 'Недостаточно прав'
  ]);

}

?>
