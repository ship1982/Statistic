<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 30.07.16
 * Time: 8:11
 */

/**
 * Check that daemon is active.
 *
 * @param $pid_file
 * @return bool
 */
function isDaemonActive($pid_file)
{
    if (is_file($pid_file))
    {
        $pid = file_get_contents($pid_file);
        if (posix_kill($pid, 0))
            return true;
        else
        {
            if (!unlink($pid_file))
                exit(-1);
        }
        unset($pid);
    }
    return false;
}

/**
 * Exist daemon or not.
 *
 * @param $pid_file
 */
function checkDaemon($pid_file)
{
    if (isDaemonActive($pid_file))
    {
        echo 'Daemon already active'.PHP_EOL;
        exit;
    }
}

/**
 * Install a signal.
 *
 * @param $signo
 */
function sigHandler($signo)
{
    echo "signal - " . $signo . PHP_EOL;
    global $config;
    switch ($signo)
    {
        case SIGTERM:
            $config['stop_server'] = true;
            break;
        default:
            //все остальные сигналы
    }
}

/**
 * Set a daemon pid
 */
function setDaemonPid()
{
    global $config;
    file_put_contents($config['pid'], getmypid());
}

/** executable code */
$child_pid = pcntl_fork();
if ($child_pid)
    exit;

posix_setsid();

checkDaemon($config['pid']);

pcntl_signal(SIGTERM, "sigHandler");

declare(ticks = 1);

setDaemonPid();

while (!$config['stop_server'])
{
    if (!$config['stop_server'] and (count($config['currentJobs']) < $config['maxProcesses']))
    {
        sleep(1);
        $pid = pcntl_fork();
        if ($pid == -1)
        {

        }
        elseif($pid)
        {
            $config['currentJobs'][$pid] = true;
        }
        else
        {
            $pid = getmypid();
            work($pid);
            exit;
        }
    }
    else
    {
        sleep($config['delay']);
    }

    while ($signaled_pid = pcntl_waitpid(-1, $status, WNOHANG))
    {
        if ($signaled_pid == -1)
        {
            $config['currentJobs'] = [];
            break;
        }
        else
        {
            unset($config['currentJobs'][$signaled_pid]);
        }
    }
}
