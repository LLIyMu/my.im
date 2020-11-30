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
    // свойство данных о внешних ключах
    protected $foreignData;

    protected $adminPath;
    // создаю свойство для отображения меню
    protected $menu;
    // свойство тайтла
    protected $title;

    protected $translate;
    protected $blocks = [];

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
        if (!$this->content){
            // в $args записываю 0 элемент массива который возвращается функцией func_get_arg
            $args = func_get_arg(0);
            // если есть $args то записываю его в $vars иначе записываю пустой массив []
            $vars = $args ? $args : [];
            // если не свойство template записываю в него константу ADMIN_TEMPLATE и конкатенирую к ней строку 'show'
            //if (!$this->template) $this->template = ADMIN_TEMPLATE . 'show';
            // в свойство content записываю тот шаблон что вернул метод render
            $this->content = $this->render($this->template, $vars);
        }
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
    protected function createTableData($settings = false){
        // если не свойство $this->table
        if (!$this->table){
            // если есть свойство параметров, записываю в $this->table ключ с 0 порядковым номером
            if($this->parameters) $this->table = array_keys($this->parameters)[0];
                else{// иначе записываю таблицу по умолчанию
                    if (!$settings) $settings = Settings::instance();
                    $this->table = $settings::get('defaultTable');
                }
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
    // метод создания выходных данных
    protected function createOutputData($settings = false){
        // если в $settings ничего не пришло то сохраняю в него настройки класса Settings
        if (!$settings) $settings = Settings::instance();
        // в $blocks записываю то что определено в классе Settings в свойстве blockNeedle
        $blocks = $settings::get('blockNeedle');
        // в translate записываю то что определено в классе Settings в свойстве translate
        $this->translate = $settings::get('translate');
        // если в $blocks ничего не пришло ИЛИ $blocks не является массивом
        if (!$blocks || !is_array($blocks)){
            // прохожусь циклом по колонкам таблицы из БД
            foreach($this->columns as $name => $item){
                //если в ключе содержится строка id_row пропускаю итерацию
                if ($name === 'id_row') continue;
                // если в свойство translate ничего не пришло то записываю в его 0 элемент значение поля $name
                if (!$this->translate[$name]) $this->translate[$name][] = $name;
                // в 0 элемент свойства blocks записываю все полученные $name
                $this->blocks[0][] = $name;
            }

            return;
        }
        // определяю блок по дефолту, это будет тот блок который записан в свойстве первым т.е 0 елемент
        $default = array_keys($blocks)[0];

        foreach($this->columns as $name => $item) {
            //если в ключе содержится строка id_row пропускаю итерацию
            if ($name === 'id_row') continue;

            $insert = false;

            foreach ($blocks as $block => $value){

                if (!array_key_exists($block, $this->blocks)) $this->blocks[$block] = [];

                if (in_array($name, $value)){
                    $this->blocks[$block][] = $name;
                    $insert = true;
                    break;
                }
            }

            if (!$insert) $this->blocks[$default][] = $name;

            if (!$this->translate[$name]) $this->translate[$name][] = $name;


        }

        return;

    }
    // метод получения свойств для радио кнопки
    protected function createRadio($settings = false){
        // если ничего не пришло в аргумент $settings записываю в $settings ссылку на класс Settings
        if (!$settings) $settings = Settings::instance();
        // получаю в $radio свойства 'radio'
        $radio = $settings::get('radio');
        // если есть $radio
        if ($radio){
            // прохожу циклом по текущим колонкам таблицы
            foreach ($this->columns as $name => $item){
                // если в $radio есть ячейка [$name]
                if ($radio[$name]){
                    // записываю в foreignData[$name] массив $radio[$name]
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }

    }

}