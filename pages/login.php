<?php
defined('enter') or die();

//Данные из формы для проверки авторизации
$data = $_POST;

//Обрабатываем запрос на авторизацию пользователя
if ($data){

$email = trim($data['email']);
$password = trim($data['password']);

//Выбор одной из записей заказов
if ($email && $password){

  //Пробуем авторизоваться
  if ($auth = mysqlAction($data, 'login', 'users')){

    redisAction(false, $auth['auth_token'], 'del');

    echo json_encode([
      "auth" => $auth,
      "action" => 'auth'
    ]);

  }else{
    $error[] = 'Неверный E-mail или пароль';
  }

}else{

  $error[] = 'Заполните все поля';

}

if ($error){
  header("HTTP/1.0 403 Forbidden");
  echo json_encode($error);
}

}else{

  echo json_encode([
    "auth" => $auth,
    "_csrf" => $_csrf,
    "title" => "Вход"
  ]);

}


?>
