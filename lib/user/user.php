<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 22.04.16
 * Time: 13:09
 */

/**
 * Get user from conf array.
 *
 * @param bool $login
 * @return array|bool
 */
function user_get($login = false)
{
    if(!$login) return false;
    $conf = $GLOBALS['conf']['user'];
    if(empty($conf[$login]) && empty($conf[$login]['pass']) && empty($conf[$login]['hash'])) return false;
    return [
        'login' => $login,
        'hash' => $conf[$login]['hash'],
        'pass' => $conf[$login]['pass']
    ];
}

function user_set()
{

}

function user_delete()
{

}