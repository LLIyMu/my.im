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

    protected $fileArray;

    protected $alias;
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
        if (!$this->messages) $this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') .
        'informationMessages.php';
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
    // метод записи данных в сессию
    protected function addSessionData($arr = []){
        // если не $arr то записываю в $arr массив $_POST
        if (!$arr) $arr = $_POST;

        foreach($arr as $key => $item){
            // записываю в $_SESSION['res'][$key] значение $item
            $_SESSION['res'][$key] = $item;
        }
        // делаю редирект
        $this->redirect();
    }

    protected function countChar($str, $counter, $answer, $arr){
        // если количество символов в строке больше чем установлено в свойстве $validation
        if (mb_strlen($str) > $counter){
            // формирую сообщение
            $str_res = mb_str_replace('$1', $answer, $this->messages['count']);
            $str_res = mb_str_replace('$2', $counter, $str_res);
            // записываю сообщение в сессию
            $_SESSION['res']['answer'] = '<div class="error">' . $str_res . '</div>';
            // передаю массив данных в сессию
            $this->addSessionData($arr);
        }

    }

    // метод добавления сообщения если поля для заполнения пустые
    protected function emptyFields($str, $answer, $arr = []){
        // если строка $str пустая
        if (empty($str)){
            // записываю в сессию сообщение из ячейки $this->messages['empty'] и добавляем $answer
            $_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' . $answer . '</div>';
            // вызываю метод который записывает данные в $_POST из переданного массива $arr, нужен для того что бы
            // данные которые ввели в поля не затерлись
            $this->addSessionData($arr);
        }

    }

    // метод очистки данных пришедших из форм методом $_POST до того как их добавить в БД
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
                // еслт $item состоит из чисел
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
                                // хэширую данные
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

    protected function editData($returnId = false) {

        $id = false;
        $method = 'add';
        // проверяю пришел ли вместе с POST первичний ключ
        if ($_POST[$this->columns['id_row']]){
            // записываю в $id значение по условию - если columns['id_row'] это число то записываю приведенное к
            // нормальному числу значение clearNum($_POST[$this->columns['id_row']] иначе записываю $this->clearStr($_POST[$this->columns['id_row']]
            $id = is_numeric($_POST[$this->columns['id_row']]) ?
                $this->clearNum($_POST[$this->columns['id_row']]) :
                $this->clearStr($_POST[$this->columns['id_row']]);
            // если в $id что то пришло
            if ($id){
                // записываю в оператор $where [$this->columns['id_row'] => $id]
                $where = [$this->columns['id_row'] => $id];
                // а в метод записываю $method = 'edit'
                $method = 'edit';
            }
        }
        // прохожу форычем по текущим колонкам таблицы $this->columns
        foreach ($this->columns as $key => $item){
            // если $key равен строке 'id_row' перехожу на следующую итерацию
            if ($key === 'id_row') continue;
            // если поле 'Type' равно 'date' или 'datetime'
            if ($item['Type'] === 'date' || $item['Type'] === 'datetime'){
                // и если в $_POST[$key] ничего нет то записываю в него строку 'NOW()', это сокращённый синтаксис
                // конструкции if else
                !$_POST[$key] && $_POST[$key] = 'NOW()';
            }
        }
        // вызываю метод создания файла
        $this->createFile();
        // вызываю метод создания ЧПУ
        $this->createAlias($id);

        $this->updateMenuPosition();
        // в $except записываю результат метода который исключает поля для добавления в БД
        $except = $this->checkExceptFields();
        // сохраняю в переменную метод 'add' или 'edit'
        $res_id = $this->model->$method($this->table, [
            'files' => $this->fileArray,
            'where' => $where,
            'return_id' => true,
            'except' => $except
        ]);
        // если не $id и $method === 'add' т.е. если добавляли данные
        if (!$id && $method === 'add'){
            // записываю в $_POST[$this->columns['id_row']] то что есть в $res_id
            $_POST[$this->columns['id_row']] = $res_id;
            // формирую сообщение о успешном добавлении
            $answerSuccess = $this->messages['addSuccess'];
            // формирую сообщение о ошибке в добавлении данных
            $answerFail = $this->messages['addFail'];
        }else{
            // формирую сообщение о успешном изменении данных
            $answerSuccess = $this->messages['editSuccess'];
            // формирую сообщение о ошибке в изменении данных
            $answerFail = $this->messages['editFail'];
        }
        // запускаю метод расширения функционала системы, передаю все объявленные переменные в этом методе
        $this->expansion(get_defined_vars());
        // записываю в переменную метод проверки алиаса передавая ему идентификатор
        $result = $this->checkAlias($_POST[$this->columns['id_row']]);
        // если в $res_id что то есть
        if ($res_id){
            // записываю сообщение об успехе заполнения БД данными из формы
            $_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>';
            // если не нужно возвращать $returnId то делаю редирект
            if (!$returnId) $this->redirect();
            // возвращаю $_POST[$this->columns['id_row']]
            return $_POST[$this->columns['id_row']];
        }else{
            // записываю сообщение об ошибке заполнения БД данными из формы
            $_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>';
            if (!$returnId) $this->redirect();
        }

    }

    protected function checkExceptFields($arr = []){
        // если не $arr, записываю в $arr то что пришло из $_POST
        if (!$arr) $arr = $_POST;
        // создаю пустую переменную - массив
        $except = [];
        // если $arr
        if ($arr){
            // то про хоже по $arr в форыче
            foreach ($arr as $key => $item){
                // если не $this->columns[$key] записываю в $except ключ $key
                if (!$this->columns[$key]) $except[] = $key;
            }
        }
        // возвращаю поля исключения
        return $except;
    }

    protected function createFile(){

    }

    protected function updateMenuPosition(){

    }
    // метод формирования алиаса
    protected function createAlias($id = false){
        // если есть $this->columns['alias']
        if ($this->columns['alias']){
            // в $_POST нет поля ['alias']
            if (!$_POST['alias']){
                // если есть $_POST['name']
                if ($_POST['name']){
                    // записываю в $alias_str очищенную строку $_POST['name']
                    $alias_str = $this->clearStr($_POST['name']);
                }else{
                    // прохожу по $_POST форычем
                    foreach($_POST as $key => $item){
                        // если позиция подстроки 'name' есть в $key и она не равна false и есть $item
                        if (strpos($key, 'name') !== false && $item){
                            // записываю в $alias_str алиас
                            $alias_str = $this->clearStr($item);
                            break;// заканчиваю цикл
                        }
                    }
                }

            }else{
                // записываю алиас сначала обрабатываю строку $this->clearStr($_POST['alias']) и записываю её в
                // $_POST['alias'] затем уже записываю в $alias_str
                $alias_str = $_POST['alias'] = $this->clearStr($_POST['alias']);

            }

            // подключаю библиотеку
            $textModify = new \libraries\TextModify();
            // записываю в $alias транслитирированную строку
            $alias = $textModify->translit($alias_str);

            // записываю в $where['alias'] полученный $alias
            $where['alias'] = $alias;
            // в операнд записываю знак равно
            $operand[] = '=';
            // если есть $id то значит мы редактируем а не добавляем данные
            if ($id){
                // в $where и его ячейку $this->columns['id_row'] записываю $id
                $where[$this->columns['id_row']] = $id;
                // в операнд знак не равно
                $operand[] = '<>';
            }
            // сохраняю в переменную результат работы model->get
            $res_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => $where,
                'operand' => $operand,
                'limit' => '1'
            ])[0];
            // если в $res_alias ничего не пришло
            if (!$res_alias) {
                // записываю в $_POST['alias'] то что есть $alias
                $_POST['alias'] = $alias;

            }else{
                // в свойство $this->alias записываю $alias
                $this->alias = $alias;
                // в $_POST['alias'] сохраняю пустую строку, что бы не было дублирования
                $_POST['alias'] = '';

            }
            // если есть $_POST['alias'] и есть $id т.е. это метод редактирования
            if ($_POST['alias'] && $id){
                // если в текущем объекте есть метод 'checkOldAlias' то вызываю его и передаю ему $id
                method_exists($this, 'checkOldAlias') && $this->checkOldAlias($id);
            }


        }

    }
    // метод проверки алиасов
    protected function checkAlias($id){
        // если $id
        if ($id){
            //если есть свойство $this->alias
            if ($this->alias){
                // в $this->alias добавляю с дефисом идентификатор
                $this->alias .= '-' . $id;
                // вызываю метод модели model->edit, что бы отредактировать алиас
                $this->model->edit($this->table, [
                    'fields' => ['alias' => $this->alias],
                    'where' => [$this->columns['id_row'] => $id]
                ]);
                // возвращаю true в случае успеха
                return true;
            }
        }
        // возвращаю false если ничего не выполнится
        return false;

    }

}