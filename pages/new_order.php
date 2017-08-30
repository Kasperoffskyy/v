<?php
defined('enter') or die();

//Заказчик создает новый заказ на сайте
//Страница только для заказчиков
if (!$auth || $auth['type'] != 1){
  echo json_encode([
    "auth" => $auth,
    "action" => "back"
  ]);
die();
}

$data = $_POST;

if ($data){

  $title = trim($data['title']);
  $text = trim($data['text']);
  $data['price'] = floatval(preg_replace('/,/', '.', $data['price'], 1));
  $price = $data['price'];

  if ($price == 0 || $price < 0){

    $error[] = 'Некорректная сумма';
  }else{

    if (($auth['balance'] - $price) < 0){

      $error[] = 'Недостаточно средств.';
    }

  }


  if (!$title || !$text || !$price){

    $error[] = 'Заполните все поля';
  }



  if (!$error){

    //Подстрахуемся редисом, чтобы случайно не снять лишнего с заказчика
    if (!$paid = redisAction(false, 'last-pay-'.$auth['id'], 'get')){
      if ($paid = mysqlAction($data, 'pay-for-order', 'users')){
        redisAction($price, 'last-pay-'.$auth['id'], 'set');
      }
    }

    if ($paid){

      if (!mysqlAction($data, 'new-order', 'orders')){

          $error[] = 'Не удалось создать заказ, повторите еще раз';
      }

    }else{
      $error[] = 'Не удалось снять с Вас деньги, попробуйте еще раз';
    }

  }



  if (!$error){

    //Если платеж завершен и заказ создан
    redisAction(false, 'last-pay-'.$auth['id'], 'del');

    //Обновим кеш баланса заказчика
    redisAction(false, $auth_token, 'del');

    //Обновим кеш всех заказов т.к у нас появился новый в списке
    redisAction(false, 'all_orders', 'del');

    //Обновим кеш моих заказов
    redisAction(false, 'all_orders'.$auth['id'], 'del');

    echo json_encode([
      "auth" => $auth,
      "action" => 'new-order'
    ]);

  }else{

    header("HTTP/1.0 403 Forbidden");
    echo json_encode($error);
  }


}else{

echo json_encode([
  "auth" => $auth,
  "_csrf" => $_csrf,
  "title" => "Опубликовать свой новый заказ"
]);

}

?>
