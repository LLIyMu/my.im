<?php


namespace core\admin\controller;


class ShowController extends BaseAdmin
{

    protected function inputData(){
        $this->exectBase();

        $this->createTableData();

    }

    protected function outputData(){

    }

}