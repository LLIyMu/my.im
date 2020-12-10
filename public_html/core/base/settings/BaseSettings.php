<?php


namespace core\base\settings;

use core\base\controller\Singleton;
use core\base\settings\Settings;

trait BaseSettings
{

    use Singleton{
        instance as SingletonInstance;
    }

    private $baseSettings;

    static public function get($property){
        return self::instance()->$property;
    }

    static public function instance(){
        if (self::$_instance instanceof self){
            return self::$_instance;
        }
        // сохраняем в свойство baseSettings ссылку на объект класса Settings
        self::SingletonInstance()->baseSettings = Settings::instance();
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
}