<?php


namespace core\base\controller;


use core\base\exceptions\RouteException;
use core\base\settings\Settings;

class RouteController extends BaseController
{
    // использую трейт
    use Singleton;
    // свойство маршрутов
    protected $routes;
    // магический метод __construct выполняется каждый раз при создании объекта класса
    private function __construct()
    {
        $address_arr = $_SERVER['REQUEST_URI'];
        // сохранил в переменную $path обрезанную строку в которой содержиться имя выполнения скрипта
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));
        // если $path равна константе PATH
        if ($path === PATH) {
            //делаю проверку на то что это не корневой вызов
            //конструкция strrpos возвращает позицию последнего вхождения подстроки в строке
            if (strrpos($address_arr, '/') === strlen($address_arr) - 1 &&
                strrpos($address_arr, '/') !== strlen(PATH) -1) {
                $this->redirect(rtrim($address_arr, '/'), 301);}
            // сохраняю маршруты в свойство $routes с помощью метода get класса Settings
            $this->routes = Settings::get('routes');
            //если маршруты не были получены выбрасываю сообщение
            if (!$this->routes) throw new RouteException('Отсутствуют маршруты в базовых настройках', 1);
            //сохраняю в переменную $url, получаю массив с разделителем '/', обрезаю адресную строку с помощью
            // конструкции substr где первый элемент это PATH т.е. начиная с '/' возвращаю путь в $url
            $url = explode('/', substr($address_arr, strlen(PATH)));
            //проверяю адресную строку, если в $url что то есть и оно соответствует алиасу админ
            // подключаемся к админ панели, и разбираем адресную строку относительно админ панели
            if ($url[0] && $url[0] === $this->routes['admin']['alias']) {
                //функция array_shift удалит первый элемент из массива $url
                array_shift($url);

                // если в $url[0] содержится admin И путь до директории содержит путь до плагина, если это плагин
                if ($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0])) {
                    // сохраняю в переменную название плагина, с помощью конструкции array_shift она возвращает
                    // первый элемент массива и смещает оставшийся массив что бы он начинался с 0 индекса
                    $plugin = array_shift($url);
                    // сохраняю имя файла настроек в переменную
                    $pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin . 'Settings');
                    // проверяю существует ли такой файл
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettings . '.php')) {
                        // сохраняю в переменную путь заменяя обычные слешы на обратные, для правильного именования
                        // неймспейсов
                        $pluginSettings = str_replace('/', '\\', $pluginSettings);
                        // переопределяю маршрут получая все склеенные массивы с помощью метода get()
                        $this->routes = $pluginSettings::get('routes');
                    }
                    // проверяю и записываю в переменную, если есть такой путь то записываем слеш '/' в начале пути и
                    // после него, если нет то просто записываем слещ '/'
                    $dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugins']['dir'] . '/' : '/';
                    // проверяю с помощью конструкции str_replace наличие двойного слеша '//' заменяю его на одинарный
                    $dir = str_replace('//', '/', $dir);
                    // сохраняю в свойство контроллера полный путь
                    $this->controller = $this->routes['plugins']['path'] . $plugin . $dir;
                    // записываю в переменную, для создания ЧПУ
                    $hrUrl = $this->routes['plugins']['hrUrl'];
                    // присваиваю в роут маршрут админ
                    $route = 'plugins';

                }else{// если это не плагин
                    // определяем какой подключать контроллер
                    $this->controller = $this->routes['admin']['path'];
                    // записываю в переменную, для создания ЧПУ
                    $hrUrl = $this->routes['admin']['hrUrl'];
                    // присваиваю в роут маршрут админ
                    $route = 'admin';
                }
            }else{
                // записываю в переменную, для создания ЧПУ
                $hrUrl = $this->routes['user']['hrUrl'];
                // определяем какой подключать контроллер
                $this->controller = $this->routes['user']['path'];
                // присваиваю в роут маршрут юзер
                $route = 'user';
            }
            // передаю методу по созданию роутов на вход $route, $url
            $this->createRoute($route, $url);
            // если в $url есть
            if ($url[1]) {
                // создаю переменную где с помощью конструкции  count  подсчитываю количество элементов массива
                $count = count($url);
                // задаю пустую строку
                $key = '';
                // если это не ЧПУ
                if (!$hrUrl) {
                    $i = 1; //создаю переменную для цикла for
                }else{ // если это чпу
                    $this->parameters['alias'] = $url[1];
                    $i = 2; //создаю переменная для цикла for
                }
                // цикл который формирует адресную строку
                for ( ; $i < $count; $i++){
                    if (!$key) {// на первой итерации в $key  нет ничего присваиваю ей $url с индексом
                        $key = $url[$i];
                        $this->parameters[$key] = '';
                    }else{// на второй итерации в $key уже есть $url поэтому записываем его в параметры и обнуляем
                        // $key до пустого и начинаем цикл с первого условия if
                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }
            }
        }else{ // если не равна то выбрасываем исключения
            throw new RouteException('Не корректная директория сайта', 1);
        }
    }
    // метод для создания маршрутов пользовательской и административной части сайта, принимает на вход
    // $route[маршрут $var], $url[масив ссылок $arr]
    private function createRoute(string $var, array $arr){
        $route = [];//задаю пустую переменную

        // если не пуст массив то это контроллер
        if (!empty($arr[0])) {
            // если существуют в маршрутах псевдоним контроллера
            if ($this->routes[$var]['routes'][$arr[0]]) {
                // разбираем маршрут и сохраняем в переменную с разделителем /
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);
                // добавляем контроллер его имя переводя его первый символ в верхний регистр
                $this->controller .= ucfirst($route[0].'Controller');
            }else{// если маршрут не описан а контроллер есть
                $this->controller .= ucfirst($arr[0].'Controller');
            }
        }else{// если не будет [0] элемента массива
            $this->controller .= $this->routes['default']['controller'];
        }
        // если есть $route и в нём что то есть, то в метод записываем $route[1] если нет то записываем маршрут по
        // умолчанию (default)
        $this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];

        return;
    }
}