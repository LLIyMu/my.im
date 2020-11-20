<?php


namespace core\base\model;


abstract class BaseModelMethods
{

    // метод создания выборки полей для запроса в БД
    protected function createFields($set, $table = false){
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
    protected function createOrder($set, $table = false){
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
                // если пришло число вместо операнда то сортирую по порядковому номеру поля
                if (is_int($order)) $order_by .= $order . ' ' . $order_direction . ',';
                else $order_by .= $table . $order . ' ' . $order_direction . ',';// присоединяю к переменной строку
                // запроса где $table это таблица, $order поле запроса
                // $order_direction направление запроса - DESC, ASC
            }
            // обрезаю получившуюся строку, убираю последнюю запятую
            $order_by = rtrim($order_by, ',');
        }
        //возвращяю значение
        return $order_by;

    }
    //метод создания з
    protected function createWhere($set, $table = false, $instruction = 'WHERE'){

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
                    if(is_string($item) && strrpos($item, 'SELECT') === 0){
                        $in_str = $item; // то записываю в переменную значение $item
                    }else{// иначе если пришел массив записываю массив
                        if(is_array($item)) $temp_item = $item;
                        // разбираю строку по запятым и записываю в $temp_item
                        else $temp_item = explode(',', $item);
                        //задаю пустую строку переменной, перед циклом
                        $in_str = '';

                        foreach ($temp_item as $v){
                            $in_str .= "'" . addslashes(trim($v)) . "',";
                        }
                    }
                    //присоеднияю получившуюся строку с пришедшими операндами
                    $where .= $table . $key . ' ' . $operand . ' (' .trim($in_str, ',') . ') ' . $condition;
                    //иначе если в операнде содержится LIKE и он строго не равен значению false
                }elseif (strpos($operand, 'LIKE') !== false){
                    // разбиваю строку по разделителю % если просто придет LIKE то он и запишется в переменную, если
                    // первым придет %LIKE то explode вернет 0 элементом % а первым LIKE
                    $like_template = explode( '%', $operand);

                    foreach ($like_template as $lt_key => $it){
                        if(!$it){// если в неё ничего не пришло
                            if(!$lt_key){ // и если это 0 элемент
                                $item = '%' . $item; // записываю в переменную $item %
                            }else{// иначе если пришел % 3 элементом массива то добавляю его к $item
                                $item .= '%';
                            }
                        }
                    }
                    // добавляю к строке оператор LIKE с полученными или не полученными %
                    $where .= $table . $key . ' LIKE ' . "'" . addslashes($item) . "' $condition";

                }else{// иначе если пришли обычные операторы <> =
                    // если в $item оператор SELECT стоит в первой позиции - эта проверка для вложенного запроса
                    if (strpos($item, 'SELECT') === 0){
                        // формирую вложенный запрос
                        $where .= $table . $key . $operand . '(' . $item . ") $condition";
                    }else{
                        $where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";
                    }

                }

            }
            // убираю последний $condition, substr обрезает подстроку в строке $where с 0 элемента на позиции strrpos
            //($where - где обрезаем, $condition - что обрезаем) т.е. последний элемент
            $where = substr($where, 0, strrpos($where, $condition));

        }
        // возвращаю строку
        return $where;
    }
    // метод обработки JOIN
    protected function createJoin($set, $table, $new_where = false){

        // для дальнейшей работы задаю пустую строку в переменные
        $fields = '';
        $join = '';
        $where = '';
        // если пришел массив ['join']
        if ($set['join']){
            // записываю в $join_table таблицу для соединения
            $join_table = $table;

            foreach ($set['join'] as $key => $item){
                // если в $key пришло число т.е. числовой массив
                if (is_int($key)){
                    if (!$item['table']) continue; // и если это не ключ table, перехожу на следущую итерацию цикла
                    else $key = $item['table']; // иначе записываю в $key таблицу
                }
                // если в $join что то есть то присоединяем к ней пробел
                if ($join) $join .= ' ';
                // если $item содержит 'on'
                if ($item['on']){
                    $join_fields = [];// создаю пустой массив

                    switch (2) { // если в массиве есть 2 элемента вида ['on']['fields']
                        // если в поле ['on']['fields'] есть 2 элемента массива
                        case count($item['on']['fields']):
                            $join_fields = $item['on']['fields']; // записываю эти поля
                            break;
                        // если поля для присоединения описаны в ячейке 'on'
                        case count($item['on']): // посчитаем их, если их 2 то записываем их в $join_fields
                            $join_fields = $item['on'];
                            break;
                        default:
                            // по дефолту конструкция continue выведет цикл на foreach
                            continue 2;
                            break;
                    }
                    // если не содержится типа присоединения, то по умолчанию записываю в $join - LEFT JOIN
                    if (!$item['type']) $join .= 'LEFT JOIN ';
                    // иначе в $item['type'] записываю 'JOIN'
                    else $join .= trim(strtoupper($item['type'])) . ' JOIN ';
                    // к $join добавляем $key и к какой таблице её присоединяем ON
                    $join .= $key . ' ON ';
                    // если существует $item['on']['table'] то присоединяем её к $join
                    if ($item['on']['table']) $join .= $item['on']['table'];
                    // если её не существует то добавляем таблицу которая пришла в метод в качестве аргумента
                    else $join .= $join_table;
                    // в $join добавляем поля которые пришли
                    $join .= '.' . $join_fields[0] . '=' . $key . '.' . $join_fields[1];
                    // присваиваю в $join_table текущую таблицу $key что бы следующая итерация цикла могла работать с
                    // предыдущей
                    $join_table = $key;
                    // если в $new_where что то есть т.е. это новая инструкция where
                    if ($new_where){
                        // если в $item['where'] что то содержится то в $new_where записываем false
                        if ($item['where']){
                            $new_where = false;
                        }

                        $group_condition = 'WHERE';
                    }else{
                        // если есть $item['group_condition'] то записываю его и перевожу в верхний регистр, а если
                        // нет то просто записываю значение по умолчанию 'AND'
                        $group_condition = $item['group_condition'] ? strtoupper($item['group_condition']) : 'AND';
                    }

                    $fields .= $this->createFields($item, $key);
                    $where .= $this->createWhere($item, $key, $group_condition);
                }
            }

        }
        // возвращаю результат работы сохранённый в переменных с помощью compact()
        return compact('fields', 'join', 'where');

    }

    protected function createInsert($fields, $files, $except){
        // задаю массив
        $insert_arr = [];

        if ($fields){
            // массив функций для Mysql запроса
            $sql_func = ['NOW()'];

            foreach ($fields as $row => $value){
                // есть $except и массив $row - ряд и $except то переходим на следующую итерацию цикла
                if ($except && in_array($row, $except)) continue;
                // добавляю в $insert_arr и его ячейку ['fields'] поле $row
                $insert_arr['fields'] .= $row . ',';
                // если в массиве $sql_func есть значение $value
                if (in_array($value, $sql_func)){
                    // добавляю в $insert_arr['values'] то что есть в $value
                    $insert_arr['values'] .= $value . ',';
                }else{
                    // в противном случае обрабатываю $value функцией addslashes() добавляя одинарные кавычки
                    $insert_arr['values'] .= "'" . addslashes($value) . "',";
                }

            }
            // если есть $files
            if ($files){

                foreach ($files as $row => $file){

                    // конкатенирую к массиву полей имя файла
                    $insert_arr['fields'] .= $row . ',';
                    // если $file это массив в $insert_arr записываю строку в Json формате
                    if (is_array($file)) $insert_arr['values'] .= "'" . addslashes(json_encode($file)) . "',";
                    // иначе добавляю простую строку
                    else $insert_arr['values'] .= "'" . addslashes($file) . "',";
                }

            }

        }

        foreach ($insert_arr as $key => $arr) $insert_arr[$key] = rtrim($arr, ',');



        return $insert_arr;

    }


}