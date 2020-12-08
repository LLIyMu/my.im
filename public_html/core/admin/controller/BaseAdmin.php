<?php


namespace core\admin\controller;


use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;

abstract class BaseAdmin extends BaseController
{
    // создаю свойство модели
    protected $model;

    // создаю свойство таблиц
    protected $table;
    //создаю свойство колонок
    protected $columns;
    // свойство данных
    protected $data;
    // свойство данных о внешних ключах
    protected $foreignData;

    protected $adminPath;
    // создаю свойство для отображения меню
    protected $menu;
    // свойство тайтла
    protected $title;
    // свойство служебных сообщений
    protected $messages;

    protected $translate;
    protected $blocks = [];
    // массив шаблонов
    protected $templateArr;
    // шаблоны форм
    protected $formTemplates;
    protected $noDelete;

    protected function inputData(){
        // инициализирую стили для админ панели
        $this->init(true);
        // задаю title веб страницы
        $this->title = 'VG engine';

        // если в свойстве $model ничего нет создаю модель
        if (!$this->model) $this->model = Model::instance();
        // если в свойстве $menu ничего нет создаю его
        if (!$this->menu) $this->menu = Settings::get('projectTables');

        if (!$this->adminPath) $this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';

        if (!$this->templateArr) $this->templateArr = Settings::get('templateArr');
        if (!$this->formTemplates) $this->formTemplates = Settings::get('formTemplates');
        if (!$this->messages) $this->messages = $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . 'informationMessages.php';
        // вызываю заголовки (headers) для браузера что бы он не кешировал данные а подгружал их с сервера
        $this->sendNoCacheHeaders();
    }

    protected function outputData(){
        if (!$this->content){
            // в $args записываю 0 элемент массива который возвращается функцией func_get_arg
            $args = func_get_arg(0);
            // если есть $args то записываю его в $vars иначе записываю пустой массив []
            $vars = $args ? $args : [];
            // если не свойство template записываю в него константу ADMIN_TEMPLATE и конкатенирую к ней строку 'show'
            //if (!$this->template) $this->template = ADMIN_TEMPLATE . 'show';
            // в свойство content записываю тот шаблон что вернул метод render
            $this->content = $this->render($this->template, $vars);
        }
        // записываю в свойство хедер
        $this->header = $this->render(ADMIN_TEMPLATE . 'include/header');
        // записываю в свойство footer
        $this->footer = $this->render(ADMIN_TEMPLATE . 'include/footer');
        // возвращаю шаблон
        return $this->render(ADMIN_TEMPLATE . 'layout/default');

    }
    // метод отправки заголовков браузеру, что бы не кешировать данные
    protected function sendNoCacheHeaders(){
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0,pre-check=0");
    }
    // метод для вызова метода inputData
    protected function execBase(){
        self::inputData();
    }
    // метод который определяет из какой таблицы подключать данные
    protected function createTableData($settings = false){
        // если не свойство $this->table
        if (!$this->table){
            // если есть свойство параметров, записываю в $this->table ключ с 0 порядковым номером
            if($this->parameters) $this->table = array_keys($this->parameters)[0];
                else{// иначе записываю таблицу по умолчанию
                    if (!$settings) $settings = Settings::instance();
                    $this->table = $settings::get('defaultTable');
                }
        }
        // записываю в свойство $columns колонки с помощью метода showColumns передавая таблицу из свойств
        $this->columns = $this->model->showColumns($this->table);
        // если не свойство $columns выбрасываю исключение
        if (!$this->columns) new RouteException('Не найдены поля в таблице - ' . $this->table, 2);

    }

