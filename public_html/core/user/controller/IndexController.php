<?php

namespace core\user\controller;

use core\base\controller\BaseController;

class IndexController extends BaseController {

    protected $name;
    //входной метод для формирования данных и подготовки их к методу $outputData

    protected function inputData() {
        exit();
    }
    //метод для вывода данных и формирования видов-шаблонов
    /*protected function outputData() {
        //сохраняю в переменную нулевой(первый) аргумент
        $vars = func_get_arg(0);
        //в переменную $page записываю путь до шаблона и аргументы полученные на входе
        $this->page = $this->render(TEMPLATE.'templater', $vars);
    }*/

}