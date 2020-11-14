<?php
/**
Класс обработки исключений
 */

namespace core\base\exceptions;


use core\base\controller\BaseMethods;

class DbException extends \Exception
{
    // защищённое свойство
    protected $messages;
    //импортирую трейт
    use BaseMethods;

    public function __construct($message = "", $code = 0)
    {
        // вызываю родительский класс конструктор что бы дочерний его не переопределил, передаю в него сообщение и
        // код ошибки
        parent::__construct($message, $code);
        // подключаю файл с сообщениями для пользователей
        $this->messages = include 'messages.php';

        $error = $this->getMessage() ? $this->getMessage() : $this->messages[$this->getCode()];

        $error .= "\r\n" . 'file ' . $this->getFile() . "\r\n" . 'In line ' . $this->getLine() . "\r\n";

        //if ($this->messages[$this->getCode()]) $this->message = $this->messages[$this->getCode()];
        // записываю сообщение об ошибке в лог файл
        $this->writeLog($error, 'db_log.txt');
    }
}