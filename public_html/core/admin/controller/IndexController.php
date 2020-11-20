<?php


namespace core\admin\controller;


use core\base\controller\BaseController;
use core\admin\model\Model;

class IndexController extends BaseController
{

    protected function inputData(){

        $db = Model::instance();
        $table = 'teachers';

        $files['gallery_img'] = ["red-bull''.jpg", 'blue-water.jpg', 'black-death.jpg'];
        $files['img'] = 'main_img2.jpg';

        $_POST['name'] = '4e4en';

        $res = $db->showColumns($table);
        exit('id =' . $res['id'] . ' Name = ' . $res['name']);
    }
}