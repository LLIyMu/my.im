<?php
//константа безопасности, нужна что бы не давать заходить в другие файлы проекта кроме index.php
define('VG_ACCESS', true);
//отправляю заголовки браузеру пользователя
header('Content-Type:text/html;charset=utf-8');
//стартую сессию
session_start();


require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';
require_once 'libraries/functions.php';

use core\base\controller\BaseRoute;
use core\base\exceptions\DbException;
use core\base\exceptions\RouteException;

//ловлю исключения
try{//попытаться выполнить код RouteController
    BaseRoute::routeDirection();
}
catch (RouteException $e) { // ловлю исключение, завершаю работу скрипта при этом вызывая сообщение об ошибке

    exit($e->getMessage());
}
catch (DbException $e) { // тоже самое но для баз данных
    exit($e->getMessage());
}

