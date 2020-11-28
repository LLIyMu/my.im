<?php


namespace core\base\controller;


trait Singleton
{
    // приватное статическое свойство в котором хранится ссылка на объект класса Settings
    static private $_instance;

    // магический метод __construct выполняется каждый раз при создании объекта класса
    private function __construct()
    {
    }
    // магический метод __clone клонирует объект класса
    private function __clone()
    {
        // TODO: Implement __clone() method.
    }
    // статичный метод класса вызывается без создания объекта класса
    static public function instance(){
        // если в свойстве $_instance хранится объект класса, то возвращаем объект класса
        if (self::$_instance instanceof self){
            return self::$_instance;
        }
        // создаю объект класса Singleton
        self::$_instance = new self;
        // если есть метод connect() в объекте класса то вызываю его
        if (method_exists(self::$_instance, 'connect')){
            self::$_instance->connect();
        }

        // если нет объекта, то создаем его и возвращаем свойсвто с объектом
        return self::$_instance;
    }
}