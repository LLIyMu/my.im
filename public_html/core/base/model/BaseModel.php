<?php

namespace core\base\model;


use core\base\controller\Singleton;
use core\base\exceptions\DbException;

class BaseModel extends BaseModelMethods
{
    // подключаю трейт
    use Singleton;
    // объявляю свойство $db в нём будет хранится объект подключения mysqli
    protected $db;
    //
    private function __construct()
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

        $fields = $this->createFields($set, $table);

        $order = $this->createOrder($set, $table);

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

        if ($insert_arr){
            $query = "INSERT INTO $table ({$insert_arr['fields']}) VALUES ({$insert_arr['values']})";
            return $this->query($query, 'c', $set['return_id']);
        }

        return false;
    }

    final public function edit($table, $set = []){

        // если $set['fields'] массив и он не пуст то его и запишем, а иначе запишем false
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
        // аналогично только с массивом 'files'
        $set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;
        // если не $set['fields'] и не $set['files'] то завершаем работу срипта
        if(!$set['fields'] && !$set['files']) return false;

        $set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

        if (!$set['all_rows']){

            if ($set['where']){
                $where = $this->createWhere($set);
            }else{
                $columns = $this->showColumns($table);

                if (!$columns) return false;

                if ($columns['id_row'] && $set['fields'][$columns['id_row']]){
                    $where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];
                    unset($set['fields'][$columns['id_row']]);
                }
            }

        }

        $update = $this->createUpdate($set['fields'], $set['files'], $set['except']);

        $query = "UPDATE $table SET $update $where";

        return $this->query($query, 'u');
    }

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
        return $columns;
    }
}