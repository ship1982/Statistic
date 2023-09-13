<?php

// session_start();

$oauth_wrapper = $params['oauth_wrapper'];
$server = $params['server'];
$request = $params['request'];
$response = $params['response'];

/**
 * Получаем параметры, либо из $_POST либо из $_GET
 */
$client_id = filter_input(INPUT_GET, 'client_id', FILTER_SANITIZE_STRING);
$client_id = (!$client_id) ? filter_input(INPUT_POST, 'client_id', FILTER_SANITIZE_STRING) : $client_id;

$response_type = filter_input(INPUT_GET, 'response_type', FILTER_SANITIZE_STRING);
$response_type = (!$response_type) ? filter_input(INPUT_POST, 'response_type', FILTER_SANITIZE_STRING) : $response_type;

$redirect_uri = filter_input(INPUT_GET, 'redirect_uri', FILTER_SANITIZE_STRING);
$redirect_uri = (!$redirect_uri) ? filter_input(INPUT_POST, 'redirect_uri', FILTER_SANITIZE_STRING) : $redirect_uri;

$state = filter_input(INPUT_GET, 'state', FILTER_SANITIZE_STRING);
$state = (!$state) ? filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING) : $state;


$authorized = filter_input(INPUT_GET, 'authorized', FILTER_SANITIZE_STRING);
$authorized = (!$authorized) ? filter_input(INPUT_POST, 'authorized', FILTER_SANITIZE_STRING) : $authorized;

// validate the authorize request
// Проверка авторизации запроса
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}

// Отображаем форму авторизации
/**
 * Я так полагаю что здесь должна быть форма ввода логина и пароля,
 * и если всё ок, то тогда генерируем код авторизации для получения токена
 */
common_inc('auth');
if (!auth_is()) {
//    print('Вы неавторизованы');
    $param = [
        'redirect_view' => 'oauth/authorize_code',
        'client_id' => $client_id,
        'response_type' => $response_type,
        'redirect_uri' => $redirect_uri,
        'state' => $state
    ];
    common_setView('auth/main', [
        'param_authorize_code' => $param
    ]);
} else {
//    print('Вы авторизованы');
    $is_authorized = ($authorized === 'Разрешить');
    if (empty($authorized)) {
        exit('
<style>
    form{
        width: 450px;
        margin: 150px auto;
    }
</style>
<form method="post">
  <label>Вы действительно желаете дать доступ к приложению \'' . $client_id . '\'?</label><br />
  <input type="submit" name="authorized" value="Разрешить">
  <input type="submit" name="authorized" value="Не разрешать">
</form>');
    } else {
        /**
         * Проверяем доступ пользователя к приложению
         */
        $login = (!empty($_SESSION['auth']['login'])) ? ($_SESSION['auth']['login']) : '';
        common_inc('_database');

        $is_exist_clien_id_from_user = select_db(
                1,
                'oauth_rules',
                ['client_id'],
                [
                    'client_id' => $client_id,
                    'username' => $login
                ]
            );

        $is_exist_redirect_uri_from_user = select_db(
                1,
                'oauth_users',
                ['redirect_uri'],
                [
                    'redirect_uri' => parse_url($redirect_uri, PHP_URL_HOST),
                    'username' => $login
                ]
            );

        /**
         * Если найдено соответствие приложение и логина,
         * а также соответствие ссылки перенаправления и логина,
         * то генерируем авторизационный код, иначе вернём соответствующую ошибку.
         */
        $error = '';

        if (!$is_exist_clien_id_from_user || $is_exist_clien_id_from_user->num_rows <= 0) {
            $error .= 'The+user+has+no+access+to+application';
        }

        if (!$is_exist_redirect_uri_from_user || $is_exist_redirect_uri_from_user->num_rows <= 0) {
            $error .= ((!empty($error)) ? '&' : '') . 'Redirect+uri+does+not+belong+to+the+user';
        }

        //Если ошибок нет, то генерируем авторизационный код
        if (empty($error)) {
            $oauth_wrapper->create_token_controller_from_authorize_code($redirect_uri, $is_authorized);
        } else {
            //Иначе вернём соответствующую ошибку
            header('Location: ' . $redirect_uri . '?error=' . $error);
        }
    }
}
die;