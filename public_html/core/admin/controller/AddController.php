<?php


namespace core\admin\controller;


use core\base\settings\Settings;

class AddController extends BaseAdmin
{
    // свойство для определения пути к шаблону
    protected $action = 'add';

    protected function inputData()
    {
        // вызываю родительский метод, который инициализирует и запускает все нужные методы
        if (!$this->userId) $this->execBase();

        $this->checkPost();

        $this->createTableData();

        $this->createForeignData();

        $this->createMenuPosition();

        $this->createRadio();

        $this->createOutputData();

        $this->createManyToMany();

        return $this->expansion();

    }


}