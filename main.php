<?php

//Это единая точка входа в приложение
define('enter', true);

//Устанавливаем доступные пути GET запросов, которые мы будем обрабатывать
//Все они соответствуют названиям страниц в /pages
$route = array(
  'login', 'register', 'all_orders', 'select_order', 'show_more',
  'logout', 'new_order', 'order_action', 'up_balance', 'ref_balance'
);

//Разбираем запрос из REQUEST_URI, выявляем к какой страницы идет обращение
$query     = explode('&', explode('?', urldecode($_SERVER['REQUEST_URI']))[1]);
$page      = explode('=', $query[0])[1]; //Страница
$pageParam = explode('=', $query[1])[1]; //Параметр запроса
$pgSm        = explode('=', $query[1])[1]; //Параметр для "показать еще"

//Проверяем существование запрашиваемого пути в наших доступных путях (см. выше: Array $route)
//Если такого пути нет - мы покажем 404 ошибку и прекратим выполнение скрипта
in_array($page, $route) ? : die(@include __DIR__ . "/pages/404.php");

//Подключаем файл настроек MYSQL & REDIS, и настрока процента% комиссии
require __DIR__ . "/config.php";

//Функция соединения, проверки состояния и выполнения запросов MYSQL
require __DIR__ . "/common/mysql.php";

//Функция соединения, проверки состояния и выполнения запросов Redis
require __DIR__ . "/common/redis.php";

//Всё, что связано с правами пользователя и его профилем
require __DIR__ . "/common/user.php";

//Подключаем нужный файл.
//Разрешенный в списке Array $route
@include __DIR__ . "/pages/$page.php";

?>
