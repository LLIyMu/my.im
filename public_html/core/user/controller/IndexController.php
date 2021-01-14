<?php

namespace core\user\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;

class IndexController extends BaseController {

    protected $name;
    //входной метод для формирования данных и подготовки их к методу $outputData

    protected function inputData() {

        $model = Model::instance();

        $res = $model->get('goods', [
            'where' => ['id' => '5,6'],
            'operand' => ['IN'],
            'join' => [
                'goods_filters' => [
                    'fields' => null,
                    'on' => ['id', 'teachers']
                ],
                'filters f' => [
                    'fields' => ['name as student_name', 'content'],
                    'on' => ['students', 'id']
                    ],
                    [
                        'table' => 'filters',
                        'on' => ['parent_id', 'id']
                    ]
            ],
            //'join_structure' => true,
            'order' => ['id'],
            'order_direction' => ['DESC']
        ]);

        exit;

    }
    //метод для вывода данных и формирования видов-шаблонов
    /*protected function outputData() {
        //сохраняю в переменную нулевой(первый) аргумент
        $vars = func_get_arg(0);
        //в переменную $page записываю путь до шаблона и аргументы полученные на входе
        $this->page = $this->render(TEMPLATE.'templater', $vars);
    }*/

}