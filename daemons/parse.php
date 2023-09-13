<?php

/**
 * Set config array.
 *
 * @return array
 */
function config()
{
    $maxProcesses = 10;
    $root = __DIR__ . '/../';
    $stop_server = false;
    $currentJobs = [];
    $pid = __DIR__ . '/pid/parse.pid';
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
    include_once (__DIR__ . '/../lib/autoload.php');
    $GLOBALS['conf'] = common_conf();
    common_inc('parser');
    parser_init();
}

$config = config();

require_once(__DIR__ . '/../lib/daemons/daemons.php');
