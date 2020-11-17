<?php


namespace core\base\model;


use core\base\controller\Singleton;
use core\base\exceptions\DbException;

class BaseModel
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
     */
    // неизменяемый метод из вне по выборке данных из БД (SELECT)
    final public function get($table, $set = []){

        $fields = $this->createFields($table, $set);

        $order = $this->rder($table, $set);

        $where = $this->createWhere($table, $set);

        $join_arr = $this->createJoin($table, $set);

        $fields .= $join_arr['fields'];
        $join = $join_arr['join'];
        $where .= $join_arr['where'];
        // обрезаю последнюю запятую в полях
        $fields = rtrim($fields, ',');


        // если в $limit что то пришло то записываю, а иначе передаю пустую строку
        $limit = $set['limit'] ? $set['limit'] : '';

        $query = "SELECT $fields FROM $table $join $where $order $limit";

        return $this->query($query);

    }
    // метод создания выборки полей для запроса в БД
    protected function createFields($table = false, $set){
        // если в $set пришел массив и он не пуст, то записываю, если нет записываю символ выбрать всё ['*']
        $set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : ['*'];
        // если в $table что то пришло то записываю и конкатенирую точку,
        $table = $table ? $table . '.' : '';

        $fields = '';

        foreach ($set['fields'] as $field){
            $fields .= $table . $field . ',';
        }

        return $fields;
    }
    //метод создания строки сортировки для запроса
    protected function createOrder($table = false, $set){
        // если в $table что то пришло то записываю и конкатенирую точку,
        $table = $table ? $table . '.': '';
        // записываю в переменную пустую строку
        $order_by = '';
        // если $set масив и он не пустой
        if (is_array($set['order']) && !empty($set['order'])){
            // если есть order_direction и он является массиво и он не пустой я его записываю, иначе записываю ['ASC']
            $set['order_direction'] = (is_array($set['order_direction'])
                && !empty($set['order_direction']))
                    ? $set['order_direction'] : ['ASC'];
            // по умолчанию записываю в переменную 'ORDER BY '
            $order_by = 'ORDER BY ';
            // ставлю счётчик 0
            $direct_count = 0;

            foreach ($set['order'] as $order){
                // если существует $set['order_direction'] и его ячейка [$direct_count]
                if($set['order_direction'][$direct_count]){
                    // записываю в переменную в верхнем регистре значение
                    $order_direction = strtoupper($set['order_direction'][$direct_count]);
                    // и увеличиваю
                    $direct_count++;
                }else{ // если ничего не пришло записываю ['ASC']
                    $order_direction = strtoupper($set['order_direction'][$direct_count - 1]);
                }

                $order_by .= $table . $order . ' ' . $order_direction . ',';
            }

            $order_by = rtrim($order_by, ',');
        }

        return $order_by;

    }

    protected function createWhere($table = false, $set, $instruction = 'WHERE'){

        // если в $table что то пришло то записываю и конкатенирую точку,
        $table = $table ? $table . '.': '';
        // записываю в переменную пустую строку
        $where = '';

        if (is_array($set['where']) && !empty($set['where'])){
            // если $set['operand'] массив и он не пуст то записываем тот опернд что пришел, иначе записываем знак
            // равенства
            $set['operand'] = (is_array($set['operand']) && !empty($set['operand'])) ? $set['operand'] : ['='];
            $set['condition'] = (is_array($set['condition']) && !empty($set['condition'])) ? $set['condition'] : ['AND'];
            // присваиваю в $where то что пришло в переменной $instruction по умолчанию 'WHERE'
            $where = $instruction;

            $o_count = 0;//operand_count
            $c_count = 0;//condition_count

            foreach ($set['where'] as $key => $item) {
                // добавляю пробел на каждой итерации цикла
                $where .= ' ';
                // если в $set что то есть то в $operand присваиваю что пришло и увеличиваю на 1
                if ($set['operand'][$o_count]){
                    $operand = $set['operand'][$o_count];
                    $o_count++;
                }else{// иначе ставлю предыдущее значение
                    $operand = $set['operand'][$o_count -1];
                }

                if ($set['condition'][$c_count]){
                    $condition = $set['condition'][$c_count];
                    $c_count++;
                }else{
                    $condition = $set['condition'][$c_count -1];
                }
                // если в $operand содержится 'IN' или 'NOT IN'
                if ($operand === 'IN' || $operand === 'NOT IN'){
                    // и если $item является строкой и первая позиция $item это SELECT
                    if(is_string($item) && strrpos($item, 'SELECT')){
                        $in_str = $item; // то записываю в переменную значение $item
                    }else{// иначе если пришел массив записываю массив
                        if(is_array($item)) $temp_item = $item;
                            else $temp_item = explode(',', $item);// разбираю строку по запятым и записываю в $temp_item

                        $in_str = '';

                        foreach ($temp_item as $v){
                            $in_str .= "'" . trim($v) . "',";
                        }
                    }
                    $where .= $table . $key . ' ' . $operand . ' (' .trim($in_str, ',') . ') ' . $condition;

                }elseif (strpos($operand, 'LIKE') !== false){

                    $like_template = explode( '%', $operand);

                    foreach ($like_template as $lt_key => $it){
                        if(!$it){
                            if(!$lt_key){
                                $item = '%' . $item;
                            }else{
                                $item .= '%';
                            }
                        }
                    }

                    $where .= $table . $key . ' LIKE ' . "'" . $item . "' $condition";

                }else{

                    if (strpos($item, 'SELECT') === 0){
                        $where .= $table . $key . $operand . '(' . $item . ") $condition";
                    }else{
                        $where .= $table . $key . $operand . "'" . $item . "' $condition";
                    }

                }

            }

            $where = substr($where, 0, strrpos($where, $condition));

        }

        return $where;
    }

}