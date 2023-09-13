<?php

/**
 * Get last id from pid.
 *
 * @param string $name - name of pis file
 * @return string
 */
function get_pid($name = '', $service = '')
{
  if(!empty($service))
    $dir = __DIR__ . '/../../../services/' . $service . '/cron/pid/' . $name . '.pid';
  else
    $dir = __DIR__ . '/../../../cron/pid/' . $name . '.pid';

  if(file_exists($dir))
    return file_get_contents($dir);
  else
    return 0;
}

/**
 * Set last id into pid.
 *
 * @param string $pid - value for write
 * @param string $file - name of pid file
 */
function set_pid($pid = '', $file = '', $service = '')
{
  if(!empty($pid))
  {
    if(!empty($service))
      $dir = __DIR__ . '/../../../services/' . $service . '/cron/pid/' . $file . '.pid';
    else
      $dir = __DIR__ . '/../../../cron/pid/' . $file . '.pid';

    file_put_contents($dir, $pid);
  }
}

/**
 * Set key for table.
 *
 * @param string $key
 * @param string $file - table number (sharding)
 */
function setTableKey($key = '', $file = '', $service = '')
{
  if(!empty($key))
  {
    if(!empty($service))
      $dir = __DIR__ . '/../../../services/' . $service . '/cron/pid/' . $file;
    else
      $dir = __DIR__ . '/../../../cron/pid/' . $file;

    file_put_contents($dir, $key);
  }
}

/**
 * Get last id from pid.
 *
 * @param string $name name of file
 * @return string
 */
function getTableKey($name = '', $service = '')
{
  if(!empty($service))
    $dir = __DIR__ . '/../../../services/' . $service . '/cron/pid/' . $name;
  else
    $dir = __DIR__ . '/../../../cron/pid/' . $name;

  if(file_exists($dir))
    return file_get_contents($dir);
  else
    return 0;
}

/**
 * Write log file in syslog (from config main).
 *
 * @param string $name - name of config file.
 * @param string $text - log info
 * @return void
 */
function writeLog($name = '', $text = '')
{
  if(!empty($name)
      || empty($text)
  )
  {
    $config = setConfig('main/main');
    if(!empty($config['syslog']))
    {
      $partOfName = date('Y-m-1', time());
      file_put_contents(
        $config['syslog'] . '/' . $name . '-' .$partOfName . '.log',
        $text . "\n",
        FILE_APPEND
      );
    }
  }
}

function cron_fls($text = '', $time)
{
  if(!empty($text)
    && !empty($time)
  )
    return $text . ':' . number_format(microtime(true) - $time) . " ms";
  return '';
}