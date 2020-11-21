<?php


namespace core\admin\controller;


use core\base\controller\BaseController;
use core\admin\model\Model;

class IndexController extends BaseController
{

    protected function inputData(){

        $db = Model::instance();
        $table = 'teachers';

        $files = [];

        $_POST['id'] = 3;
        $_POST['name'] = 'Osho';
        $_POST['gallery_img'] = "<p>Old Turist<p>";


        $res = $db->edit($table, [
            'files' => $files
        ]);
            exit('id =' . $res['id'] . ' Name = ' . $res['name']);
        }
    }