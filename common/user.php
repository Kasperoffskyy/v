<?php
defined('enter') or die();

//Наличие уникального токена в куках пользователя
$auth_token = $_COOKIE['auth_token'];
$_csrf = $_COOKIE['_csrf'];

//Если есть токен
if ($auth_token){

  //Проверка авторизации и необходимые данные о пользователе из базы
  //Идет попытка обойтись без mysql через redis
  if (!$auth = redisAction(false, $auth_token, 'get')){
    $auth = mysqlAction($auth_token, 'check-auth', 'users');
  }

  //Если пользователь авторизован
  if ($auth){

    //Запишем это в REDIS
    redisAction($auth, $auth_token, 'set');

    //Уберем из вывода данных о пользователя все нежелательные спецсимволы
    foreach ($auth as &$a){
     $a=htmlspecialchars($a);
    }

  }

}

//Вызывается при авторизации и регистрации
//Генерирует уникальный токен для таблицы users и cookies пользователя
function setAuthUser($id, $type, $email){

  //Стараемся сделать его из данных пользователя + немного рандома, чтобы менялся при каждом перезаходе
  $token = md5('auth_token'.md5(time().$id.$type.$email.rand(1,1000).rand(1,1000)));
  setcookie("auth_token", $token, time() + 3600 * 10);
  return $token;
}

//CSRF
if ($_POST){

  //Проверка CSRF
  if ($_POST['_csrf'] != $_csrf){

    $error[] = 'Ошибка CSRF';
  }
}

  if (($_POST && !$_POST['_csrf']) || !$_csrf){

    //Создадим CSRF для следующего запроса
    setcookie("_csrf", md5(time().$_SERVER['REMOTE_ADDR']), time() + 3600 * 10);
  }

?>
