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
        // если существует $data['ADMIN_MODE']
        if (isset($data['ADMIN_MODE'])){
            // разрегистрирую его
            unset($data['ADMIN_MODE']);
            // и в контроллер записываю роут до ajax контроллера
            $controller = $route['admin']['path'] . 'AjaxController';

        }
        $controller = str_replace('/', '\\', $controller);
        // создаю объект $controller
        $ajax = new $controller;
        // вызываю метод createAjaxData
        $ajax->createAjaxData($data);
        // возвращаю ajax
        return ($ajax->ajax());
    }

    protected function createAjaxData($data){

        $this->data = $data;

    }
}