<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 31.07.16
 * Time: 19:51
 */

/**
 * Get last used time.
 */
function userInfo_getLastDate()
{
    if(!file_exists(__DIR__ . '/../../config/userInfo/lastTime'))
    {
        include_once (__DIR__ . '/../../lib/error/error.php');
        error_show(1,'userInfo',['line' => __LINE__, 'file' => __FILE__, 'function' => __FUNCTION__]);
    }

    $time = file_get_contents(__DIR__ . '/../../config/userInfo/lastTime');
    /** default date. This date is a time, then project was started */
    if(empty($time))
        $time = 1461628800;

    return $time;
}

function userInfo_getPart($time = 0)
{
    if(empty($time)) return false;
    common_inc('_database');
    query_db($time, 'dirty', '');
}

function userInfo_run()
{
    $lastDate = userInfo_getLastDate();
}