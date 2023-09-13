<?php
session_start();

common_setAloneView('statistic/inc/navbar');

$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
$code = (!$code) ? filter_input(INPUT_POST, 'code', FILTER_SANITIZE_STRING) : $code;
?>
<script>
  var access_token = ''; //Токен доступа
  var refresh_token = ''; //Токен обновления токена доступа =)
  var code = '<?php print($code); ?>';
  //Адрес редиректа
  var redirect_uri = document.location.protocol + '//' + document.location.hostname + document.location.pathname;
  //Если последний символ не / то добавим его
  if (redirect_uri[redirect_uri.length - 1] !== "/") {
    redirect_uri += '/';
  }

  /*
   if (code) {
   //alert('Авторизационный код успешно получен: ' + code_resp);
   console.info('Авторизационный код успешно получен: ', code);
   }
   */

  /**
   * Функция возвращает случайную строку символов
   * @param {int} str_length
   * @return {string}
   */
  function str_rand(str_length) {
    var result = '';
    var words = '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
    var max_position = words.length - 1;
    for (i = 0; i < str_length; ++i) {
      position = Math.floor(Math.random() * max_position);
      result = result + words.substring(position, position + 1);
    }
    return result;
  }

  /**
   * Функция кодирует в BASE64 строку логина и пароля
   * @param {string} user     -   Логин пользователя
   * @param {string} password -   Пароль пользователя
   * @returns {string}
   */
  function make_base_auth(user, password) {
    var tok = user + ':' + password;
    var hash = btoa(tok);
    return "Basic " + hash;
  }

  /**
   * Функция парсит url строку и получает из неё параметры,
   * взята из: http://brentertainment.com/oauth2
   * Возможно пригодится в будущем для парсинга ответов
   * от авторизации по коду, например.
   * @returns {array}
   */
  function get_parse_url() {
    var queryString = window.location.hash.substr(1);
    var queries = queryString.split("&");
    var params = {};
    for (var i = 0; i < queries.length; i++) {
      pair = queries[i].split('=');
      params[pair[0]] = pair[1];
    }

    return params;
  }

  /**
   * Функция выполняет запрос к созданию тестового приложения,
   * если он ещё не был создан.
   * @returns {}
   */
  function create_test_app() {
    $.ajax({
      dataType: 'json',
      async: true,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/createtestapp/",
      success: function (data) {
        console.info('data', data);
      }
    });
  }

  /**
   * Функция переадресовывает на страницу получения авторизационного кода,
   * там пользователь авторизуется, и в случае успешной авторизации,
   * перенаправляется обратно с авторизационным кодом.
   * @param {string} client_id - Идентификатор приложения
   * @param {boolean} sync - Тип синхронности запроса
   * @returns {}
   */
  function get_authorize_code(client_id, sync) {
    document.location = '<?php echo $GLOBALS['conf']['web'] ?>/oauth/authorize_code/?' +
            'response_type=code' +
            '&client_id=' + client_id +
            '&redirect_uri=' + window.redirect_uri +
            '&state=' + str_rand(7);
  }

  /**
   * Функция использует ранее полученный авторизационный код для получения токена,
   * если это удалось, то в переменной access_token, появится токен доступа.
   * @param {string} client_id - Идентификатор приложения
   * @param {string} client_secret - Ключ приложения
   * @param {boolean} sync - Тип синхронности запроса
   * @returns {}
   */
  function get_token_from_authorize_code(client_id, client_secret, sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/gettoken/",
      data: {
        grant_type: 'authorization_code',
        code: window.code,
        redirect_uri: window.redirect_uri
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", make_base_auth(client_id, client_secret));
      },
      success: function (data) {
        console.info('data', data);
        window.access_token = data.access_token;
        window.refresh_token = data.refresh_token;
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  /**
   * Функция получает токен доступа по идентификатору и ключу приложения.
   * Такой подходм может использоваться только для локальных приложений.
   * К примеру для получения статистики, или обновления данных приложения.
   * Тип: client_credentials
   * @param {string} client_id - Идентификатор приложения
   * @param {string} client_secret - Ключ приложения
   * @param {boolean} sync - Тип синхронности запроса
   * @returns {}
   */
  function get_token_for_application(client_id, client_secret, sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/gettoken/",
      data: {
        grant_type: "client_credentials"
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", make_base_auth(client_id, client_secret));
      },
      success: function (data) {
        console.info('data', data);
        window.access_token = data.access_token;
        window.refresh_token = data.refresh_token;
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  /**
   * Функция получает токен доступа по логину и паролю пользователя.
   * Такой подход может использоваться только для локальных пользователей.
   * Тип: user_credentials
   * @param {string} client_id - Идентификатор приложения
   * @param {string} client_secret - Ключ приложения
   * @param {type} user_id - Логин пользователя
   * @param {type} password - Пароль пользователя
   * @param {boolean} sync - Тип синхронности запроса
   * @returns {}
   */
  function get_token_for_user_application(client_id, client_secret, user_id, password, sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/gettoken/",
      data: {
        grant_type: "password",
        client_id: client_id,
        client_secret: client_secret,
        username: user_id,
        password: password
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", make_base_auth(client_id, client_secret));
      },
      success: function (data) {
        console.info('data', data);
        window.access_token = data.access_token;
        window.refresh_token = data.refresh_token;
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  /**
   * Функция выполняет обновление токена
   * @param {string} client_id - Идентификатор приложения
   * @param {string} client_secret - Ключ приложения
   * @param {boolean} sync - Тип синхронности запроса
   * @returns {}
   */
  function set_refresh_token(client_id, client_secret, sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/gettoken/",
      data: {
        grant_type: "refresh_token",
        refresh_token: window.refresh_token
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", make_base_auth(client_id, client_secret));
      },
      success: function (data) {
        console.info('data', data);
        window.access_token = data.access_token;
        window.refresh_token = data.refresh_token;
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  /**
   * Функция выполняет получение доступа к API,
   * технически, если получили токен,
   * то мы можем спокойно использовать
   * его для запросов API, добавляя к запросу параметр access_token
   * @returns {}
   */
  function get_acess_api(sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/getaccessapi/",
      data: {
        access_token: window.access_token
      },
      success: function (data) {
        console.info('data', data);
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  function get_domain(sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    ids = JSON.stringify([0, 3, 59, 359]);
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/sequence/get.domain",
      data: {
        access_token: window.access_token,
        ids: ids
      },
      success: function (data) {
        console.info('data', data);
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  function get_stat_from_sequence(url, sync) {
    sync = typeof sync !== 'undefined' ? sync : true;

    fields = JSON.stringify([
      "utm_term",
      "utm_content",
      "utm_source",
      "utm_medium",
      "utm_campaign"
    ]);

    conditions = JSON.stringify({
      /*
       link_text:{
       type: "regexp",
       value: "mts\.ru"
       },*/
      domain_text: {
        type: "regexp",
        value: "home.mgts\\.ru"
      }/*,
       domain_text:{
       type: "like",
       value: "%lk.%"
       }*/,
      time: {
        type: "between",
        value: '1477953074-1479762000'
      }
    });

    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: url,
      data: {
        access_token: window.access_token,
        time: '1479756756-1479762000 ', //'1477995866',
        /*fields: fields,*/
        conditions: conditions
      },
      success: function (data) {
        console.info('data', data);
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  /**
   * Функция получает токен доступа по логину и паролю пользователя.
   * Такой подход может использоваться только для локальных пользователей.
   * Тип: password
   * @returns {}
   */
  function get_token_for_user_password(username, password, client_id, client_secret, sync) {
    sync = typeof sync !== 'undefined' ? sync : true;
    $.ajax({
      dataType: 'json',
      async: sync,
      method: "POST",
      url: "<?php echo $GLOBALS['conf']['web'] ?>/oauth/gettoken/",
      data: {
        grant_type: "password",
        username: username,
        password: password,
        client_id: client_id,
        client_secret: client_secret
      },
      beforeSend: function (xhr) {
        xhr.setRequestHeader("Authorization", make_base_auth(username, password) /*"Basic " + username + ':' + password*/);
      },
      success: function (data) {
        console.info('data', data);
        access_token = data.access_token;
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  /**
   * Чтобы не авторизовываться каждый раз...
   */
  window.onload = function(){
    get_token_for_user_application('fake', 'fake_secret_pass', 'user2', 'pass2', false);
    $('#tab-01').tab('show')
  };

  function get_list_city(text) {
    //Если длина символов >= трёх, то делаем запрос к данным.
    if (text.value.length > 2) {
      $.ajax({
        dataType: 'json',
        async: true,
        method: "POST",
        url: "<?php echo $GLOBALS['conf']['web'] ?>/sequence/get.listcity/",
        data: {
          access_token: window.access_token,
          string_like: text.value
        },
        success: function (data) {
          console.info('data', data);
        },
        error: function (data) {
          console.info('Ошибка:', data.status);
        }
      });
    } else {
      console.info(text.value);
    }
  }

  function get_list_ips(text) {
    //Если длина символов >= трёх, то делаем запрос к данным.
    if (text.value.length > 2) {
      $.ajax({
        dataType: 'json',
        async: true,
        method: "POST",
        url: "<?php echo $GLOBALS['conf']['web'] ?>/sequence/get.listips/",
        data: {
          access_token: window.access_token,
          string_like: text.value
        },
        success: function (data) {
          console.info('data', data);
        },
        error: function (data) {
          console.info('Ошибка:', data.status);
        }
      });
    } else {
      console.info(text.value);
    }
  }

  function get_ip_to_binary_32(text) {
    if (text.value.length > 3) {
      $.ajax({
        dataType: 'json',
        async: true,
        method: "POST",
        url: "<?php echo $GLOBALS['conf']['web'] ?>/ip_conv_ip_to_binary_32/",
        data: {
          access_token: window.access_token,
          ip: text.value
        },
        success: function (data) {
          console.info('data', data);
        },
        error: function (data) {
          console.info('Ошибка:', data.status);
        }
      });
    } else {
      console.info(text.value);
    }
  }

  function get_user_proprety(uuid) {
    url = '<?php echo $GLOBALS['conf']['web'] ?>/api/v1/api_sequencer.get_user_property';

    $.ajax({
      dataType: 'json',
      async: true,
      method: "POST",
      url: url,
      data: {
        access_token: window.access_token,

        uuid: uuid
      },
      success: function (data) {
        console.info('data', data);
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

  function get_repeate_actions(uuid) {
    url = '<?php echo $GLOBALS['conf']['web'] ?>/api/v1/repeate_actions.get';

    $.ajax({
      dataType: 'json',
      async: true,
      method: "POST",
      url: url,
      data: {
        access_token: window.access_token,
        time_start: 0,        
        time_end: 1493121381,        
        uuid: '0000b0d6ce704151182a75498e0211c3',//uuid,        
        type_access_site: 'for_uuid',        
        ip: '91.76.47.249'
        
      },
      success: function (data) {
        console.info('data', data);
      },
      error: function (data) {
        console.info('Ошибка:', data.status);
      }
    });
  }

</script>

<style>
  body{
    width: 750px;
    margin: 0 auto;
    background: #FFFFFF;
    overflow: auto;
  }
  fieldset{
    -webkit-margin-start: 2px;
    -webkit-margin-end: 2px;
    -webkit-padding-before: 0.35em;
    -webkit-padding-start: 0.75em;
    -webkit-padding-end: 0.75em;
    -webkit-padding-after: 0.625em;
    border-width: 2px;
    border-style: groove;
    border-color: threedface;
    border-image: initial;
  }
  legend{
    text-decoration: underline;
    -webkit-padding-start: 2px;
    -webkit-padding-end: 2px;
    border-width: initial;
    border-style: none;
    border-color: initial;
    border-image: initial;
  }
  h1{
    font-size: 1.5em;
    -webkit-margin-before: 0.83em;
    -webkit-margin-after: 0.83em;
    display: block;
    -webkit-margin-start: 0px;
    -webkit-margin-end: 0px;
    font-weight: bold;
  }
  .hr_bold {
    border: none; /* Убираем границу */
    background-color: #0076ba; /* Цвет линии */
    color: #0076ba; /* Цвет линии для IE6-7 */
    height: 4px; /* Толщина линии */
   }
</style>

<div>
  <!-- Навигация -->
  <ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#api_buttons" aria-controls="api_buttons" role="tab" data-toggle="tab">API запросы кнопками</a></li>
    <li><a href="#api_text" aria-controls="api_text" role="tab" data-toggle="tab">API с текстовыми полями</a></li>
    <li><a href="#oauth_type" aria-controls="oauth_type" role="tab" data-toggle="tab">Виды OAUTH авторизации</a></li>
  </ul>
  <!-- Содержимое вкладок -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane fade in active" id="api_buttons">
      <fieldset>
        <legend>API запросы кнопками:</legend>
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Запрос к API:
          </h1>
          <p>
            <strong>Описание:</strong>
            Запрос к API, с использованием полученного токена выше приведёнными вариантами
          </p>
          <p>
            <input type="button" value="Отправить запрос к API." onclick="get_acess_api(false);" />
          <p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Получить домены:
          </h1>
          <p>
            <strong>Описание:</strong>
            Передаём токен и json массив с идентификаторами
          </p>
          <p>
            <input type="button" value="Получить домены." onclick="get_domain(true);" />
          <p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Получить статистику:
          </h1>
          <p>
            <strong>Описание:</strong>
            Передаём токен и json с параметрами
          </p>
          <p>
            <input type="button" value="Получить статистику." onclick="get_stat_from_sequence('<?php echo $GLOBALS['conf']['web'] ?>/api/v1/api_sequencer.get', true);" />
          </p>
        </section>
        <br />
        <hr class="hr_bold" />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Прочие API функции:
          </h1>
          <p>
            <strong>Описание:</strong>
            Прочие API функции для отладки
          </p>
          <hr />
          <p>
            <input type="button" value="Получить статистику из класса." onclick="get_stat_from_sequence('<?php echo $GLOBALS['conf']['web'] ?>/api/v1/api_sequencer.get', true);" />
          </p>
          <hr />
          <p>
            <input type="button" value="Получить данные о пользователе." onclick="get_user_proprety('00019b6b55efff90315b2ed2a3c06c97');" />
          </p>
          <hr />
          <p>
            <input type="button" value="Получить данные о повторяющихся действиях." onclick="get_repeate_actions('03C6E20A13C3DE5869222C1F022F2705');" />
          </p>
        </section>
      </fieldset>
    </div>
    <div role="tabpanel" class="tab-pane fade" id="api_text">
      <fieldset>
        <legend>API с текстовыми полями:</legend>
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Получить список городов:
          </h1>
          <p>
            <strong>Описание:</strong>
            Возвращает список городов по набранным символам.
            Поиск начинается от трёх символов.
          </p>
          <p>
            <label for="get_list_city">Город</label>
            <input type="text" name="get_list_city" id="get_list_city" onkeyup="get_list_city(this)" />
          </p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Получить список провайдеров:
          </h1>
          <p>
            <strong>Описание:</strong>
            Возвращает список провайдеров по набранным символам.
            Поиск начинается от трёх символов.
          </p>
          <p>
            <label for="get_list_ips">Провайдер</label>
            <input type="text" name="get_list_ips" id="get_list_ips" onkeyup="get_list_ips(this)" />
          </p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Получить расширенный ip адрес:
          </h1>
          <p>
            <strong>Описание:</strong>
            Возвращает расширенный адрес для сокращенного.
          </p>
          <p>
            <label for="get_list_ips">IP адрес</label>
            <input type="text" name="get_ip_to_binary_32" id="get_ip_to_binary_32" onkeyup="get_ip_to_binary_32(this)" />
          </p>
        </section>
      </fieldset>
    </div>
    <div role="oauth_type" class="tab-pane fade" id="oauth_type">
      <fieldset>
        <legend>Виды авторизации:</legend>

        <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
        <input type="button" value="Создать тестовое приложение." onclick="create_test_app();" />
        - нужна для создания первого приложения в системе.
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Авторизация по авторизационному коду:
          </h1>
          <p>
            <strong>Описание:</strong>
            Используется, когда клиент хочет запросить доступ к защищенным ресурсам от имени другого пользователя.
          </p>
          <p>
            <strong>Случаи использования:</strong>
            Выполнение запросов от третьего лица.<br />
            Также нужно учитывать: как только мы получили токен по авторизационному коду,
            то он тот час же становится недействительным.
          </p>
          <p>
            <input type="button" value="Получить код для sequencer." onclick="get_authorize_code('sequencer', 'sequence_pass', false);" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="button" value="Получить токен для sequencer." onclick="get_token_from_authorize_code('sequencer', 'sequence_pass', false);" />
          </p>
          <hr align="left" width="500" size="4" color="#ff9900" /><br >
          <p>
            <input type="button" value="Получить код для top_detalizer." onclick="get_authorize_code('top_detalizer', 'top_detalizer_pass', false);" />
            &nbsp;&nbsp;
            <input type="button" value="Получить токен для top_detalizer." onclick="get_token_from_authorize_code('top_detalizer', 'top_detalizer_pass', false);" />
          </p>
          <hr align="left" width="500" size="4" color="#ff9900" /><br >
          <p>
            <input type="button" value="Получить код к новому client_id." onclick="get_authorize_code('0ed1c5a6dce4edcd6bf9ccb4dd4ad6980b926d29', '9ccb4dd4ad6980b926d290ed1c5a6dce4edcd6bf', false);" />
            &nbsp;&nbsp;
            <input type="button" value="Получить токен к новому client_id." onclick="get_token_from_authorize_code('0ed1c5a6dce4edcd6bf9ccb4dd4ad6980b926d29', '9ccb4dd4ad6980b926d290ed1c5a6dce4edcd6bf', false);" />
          </p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Авторизация для приложения:
          </h1>
          <p>
            <strong>Описание:</strong>
            Приоритетный тип прав, ипользования приложения, когда приложение запрашивает доступ к своим защищённым ресурсам (т.е. нет посредника, третьего лица).
          </p>
          <p>
            <strong>Случаи использования:</strong>
            Служебное использование.<br />
            Использование от имени пользователя, который создал это приложение.
          </p>
          <p>
            <input type="button" value="Отправить запрос токена методом авторизации приложением." onclick="get_token_for_application('fake', 'fake_secret_pass', false);" />
          </p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод в разработке" title="Метод в разработке" />
            Авторизация пользователя приложения:
          </h1>
          <abbr>
            Выяснилось, что пароль нужно хранить в sha1 хеше,
            т.к. oAuth хэширует полученный пароль от пользователя в этот вид хэша исравнивает с записью в БД.
          </abbr>
          <p>
            <br /><strong>Описание:</strong>
            Пользовательский тип авторизации исапользуется, когда пользователь имеет доверенную связь с приложением,
            и именно так может указать свои учётные данные.
            <br />
            Если приложение общедоступно, т.е. не закреплено за конкретным пользователем,<br />
            то в запросе можно опустить поле client_secret.
          </p>
          <p>
            <strong>Случаи использования:</strong>
            Когда приложение требует вывода формы входа в систему.<br />
            Для приложений, управляемых сервером ресурсов (Мобильные и настольные приложения).<br />
            Для приложений, не имеющих доступ к прямой аутентификации и хранению учётных данных.<br />
          </p>
          <p>
            <input type="button" value="Отправить запрос токена методом авторизации пользователя приложения." onclick="get_token_for_user_application('fake', 'fake_secret_pass', 'user2', 'pass2', false);" />
          </p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/delete.png" height="15" width="15" alt="Метод в разработке" title="Метод в разработке" />
            Неявная авторизация:
          </h1>
          <abbr>(Не применяется - в библиотеке найден только ImplicitTest, который явно используется лишь для отладочных целей,
            поэтому пока не рассматривается).
          </abbr>
          <p>  
            <br /><strong>Описание:</strong>
            Неявный тип авторизации подобен авторизации по коду, для доступа к защищённым ресурсам от имени другого пользователя (т.е. третьего лица).
            <br />
            Этот тип оптимизирован для публичных клиентов, таких как javascript, или мобильные устрйства, где данные доступа не могут хранится.
          </p>
          <p>
            <strong>Случаи использования:</strong>
            Запросы от третьих лиц.<br />
            Для браузерных приложений типа (javascript).<br />
            Для частных запросов (настольные и мобильные устройства).<br />
            Для любых запросов, где данные доступа не могут хранится.
          </p>
          <p>
            <!--input type="button" value="Отправить запрос токена методом нявной авторизации." onclick="" />-->
          </p>
        </section>
        <hr />
        <section>
          <h1>
            <img src="../../bundles/img/ok.png" height="15" width="15" alt="Метод проработан" title="Метод проработан" />
            Обновление токена:
          </h1>
          <p>
            <strong>Описание:</strong>
            Выполняет обновление текущего токена.<br />
            Данная функция доступна только в том случае, если выполнялась авторизация по коду (1-й тип),
            либо авторизация пользователя сервера.
          </p>
          <p>
            <input type="button" value="Обновить токен." onclick="set_refresh_token('fake', 'fake_secret_pass', false);" />
          <p>
        </section>
      </fieldset>
    </div>
  </div>
  <!-- Навигация -->
  <ul class="nav nav-tabs" role="tablist">
    <li class="active"><a href="#api_buttons" aria-controls="api_buttons" role="tab" data-toggle="tab">API запросы кнопками</a></li>
    <li><a href="#api_text" aria-controls="api_text" role="tab" data-toggle="tab">API с текстовыми полями</a></li>
    <li><a href="#oauth_type" aria-controls="oauth_type" role="tab" data-toggle="tab">Виды OAUTH авторизации</a></li>
  </ul>
</div>
