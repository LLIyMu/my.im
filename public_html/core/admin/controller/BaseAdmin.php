<?php


namespace core\admin\controller;


use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseAdmin extends BaseController
{
    // создаю свойство модели
    protected $model;

    // создаю свойство таблиц
    protected $table;
    //создаю свойство колонок
    protected $columns;
    // свойство данных
    protected $data;

    protected $adminPath;
    // создаю свойство для отображения меню
    protected $menu;
    // свойство тайтла
    protected $title;

    protected function inputData(){
        // инициализирую стили для админ панели
        $this->init(true);
        // задаю title веб страницы
        $this->title = 'VG engine';

        // если в свойстве $model ничего нет создаю модель
        if (!$this->model) $this->model = Model::instance();
        // если в свойстве $menu ничего нет создаю его
        if (!$this->menu) $this->menu = Settings::get('projectTables');

        if (!$this->adminPath) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';
        // вызываю заголовки (headers) для браузера что бы он не кешировал данные а подгружал их с сервера
        $this->sendNoCacheHeaders();
    }

    protected function outputData(){
        // записываю в свойство хедер
        $this->header = $this->render(ADMIN_TEMPLATE . 'include/header');
        // записываю в свойство footer
        $this->footer = $this->render(ADMIN_TEMPLATE . 'include/footer');
        // возвращаю шаблон
        return $this->render(ADMIN_TEMPLATE . 'layout/default');

    }
    // метод отправки заголовков браузеру, что бы не кешировать данные
    protected function sendNoCacheHeaders(){
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0,pre-check=0");
    }
    // метод для вызова метода inputData
    protected function execBase(){
        self::inputData();
    }
    // метод который определяет из какой таблицы подключать данные
    protected function createTableData(){
        // если не свойство $this->table
        if (!$this->table){
            // если есть свойство параметров, записываю в $this->table ключ с 0 порядковым номером
            if($this->parameters) $this->table = array_keys($this->parameters)[0];
                // иначе записываю таблицу по умолчанию
                else $this->table = Settings::get('defaultTable');
        }
        // записываю в свойство $columns колонки с помощью метода showColumns передавая таблицу из свойств
        $this->columns = $this->model->showColumns($this->table);
        // если не свойство $columns выбрасываю исключение
        if (!$this->columns) new RouteException('Не найдены поля в таблице - ' . $this->table, 2);

    }

    // метод раширения проекта - подключение плагинов, и других проектов
    protected function expansion($args = [], $settings = false){

        $filename = explode('_', $this->table);
        $className = '';

        foreach ($filename as $item) $className .= ucfirst($item);

        if (!$settings){
            $path = Settings::get('expansion');
        }elseif (is_object($settings)){
            $path = $settings::get('expansion');
        }else{
            $path = $settings;
        }
        $class = $path . $className . 'Expansion';
        // если файл читается (именно читается а не есть или существует), то подключаем его
        if (is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')){
            // в переменной $class меняем слеши
            $class = str_replace('/', '\\', $class);

            $exp = $class::instance();

            foreach ($this as $name => $value){
                $exp->$name = &$this->$name;
            }

            return $exp->expansion($args);
            
        }else{

            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

            extract($args);

            if (is_readable($file)) return include $file;

        }

        return false;
    }
}