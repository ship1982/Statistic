<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 02.05.16
 * Time: 23:00
 */

include_once (__DIR__ . '/../lib/common/common.php');
$GLOBALS['conf'] = common_conf();

/**
 * remove error log form nginx
 */
function removeErrorLog()
{
    if(file_exists($GLOBALS['conf']['log_dir']))
    {
        $files = scandir($GLOBALS['conf']['log_dir']);
        for($i = 0; $i < $ic = count($files); $i++)
        {
            if($files[$i] == '.' || $files[$i] == '..') continue;
            if(strpos($files[$i], 'pixel_error_') !== false)
            {
                if(filesize($GLOBALS['conf']['log_dir'] . '/' . $files[$i]) == 0)
                    unlink($GLOBALS['conf']['log_dir'] . '/' . $files[$i]);
                else
                    common_appLog(__FILE__, __LINE__, __FUNCTION__, 'error in error log by nginx');
            }
        }
    }
}

/** start */
removeErrorLog();