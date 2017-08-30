<?php
defined('enter') or die();

//Всякое разное с Redis
//По умолчанию выбрано подключение db1 из config.php
function redisAction($data, $key, $action, $db = 'db1'){

  global $_RD;

  //Подключение к Redis
  $redis = new Redis();
  $redis->connect($_RD[$db]['host'], $_RD[$db]['port']);
  $redis->auth($_RD[$db]['password']);
  $redis->select($_RD[$db]['database']);

  try {
      $redis->ping();
      } catch (Exception $e) {
      // Что-то пошло не так с нашей редиской
  }

  if(isset($e)) {
      //Дальше можно не идти, придется обойтись без redis
      return false;
  } else {
      //С Redis всё ок, можно продолжать

      //Запись чего-либо в Redis
      if ($action == 'set'){
        return $redis->set($key,  serialize($data));
      }

      //Получение чего-либо из Redis
      if ($action == 'get'){
        return unserialize($redis->get($key));
      }

      //Удалить что-либо из Redis
      if ($action == 'del'){
        return $redis->del($key);
      }

  }

  //Закрываем соединение с redis
  $redis ->close();

}

?>
