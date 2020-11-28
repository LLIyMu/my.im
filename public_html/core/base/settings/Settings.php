<?php


namespace core\base\settings;


use core\base\controller\Singleton;

class Settings
{
    // использую трейт
    use Singleton;

    //маршруты, не изменяемые извне
    private $routes = [
        'admin' => [
            'alias' => 'admin',
            'path' => 'core/admin/controller/',//путь к файлам админ панели
            'hrUrl' => false, //human reliable URL человеко-понятный URL (ЧПУ)
            'routes' => [

                ]
        ],
        'settings' => [ //маршруты главных настроек
            'path' => 'core/base/settings/'
        ],
        'plugins' => [ //маршруты плагинов
            'path' => 'core/plugins/',
            'hrUrl' => false,
            'dir' => false
        ],
        'user' => [
            'path' => 'core/user/controller/', //маршруты файлов для пользователей
            'hrUrl' => true,
            'routes' => [

            ]
        ],
        'default' => [ //маршруты по умолчанию
            'controller' => 'IndexController',//контроллер по умолчанию
            'inputMethod' => 'inputData',//метод по умолчанию который вызывается у контроллера, если по маршруту не
                                         // определился метод вызова
            'outputMethod' => 'outputData'// метод вывода данных
        ],
    ];
    // свойство расширения приложения
    private $expansion = 'core/admin/expansion/';

    private $defaultTable = 'teachers';

    private $projectTables = [
        'teachers' => ['name' => 'Учителя', 'img' => 'pages.png'],
        'students' => ['name' => 'Ученики']
    ];

    private $templateArr = [
        'text' => ['name', 'phone', 'address'],
        'textarea' => ['content', 'keywords']
    ];


    // метод get() обращается к методу instance() который содержит в себе ссылку на объект класса
    static public function get($property){
        return self::instance()->$property;
    }
    // метод для склейки массивов на вход принимает класс
    public function clueProperties($class){
        $baseProperties = [];

        foreach ($this as $name => $item){
            $property = $class::get($name);
            // если в свойстве $property массив и в значении $item тоже массив склеиваем их
            if (is_array($property) && is_array($item)){

                $baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
                continue;
            }
            // если не $property (false, null, или что то другое) то в результирующий массив $baseProperties в его
            // ячейку $name записываем все определённые в этом классе свойства
            if (!$property) $baseProperties[$name] = $this->$name;
        }
        // возвращаем результирующий массив
        return $baseProperties;
    }

    // рекурсивный метод перебора массива, и слияния несколько массивов в один
    public function arrayMergeRecursive(){
        // сохраняю в переменную $arrays аргументы которые пришли в метод arrayMergeRecursive
        // конструкция func_get_args() получает аргументы пришедшие в метод
        $arrays = func_get_args();
        // определяем первый(основной массив) к которому будем приклеивать массивы
        // конструкция array_shift() возвращает первый элемент массива удаляя его из полученного на вход массива
        $base = array_shift($arrays);
        // проходимся по массивам и забираем оставшиеся елементы массива
        foreach ($arrays as $array){
            // проходимся теперь по массиву $array
            foreach ($array as $key => $value){
                //если массив $value и $key тоже массив
                if (is_array($value) && is_array($base[$key])){
                    // сохраняем в переменную полученные массивы
                    $base[$key] = $this->arrayMergeRecursive($base[$key], $value);
                } else{
                    // если это нумерованный массив а не ассоциативный
                    if (is_int($key)){
                        //  если не существует такой элемент в массиве, то записываем в массив $base значение и
                        // переходим на следующую итерацию цикла
                        if (!in_array($value, $base)) array_push($base, $value);
                        continue;
                    }
                    // если ключ не цифровой а строковый, перезаписываем значение
                    $base[$key] = $value;
                }
            }
        }
        // возвращаем массив
        return $base;
    }
}