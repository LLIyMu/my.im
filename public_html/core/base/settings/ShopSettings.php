<?php


namespace core\base\settings;

use core\base\settings\Settings;

class ShopSettings
{

    static private $_instance;
    private $baseSettings;

    private $routes = [
        'plugins' => [ //маршруты плагинов

            'dir' => false,
            'routes' => [

            ]
        ],
    ];

    private $templateArr = [
        'text' => ['price', 'abort', 'name'],
        'textarea' => ['goods_content', 'name']
    ];


    static public function get($property){
        return self::instance()->$property;
    }

    static public function instance(){
        if (self::$_instance instanceof self){
            return self::$_instance;
        }
        // сохраняю в свойство $_instance сслыку на объект класса ShopSettings
        self::$_instance = new self;
        // сохраняем в свойство baseSettings ссылку на объект класса Settings
        self::$_instance->baseSettings = Settings::instance();
        // в переменную $baseProperties сохраняю работу метода для склейки массивов
        $baseProperties = self::$_instance->baseSettings->clueProperties(get_class());
        self::$_instance->setProperty($baseProperties);

        return self::$_instance;
    }

    // метод записи свойств в массив принимает массив свойств созданных в классе
    protected function setProperty($properties){
        //если пришло свойство проходимся циклом
        if ($properties){
            foreach ($properties as $name => $property) {
                // записываем в объект класса название полученное свойство
                $this->$name = $property;
            }
        }
    }

    private function __construct()
    {
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }
}