<?php

/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 10:56
 */
$layout = 'default';

/**
 * If user is auth, then go to /maindomain.
 * If user not auth, try to auth and if success go to /maindomain,
 * else print error.
 *
 * @return string - template
 */
function AuthController() {
    $post = common_prepareRequest();
    
    common_inc('auth');
    if (auth_is()) {
        //header('Location: /maindomain');
        redirect_after_auth($post);
    }

    $loginError = [];
    if (!empty($post)) {
        if (auth_login($post['login'], $post['password']))
            //header('Location: /maindomain');
            redirect_after_auth($post);
        else
            $loginError[] = 'Неправильные логин\пароль.';
    }

    return common_setView('auth/main', ['error' => $loginError]);
}

function redirect_after_auth($param) {
    //Если передан параметр переадресации с нужными ключами,
    //то переадресуем на страницу $params['param_authorize_code']['redirect']
    if (!empty($param['redirect_view'])) {
        header('Location: /'.$param['redirect_view'].
                "?response_type={$param['response_type']}&client_id={$param['client_id']}&redirect_uri={$param['redirect_uri']}&state={$param['state']}");

    } else {
        header('Location: /maindomain');
    }
}

/**
 * User logout.
 * If user is not auth, then go to /.
 * If user auth, then check referrer and go to referrer.
 * If referrer is empty then go to main page.
 */
function AuthLogoutController() {
    common_inc('auth');
    if (!auth_is())
        header('Location: /');

    if (auth_logout($_SESSION['auth']['login'])) {
        if (empty($_SERVER['HTTP_REFERRER'] || $_SERVER['HTTP_REFERRER'] == $_SERVER['REQUEST_URI'])
        )
            header('Location: /');
        else
            header('Location: ' . $_SERVER['HTTP_REFERRER']);
    }
}
