<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 22.04.16
 * Time: 13:08
 */

session_start();

/**
 * Check authorization of user.
 * If user is auth, then return true
 *
 * @return bool
 */
function auth_is()
{
    if(empty($_SESSION['auth']['login']) || empty($_SESSION['auth']['hash']))
        return false;
    else
    {
        $users = $GLOBALS['conf']['user'];
        $login = $_SESSION['auth']['login'];
        $hash = $_SESSION['auth']['hash'];
        if(!empty($users[$login])
            && $hash == md5($users[$login]['pass'] . $users[$login]['hash'])
        )
        {
            $_SESSION['auth'] = [
                'login' => $login,
                'hash' => $hash
            ];

            return true;
        }
        else
            return false;
    }
}

/**
 * Login user.
 * Check user in configuration file (/config/user/user.php)
 * and get array of configuration if user is.
 * If hash by config file not equal md5(filled password . config hash) then return false
 * else set hash and login by session array and return true
 *
 * @param bool $login
 * @param bool $pass
 * @return bool|int
 */
function auth_login($login = false, $pass = false)
{
    if(!$login || !$pass) return false;
    common_inc('user');

    $user = user_get($login);

    if(false == $user){
        $_SESSION['auth'] = '';
        return false;
    }

    if(md5(trim($pass) . $user['hash']) != md5($user['pass'] . $user['hash'])){
        $_SESSION['auth'] = '';
        return false;
    }

    $_SESSION['auth'] = [
        'login' => $login,
        'hash' => md5($pass . $user['hash'])
    ];

    return true;
}

/**
 * Logout user.
 * 
 * @param bool $login
 * @return bool
 */
function auth_logout($login = false)
{
    $_SESSION['auth'] = '';
    return true;
    
    if(!$login) return false;
    if($_SESSION['auth']['login'] != $login) return false;
    $_SESSION['auth'] = [
        'login' => '',
        'hash' => ''
    ];
    return true;
}