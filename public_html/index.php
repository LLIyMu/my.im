<?php


//константа безопасности, нужна что бы не давать заходить в другие файлы проекта кроме index.php
define('VG_ACCESS', true);
//отправляю заголовки браузеру пользователя
header('Content-Type:text/html;charset=utf-8');
//стартую сессию
session_start();

require_once 'config.php';
require_once 'core/base/settings/internal_settings.php';

use core\base\exceptions\RouteException;
use core\base\controller\RouteController;
//ловлю исключения
try{//попытаться выполнить код RouteController
    RouteController::getInstance()->route();
}
catch (RouteException $e) { // ловлю trow и вывожу исключение и завершаю работу скрипта
    exit($e->getMessage());
}

