<?php
defined('enter') or die();

//Данные из формы для регистрации
$data = $_POST;

//Обрабатываем запрос на регистрацию нового пользователя
if ($data){

  $name = trim($data['name']);
  $email = trim(filter_var($data['email'], FILTER_VALIDATE_EMAIL));
  $password = trim($data['password']);
  $rpassword = trim($data['rpassword']);
  $type = intval($data['type']);

  if ($type < 1 || $type > 2){
    $type = null;
  }

  if ($type && $name && $email && $password && $rpassword && $type){

    //Проверка Email пользователя на уникальность
    if (mysqlAction($email, 'check-email', 'users')){
      $error[] = 'Такой Email адрес уже зарегистрирован';
    }

    if ($password != $rpassword){
      $error[] = 'Пароли не совпадают';
    }

  }else{
    $error[] = 'Заполните все поля';
  }


  if (!$error){

    //Регистрация пользователя
    if (mysqlAction($data, 'register', 'users')){

      //Авторизуем зарегистрированного пользователя
      require_once __DIR__ . "/login.php";

    }

  }else{

    header("HTTP/1.0 403 Forbidden");
    echo json_encode($error);
  }

}else{

  echo json_encode([
    "auth" => $auth,
    "_csrf" => $_csrf,
    "title" => "Регистрация"
  ]);

}



?>
