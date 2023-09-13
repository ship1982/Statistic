<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 09.09.16
 * Time: 0:13
 */
date_default_timezone_set('Europe/Moscow');
/**
 * Set config array.
 *
 * @return array
 */
function config()
{
    $maxProcesses = 15;
    $root = __DIR__ . '/../';
    $stop_server = false;
    $currentJobs = [];
    $pid = __DIR__ . '/pid/dirty.pid';
    $delay = 1;
    $log = 1;

    return [
        'root' => $root,
        'maxProcesses' => $maxProcesses,
        'stop_server' => $stop_server,
        'currentJobs' => $currentJobs,
        'pid' => $pid,
        'delay' => $delay,
        'log'=> $log
    ];
}

/**
 * Daemon work code.
 * This is a user code
 *
 * @param $pid
 */
function work($pid)
{
    global $config;
    include_once (__DIR__ . '/../lib/common/common.php');
    $GLOBALS['conf'] = common_conf();
    include_once (__DIR__ . '/../tests/issue.php');
}

$config = config();

require_once(__DIR__ . '/../lib/daemons/daemons.php');
