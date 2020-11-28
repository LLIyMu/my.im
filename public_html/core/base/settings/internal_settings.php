<?php

use core\base\exceptions\RouteException;
//проверяет константу, если пользователь пытается напрямую подключится к этому файлу, то выводится сообщение 'Access
// denied'
defined('VG_ACCESS') or die('Access denied');

const TEMPLATE = 'templates/default/'; //шаблоны видов страниц пользовательской части сайта
const ADMIN_TEMPLATE = 'core/admin/view/';//шаблоны видов страниц административной части сайта
const UPLOAD_DIR = 'userfiles/';//директория для добавления изображений из административной панели

const COOKIE_VERSION = '1.0.0';//версия куки сайта
const CRYPT_KEY = '';//ключ шифрования
const COOKIE_TIME = 60;//время бездействия админа в админ панели
const BLOCK_TIME = 3;//время блокировки пользователя который пытается подобрать пароль к сайту, применяется после 2х
                     // неудачныйх попыток
const QTY = 8;//константа для постраничной навигации 8 товаров в данном случае
const QTY_LINKS = 3;//3 ссылки навигации

const ADMIN_CSS_JS = [ //пути к файлам JS, CSS админ панели
    'styles' => ['css/main.css'],
    'scripts' => []
];
const USER_CSS_JS = [ //пути к файлам JS, CSS пользовательской части сайта
    'styles' => [],
    'scripts' => []
];
//функция автозагрузки классов
function autoloadMainClasses($class_name) {
    //конструкция str_replace ищет обратный слеш \ и меняет его на обычный / слеш
    $class_name = str_replace('\\', '/', $class_name);

    //если не подключен класс то выбрасываем исключение,
    //символ @ у инклюд убирает все notice and warning
    if (!@include_once $class_name . '.php') {
        throw  new RouteException('Не верное имя файла для подключения - ' .$class_name);
    }
}
//регистрирует функцию в качестве __autoload
spl_autoload_register('autoloadMainClasses');