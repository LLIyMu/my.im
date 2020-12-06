<?php


namespace core\admin\controller;

use core\base\settings\Settings;

class ShowController extends BaseAdmin
{

    protected function inputData(){

        if (!$this->userId) $this->execBase();

        $this->createTableData();

        $this->createData();

        return $this->expansion(get_defined_vars());

    }

    // метод создания данных для отображения и сортировки меню
    protected function createData($arr = []){
        // свойство полей из БД
        $fields = [];
        // сортировка
        $order = [];
        // направление сортировки
        $order_direction = [];
        // если нет колонки 'id_row' возвращаю то что пришло в свойство $this->data
        if (!$this->columns['id_row']) return $this->data;
        // присваиваю в $fields псевдоним 'as id'
        $fields[] = $this->columns['id_row'] . ' as id';

        if ($this->columns['name']) $fields['name'] = 'name';
        if ($this->columns['img']) $fields['img'] = 'img';

        if (count($fields < 3)){
            foreach ($this->columns as $key => $item){
                // если не поле $fields['img'] и его позиция в массиве не равна false
                if (!$fields['name'] && strpos($key, 'name') !== false){
                    // записываю псевдоним
                    $fields['name'] = $key . ' as name';
                }
                // если не поле $fields['img'] и его позиция в массиве не равна 0 т.е. он не стоит первым элементом
                if (!$fields['img'] && strpos($key, 'img') === 0){
                    // записываем в поле псевдоним
                    $fields['img'] = $key . ' as img';
                }
            }
        }

        // если пришел массив $arr и его ячейка fields
        if ($arr['fields']){
            // если $arr является массивом
            if (is_array($arr['fields'])){
                // сохраняю в переменную склеенный массив с помощью метода arrayMergeRecursive подавая в качестве
                // параметров поля -$fields и массив параметров $arr
                $fields = Settings::instance()->arrayMergeRecursive($fields, $arr['fields']);
            }else{
                $fields[] = $arr['fields'];
            }

        }
        // если в свойстве $columns есть parent_id
        if ($this->columns['parent_id']){
            // если в массиве $fields нет parent_id то записываем его в массив $fields и в массив $order
            if (!in_array('parent_id', $fields)) $fields[] = 'parent_id'; $order[] = 'parent_id';
        }
        // если в свойстве $columns есть ячейка menu_position записываю его в $order
        if ($this->columns['menu_position']) $order[] = 'menu_position';
        elseif ($this->columns['date']){

            if ($order) $order_direction = ['ASC', 'DESC'];
            else $order_direction[] = 'DESC';

            $order[] = 'date';
        }

        // если пришел массив $arr и его ячейка order
        if ($arr['order']){
            // если $arr является массивом
            if (is_array($arr['order'])){
                // сохраняю в переменную склеенный массив с помощью метода arrayMergeRecursive подавая в качестве
                // параметров сортировка - $order и массив параметров $arr['order']
                $order[] = Settings::instance()->arrayMergeRecursive($order, $arr['order']);
            }else{
                $order[] = $arr['order'];
            }

        }
        // если пришел массив $arr и его ячейка order_direction
        if ($arr['order_direction']){
            // если $arr является массивом
            if (is_array($arr['order_direction'])){
                // сохраняю в переменную склеенный массив с помощью метода arrayMergeRecursive подавая в качестве
                // параметров направление сортировки order_direction и массив $arr['order_direction'
                $order_direction[] = Settings::instance()->arrayMergeRecursive($order_direction, $arr['order_direction']);
            }else{
                $order_direction[] = $arr['order_direction'];
            }

        }


        $this->data = $this->model->get($this->table, [
            'fields' => $fields,
            'order' => $order,
            'order_direction' => $order_direction
        ]);

    }

}