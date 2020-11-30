<?php


namespace core\admin\controller;


use core\base\settings\Settings;

class AddController extends BaseAdmin
{

    protected function inputData()
    {
        if (!$this->userId) $this->execBase();

        $this->createTableData();

        $this->createForeignData();

        $this->createRadio();

        $this->createOutputData();
    }
    // метод формирования внешних ключей для метода createForeignData() принимает $arr массивб и
    protected function createForeignProperty($arr, $rootItems){
        // если в массиве $rootItems['tables'] есть таблица $this->table
        if (in_array($this->table, $rootItems['tables'])){
            // записываю в foreignData с ячейкой ['id'] имя колонки текущей таблицы которая ссылается на
            // родительскую
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 0;
            // записываю в foreignData с ячейкой ['name'] то что содержится в $rootItems['name']
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
        }
        // получаю имя колонок родительской таблицы
        $columns = $this->model->showColumns($arr['REFERENCED_TABLE_NAME']);
        // записываю пустую строку
        $name = '';
        // если в $columns есть что то
        if ($columns['name']){
            // записываю в $name строку 'name'
            $name = 'name';
        }else{
            foreach ($columns as $key => $value){
                // если в $key 'name' не равна false
                if (strpos($key, 'name') !== false){
                    // записываю в $name псевдоним
                    $name = $key . ' as name';
                }
            }
            // если в $name ничего нет, записываю псевдоним текущей таблицы $columns['id_row'] как ' as name'
            if (!$name) $name = $columns['id_row'] . ' as name';

        }
        // если в данные уже заполнены
        if ($this->data){
            // если таблица ссылается сама на себя т.е. REFERENCED_TABLE_NAME === $this->table текущей таблице
            if ($arr['REFERENCED_TABLE_NAME'] === $this->table){
                // в свойство  $where[$this->columns['id_row'] записываю $this->data[$this->columns['id_row']
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                // и в $operand ставлю символ '<>'(не равно)
                $operand[] = '<>';
            }
        }
        // формирую в переменную все данные которые отонсятся к таблице которую получили из model, так же записываю
        // все поля 'fields', 'where' если пришло, 'operand' если пришел
        $foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'],[
            'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $name],
            'where' => $where,
            'operand' => $operand
        ]);
        // если свойства заполнились
        if ($foreign){
            // если в foreignData и его ячейке COLUMN_NAME что то есть прохожу форейч по его значению
            if ($this->foreignData[$arr['COLUMN_NAME']]){
                foreach ($foreign as $value) {
                    // записываю значение в foreignData и его ячейку COLUMN_NAME
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            }else{
                // иначе записываю то что пришло в $foreign
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;
            }

        }

    }

    // метод создания данных для внешних ключей
    protected function createForeignData($settings =false){
        // если в $settings ничего не пришло, записываю в $settings ссылку на класс Settings
        if (!$settings) $settings = Settings::instance();
        // получаю в переменную свойство из Settings с помощью метода get()
        $rootItems = $settings::get('rootItems');
        // в переменную $keys получаю внешние(Foreign) ключи с помощью метода showForeignKeys передавая текущую
        // таблицу($this->table)
        $keys = $this->model->showForeignKeys($this->table);
        // если в $keys что то пришло
        if ($keys){

            foreach ($keys as $item){
                $this->createForeignProperty($item, $rootItems);
            }
        // иначе ели в свойстве $this->columns ['parent_id'] есть что то т.е. если таблица ссылается сама на себя
        }elseif ($this->columns['parent_id']){
            // записываю строку 'parent_id' в имя колонки
            $arr['COLUMN_NAME'] = 'parent_id';
            // записываю ссылку на имя колонки, т.е. то что находится в $this->columns
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            // записываю текущую таблицу
            $arr['REFERENCED_TABLE_NAME'] = $this->table;
            // вызываю метод передавая ему сформированные аргументы
            $this->createForeignProperty($arr, $rootItems);
        }

         return;

    }

}