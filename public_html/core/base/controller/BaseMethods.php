<?php


namespace core\base\controller;


trait BaseMethods
{

    // метод очистки строковых данных
    protected function clearStr($str){
        // если пришел массив, разбираю его форычем на ключ значение, обрезаю с помощью trim все пробелы, а с помощью
        // strip_tags все теги которые содержатся в значении $item
        if (is_array($str)){
            foreach($str as $key => $item) $str[$key] = trim(strip_tags($item));
            return $str;
        }else{ // если строка тоже всё обрезаю, но без форыча
            return trim(strip_tags($str));
        }

    }
    // метод очистки числовых данных
    protected function clearNum($num){
        // если в $num приходит число или число с плавающей точкой, умножаются на 1 и возвращают число, если число
        // пришло в строчном виде то при умножении на 1 оно приводится к числовому значению и возвращается число,
        //если пришла строка то умножение на 1 даст 0 и вернется false
        return $num * 1;
    }
    // метод проверяющий что данные пришли $_POSTом
    protected function isPost(){
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }
    // метод проверяющий данные на Ajax запрос любое несовпадение с проверкой вернет false
    protected function isAjax(){
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
    // метод редиректа
    protected function redirect($http = false, $code = false){
        // если в $code что то пришло то в ячейку массива $codes 301 записываю заголовок для браузера
        if ($code){
            $codes = ['301' => 'HTTP/1.1 301 Move Permanently'];
            // если в $codes записалось то отправляю заголовки
            if ($codes[$code]) header($codes[$code]);
        }
        if ($http) $redirect = $http;
        else $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;

        header("Location: $redirect");

        exit;
    }
    // метод записи логов принимает сообщение, филе (по умолчанию log.txt), событие(ошибка) $event
    protected function writeLog($message, $file = 'log.txt', $event = 'Fault'){
        // создаю метку времени и сохраняю её в переменную $dateTime
        $dateTime = new \DateTime();
        // в переменную $str записываю событие в понятном для человека формате
        $str = $event . ': ' . $dateTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";
        // с помошью функции записываю в файл новое событие, добавляя каждый раз новое, не стирая старое с помощью
        // флага FILE_APPEND
        file_put_contents('log/' . $file, $str, FILE_APPEND);
    }
}