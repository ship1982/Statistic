<?php

/**
 * Обёртка над oAuth
 */
class oAuthWrapper {

    //PDO параметры БД
    private $db_dsn = '';
    private $db_username = '';
    private $db_password = '';
    //Путь к автолоадеру oAuth
    private $path_oauth_autoloader = '';
    private $storage = null;
    //Конфигурация соединения с oAuth сервером
    private $config = [
        'allow_credentials_in_request_body' => true, //Запрещаем авторизацию в теле запроса, пускай она будет только в заголовках HTTP
        'always_issue_new_refresh_token' => true, //Разрешить использовать обновлённый токен
        'refresh_token_lifetime' => 2419200, //Время жизни обновлённого токена: 28 дней о_О
        'allow_implicit' => true, //Разрешаем неявное получение доступа
        'access_lifetime' => 3600// * 24 * 7 * 3
    ];
    private $request = null;
    private $response = null;
    public $server = null;

    /**
     * Инициализация oAuth сервера
     * @param string|array $arr_pdo['dsn'=>'','username'=>'','password'=>'']
     * @param string $path_autoloader
     */
    public function __construct($arr_pdo = '', $path_autoloader = '') {
        $db_param = $this->convert_var_to_object($arr_pdo);
        $this->db_dsn = (!empty($db_param['dsn'])) ? filter_var($db_param['dsn'], FILTER_SANITIZE_STRING) : '';
        $this->db_username = (!empty($db_param['username'])) ? filter_var($db_param['username'], FILTER_SANITIZE_STRING) : '';
        $this->db_password = (!empty($db_param['password'])) ? filter_var($db_param['password'], FILTER_SANITIZE_STRING) : '';
        $this->path_oauth_autoloader = filter_var($path_autoloader, FILTER_SANITIZE_STRING);

        /**
         * Автозагрузка библиотеки и её регистрация
         */
        //Если файла нет, то вернём ошибку
        if (!is_file($this->path_oauth_autoloader)) {
            exit('Не найден путь к автолоадеру библиотеки');
        }
        require_once($this->path_oauth_autoloader);
        OAuth2\Autoloader::register();

        //Определяем request и response
        $this->request = OAuth2\Request::createFromGlobals();
        $this->response = new OAuth2\Response();

        //Инициализация соединения с БД по средствам PDO в виде: "mysql:dbname=my_oauth2_db;host=localhost"
        $this->storage = new OAuth2\Storage\Pdo([
            'dsn' => $this->db_dsn,
            'username' => $this->db_username,
            'password' => $this->db_password
        ]);

        //Передаём объект соединения с БД классу сервера OAuth2
        $this->server = new OAuth2\Server($this->storage, $this->config);

        /**
         * Если типы авторизации не указаны,
         * то применяются все возможные.
         */
        //Добавляем тип предоставления "Код авторизации", написали что тут происходит волшебство =)
        //Запрос от имени третьего лица
        $this->server->addGrantType(new OAuth2\GrantType\AuthorizationCode($this->storage));

        //Добавляем тип предоставления "Удостоверение пользователя приложения"
        //(Авторизация локального пользователя по логину и паролю, с ипсользованием идентификатора приложения и его секретного ключа)
        $this->server->addGrantType(new OAuth2\GrantType\UserCredentials($this->storage));

        //Добавляем тип предоставления "Удостоверение приложения"
        //(Авторизация локального приложения по идентификатору приложения и его секретному ключу)
        $this->server->addGrantType(new OAuth2\GrantType\ClientCredentials($this->storage));

        //Добавляем тип предоставления "Неявная авторизация"
        //$this->server->addGrantType(new OAuth2\GrantType\ImplicitTest($this->storage));
        //Добавляем тип предоставления "Обновление токена"
        //(Даёт возможностьобновлять токен по истечению его срока действия)
        $this->server->addGrantType(new OAuth2\GrantType\RefreshToken($this->storage));
    }

    /**
     * Возвращает объект запроса к серверу
     * @return object
     */
    function get_request() {
        return $this->request;
    }

    /**
     * Возвращает объект результата к серверу
     * @return object
     */
    function get_response() {
        return $this->response;
    }

    /**
     * Создаём диспетчер токенов.
     */
    public function create_token_controller() {
        //Обработка запроса для маркера доступа и возврат ответа клиенту
        $this->server->handleTokenRequest($this->request)->send();
    }

