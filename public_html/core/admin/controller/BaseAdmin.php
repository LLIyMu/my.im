<?php


namespace core\admin\controller;


use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

abstract class BaseAdmin extends BaseController
{
    // создаю свойство модели
    protected $model;

    // создаю свойство таблиц
    protected $table;
    //создаю свойство колонок
    protected $columns;
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
    // свойство настроек по умолчанию
    protected $settings;

    protected $translate;
    protected $blocks = [];
    // массив шаблонов
    protected $templateArr;
    // шаблоны форм
    protected $formTemplates;
    protected $noDelete;


    protected function inputData(){

        if (!MS_MODE){

            if(preg_match('/msie|trident.+?rv\s*:/i', $_SERVER['HTTP_USER_AGENT'])){

                echo "https://yandex.ru/promo/browser/general/s/014?from=direct_serp&utm_source=yandex&utm_medium=search&utm_campaign=search_general_new%7C55570394&utm_content=4326397039%7C9712719757&utm_term=браузер%20для%20скачивания&yclid=56554725977129840";
                echo "https://www.opera.com/ru/computer?utm_source=yandex&utm_medium=pa&utm_campaign=Russia_NonBrand_Search&utm_term=мозила%20фирефох%20скачать%20бесплатно&utm_content=5138413930&yclid=56604565969340700";
                echo "https://www.google.ru/chrome/";

                exit('Вы используете устаревшую версию браузера. Пожалуйста обновитесь актуальной версии 
                или можете скачать любой браузер по ссылке');

            };

        }
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

        if (!empty($_POST['return_id'])) $returnId = true;
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
        // если есть $id и существует метод checkFiles, то вызываю его и передаю ему $id
        if ($id && method_exists($this, 'checkFiles')) $this->checkFiles($id);
        // вызываю метод создания ЧПУ
        $this->createAlias($id);

        $this->updateMenuPosition($id);
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

        $this->checkManyToMany();
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

        $fileEdit = new FileEdit();
        $this->fileArray = $fileEdit->addFile();

    }

    protected function updateMenuPosition($id = false){

        if (isset($_POST['menu_position'])){

            $where = false;

            if ($id && $this->columns['id_row']) $where = [$this->columns['id_row'] => $id];

            if (array_key_exists('parent_id', $_POST))
                $this->model->updateMenuPosition($this->table, 'menu_position', $where, $_POST['menu_position'], ['where' => 'parent_id']);
            else
                $this->model->updateMenuPosition($this->table, 'menu_position', $where, $_POST['menu_position']);

        }

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
    // промежуточный(служебный) метод сортировки данных из БД
    protected function createOrderData($table){

        // получаю имя колонок родительской таблицы
        $columns = $this->model->showColumns($table);

        if (!$columns){
            throw new RouteException('Отсутствуют поля в таблице' . $table);
        }
        // записываю пустую строку в переменные
        $name = '';
        $order_name = '';
        // если в $columns есть что то
        if ($columns['name']){
            // записываю в $order_name и $name строку 'name'
            $order_name = $name = 'name';
        }else{
            foreach ($columns as $key => $value){
                // если в $key 'name' не равна false
                if (strpos($key, 'name') !== false){
                    $order_name = $key;
                    // записываю в $name псевдоним
                    $name = $key . ' as name';
                }
            }
            // если в $name ничего нет, записываю псевдоним текущей таблицы $columns['id_row'] как ' as name'
            if (!$name) $name = $columns['id_row'] . ' as name';

        }

        $parent_id = '';
        $order = [];

        if ($columns['parent_id'])
            // в ячейку массива $order и в $parent_id записываю 'parent_id'
            $order[] = $parent_id = 'parent_id';

        if ($columns['menu_position']) $order[] = 'menu_position';
            // иначе в $order записываю значение переменной $order_name
            else $order[] = $order_name;

        return compact('name', 'parent_id', 'order', 'columns');
    }

    protected function createManyToMany($settings = false){
        // Если не $settings то записываю свойство по умолчанию, иначе, записываю Settings::instance()
        if (!$settings) $settings = $this->settings ?: Settings::instance();

        $manyToMany = $settings::get('manyToMany');
        $blocks = $settings::get('blockNeedle');

        if ($manyToMany){

            foreach($manyToMany as $mTable => $tables){
                // array_search вернет 1 если найдёт $this->table в $tables и 0 если нет
                $targetKey = array_search($this->table, $tables);
                // если $targetKey не равна false
                if ($targetKey !== false){
                    // если $targetKey = 1 записываю 0 в $otherKey, если $targetKey = 1 то записываю 0 в $otherKey
                    $otherKey = $targetKey ? 0 : 1;
                    // записываю ячейку ['checkboxlist'] из массива ['templateArr']
                    $checkBoxList = $settings::get('templateArr')['checkboxlist'];
                    // если не $checkBoxList ИЛИ в массиве $checkBoxList нет таблицы $tables и её ячейки [$otherKey],
                    // перехожу на следующую итерацию цикла
                    if (!$checkBoxList || !in_array($tables[$otherKey], $checkBoxList)) continue;
                    // если в свойстве translate нет [$tables[$otherKey]]
                    if (!$this->translate[$tables[$otherKey]]){
                        // если в $settings есть 'projectTables'
                        if ($settings::get('projectTables')[$tables[$otherKey]])
                            // в свойство translate и его ячейку [$tables[$otherKey]] записываю то что лежит
                            // в $settings 'projectTables' [$tables[$otherKey]] и её ячейке ['name']]
                            $this->translate[$tables[$otherKey]] = [$settings::get('projectTables')[$tables[$otherKey]]['name']];

                    }
                    // в $orderData записываю результат работы метода createOrderData передав ему на вход $tables[$otherKey]
                    $orderData = $this->createOrderData($tables[$otherKey]);
                    // ставлю значение по умолчанию в false
                    $insert = false;
                    // если в $blocks что то есть
                    if ($blocks){

                        foreach($blocks as $key => $item){
                            // если в массиве $item есть $tables[$otherKey]
                            if (in_array($tables[$otherKey], $item)){
                                // в $this->blocks[$key][] записываю $tables[$otherKey]
                                $this->blocks[$key][] = $tables[$otherKey];
                                // в $insert ставлю true
                                $insert = true;
                                // завершаю цикл
                                break;

                            }

                        }

                    }

                    if (!$insert) $this->blocks[array_keys($this->blocks)[0]][] = $tables[$otherKey];

                    $foreign = [];

                    if ($this->data){

                        $res = $this->model->get($mTable, [
                            'fields' => [$tables[$otherKey] . '_' . $orderData['columns']['id_row']],
                            'where' => [$this->table . '_' . $this->columns['id_row'] => $this->data[$this->columns['id_row']]]
                        ]);

                        if ($res){

                            foreach($res as $item){

                                $foreign[] = $item[$tables[$otherKey] . '_' . $orderData['columns']['id_row']];

                            }

                        }

                    }

                    if (isset($tables['type'])){

                        $data = $this->model->get($tables[$otherKey], [
                            'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parent_id']],
                            'order' => $orderData['order']
                        ]);

                        if ($data){

                            $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['name'] = 'Выбрать';

                            foreach($data as $item){

                                if ($tables['type'] === 'root' && $orderData['parent_id']){

                                    if ($item[$orderData['parent_id']] === null){

                                        $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;

                                    }

                                }elseif ($tables['type'] === 'child' && $orderData['parent_id']){

                                    if ($item[$orderData['parent_id']] !== null){

                                        $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;

                                    }

                                }else{

                                    $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;

                                }

                                if (in_array($item['id'], $foreign)){

                                    $this->data[$tables[$otherKey]][$tables[$otherKey]][] = $item['id'];

                                }

                            }

                        }
                    // иначе если поле 'parent_id' ссылается сам на себя !!!
                    }elseif ($orderData['parent_id']){
                        // в $parent записываю $tables[$otherKey]
                        $parent = $tables[$otherKey];
                        // получаю в $keys результат работы метода showForeignKeys передавая ему таблицу
                        $keys = $this->model->showForeignKeys($tables[$otherKey]);
                        // если в $keys что то есть
                        if ($keys){

                            foreach ($keys as $item){
                                // если $item и его ячейка ['COLUMN_NAME'] равна 'parent_id'
                                if ($item['COLUMN_NAME'] === 'parent_id'){
                                    // в родителя записываю имя таблицы с которой будет связь
                                    $parent = $item['REFERENCED_TABLE_NAME'];
                                    // прерываю цикл
                                    break;

                                }

                            }

                        }

                        if ($parent === $tables[$otherKey]){

                            $data = $this->model->get($tables[$otherKey], [
                                'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parent_id']],
                                'order' => $orderData['order']
                            ]);

                            if ($data){
                                // до тех пор пока в $data будут ключи цикл будет выполняться и будут записыватся в $key
                                while(($key = key($data)) !== null){

                                    if (!$data[$key]['parent_id']){

                                        $this->foreignData[$tables[$otherKey]][$data[$key]['id']]['name'] = $data[$key]['name'];
                                        unset($data[$key]);
                                        reset($data);
                                        continue;

                                    }else{

                                        if ($this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]]){

                                            $this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]]['sub'][$data[$key]['id']] = $data[$key];

                                            if (in_array($data[$key]['id'], $foreign))
                                                $this->data[$tables[$otherKey]][$data[$key][$orderData['parent_id']]][] = $data[$key]['id'];

                                            unset($data[$key]);
                                            reset($data);
                                            continue;

                                        }else{

                                            foreach ($this->foreignData[$tables[$otherKey]] as $id => $item){

                                                $parent_id = $data[$key][$orderData['parent_id']];

                                                if (isset($item['sub']) && $item['sub'] && isset($item['sub'][$parent_id])){

                                                    $this->foreignData[$tables[$otherKey]][$id]['sub'][$data[$key]['id']] = $data[$key];

                                                    if (in_array($data[$key]['id'], $foreign))
                                                        $this->data[$tables[$otherKey]][$id][] = $data[$key]['id'];

                                                    unset($data[$key]);
                                                    reset($data);
                                                    continue 2;

                                                }

                                            }

                                        }

                                        // перемещаю указатель цикла на следующий элемент массива
                                        next($data);

                                    }

                                }

                            }
                        // если parent_id находится в другой таблице
                        }else{

                            $parentOrderData = $this->createOrderData($parent);

                            $data = $this->model->get($parent, [
                                'fields' => [$parentOrderData['name']],
                                'join' => [
                                    $tables[$otherKey] => [
                                        'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name']],
                                        'on' => [$parentOrderData['columns']['id_row'], $orderData['parent_id']]
                                        ]
                                    ],
                                'join_structure' => true
                            ]);

                            foreach($data as $key => $item){

                                if (isset($item['join'][$tables[$otherKey]]) && $item['join'][$tables[$otherKey]]){

                                    $this->foreignData[$tables[$otherKey]][$key]['name'] = $item['name'];
                                    $this->foreignData[$tables[$otherKey]][$key]['sub'] = $item['join'][$tables[$otherKey]];

                                    foreach($item['join'][$tables[$otherKey]] as $value){

                                        if (in_array($value['id'], $foreign)){

                                            $this->data[$tables[$otherKey]][$key][] = $value['id'];

                                        }

                                    }

                                }

                            }

                        }

                    }else{

                        $data = $this->model->get($tables[$otherKey], [
                            'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parent_id']],
                            'order' => $orderData['order']
                        ]);

                        if ($data){

                            $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['name'] = 'Выбрать';

                            foreach($data as $item){

                                $this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;

                                if (in_array($item['id'], $foreign)){

                                    $this->data[$tables[$otherKey]][$tables[$otherKey]][] = $item['id'];

                                }

                            }

                        }

                    }

                }

            }

        }

    }

    protected function checkManyToMany($settings = false){
        // Если не $settings то записываю свойство по умолчанию, иначе, записываю Settings::instance()
        if (!$settings) $settings = $this->settings ?: Settings::instance();

        $manyToMany = $settings::get('manyToMany');
        // если в $manyToMany что то есть
        if ($manyToMany){

            foreach($manyToMany as $mTable => $tables){
                // если в $this->table есть таблица $tables, эаписываю в $targetKey 1 т.е. тру
                $targetKey = array_search($this->table, $tables);

                if ($targetKey !== false){
                    // если $targetKey то в $otherKey записываю 0 иначе записываю 1
                    $otherKey = $targetKey ? 0 : 1;
                    // получаю в переменную то что содержится в свойстве 'templateArr' и его ячейке ['checkboxlist']
                    $checkboxlist = $settings::get('templateArr')['checkboxlist'];
                    // если не !$checkboxlist ИЛИ в массиве $tables[$otherKey] нет $checkboxlist перехожу на
                    // следующую итерацию цикла
                    if (!$checkboxlist || !in_array($tables[$otherKey], $checkboxlist)) continue;
                    // получаю колнки из таблицы $tables[$otherKey]
                    $columns = $this->model->showColumns($tables[$otherKey]);
                    // сохраняю в $targetRow(целевые поля) текущую таблицу и колонку с ['id_row']
                    $targetRow = $this->table . '_' . $this->columns['id_row'];

                    $otherRow = $tables[$otherKey] . '_' . $columns['id_row'];

                    $this->model->delete($mTable, [
                        'where' => [$targetRow => $_POST[$this->columns['id_row']]]
                    ]);

                    if ($_POST[$tables[$otherKey]]){

                        $insertArr = [];
                        $i = 0;

                        foreach($_POST[$tables[$otherKey]] as $value){

                            foreach ($value as $item){

                                if ($item){

                                    $insertArr[$i][$targetRow] = $_POST[$this->columns['id_row']];
                                    $insertArr[$i][$otherRow] = $item;

                                    $i++;

                                }

                            }

                        }

                        if ($insertArr){

                            $this->model->add($mTable, [
                                'fields' => $insertArr
                            ]);

                        }

                    }

                }

            }

        }

    }

    // метод формирования внешних ключей для метода createForeignData() принимает $arr массив
    protected function createForeignProperty($arr, $rootItems){
        // если в массиве $rootItems['tables'] есть таблица $this->table
        if (in_array($this->table, $rootItems['tables'])){
            // записываю в foreignData с ячейкой ['id'] имя колонки текущей таблицы которая ссылается на
            // родительскую
            $this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 'NULL';
            // записываю в foreignData с ячейкой ['name'] то что содержится в $rootItems['name']
            $this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
        }
        // в $orderData сохраняю результат работы метода createOrderData передаю текущую таблицуR
        $orderData = $this->createOrderData($arr['REFERENCED_TABLE_NAME']);

        // если в данные уже заполнены
        if ($this->data){
            // если таблица ссылается сама на себя т.е. REFERENCED_TABLE_NAME === $this->table текущей таблице
            if ($arr['REFERENCED_TABLE_NAME'] === $this->table){
                // в свойство  $where[$this->columns['id_row'] записываю $this->data[$this->columns['id_row']
                $where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
                // и в $operand ставлю символ '<>'(не равно)
                $operand[] = '<>';
            }
        }
        // формирую в переменную все данные которые отонсятся к таблице которую получили из model, так же записываю
        // все поля 'fields', 'where' если пришло, 'operand' если пришел
        $foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'],[
            'fields' => [$arr['REFERENCED_COLUMN_NAME'] . ' as id', $orderData['name'], $orderData['parent_id']],
            'where' => $where,
            'operand' => $operand,
            'order' => $orderData['order']
        ]);
        // если свойства заполнились
        if ($foreign){
            // если в foreignData и его ячейке COLUMN_NAME что то есть прохожу форейч по его значению
            if ($this->foreignData[$arr['COLUMN_NAME']]){
                foreach ($foreign as $value) {
                    // записываю значение в foreignData и его ячейку COLUMN_NAME
                    $this->foreignData[$arr['COLUMN_NAME']][] = $value;
                }
            }else{
                // иначе записываю то что пришло в $foreign
                $this->foreignData[$arr['COLUMN_NAME']] = $foreign;
            }

        }

    }

    // метод создания данных для внешних ключей
    protected function createForeignData($settings =false){
        // если в $settings ничего не пришло, записываю в $settings ссылку на класс Settings
        if (!$settings) $settings = Settings::instance();
        // получаю в переменную свойство из Settings с помощью метода get()
        $rootItems = $settings::get('rootItems');
        // в переменную $keys получаю внешние(Foreign) ключи с помощью метода showForeignKeys передавая текущую
        // таблицу($this->table)
        $keys = $this->model->showForeignKeys($this->table);
        // если в $keys что то пришло
        if ($keys){

            foreach ($keys as $item){
                $this->createForeignProperty($item, $rootItems);
            }
            // иначе ели в свойстве $this->columns ['parent_id'] есть что то т.е. если таблица ссылается сама на себя
        }elseif ($this->columns['parent_id']){
            // записываю строку 'parent_id' в имя колонки
            $arr['COLUMN_NAME'] = 'parent_id';
            // записываю ссылку на имя колонки, т.е. то что находится в $this->columns
            $arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
            // записываю текущую таблицу
            $arr['REFERENCED_TABLE_NAME'] = $this->table;
            // вызываю метод передавая ему сформированные аргументы
            $this->createForeignProperty($arr, $rootItems);
        }

        return;

    }
    // метод создания сортировки блоков меню относительно родительской таблицы БД
    protected function createMenuPosition($settings = false){
        // если в текущих колонках таблицы есть ячейка ['menu_position']
        if ($this->columns['menu_position']){
            // если не $settings то сохраняю в переменную $settings ссылку на объект класса Settings
            if (!$settings) $settings = Settings::instance();
            // в $rootItems получаю свойство rootItems, которое лежит в классе Settings
            $rootItems = Settings::get('rootItems');
            // если в свойстве columns есть ячейка массива ['parent_id']
            if ($this->columns['parent_id']){
                // если в массиве $rootItems['tables'] есть таблица $this->table
                if (in_array($this->table, $rootItems['tables'])){
                    // то в $where записываю строку для запроса, где parent_id, проверяется, равен ли он NULL или 0
                    $where = 'parent_id IS NULL OR parent_id = 0';
                }else{
                    // иначе записываю в $parent колонки с внешними ключами, передаю второй параметр $key => 'parent_id'
                    // когда метод showForeignKeys получает второй аргумент он вставляет его в запрос к БД который
                    // уточняет какое поле мы ищем
                    $parent = $this->model->showForeignKeys($this->table, 'parent_id')[0];
                    // если в $parent что то пришло
                    if ($parent){

                        if ($this->table === $parent['REFERENCED_TABLE_NAME']){
                            $where = 'parent_id IS NULL OR parent_id = 0';
                        }else{
                            // записываю в переменную колонки которые пришли из родительской (parent) таблицы
                            $columns = $this->model->showColumns($parent['REFERENCED_TABLE_NAME']);
                            // если есть $columns и его ячейка ['parent_id'] то записываю в свойство выборки $order[] =
                            // 'parent_id'
                            if ($columns['parent_id']) $order[] = 'parent_id';
                            // иначе записываю $columns['id_row']
                            else $order[] = $parent['REFERENCED_COLUMN_NAME'];
                            // в $id записываю [0] элемент таблицы $parent['REFERENCED_TABLE_NAME'] т.е. возвращаю его
                            // значение в переменную
                            $id = $this->model->get($parent['REFERENCED_TABLE_NAME'],[
                                'fields' => [$parent['REFERENCED_COLUMN_NAME']],
                                'order' => $order,
                                'limit' => 1
                            ])[0][$parent['REFERENCED_COLUMN_NAME']];

                            if ($id) $where = ['parent_id' => $id];
                        }


                    }else{
                        //в $where записываю строку для запроса, где parent_id, проверяется, равен ли он NULL или 0
                        $where = 'parent_id IS NULL OR parent_id = 0';

                    }

                }

            }

            // записываю в $menu_pos считая все поля с помощью COUNT(*) получая нулевой элемент текущей таблицы + 1
            // элемент т.к. это метод add()
            $menu_pos = $this->model->get($this->table, [
                    'fields' => ['COUNT(*) as count'],// посчитать всё COUNT(*) и присвоить  count
                    'where' =>  $where, // записываю то что пришло в $where или пустоту если ничего не пришло
                    'no_concat' => true // ставлю флаг 'no_concat' в true
                ])[0]['count'] + (int)!$this->data;

            for ($i = 1; $i <= $menu_pos; $i++){
                $this->foreignData['menu_position'][$i - 1]['id'] = $i;
                $this->foreignData['menu_position'][$i - 1]['name'] = $i;
            }
        }


        return;

    }


    protected function checkOldAlias($id){

        $tables = $this->model->showTables();

        if (in_array('old_alias', $tables)){

            $old_alias = $this->model->get($this->table, [
                'fields' => ['alias'],
                'where' => [$this->columns['id_row'] => $id]
            ])[0]['alias'];

            if ($old_alias && $old_alias !== $_POST['alias']){

                $this->model->delete('old_alias', [
                    'where' => ['alias' => $old_alias, 'table_name' => $this->table]
                ]);

                $this->model->delete('old_alias', [
                    'where' => ['alias' => $_POST['alias'], 'table_name' => $this->table]
                ]);

                $this->model->add('old_alias', [
                    'fields' => ['alias' => $old_alias, 'table_name' => $this->table, 'table_id' => $id]
                ]);

            }

        }

    }

    protected function checkFiles($id){

        if ($id && $this->fileArray){

            $data = $this->model->get($this->table, [
                'fields' => array_keys($this->fileArray),
                'where' => [$this->columns['id_row'] => $id]
            ]);

            if ($data){

                $data = $data[0];

                foreach($this->fileArray as $key => $item){

                    if (is_array($item) && !empty($data[$key])){

                        $fileArr = json_decode($data[$key]);

                        if ($fileArr){

                            foreach ($fileArr as $file){
                                $this->fileArray[$key][] = $file;
                            }

                        }

                    }elseif(!empty($data[$key])){

                        @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $data[$key]);

                    }

                }

            }

        }

    }

}