<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 07.07.16
 * Time: 23:38
 */

/**
 * Show errors and stop execution.
 *
 * @param bool $code - error code
 * @param bool $moduleName - name of module, which evoke error
 * @param array $params - array with __LINE__, __FILE__, __FUNCTION__ variables
 * @return bool
 */
function error_show($code = false, $moduleName = false, $params = [])
{
    if(empty($code) || empty($moduleName)) return true;
    if(empty($params['line'])
        || empty($params['file'])
        || empty($params['function'])
    )
    {
        exit('Неправильный вызов ошибки!');
    }
    $message = [];
    if(file_exists(__DIR__ . '/../../errors/' . $moduleName . '/Message.php'))
        $message = require(__DIR__ . '/../../errors/' . $moduleName . '/Message.php');

    $str = $message[$code] ? $message[$code] : false;
    debug_print_backtrace();
    echo "\n\n" . error_setByTemplate($str, $params) . "\n\n";
    exit;
}

/**
 * Return a formatted string of error.
 * Replace #line#' by __LINE__, '#file#' by __FILE__, '#function# by __FUNCTION__
 *
 * @param string $str not formatted string form module Message.php
 * @param array $params @see error_show $params
 * @return mixed|string
 */
function error_setByTemplate($str = '', $params = [])
{
    if(empty($str)) return '';
    return str_replace(['#line#', '#file#', '#function#'], $params, $str);
}