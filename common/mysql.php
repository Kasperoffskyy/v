<?php
defined('enter') or die();


//Все дейсвтия с MYSQL в этой функции
//$data - массив данных запроса
//$action - секция дейсвтия в функции
//$table - таблица к которой пойдет запрос
function mysqlAction($data, $action, $table){

  //$auth Данные авторизации пользователя, если имеются. Нужны в некоторых запросах
  //$_DB Массив подключений к базе MYSQL
  global $_DB, $auth;

  //Выбор базы из config.php в зависимости от таблицы к которой идет обращение
  //По умолчанию везде выбрана база db1
  //Всего пока три таблицы users / orders / user_order
  if ($table == 'users'){
    $db = 'db1';
  }

  if ($table == 'orders'){
    $db = 'db1';
  }

  if ($table == 'user_order'){
    $db = 'db1';
  }

  //Настройки соединения в зависимости от выбранной базы данных для текущего запроса
  $host = $_DB[$db]['host'];
  $user = $_DB[$db]['user'];
  $name = $_DB[$db]['name'];
  $pass = $_DB[$db]['pass'];

  //Подключаем mysql
  $mysql = @mysqli_connect($host, $user, $pass, $name);

  //Проверка соединения с mysql
  if (!mysqli_connect_errno() && mysqli_ping($mysql)) {
    $mysqlConnected = true;
  }else{
    $mysqlConnected = false;
  }

  //Если соединение в порядке, то выполним нужные запросы
  if ($mysqlConnected){

    //Вывод всех заказов
    //Вызывается из /pages/all_orders.php
    //В зависимости от статуса, типа пользователя и выбора "Все" или "Мои"
    if ($action == 'all_orders'){

        //Все мои заказы (заказчика)
        if ($data['param'] && $auth['type'] == 1){

            $w = "WHERE `user_id` = {$auth['id']} AND `performer` IS NULL";

        }

        //Все заказы где я (исполнитель) был утвержден
        if ($data['param'] && $auth['type'] == 2){

            $w = "WHERE `performer` = {$auth['id']} ";
        }

        if (!$data['param']){

          $w = "WHERE `performer` IS NULL";
        }

        $result = mysqli_fetch_all(mysqli_query($mysql, "SELECT `id`, `user_id`, `title`, `text`, `price` FROM `orders` $w ORDER BY `id` DESC LIMIT 25"), MYSQLI_ASSOC);

    }

    //Кнопка "показать еще"
    if ($action == 'show-more'){

      if ($data['my']){

        $w = "WHERE `user_id` = {$auth['id']} AND";
      }

      $result = mysqli_fetch_all(mysqli_query($mysql, "SELECT `id`, `user_id`, `title`, `text`, `price` FROM `orders` WHERE $w `id` < {$data['param']}  AND `performer` IS NULL ORDER BY `id` DESC LIMIT 25"), MYSQLI_ASSOC);

    }

    //Вывод конкретного заказа из списка
    if ($action == 'select-order'){

        $result = mysqli_fetch_array(mysqli_query($mysql, "SELECT `id`, `user_id`, `name`, `title`, `text`, `price`, `performer` FROM `orders` WHERE `id` = {$data['id']} limit 1"), MYSQLI_ASSOC);

    }

    //Проверка для исполнителя, может быть он уже брал этот заказ
    if ($action == 'select-order-performer-check'){

      $result = mysqli_num_rows(mysqli_query($mysql, "SELECT `id` FROM `user_order` WHERE `performer_id` = {$auth['id']} AND `order_id` = {$data['id']} limit 1"));
    }

    //Проверка статуса заказа (для исполнителя)
    if ($action == 'order-check'){

        $result = mysqli_fetch_array(mysqli_query($mysql, "SELECT `user_id`, `price` FROM `orders` WHERE `id` = {$data['take-order']} AND `performer` IS NULL limit 1"), MYSQLI_ASSOC);

    }

    //Взять заказ (для исполнителя)
    if ($action == 'take-order'){

      //Может выполниться только один раз для конкретного исполнителя
      $result = mysqli_query($mysql, "INSERT INTO `user_order` SET `user_id` = {$data['user_id']}, `performer_id` = {$auth['id']}, `order_id` = {$data['take-order']}, `info` = '".urlencode($auth['name'])."/{$auth['id']}/".urlencode($auth['email'])."/{$data['price']}/{$data['take-order']}' ");
    }

    //Список исполнителей (для заказчика)
    if ($action == 'performers'){

        $result = mysqli_fetch_all(mysqli_query($mysql, "SELECT `performer_id`, `info` FROM `user_order` WHERE `order_id` = $data "), MYSQLI_ASSOC);

    }

    //Проверка выбранного исполнителя (для заказчика)
    if ($action == 'select-performer'){

        $result = mysqli_fetch_array(mysqli_query($mysql, "SELECT `info` FROM `user_order` WHERE `order_id` = {$data['order_id']} AND `user_id` = {$auth['id']} AND `performer_id` = {$data['select-performer']} limit 1"), MYSQLI_ASSOC);
    }


    //Переводим деньги исполнителю (для заказчика)
    //Если запрос один раз выполнился - то больше не сможет (для защиты от случайных повтроных запросов заказчика)
    if ($action == 'performer-upbalance'){

        $result = mysqli_query($mysql, "UPDATE `users` SET `balance` = `balance` + {$data['price']}, `last_order_req` = {$data['order_id']} WHERE `id` = {$data['select-performer']} AND `last_order_req` <> {$data['order_id']} limit 1");
    }

    //Закрываем заказ (для заказчика)
    if ($action == 'close-order'){

      $result = mysqli_query($mysql, "UPDATE `orders` SET `performer` = {$data['select-performer']} WHERE `id` = {$data['order_id']} limit 1");
    }

    //Новывй заказ от заказчика
    if ($action == 'new-order'){

        //Безопасный INSERT -  создание нового заказа с данными от пользователя из формы
        if ($stmt = mysqli_prepare($mysql, "INSERT INTO `orders` (`user_id`, `name`, `price`, `title`, `text`) VALUES (?, ?, ?, ?, ?)")) {
            mysqli_stmt_bind_param($stmt, 'issss', $user_id, $name, $price, $title, $text);

            $user_id = $auth['id'];
            $name = $auth['name'];
            $price = $data['price'];
            $title = $data['title'];
            $text = $data['text'];

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_affected_rows($stmt);
            $result > 0 ? $result = true : $result = false;
            mysqli_stmt_close($stmt);
        }
      }

    //Обновляем баланс заказчика
    if ($action == 'pay-for-order'){
      $result = mysqli_query($mysql, "UPDATE `users` SET `balance` = `balance` - {$data['price']} WHERE `id` = {$auth['id']} limit 1");
    }


    //Регистрация нового пользователя
    if ($action == 'register'){

        //Безопасный INSERT - регистрация данных от пользователя из формы
        if ($stmt = mysqli_prepare($mysql, "INSERT INTO `users` (`type`, `name`, `email`, `password`) VALUES (?, ?, ?, ?)")) {
            mysqli_stmt_bind_param($stmt, 'isss', $type, $name, $email, $password);

            $type = $data['type'];
            $name = $data['name'];
            $email = $data['email'];
            $password = md5(md5($data['password']));

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_affected_rows($stmt);
            $result > 0 ? $result = true : $result = false;
            mysqli_stmt_close($stmt);
        }
      }

    //Авторизация пользователя
    if ($action == 'login'){

      //Безопасный SELECT данных от пользователя из формы входа
      if ($stmt = mysqli_prepare($mysql, "SELECT `id`, `type`, `email`, `name`, `auth_token` FROM `users` WHERE `email`=? AND `password` = ? limit 1")) {
            mysqli_stmt_bind_param($stmt, 'ss', $email, $password);

            $email = $data['email'];
            $password = md5(md5($data['password']));

            mysqli_stmt_execute($stmt);
            $result = mysqli_fetch_array(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
      }

      if ($result){
            //Создание auth_token для пользователя (меняется каждый раз при авторизации)
            mysqli_query($mysql, "UPDATE `users` SET `auth_token` = '".setAuthUser($result['id'], $result['type'], $result['email'])."' WHERE `id` = {$result['id']}");
      }

    }

    //Проверка уникальности Email
    //Это не обязательно, т.к `email` unique, но можно использовать для поэтапной проверки регистрации
    if ($action == 'check-email'){

      //Безопасный SELECT проверка e-mail от пользователя из формы регистрации
      if ($stmt = mysqli_prepare($mysql, "SELECT `id` FROM `users` WHERE `email`=? limit 1")) {
            mysqli_stmt_bind_param($stmt, 's', $email);

            $email = $data;

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_fetch($stmt);
            $result > 0 ? $result = true : $result = false;
            mysqli_stmt_close($stmt);
      }
    }

    //Проверка авторизации
    if ($action == 'check-auth'){

      //Безопасный SELECT - првоерка TOKEN (cookies) от пользователя
      if ($stmt = mysqli_prepare($mysql, "SELECT `id`, `type`, `name`, `balance`, `email` FROM `users` WHERE `auth_token` = ? limit 1")) {
            mysqli_stmt_bind_param($stmt, 's', $auth_token);

            $auth_token = $data;

            mysqli_stmt_execute($stmt);
            $result = mysqli_fetch_array(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
      }

    }


    //DEMO пополнение счета заказчика
    if ($action == 'up-balance'){

          //Создание auth_token для пользователя (меняется каждый раз при авторизации)
          $result = mysqli_query($mysql, "UPDATE `users` SET `balance` = `balance` + $data WHERE `id` = {$auth['id']} limit 1");
          return $result;
    }


    //Выход пользователя через кнопку выхода
    if ($action == 'logout'){

        //Аннулируем TOKEN пользователя
        $result = mysqli_query($mysql, "UPDATE `users` SET `auth_token` = '' WHERE `id` = $data limit 1");
    }


  mysqli_close($mysql);
  }else{

    $result = false;
  }

  return $result;
}


?>
