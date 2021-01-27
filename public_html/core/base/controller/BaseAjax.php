<?php


namespace core\base\controller;


use core\base\settings\Settings;

class BaseAjax extends BaseController
{

    public function route()
    {
        // сохраняю роуты в переменную
        $route = Settings::get('routes');
        // записываю путь к контроллеру в $controller
        $controller = $route['user']['path'] . 'AjaxController';
        // если есть $this->isPost() то записываю $_POST иначе записываю $_GET в $data
        $data = $this->isPost() ? $_POST : $_GET;

        $httpReferer = str_replace('/', '\/', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . PATH .
            $route['admin']['alias']);
        // если существует $data['ADMIN_MODE']
        if (isset($data['ADMIN_MODE']) || preg_match('/^'. $httpReferer . '(\/?|$)/', $_SERVER['HTTP_REFERER'])){
            // разрегистрирую его
            unset($data['ADMIN_MODE']);
            // и в контроллер записываю роут до ajax контроллера
            $controller = $route['admin']['path'] . 'AjaxController';

        }
        $controller = str_replace('/', '\\', $controller);
        // создаю объект $controller
        $ajax = new $controller;
        // вызываю метод createAjaxData
        $ajax->ajaxData = $data;
        // возвращаю ajax
        $res = $ajax->ajax();

        if (is_array($res) || is_object($res)) $res = json_encode($res);
        elseif (is_int($res)) $res = (float)$res;

        return $res;
    }

}