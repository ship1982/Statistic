<?php

/**
 * Set config array.
 *
 * @return array
 */
function config()
{
    $maxProcesses = 1;
    $root = __DIR__ . '/../';
    $stop_server = false;
    $currentJobs = [];
    $pid = __DIR__ . '/pid/eventsNew.pid';
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

    include_once __DIR__ . '/../cron/Events.php';
    work1();
}

$config = config();

include_once __DIR__ . '/../lib/daemons/daemons.php';