<?php

namespace tools;

class Execution
{
  /**
   * @var int - максимальное время исполнения скрипта
   */
  protected static $executionTime = 0;

  /**
   * @var int - максимально возможное кол-во процессов
   */
  protected static $maxCount = 1;

  /**
   * Установка максимального времени испольнения для скрипта.
   *
   * @param int $time
   */
  static function setExecutionTime($time = 0)
  {
    self::$executionTime = $time;
  }

  /**
   * Установка максимльного кол-ва процессов.
   *
   * @param int $count
   */
  static function setMaxCount($count = 1)
  {
    self::$maxCount = $count;
  }

  /**
   * Возвращает PID первого найденного процесса, по его имени.
   *
   * @param string $process_name - название процесса, пример: /misk_sequence_fill_uids.php/
   *
   * @return object
   */
  static function getPid($process_name = '')
  {
    if (!empty($process_name))
    {
      $pid = null;
      exec("ps ux | awk '/$process_name/ && !/awk/ {print $2}'", $output, $return);//находим пид процесса
      if (!empty($output))
      {
        $pid = end($output);
      }
      return (object)[
          'count' => count($output),
          'pid' => $pid
      ];
    }

    return (object)[];

  }

  /**
   * Возвращает время работы процесса по его PID, в секундах
   *
   * @param int $pid - идентификатор процесса
   *
   * @return int|null
   */
  static function getTime4Pid($pid)
  {
    $pid = (int)$pid;
    $time = null;
    exec("ps -eo pid,time | grep $pid | awk '{print $2}'", $output, $return);
    if (!empty($output[0]))
    {
      $time = self::parseTime2Sec($output[0]);
    }
    return $time;
  }

  /**
   * Выполняет запуск скрипта в консоли, пример: php misk_sequence_fill_uids.php
   *
   * @param string $process_name - misk_sequence_fill_uids.php
   *
   * @return boolean
   */
  static function runScript($process_name = '')
  {
    if (!empty($process_name))
    {
      exec("php $process_name");
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Убивает процесс
   *
   * @param int $pid
   *
   * @return boolean
   */
  static function killProcess($pid)
  {
    $pid = (int)$pid;
    exec("kill " . $pid);
    return true;
  }

  /**
   * Возвращает количество секунд пройденного времени
   *
   * @param string $string - строка в виде 00:00 или 00:00:00
   *
   * @return int|null
   */
  static function parseTime2Sec($string)
  {
    $arr_time = explode(":", $string);

    if (count($arr_time) == 2)
    {//00:02 -
      $res = (int)($arr_time[0] * 60) + (int)$arr_time[1];
    }
    elseif (count($arr_time) == 3)
    {//21:08:53
      $res = (int)($arr_time[0] * 60 * 60) + (int)($arr_time[1] * 60) + (int)$arr_time[2];
    }
    else
    {
      return null;
    }

    return $res;
  }

  /**
   * Убиваем новый процесс, если он уже запущен и исполняем колбэк.
   *
   * @param string $script_name
   * @param int    $time
   * @param        $callback
   */
  static function killByMaxExecutionTime($script_name = '', $time = 0, $callback)
  {
    if (!empty($script_name)
        && !empty($time)
    )
    {
      self::setExecutionTime($time);
      $obj_pid = self::getPid($script_name);
      $time = self::getTime4Pid($obj_pid->pid);
      // Если такой процесс уже запущен или время его работы больше отведенного, то убиваем процесс
      if (($obj_pid->pid !== NULL && $obj_pid->count > 2)
          || ($time !== NULL && $time > self::$executionTime))
      {
        // Убиваем новый процесс
        self::killProcess($obj_pid->pid);
        // Исполняем callback
        if (!empty($callback)
            && is_callable($callback)
        )
        {
          call_user_func_array($callback, [
              $script_name,
              $obj_pid->pid,
              $time
          ]);
        }
      }
    }
  }

  /**
   * Не даем породить больше процессов, чем нужно.
   *
   * @param string $script_name
   * @param int    $count
   * @param        $callback
   */
  static function killProcessIfCountGreaterThenMax($script_name = '', $count = 1, $callback)
  {
    if (!empty($script_name)
        && !empty($count)
    )
    {
      $obj_pid = self::getPid($script_name);
      // если процессов больше, чем нужно, то убиваем текущий процесс
      if ($obj_pid->pid !== NULL
          && $obj_pid->count > $count
      )
      {
        // Убиваем старый процесс
        self::killProcess($obj_pid->pid);
        // Исполняем callback
        if (!empty($callback)
            && is_callable($callback)
        )
        {
          call_user_func_array($callback, [
              $script_name,
              $obj_pid->pid,
          ]);
        }
      }
    }
  }
}

