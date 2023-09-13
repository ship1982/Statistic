<?php

$layout = 'default';


/**
 * Отображает вьюху, и если там переданы нужные параетры,
 * то возвращает на страницу redirect_uri
 */
function GetAuthorizationCode()
{
  common_inc('oauth');
  $oAuth2 = init_oauth();

  common_inc('auth');
  if (!auth_is())
  {
    // header("Location: /oauth/authorize_code");
    common_setView(
      'oauth/authorize_code', [
          'oauth_wrapper' => $oAuth2,
          'server' => $oAuth2->server,
          'request' => $oAuth2->get_request(),
          'response' => $oAuth2->get_response()
    ]);
  }
  else
  {
    common_setAloneView(
      'oauth/authorize_code', [
        'oauth_wrapper' => $oAuth2,
        'server' => $oAuth2->server,
        'request' => $oAuth2->get_request(),
        'response' => $oAuth2->get_response()
    ]);
  }
}

/**
 * Создаёт тестовое приложение в таблице oauth_clients,
 * для проверки работы oAuth
 * @return string
 */
function CreateTestAppOAuthController() {
    //Создадим тестового клиента
    $insert = [
        'client_id' => 'testclient',
        'client_secret' => 'testpass',
        'redirect_uri' => 'http://fake/'
    ];

    common_inc('_database');

    $sql = "INSERT INTO
                oauth_clients (client_id,client_secret,redirect_uri)
            VALUES
                ('testclient','testpass','http://fake/')
            ON DUPLICATE KEY UPDATE
                client_secret='testpass', redirect_uri='http://fake/34'";

    $result_ins = multyQuery_db(1, 'oauth_clients', $sql);

    print json_encode([
            'Статус добавления приложения в БД:', $result_ins
        ], JSON_UNESCAPED_UNICODE);
}

function GetToken() {
    //Подключаем модель
    common_inc('oauth');

    //Инициируем oAuth
    $oAuth2 = init_oauth();

    //Создаём и возвращаем токен контроллера
    $oAuth2->create_token_controller();
}

function GetAccessAPI() {
    //Подключаем модель
    common_inc('oauth');

    //Инициируем oAuth
    $oAuth2 = init_oauth();

    $oAuth2->create_resource_controller();
}