    // метод раширения проекта - подключение плагинов, и других проектов
    protected function expansion($args = [], $settings = false){

        $filename = explode('_', $this->table);
        $className = '';

        foreach ($filename as $item) $className .= ucfirst($item);

        if (!$settings){
            $path = Settings::get('expansion');
        }elseif (is_object($settings)){
            $path = $settings::get('expansion');
        }else{
            $path = $settings;
        }
        $class = $path . $className . 'Expansion';
        // если файл читается (именно читается а не есть или существует), то подключаем его
        if (is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')){
            // в переменной $class меняем слеши
            $class = str_replace('/', '\\', $class);

            $exp = $class::instance();

            foreach ($this as $name => $value){
                $exp->$name = &$this->$name;
            }

            return $exp->expansion($args);
            
        }else{

            $file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

            extract($args);

            if (is_readable($file)) return include $file;

        }

        return false;
    }
    // метод создания выходных данных
    protected function createOutputData($settings = false){
        // если в $settings ничего не пришло то сохраняю в него настройки класса Settings
        if (!$settings) $settings = Settings::instance();
        // в $blocks записываю то что определено в классе Settings в свойстве blockNeedle
        $blocks = $settings::get('blockNeedle');
        // в translate записываю то что определено в классе Settings в свойстве translate
        $this->translate = $settings::get('translate');
        // если в $blocks ничего не пришло ИЛИ $blocks не является массивом
        if (!$blocks || !is_array($blocks)){
            // прохожусь циклом по колонкам таблицы из БД
            foreach($this->columns as $name => $item){
                //если в ключе содержится строка id_row пропускаю итерацию
                if ($name === 'id_row') continue;
                // если в свойство translate ничего не пришло то записываю в его 0 элемент значение поля $name
                if (!$this->translate[$name]) $this->translate[$name][] = $name;
                // в 0 элемент свойства blocks записываю все полученные $name
                $this->blocks[0][] = $name;
            }

            return;
        }
        // определяю блок по дефолту, это будет тот блок который записан в свойстве первым т.е 0 елемент
        $default = array_keys($blocks)[0];

        foreach($this->columns as $name => $item) {
            //если в ключе содержится строка id_row пропускаю итерацию
            if ($name === 'id_row') continue;

            $insert = false;

            foreach ($blocks as $block => $value){

                if (!array_key_exists($block, $this->blocks)) $this->blocks[$block] = [];

                if (in_array($name, $value)){
                    $this->blocks[$block][] = $name;
                    $insert = true;
                    break;
                }
            }

            if (!$insert) $this->blocks[$default][] = $name;

            if (!$this->translate[$name]) $this->translate[$name][] = $name;


        }

        return;

    }
    // метод получения свойств для радио кнопки
    protected function createRadio($settings = false){
        // если ничего не пришло в аргумент $settings записываю в $settings ссылку на класс Settings
        if (!$settings) $settings = Settings::instance();
        // получаю в $radio свойства 'radio'
        $radio = $settings::get('radio');
        // если есть $radio
        if ($radio){
            // прохожу циклом по текущим колонкам таблицы
            foreach ($this->columns as $name => $item){
                // если в $radio есть ячейка [$name]
                if ($radio[$name]){
                    // записываю в foreignData[$name] массив $radio[$name]
                    $this->foreignData[$name] = $radio[$name];
                }
            }
        }

    }
    // метод проверки данных которые пришли постом ($_POST)
    protected function checkPost($settings = false){
        // если данные пришли POST-ом
        if ($this->isPost()){
            // вызываю метод очистки данных полей формы, передавая $settings
            $this->clearPostFields($settings);
            // в свойство table записываю ту таблицу которая содержиться в $_POST['table'], при этом обрезая пробелы
            // с помощью метода clearStr
            $this->table = $this->clearStr($_POST['table']);
            // разрегестрирую $_POST['table']
            unset($_POST['table']);
            // если в свойстве table что то содержится
            if ($this->table){
                // вызываю метод createTableData который формирует данные для подключения из текущей таблицы
                $this->createTableData($settings);
                // вызываю метод изменения данных таблицы
                $this->editData();
            }

        }

    }

    protected function addSessionData($arr = []){

        if (!$arr) $arr = $_POST;

        foreach($arr as $key => $item){
            $_SESSION['res'][$key] = $item;
        }

        $this->redirect();
    }

    protected function emptyFields($item, $answer, $arr = []){

        if (empty($item)){
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' . $answer . '</div>';
            $this->addSessionData($arr);
        }

    }

    // метод очистки данных пришедших из форм методом $_POST
    protected function clearPostFields($settings, &$arr = []){
        // если не $arr то записываю в $arr ссылку на $_POST
        if (!$arr) $arr = &$_POST;
        // если не $settings то записываю в $settings объект класса Settings
        if (!$settings) $settings = Settings::instance();
        // в $id записываю - если есть $_POST и в его ячейке [$this->columns['id_row']] есть запись, то его и
        // записываю, а иначе записываю false
        $id = $_POST[$this->columns['id_row']] ?: false;
        // в переменную $validate получаю настройки из Settings которые записаны в свойстве (validation)
        $validate = $settings::get('validation');
        // если не translate, то получаю его из класса $settings
        if (!$this->translate) $this->translate = $settings::get('translate');
        // прохожу по $arr как ключ - значение
        foreach ($arr as $key => $item){
            // если $item это массив
            if (is_array($item)){
                // то делаю рекурсивный вызов метода передавая $settings и то что есть в $item
                $this->clearPostFields($settings,$item);
            }else{
                // еслт $item это число
                if (is_numeric($item)){
                    // записываю в $arr и его ячейку [$key] приведенное к нормальнму типу чило $this->clearNum($item)
                    $arr[$key] = $this->clearNum($item);
                }
                // если в свойство $validate что то пришло
                if ($validate){
                    // если в свойстве $validate содержится ключ [$key]
                    if ($validate[$key]){
                        // если в свойстве translate есть значение ключа [$key]
                        if ($this->translate[$key]){
                            // записываю в ответ $this->translate[$key][0]
                            $answer = $this->translate[$key][0];
                        }else{
                            // иначе просто записываю что содержится в $key
                            $answer = $key;
                        }
                        // если в $validate[$key] есть ['crypt']
                        if ($validate[$key]['crypt']){
                            // и если получен $id
                            if ($id){
                                // если значение $item пустое
                                if (empty($item)){
                                    // разрегистрирую $arr[$key]
                                    unset($arr[$key]);
                                    // перехожу на следущую итерацию цикла
                                    continue;
                                }
                                // шифрую данные
                                $arr[$key] = md5($item);

                            }
                        }
                        // если есть $validate[$key]['empty'] вызываю метод emptyFields и передаю ему на вход ($item, $answer, $arr)
                        if ($validate[$key]['empty']) $this->emptyFields($item, $answer, $arr);
                        // если есть $validate[$key]['trim'] записываю в $arr[$key] обрезанное с помощью trim($item)
                        if ($validate[$key]['trim']) $arr[$key] = trim($item);
                        // если есть $validate[$key]['int'] записываю в $arr[$key] приведенное к нормальному
                        // типу число clearNum($item)
                        if ($validate[$key]['int']) $arr[$key] = $this->clearNum($item);
                        // $validate[$key]['count'] вызываю метод подсчета символов countChar, передавая ($item,
                        // $validate[$key]['count'], $answer, $arr)
                        if ($validate[$key]['count']) $this->countChar($item, $validate[$key]['count'], $answer, $arr);

                    }
                }
            }
        }
        return true;
    }

    protected function editData(){

    }

}