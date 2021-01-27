<?php


namespace core\admin\controller;


use core\base\controller\BaseMethods;

class CreateSiteMapController extends BaseAdmin
{
    // подключаю трейт
    use BaseMethods;
    // свойство для заполения ссылок
    protected $all_links = [];
    // свойство хранения временных элементов парсинга
    protected $temp_links = [];
    // свойство для 'битых' ссылок
    protected $bad_links = [];
    // максимальное количество ссылок
    protected $maxLinks = 5000;
    // свойство пути к файлу логирования
    protected $parsingLogFile = 'parsingLog.txt';
    // свойство с расширениями файлов которые содержастся в ссылке, их нужно исключить из парсинга
    protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'mp4', 'mpeg', 'mp3'];
    // свойство фильтра
    protected $filterArr = [
        'url' => [],
        'get' => []
    ];

    public function inputData($links_counter = 1, $redirect = true){

        $links_counter = $this->clearNum($links_counter);
        // если не вызвана функция curl_init
        if (!function_exists('curl_init')){

            $this->cancel(0, 'Library CURL as absent. Creation of sitemap impossible', '', true);

        }
        // вызываю родительский метод, который инициализирует и запускает все нужные методы в том числе методы модели
        if (!$this->userId) $this->execBase();
        // если не checkParsingTable
        if(!$this->checkParsingTable()){
            // записываю в сообщение о ошибке пасинга изза таблицы в БД
            $this->cancel(0, 'You have problem with database table parsing data', '', true);

        };
        // убираю ограничение времени на выполнение скрипта
        set_time_limit(0);
        // сохраняю в переменную $reserve всё что есть в таблице 'parsing_data'
        $reserve = $this->model->get('parsing_data')[0];
        // массив для создания полей БД
        $table_rows = [];

        foreach ($reserve as $name => $item){
            // заполняю ключами массив $table_rows
            $table_rows[$name] = '';

            if ($item) $this->$name = json_decode($item);
                elseif($name === 'all_links' || $name === 'temp_links') $this->$name = [SITE_URL];

        }
        // записываю в свойство количество ссылок. Если сервер выпадет в ошибку при парсинге с клиентской стороны
        // прилетит свойство в $links_counter которое поделит количество ссылок maxLinks на $links_counter т.е. на
        // себя, если свойство не будет, то просто зпишется то количество ссылок что указано по умолчанию
        $this->maxLinks = (int)$links_counter > 1 ? ceil($this->maxLinks / $links_counter) : $this->maxLinks;
        // прохожу по временным ссылкам temp_links циклом while
        while ($this->temp_links){
            // записываю количество ссылок temp_links в $temp_links_count
            $temp_links_count = count($this->temp_links);
            // в $links записываю временные ссылки temp_links
            $links = $this->temp_links;
            // сбрасываю все ссылки в свойстве класса хранящиеся в ней
            $this->temp_links = [];
            // если $temp_links_count больше чем $this->maxLinks
            if ($temp_links_count > $this->maxLinks){
                // разбиваю массив $links на количество $temp_links_count делённое на $this->maxLinks
                $links = array_chunk($links, ceil($temp_links_count / $this->maxLinks));
                // записываю в $count_chunks исходное количество ссылок т.е. то количество на сколько он поделился
                $count_chunks = count($links);
                // в for прохожу по $count_chunks
                for ($i = 0; $i < $count_chunks; $i++){
                    // вызываю метод парсинг передавая ему $links[$i]
                    $this->parsing($links[$i]);
                    // разрегистрирую $links[$i] после выполнения метода parsing
                    unset($links[$i]);
                    // если есть что то в массве $links
                    if ($links){

                        foreach ($table_rows as $name => $item){
                            // т.к. массив $links многомерный, делаю множественное присваивание с помощью ...$links
                            if ($name === 'temp_links') $table_rows[$name] = json_encode(array_merge(...$links));
                                else $table_rows[$name] = json_encode($this->$name);

                        }
                        // вызываю метод модели $this->model->edit передаю ему таблицу parsing_data и заношу в её
                        // поля оставшиеся данные
                        $this->model->edit('parsing_data', [
                            'fields' => $table_rows
                        ]);

                    }

                }

            }else{
                // иначе парсим одномерный массив $links
                $this->parsing($links);
            }
            foreach ($table_rows as $name => $item){

                $table_rows[$name] = json_encode($this->$name);

            }
            // сохраняю в БД то что заполнил метод parsing в переменные temp_links и all_links
            $this->model->edit('parsing_data', [
                'fields' => $table_rows
            ]);

        }
        foreach ($table_rows as $name => $item){

            $table_rows[$name] = '';

        }
        // после того как отработает цикл while очищаю таблицу, записывая в поля пустые значения
        $this->model->edit('parsing_data', [
            'fields' => $table_rows
        ]);

        if ($this->all_links){

            foreach ($this->all_links as $key => $link){

                if (!$this->filter($link) || (in_array($link, $this->bad_links))) unset ($this->all_links[$key]);

            }

        }
        /*// если есть файл лога, то при новом парсинге его нужно удалить, что бы он не был слишком большим
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile))
            // удаляю его, при следующем парсинге сайта он создастся снова.
            @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);*/
        // запускаю с помощью метода parsing() парсинг
        $this->parsing(SITE_URL);
        // запускаю метод createSiteMap который создаст карту сайта в формате XML
        $this->createSiteMap();
        // если $redirect = true то делаю редирект
        if ($redirect){
            // если не $_SESSION['res']['answer'] то в $_SESSION['res']['answer'] записываю сообщение об успешном выполении
            !$_SESSION['res']['answer'] && $_SESSION['res']['answer'] = '<div class="success">Sitemap is created</div>';
            // выполняю редирект
            $this->redirect();
        }else{

            $this->cancel(1, 'Sitemap is created ' . count($this->all_links) . ' links', '', true);

        }

    }
    // метод чтения сайтов, парсер
    protected function parsing($urls){
        // если ничего не пришло в $urls, завершаю работу скрипта
        if (!$urls) return;
        // инициализирую мультипоточный curl
        $curlMulti = curl_multi_init();
        // задаю переменной $curl пустой массив
        $curl = [];
        if (is_array($urls)){
            // прохожу форейчем по $urls что бы заполнить дескрипторы(настройки curl)
            foreach ($urls as $i => $url){
                // инициализирую библиотеку CURL
                $curl[$i] = curl_init();

                // устанавливаю опции библиотеки
                curl_setopt($curl[$i], CURLOPT_URL, $url);
                curl_setopt($curl[$i], CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl[$i], CURLOPT_HEADER, true);
                curl_setopt($curl[$i], CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curl[$i], CURLOPT_TIMEOUT, 120);
                curl_setopt($curl[$i], CURLOPT_ENCODING, 'gzip,deflate');
                // регистрирую обработчик событий curl
                curl_multi_add_handle($curlMulti, $curl[$i]);
            }
        }
        // запускаю цикл do while
        do{
            // запускаю текущий дескриптор
            $status = curl_multi_exec($curlMulti, $active);
            // записываю сообщения в $info - приходят сообщения от текущих операциях
            $info = curl_multi_info_read($curlMulti);
            // если false не равен $info т.е. если есть какая то ошибка
            if (false !== $info){
                // если $info и его ячейка ['result'] не равна 0, при нормальном выполнении скрипта без ошибок
                // $info['result'] всегда равен 0
                if ($info['result'] !== 0){
                    // в итый элемент записываю ключ $info['handle'] если он будет найден в массиве $curl
                    $i = array_search($info['handle'], $curl);
                    // в $error записываю номер ошибки
                    $error = curl_errno($curl[$i]);
                    // в $message записываю сообщение (текст) ошибки
                    $message = curl_error($curl[$i]);
                    // записываю информацию об операции $curl[$i]
                    $header = curl_getinfo($curl[$i]);
                    // если $error не равен нулю, т.е. если там есть ошибка
                    if ($error != 0){
                        // записываю сообщение об ошибке
                        $this->cancel(0, 'Error loading' . $header['url'] .
                            ' http code: ' . $header['http_code'] .
                            ' error: ' . $error . ' message ' . $message, '');
                    }
                }
            }
            // если $status больше 0 т.е. если есть ошибка
            if ($status > 0){
                // записываю сообщение об ошибке
                $this->cancel(0, curl_multi_strerror($status));

            }

        }while($status === CURLM_CALL_MULTI_PERFORM || $active);

        $result = [];

        if (is_array($urls)){
            foreach ($urls as $i => $url){

                $result[$i] = curl_multi_getcontent($curl[$i]);
                // удаляю дескриптор $curl[$i] из многопотока $curlMulti
                curl_multi_remove_handle($curlMulti, $curl[$i]);

                curl_close($curl[$i]);

                // если нет строки 'Content-Type:html' в $out
                if (!preg_match('/Content-Type:\s+text\/html/ui', $result[$i])){

                    $this->bad_links[] = $url;

                    $this->cancel(0, 'Incorrect content type ' . $url);

                    continue;

                }

                // если не корректный код ответа любо отличающийся от 200 до 209 т.е. 404 и.т.д.
                if (!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $result[$i])){

                    $this->bad_links[] = $url;

                    $this->cancel(0, 'Incorrect server code ' . $url);

                    continue;

                }

                $this->createLinks($result[$i]);
            }
        }

        curl_multi_close($curlMulti);

    }

    protected function createLinks($content){

        if ($content){
            // регулярка для поиска тегов ссылок
            preg_match_all('/<a\s*?[^>]*?href\s*?=(["\'])(.+?)\1[^>]*?>/uis', $content, $links);
            // если есть аттрибут href и при этом он не пустой, т.е. регулярка отрабатла как надо
            if ($links[2]){
                // прохожу по ссылкам форычем
                foreach($links[2] as $link){
                    // если пришедший $url равен / или он равен SITE_URL . '/' то завершаю работу скрипта
                    if ($link === '/' || $link === SITE_URL . '/') continue;
                    // прохожу по свойству с расширениями файлов
                    foreach ($this->fileArr as $ext){
                        // если найдены расширения
                        if ($ext){
                            // экранирую символы
                            $ext = addslashes($ext);
                            // заменяю точку на обратный слеш и точку
                            $ext = str_replace('.', '\.', $ext);
                            // если найдено расширение в $link и это конец строки
                            if (preg_match('/' . $ext . '(\s*?$|\?[^\/]*$)/ui', $link)){
                                // если совпадения найдены выхожу на первый форейч и перехожу к следующей итерации
                                continue 2;

                            }

                        }

                    }
                    // если в $link 0 позиция равна / то это относительная сылка
                    if (strpos($link, '/') === 0){
                        // записываю в $link полный путь
                        $link = SITE_URL . $link;

                    }
                    $site_url = mb_str_replace('.', '\.', mb_str_replace('/', '\/', SITE_URL));
                    // если ссылки $link нет в массиве $this->all_links И ссылка $link не равна '#' И вхождение строки $link
                    // равно SITE_URL
                    if (!in_array($link, $this->bad_links) &&
                        !preg_match('/^('. $site_url .')?\/?#[^\/]*?$/ui', $link) &&
                        strpos($link, SITE_URL) === 0 && !in_array($link, $this->all_links)){
                        // сохраняю ссылку в массив
                        $this->temp_links[] = $link;
                        $this->all_links[] = $link;

                    }

                }

            }

        }

    }

    // метод фильтрации ссылок, которые не нужны для карты сайта
    protected function filter($link){

        // если что то есть в массиве filterArr
        if ($this->filterArr){
            // запускаю по filterArr цикл форейч как тип и значение
            foreach ($this->filterArr as $type => $values){
                // если в $values что то есть
                if ($values){
                    // запускаю форейч по $values
                    foreach ($values as $item){
                        // меняю простые слеши / на экранированные \ в $item
                        $item = str_replace('/', '\/', addslashes($item));
                        // если $type равен 'url'
                        if ($type === 'url'){
                            // если совпадения найдены
                            if (preg_match('/^[^\?]*' . $item . '/ui', $link)){
                                // возвращаю false
                                return false;
                            }
                        }
                        // если $type равен 'get'
                        if ($type === 'get'){
                            // если совпадения найдены
                            if (preg_match('/(\?|&amp;|=|&)'. $item .'(=|&amp;|&|$)/ui', $link)){
                                return false;
                            }
                        }

                    }

                }

            }

        }

        return true;

    }
    // метод создания таблицы бля хоранения ссылок
    protected function checkParsingTable(){
        // сохранаяю в переменную список таблиц которые есть в БД с помощью метода модели showTables
        $tables = $this->model->showTables();
        // если в массиве $tables)) нет записи 'parsing_data'
        if (!in_array('parsing_data', $tables)){
            // создаю запрос на создание таблицы
            $query = 'CREATE TABLE parsing_data (all_links longtext, temp_links longtext, bad_links longtext)';
            // если не model->query то передаю запрос на создание ($query, 'c')
            if (!$this->model->query($query, 'c') ||
                // ИЛИ не model->add то обновляю поля , т.е. перезаписываю в них пустую строку
                !$this->model->add('parsing_data', ['fields' => ['all_links' => '', 'temp_links' => '', 'bad_links' => ''
                ]])){

                return false;
            }

        } return true;

    }
    // метод записи ошибок в log либо завершение скрипта в том месте где буду вызывать этот метод
    protected function cancel($success = 0, $message = '', $log_message = '', $exit = false){
        // массив который будет улетать в клиентскую часть
        $exitArr = [];

        // успешность выполнения запроса
        $exitArr['success'] = $success;
        // по умолчанию равно $message в противном случае 'ERROR PARSING'
        $exitArr['message'] = $message ? $message : 'ERROR PARSING';
        // по умолчанию будет равна $log_message инчае записываю $exitArr['message']
        $log_message = $log_message ? $log_message : $exitArr['message'];
        // по умолчанию 'success'
        $class = 'success';
        // если не $exitArr['success']
        if (!$exitArr['success']){
            // то меняю класс на 'error'
            $class = 'error';
            // и записываю сообщение в лог, а так же сосздаю файл лога
            $this->writeLog($log_message, 'parsing_log.txt');
        }
        // если есть $exit
        if ($exit){
            // то записываю сообщение о ошибке с нужным классом
            $exitArr['message'] = '<div class="' . $class . '">' . $exitArr['message'] . '</div>';
            // и передаю в функцию exit json и передаю $exitArr
            exit(json_encode($exitArr));

        }


    }

    protected function createSiteMap(){
        // сохраняю объект класса domDocument
        $dom = new \domDocument(1.0, 'utf-8');
        // ставлю объекту domDocument свойство выходных данных formatOutput в true
        $dom->formatOutput = true;

        // создаю корневой элемент
        $root = $dom->createElement('urlset');
        // устанавливаю аттрибуты
        $root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $root->setAttribute('xmlns:xls', 'http://w3.org/2001/XMLSchema-instance');
        $root->setAttribute('xsi:schemaLocation', 'http:///www.sitemaps.org/schemas/sitemap/0.9 http:///www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        // создаю список элементов в DOM дереве
        $dom->appendChild($root);
        // импортирую созданный объект дом с дочерними элементами
        $sxe = simplexml_import_dom($dom);
        // если заполнено свойство с сылками
        if ($this->all_links){
            // создаю временную метку
            $date = new \DateTime();
            // устанавливаю фармат даты и времени
            $lastMod = $date->format('Y-m-d') . 'T' . $date->format('H:i:s+01:00');
            // прохожу по ссылкам форычем
            foreach ($this->all_links as $item){
                // обрезаю строку $item с позиции SITE_URL, и обрезаю конечный слеш если он есть
                $elem = trim(mb_substr($item, mb_strlen(SITE_URL)), '/');
                // разбиваю строку $elem по слешу
                $elem = explode('/', $elem);
                // создаю счётчик приорритета
                $count = '0.' . (count($elem) -1);
                // записываю в $priority 1 - $count приведённый к плавающей точке
                $priority = 1 - (float)$count;
                // если $priority равен 1 записываю его в строку
                if ($priority == 1) $priority = '1.0';
                // сохраняю в DOM потомка url
                $urlMain = $sxe->addChild('url');
                // создаю потомков тега 'url', тег 'loc' экранирую с помощью htmlspecialchars т.к. в $item могут
                // содержатся спецсимволы, которые могут вызвать варниниги парсинга
                $urlMain->addChild('loc', htmlspecialchars($item));

                $urlMain->addChild('lastmode', $lastMod);

                $urlMain->addChild('changefreg', 'weekly');

                $urlMain->addChild('priority', $priority);



            }

        }
        // сохраняю файл 'sitemap.xml' в корне проекта
        $dom->save($_SERVER['DOCUMENT_ROOT'] . PATH . 'sitemap.xml');
    }



}