    /**
     * Возвращает токен по авторизационному коду,
     * если авторизация прошла успешно.
     * @param string $redirect_uri
     * @param boolean $is_authorized
     */
    public function create_token_controller_from_authorize_code($redirect_uri, $is_authorized) {
        //Фильтруем
        $redirect_uri = filter_var($redirect_uri);
        $is_authorized = (bool) filter_var($is_authorized, FILTER_SANITIZE_URL);

        // validate the authorize request
        // Проверка авторизации запроса

        if (!$this->server->validateAuthorizeRequest($this->request, $this->response)) {
            $this->response->send();
            die;
        }
        $this->server->handleAuthorizeRequest($this->request, $this->response, $is_authorized);

        if ($is_authorized) {
            // this is only here so that you get to see your code in the cURL request. Otherwise, we'd redirect back to the client
            $code = substr(
                    $this->response->getHttpHeader('Location'), strpos($this->response->getHttpHeader('Location'), 'code=') + 5, 40);
            /**
             * Если всё хорошо то переходим обратно на страницу redirect_uri,
             * и возвращаем в неё полученный код
             */
            header('Location: ' . $redirect_uri . '?code=' . $code);
        } else {
            header('Location: ' . $redirect_uri . '?error=' . $this->response->send());
        }
        $this->response->send();
    }

    /**
     * Создаём диспетчер ресурсов
     */
    public function create_resource_controller() {
        //Обработка запроса к ресурсу и подтверждение подлинности токена доступа
        if (!$this->server->verifyResourceRequest($this->request)) {
            $this->response->send();
            die;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Вы получили доступ к API!'
                ], JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Метод остановит выполнение скрипта, в случае,
     * если oAuth авторизация не прошла успешно.
     */
    public function access_api(){
        if(!$this->test_access_api()){
            echo json_encode(['status' => 'error', 'description' => 'No token or Error access tokken'], JSON_UNESCAPED_UNICODE);
            die;
        }
    }

    /**
     * Метод проверяет oAuth авторизацию,
     * и возвращает истину, в случае успеха.
     * @return boolean
     */
    public function test_access_api() {
        //Обработка запроса к ресурсу и подтверждение подлинности токена доступа
        if (!$this->server->verifyResourceRequest($this->request)) {
            return false;
        }
        return true;
    }

    /**
     * Метод выполняет конвертацию входной переменной
     * из строки в элементы массива,
     * и в случае необходимости, возвращает объект.
     * Если на входе был уже передан массив,
     * то он может быть преобразован в объект, с помощью ключа to_object
     * Если на входе был уже передан объект,
     * то он может быть преобразован в массив, с помощью отрицания ключа to_object
     * пример: 'val1,val2,val3' => [val1, val2, val3]
     * пример: 'val=1,val2=2,val3=3' => [val1 => 1, val2 => 2, val3=4]
     * 
     * @param string|array|object $variable - Входная переменная, если уже передан массив, то
     * @param string $separate - разделитель, строки на элементы
     * @param string $assoc_separate - разделитель элементов на ассоциативные
     * @param boolean $to_object - ключ: преобразовать массив в объект
     */
    public function convert_var_to_object($variable, $separate = ',', $assoc_separate = '', $to_object = false) {
        $result_variable = $variable;
        $result_assoc_variable = [];
        switch ($variable) {
            case is_string($variable):
                $result_variable = array_map('trim', explode($separate, $variable));
                //Если указан сепаратор для разбиения на ассоциативный массив
                if (!empty($assoc_separate)) {
                    foreach ($result_variable as $val) {
                        $break_explode = array_map('trim', explode($assoc_separate, $val));
                        //Если удалось разбить строку
                        if (!empty($break_explode[0]) && !empty($break_explode[1])) {
                            $result_assoc_variable[$break_explode[0]] = $break_explode[1];
                        } else {
                            $result_assoc_variable[] = $val;
                        }
                    }
                    $result_variable = ($to_object) ? (object) $result_assoc_variable : $result_assoc_variable;
                }
                break;
            //Массив в объект
            case (is_array($variable) && $to_object):
                $result_variable = (object) $variable;
                break;
            //Объект в массив
            case is_object($variable && !$to_object):
                break;
        }
        return $result_variable;
    }

    /**
     * Метод преобразует ассоциативный массив или объект
     * в строку вида: ?data1=value1&data2=value2
     * @param string|array|object $data
     */
    public function convert_object_to_url_param($data = '') {
        /**
         * Если данные переданы в виде ассоциативного массива или объекта,
         * то генерируем из неё строку в формате ?data1=value1&data2=value2.
         * Если это строка, то просто фильтруем её как строку.
         * В других случаях будем использовать пустую строку вместо данных.
         */
        if (is_string($data)) {
            $data = filter_var($address, FILTER_SANITIZE_STRING);
        } elseif (is_array || is_object) {
            $data_temp = '?';
            foreach ($data as $key => $val) {
                $data_temp .= $key . '=' . $val . '&';
            }
            //Убираем последний символ, т.к. это &
            mb_substr($data_temp, 0, -1);
            $data = $data_temp;
        } else {
            $data = '';
        }
        return $data;
    }

    /**
     * Функция возвращает результат cURL запроса
     * @param string $url
     * @param string|array $post
     * @param strung $userpwd
     * @param string|array $cookie
     * @return string
     */
    function get_web_page($url, $post = '', $userpwd = '', $cookie = '') {

        $uagent = "Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.14";

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   // возвращает веб-страницу
        //Если POST данные переданы
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, true);
            //Если массивом данные передали
            if (is_array($post)) {
                $post_query = '';
                $count_post_field = count($post);
                $counter = 0;
                foreach ($post as $post_key => $post_val) {
                    $counter ++;
                    $post_query .= $post_key . $post_val;
                    //у прдпоследнего параметра символ & ставить не нужно
                    if ($counter < $count_post_field - 1) {
                        $post_query . '&';
                    }
                }
            } else {
                $post_query = $post;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_query);
        }
        //Если указа пароль, то применяем его
        if (!empty($userpwd)) {
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        }
        //Если POST данные переданы
        if (!empty($cookie)) {
            //Если массивом данные передали
            if (is_array($cookie)) {
                $cookie_query = '';
                $count_post_field = count($cookie);
                $counter = 0;
                foreach ($cookie as $cookie_key => $cookie_val) {
                    $counter ++;
                    $cookie_query .= $cookie_key . $cookie_val;
                    //у прдпоследнего параметра символ & ставить не нужно
                    if ($counter < $count_post_field - 1) {
                        $cookie_query . '&';
                    }
                }
            } else {
                $cookie_query = $cookie;
            }
            curl_setopt($curl, CURLOPT_COOKIE, $cookie_query);
        }

