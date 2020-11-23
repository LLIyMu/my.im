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
        if(!$this->model) $this->model = Model::instance();
        // если в свойстве $menu ничего нет создаю его
        if(!$this->menu) $this->menu = Settings::get('projectTables');
        // вызываю заголовки (headers) для браузера что бы он не кешировал данные а подгружал их с сервера
        $this->sendNoCacheHeaders();
    }

    protected function outputData(){

    }
    // метод отправки заголовков браузеру, что бы не кешировать данные
    protected function sendNoCacheHeaders(){
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0,pre-check=0");
    }
    // метод для вызова метода inputData
    protected function exectBase(){
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
}