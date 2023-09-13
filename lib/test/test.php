<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 10.07.16
 * Time: 15:06
 */

/**
 * Call $function with $params and check result with $success.
 * Result is a printed message.
 *
 * @param string $function
 * @param array $params
 * @param $success
 * @return bool
 */
function TestGetIteration($function = '', $params = [], $success)
{
    if(empty($function))
    {
        include_once (__DIR__ . '/../error/error.php');
        error_show(1, 'test', [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]);
    }

    $res = call_user_func_array($function, $params);
    $message = TestShowResult($res, $success, $function);
    echo $message;
    return true;
}

/**
 * Print message.
 *
 * @param $res - result @see TestGetIteration() call_user_func_array
 * @param $success - needed result (true result).
 * @param $function - function name @see TestGetIteration()
 * @return string
 */
function TestShowResult($res, $success, $function)
{
    if($res == $success)
        return "\n$function return $res. This is done!\n";
    else
        return "\n$function return $res. This is error!\n";
}

/**
 * Check modules for testing. If exists, then run it, else - ignored.
 *
 * @param array $modules - array of modules.
 * @return void
 */
function TestGetModules($modules = [])
{
    if(is_array($modules))
    {
        foreach ($modules as $module)
        {
            if(!file_exists(__DIR__ . '/../../tests/' . $module . '.php'))
            {
                include_once (__DIR__ . '/../error/error.php');
                error_show(2, 'test', [
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'function' => __FUNCTION__
                ]);
            }
            
            include_once (__DIR__ . '/../../tests/' . $module . '.php');
        }
    }
}

function TestGetFunction()
{

}

function TestRun()
{

}