        curl_setopt($ch, CURLOPT_HEADER, 0);           // не возвращает заголовки
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);   // переходит по редиректам
        curl_setopt($ch, CURLOPT_ENCODING, "");        // обрабатывает все кодировки
        curl_setopt($ch, CURLOPT_USERAGENT, $uagent);  // useragent
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);        // таймаут ответа
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);       // останавливаться после 10-ого редиректа


        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);

        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;

        return $header;
    }

    /*
     * Выполнение cURL запроса, с помощью функции get_web_page
      //Делаем запрос к странице
      $result = get_web_page('http://mia.mgts.zionec.ru/oauthtest',['grant_type' => 'client_credentials'], 'testclient:testpass ');

      if (($result['errno'] != 0 ) || ($result['http_code'] != 200)) {
      $page = $result['errmsg'];
      } else {
      $page = $result['content'];
      }

      common_pre($page);
     */
}

function init_oauth() {
    if (is_file(__DIR__ . '/../../lib/common/common.php')) {

        include_once(__DIR__ . '/../../lib/common/common.php');

        //Инициализируем объект oAuth2
        return new oAuthWrapper(
                [
            'dsn' => $GLOBALS['conf']['oauth']['dsn'],
            'username' => $GLOBALS['conf']['oauth']['username'],
            'password' => $GLOBALS['conf']['oauth']['password']
                ], $GLOBALS['conf']['oauth']['path_autoloader']
        );
    } else {
        return null;
    }
}

/**
 * Применение класса:
 * 
 * Использование фильтра convert_var_to_object:
 * 

  $oAuth2 = new oAuthWrapper();
  print('<pre>');
  print_r($oAuth2->convert_var_to_object('val1,val2,val3',',','=',false));
  print_r($oAuth2->convert_var_to_object('val1= 1,val2= 2,val3=3',',','=',false));
  print_r($oAuth2->convert_var_to_object('val1=1&val2=2&val3=3','&','=',false));
  print_r($oAuth2->convert_var_to_object('val1= 1&val2=2&val3=3','&','=',false));
  print_r($oAuth2->convert_var_to_object('val1 =1&val2=2&val3=3','&','=',false));
  print_r($oAuth2->convert_var_to_object('val1=1,val2= 2,val3=3',',','',false));
  print_r($oAuth2->convert_var_to_object('val1=1,val2= 2,val3=3',',','',false));
  print_r($oAuth2->convert_var_to_object('val1 =1,val2=2,val3=3',',','=',true));
  print('</pre>');
 */