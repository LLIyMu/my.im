<?php

namespace core\base\model;


use core\base\exceptions\DbException;

abstract class BaseModel extends BaseModelMethods
{

    // объявляю свойство $db в нём будет хранится объект подключения mysqli
    protected $db;
    // метод подключения к базе данных
    protected function connect()
    {
        // подключаюсь к БД
        $this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);
        // если в свойстве connect_error что то есть то выводим сообещние об ошибке
        if ($this->db->connect_error){

            throw new DbException('Ошибка подключения к базе данных: '
            . $this->db->connect_errno . ' ' . $this->db->connect_error);

        }
        // если нет ошибок то просто устанавливаем кодировку подключения
        $this->db->query("SET NAMES UTF8");
    }
    //финальный метод(не изменяемый в дочерних классах) обработки запросов к БД принимает сам запрос $query, метод
    // запроса $crud по умолчанию read, для методов вставки $return_id
    /**
     * @param $query
     * @param string $crud = r - SELECT / c - INSERT / u - UPDATE / d - DELETE
     * @param bool $return_id
     * @return array|bool|mixed
     * @throws DbException
     */
    final public function query($query, $crud = 'r', $return_id = false){
        //сохраняю запрос в переменную т.к. в db хранится объект подключения mysqli
        $result = $this->db->query($query);
        //если в свойстве $this->db и в его свойстве affected_rows (эффективные ряды затронутые выборкой) есть ошибка,
        //а это именно значение -1
        if ($this->db->affected_rows === -1){
            throw new DbException('Ошибка в SQL запросе: '
            . $query . ' - ' . $this->db->errno . ' ' . $this->db->error
            );
        }
        //если условие выше не отработало и нет ошибок то будет работать оператор множественного выбора switch case
        switch ($crud){
            //если в case пришло READ -> 'r'
            case 'r':
                // если в $result что то есть
                if ($result->num_rows){
                    // создаю массив для цикла
                    $res = [];
                    //прохожусь по переменной $result и записываю всё что есть в виде ассоциативного массива в $res
                    for($i = 0; $i < $result->num_rows; $i++){
                        // заполняю массив $res методом fetch_assoc
                        $res[] = $result->fetch_assoc();
                    }
                    // возвращаю массив
                    return $res;

                }
                // возвращаем false что бы не ушла пустота если условие не отработало
                return false;

                break;
            // case который отвечает за CREATE -> 'c'
            case 'c':
                // если в $return_id что то есть возвращаю true
                if ($return_id) return $this->db->insert_id;

                return true;

                break;
            // во всех остальных случаях по дефолту возвращаю true
            default:

                return true;
                break;
        }

    }
    /**
     * @param $table - таблицы базы данных
     * @param array $set
     * 'fields' => ['id', 'name']
     * 'no_concat' => false/true если true то не присоединять имя таблицы к полям и where
     * 'where' => ['fio' => 'bakieva', 'name' => 'Natalya', 'surname' => 'Bakieva']
     * 'operand' => ['=', '<>']
     * 'condition' => ['AND', 'OR']
     * 'order' => ['fio', 'name']
     * 'order_direction' =? ['ASC', 'DESC']
     * 'limit' => '1'
         * 'join' [
            'table' => 'join_table',
            'fields' => ['id as j_id', 'name as j_name'],
            'type' => 'left',
            'where' => ['name' => 'alex'],
            'operand' => ['='],
            'condition' => ['OR'],
            'on' => ['id', 'parent_id'],
             'group_condition' => 'AND'
            ],
        'join_table2' => [
            'table' => 'join_table2',
            'fields' => ['id as j2_id', 'name as j2_name'],
            'type' => 'left',
            'where' => ['name' => 'alex'],
            'operand' => ['='],
            'condition' => ['AND'],
            'on' => [
            'table' => 'teachers',
            'fields' => ['id', 'parent_id']
            ]
        ],
     */
    // неизменяемый метод из вне по выборке данных из БД (SELECT)
    final public function get($table, $set = []){
        // записываю в свойство поля с помощью метода createFields в который передаю $set массив параметров, и
        // таблицу $table
        $fields = $this->createFields($set, $table);
        // записываю метод сортировки в свойство $order с помощью метода createOrder в который передаю $set массив
        // параметров, и таблицу $table
        $order = $this->createOrder($set, $table);
        // записываю метод сортировки в свойство $order с помощью метода createOrder в который передаю $set массив
        // параметров, и таблицу $table
        $where = $this->createWhere($set, $table);

        if (!$where) $new_where = true;
            else $new_where = false;
        $join_arr = $this->createJoin($set, $table, $new_where);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];
        // обрезаю последнюю запятую в полях
        $fields = rtrim($fields, ',');


        // если в $limit что то пришло то записываю, а иначе передаю пустую строку
        $limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);

    }

    /**
     * @param $table - таблица для вставки данных
     * @param array $set - массив параметров:
     * fields => [поле => значение]; если не указан, то обрабатывается $_POST[поле => значение]
     * разрешена передача например NOW() в качестве Mysql функции обычной строкой
     * files => [поле => значение]; можно подать массив вида [поле =>[массив значений]]
     * except => ['исключение 1', 'исключение 2'] - исключает данные элементы массива из добавления в запрос
     * return_id => true|false - возвращать или нет идентификатор вставленной записи
     * @return mixed
     */
    final public  function add($table, $set = []){
        // если $set['fields'] массив и он не пуст то его и запишем, а иначе запишем false
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        // аналогично только с массивом 'files'
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
       // если не $set['fields'] и не $set['files'] то завершаем работу срипта
        if(!$set['fields'] && !$set['files']) return false;
        // если есть значение в $set['return_id'] то записываю true а иначе записываю false
        $set['return_id'] = $set['return_id'] ? true : false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        $insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

        $query = "INSERT INTO $table {$insert_arr['fields']} VALUES {$insert_arr['values']}";
        return $this->query($query, 'c', $set['return_id']);

    }

    final public function edit($table, $set = []){

        // если $set['fields'] массив и он не пуст то его и запишем, а иначе запишем false
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        // аналогично только с массивом 'files'
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
        // если не $set['fields'] и не $set['files'] то завершаем работу срипта
        if(!$set['fields'] && !$set['files']) return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;
        // если не пришел массив all_rows - он нужен для вставки во все поля (используется редко)
        if (!$set['all_rows']){
            // если есть массив $set['where'] то сохраняю в переменную результат работы метода createWhere в котором есть все проверки для обработки массива $set
            if ($set['where']){
                $where = $this->createWhere($set);
            }else{
                // иначе записываю в переменную результат работы метода showColumns с
                $columns = $this->showColumns($table);
                // если ничего не пришло из метода, прекращаю работу скрипта
                if (!$columns) return false;
                // если массив $columns содержит ячейку с['id_row'] и в массиве $set['fields'] тоже есть такая ячейка[$columns['id_row']]
                // то записываю в переменную $where их содержимое и конкатенирую всё для правильного запроса к БД
                if ($columns['id_row'] && $set['fields'][$columns['id_row']]){
                    $where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];
                    // разрегистрирую поле т.к. в нём содержится автоинкремент,СуБД сама обработает данные
                    unset($set['fields'][$columns['id_row']]);
                }
            }

        }
        // сохраняю в переменную результат работы метода createUpdate который на вох принимает поля['fields'], файлы['files'] и исключения['except'] из изменения
        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);
        // создаю запрос к БД
        $query = "UPDATE $table SET $update $where";
        // возвращаю результат работы метода query
        return $this->query($query, 'u');
    }


    /**
     * @param $table - таблицы базы данных
     * @param array $set
     * 'fields' => ['id', 'name']
     * 'where' => ['fio' => 'bakieva', 'name' => 'Natalya', 'surname' => 'Bakieva']
     * 'operand' => ['=', '<>']
     * 'condition' => ['AND', 'OR']
         * 'join' [
            'table' => 'join_table',
            'fields' => ['id as j_id', 'name as j_name'],
            'type' => 'left',
            'where' => ['name' => 'alex'],
            'operand' => ['='],
            'condition' => ['OR'],
            'on' => ['id', 'parent_id'],
            'group_condition' => 'AND'
            ],
     * 'join_table2' => [
            'table' => 'join_table2',
            'fields' => ['id as j2_id', 'name as j2_name'],
            'type' => 'left',
            'where' => ['name' => 'alex'],
            'operand' => ['='],
            'condition' => ['AND'],
            'on' => [
            'table' => 'teachers',
            'fields' => ['id', 'parent_id']
            ]
    ],
     */
    // метод удаления полей из БД или связей полей таблиц по ключам
    public function delete($table, $set){
        // записываю полученую талицу
        $table = trim($table);
        // создаю строку запроса WHERE
        $where = $this->createWhere($set, $table);
        // записываю колноки для удаления или UPDATE
        $columns = $this->showColumns($table);
        // если колонки не пришли заканчиваю работу скрипта
        if(!$columns) return false;
        // если $set['fields'] это массив и он не пуст
        if (is_array($set['fields']) && !empty($set['fields'])){
            // если есть $columns['id_row']
            if ($columns['id_row']){
                // записываю в $key ключ $columns['id_row'] из массива $set['fields']
                $key = array_search($columns['id_row'], $set['fields']);
                // если $key строго не равен false разрегистрирую его что бы не удалить или не модифицировать первичный
                // ключ
                if ($key !== false) unset($set['fields'][$key]);
            }
            // задаю пустой массив
            $fields =[];

            foreach($set['fields'] as $field){
                $fields[$field] = $columns[$field]['Default'];
            }
            // записываю поля для обновления
            $update = $this->createUpdate($fields, false, false);
            // создаю строку запроса
            $query = "UPDATE $table SET $update $where";
        }else{
            // получаю join
            $join_arr = $this->createJoin($set, $table);
            // записываю в $join то что пришло в массив $join_arr и его ячейку ['join']
            $join = $join_arr['join'];
            // записываю таблицы которые нужно объеденить
            $join_tables = $join_arr['tables'];
            // создаю строку запроса
            $query = 'DELETE ' . $table . $join_tables . ' FROM ' . $table . ' ' . $join . ' ' . $where;
        }
        // возвращаю запрос
        return $this->query($query, 'u');

    }
    // служебный метод показа колонок
    final public function showColumns($table){
        $query = "SHOW COLUMNS FROM $table";

        $res = $this->query($query);

        $columns = [];

        if ($res){

            foreach ($res as $row){
                $columns[$row['Field']] = $row;
                if ($row['Key'] === 'PRI') $columns['id_row'] = $row['Field'];
            }

        }
        // возвращаю полученные колонки
        return $columns;
    }
    // метод для показа всех таблиц из БД
    final public function showTables(){
        // записываю запрос к БД в переменную $query
        $query = 'SHOW TABLES';
        // получаю список таблиц в $tables
        $tables = $this->query($query);
        // создаю пустой массив
        $table_arr = [];
        // если в $tables что то пришло
        if ($tables){
            // прохожу по $tables циклом форейч
            foreach($tables as $table){
                // записываю в $table_arr первый элемент массива $table, который возвращает функция reset()
                $table_arr[] = reset($table);

            }

        }
        // возвращаю массив
        return $table_arr;
    }

}