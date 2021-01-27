<?php


namespace core\base\controller;


use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseController
{
    // подключаю трейт что бы использовать во всех классах наследующих базовый класс
    use \core\base\controller\BaseMethods;

    protected $header;
    protected $content;
    protected $footer;

    //переменная для отображения страниц-видов-шаблонов
    protected $page;
    //для хранения ошибок
    protected $errors;
    // свойство контроллер
    protected $controller;
    // свойство сбора данных из БД
    protected $inputMethod;
    // свойство в котором будет храниться имя метода который будет подключать вид
    protected $outputMethod;
    // свойство параметров
    protected $parameters;
    // свойство шаблона
    protected $template;

    protected $styles;
    protected $scripts;

    protected $userId;
    // свойство данных
    protected $data;

    protected $ajaxData;
    // метод обрабатывающий входящие парматеры и передающий их методу request()
    public function route(){
        //сохраняю в переменную $controller имя класса с правильным слешем получаю из адресной строки
        $controller = str_replace('/', '\\', $this->controller);

        try{
            //в $object сохраняю объект класса ReflectionMethod который ищет в классе $controller метод 'request',
            //первым параметром принимает имя класса в строковом виде, вторым имя метода
            $object = new \ReflectionMethod($controller, 'request');
            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ];
            //вызываю метод invoke() у $object для того что бы запустить метод 'request' в классе $controller, где первым
            // параметром идёт объект класса ($controller) а вторым массив $args
            $object->invoke(new $controller, $args);
        }
        catch (\ReflectionException $e){
            throw new RouteException($e->getMessage());
        }

    }
    //метод получения параметров и данных для отображения страниц и работы с БД принимает массив аргументов
    public function request($args) {
        //принимаю параметры в котором хранятся параметры из адресной строки
        $this->parameters = $args['parameters'];
        //в $inputData хранятся водящие запросы которые используются для вычеслений, обращения к БД и т.д.
        $inputData = $args['inputMethod'];
        //в $outputData содержится вся информация для сбора готовой страницы шаблона или вида
        $outputData = $args['outputMethod'];
        //сохраняю в переменнную метод который выполнится первым! при вызове метода request().
        //в нём содержится вводная информация
        $data = $this->$inputData();
        //если в метод $outputData() пришли данные сохраняю в $page все данные для отображения видов
        if (method_exists($this, $outputData)) {
            $page = $this->$outputData($data);
            if ($page) $this->page = $page;
        }elseif($data){//иначе  формирую данные без метода $outputData и сохраняю их в свойство $page
            $this->page = $data;
        }
        //если в свойство ошибок что то попало то вызываю метод записи логирования ошибок
        if ($this->errors) {
            $this->writeLog($this->errors);
        }
        //завершаю работу скрипта вызовом метода getPage() он выводит готовую страницу
        $this->getPage();
    }
    //метод формирования пути к нужному виду - шаблонизатор, принимает путь и массив параметров
    protected function render($path = '', $parameters = []) {
        //встроенная функция PHP импортирует переменные из массива в текущую таблицу символов
        extract($parameters);
        //если в путь ничего не пришло, формирую дефолтный путь до индексного файла
        if (!$path) {
            // сохраняю в переменную $class объект класса ReflectionClass ($this) он предоставляет доступ к классу
            // объектом которого он является
            $class = new \ReflectionClass($this);
            // получаю в переменную пространство имён объектом которого является $this при этом заменяю слеши на обычные
            $space = str_replace('\\', '/', $class->getNamespaceName() . '\\');
            // получаю роуты из класса настроек для правильных маршрутов
            $routes = Settings::get('routes');
            // если $space строго равно маршруту user подключаю шаблон по умолчанию
            if ($space === $routes['user']['path']) $template = TEMPLATE;
            // иначе подключаю админку
            else   $template = ADMIN_TEMPLATE;
            // сохраняю путь разбирая строку убирая controller из полученной строки и переводя строку в нижний регистр с
            // помощью strtoLower()
            $path = $template . explode('controller', strtolower($class->getShortName()))[0];
        }
        //стартую буфер обмена
        ob_start();
        //если неподключен путь до файла выкидываю своё исключение с названием отстутствующего шаблона
        if (!@include_once $path . '.php') throw new RouteException('Отсутствует шаблон - ' . $path);
        //возвращаю результат работы скрипта из буфера обмена и очищаю его
        return ob_get_clean();
    }
    //метод вывода страниц-видов-шаблонов
    protected function getPage() {
        //если в $page пришел массив прохожусь по нему форычем и вывожу нужные данные
        if (is_array($this->page)) {
            foreach ($this->page as $block) echo $block;
            //иначе вывожу данные как есть
        }else{
            echo $this->page;
        }
        //завершаю работу скрипта
        exit();
    }

    // инициализирую подключение стилей и скриптов
    protected function init($admin = false){
        // если это не админка подключаю USER стили и скрипты
        if (!$admin){
            if (USER_CSS_JS['styles']){
                foreach (USER_CSS_JS['styles'] as $item) $this->styles[] = PATH . TEMPLATE . trim($item, '/');
            }
            if (USER_CSS_JS['scripts']){
                foreach (USER_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . TEMPLATE . trim($item, '/');
            }
        }else{ //иначе подключаю ADMIN стили и скрипты
            if (ADMIN_CSS_JS['styles']){
                foreach (ADMIN_CSS_JS['styles'] as $item) $this->styles[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
            if (ADMIN_CSS_JS['scripts']){
                foreach (ADMIN_CSS_JS['scripts'] as $item) $this->scripts[] = PATH . ADMIN_TEMPLATE . trim($item, '/');
            }
        }

    }
}