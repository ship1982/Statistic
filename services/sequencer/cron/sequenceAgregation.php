<?php

use services\MainService;

set_time_limit(0);

include_once(__DIR__ . '/../../../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');
include_once(__DIR__ . '/../models/aggregation.php');

function work1()
{
  if (!class_exists('TempCache'))
  {
    class TempCache
    {
      public $cache = [];
    }
  }

  $cache = new TempCache();

  $s = microtime(true);
  $log = "start:" . time() . ";";
  $cronName = 'cron_sequenceAgregation';

  $service = new MainService();
  $timeStart = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_first_time',
      'queue' => $cronName,
      'state' => 1
  ]);

  //Если не определили время, то в очереди нет записей => не выполнем скрипт
  if ($timeStart <= 0)
  {
    exit;
  }

  $timeEnd = $timeStart + 86400;

  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'where' => " `time` BETWEEN '$timeStart' AND '$timeEnd'",
      'limit' => 100000,
      'state' => 1
  ]);

  $log .= "getDataFromQueue:" . number_format(microtime(true) - $s, 4) . "ms;";

  $arUserPath = [];
  if (!empty($rsList))
  {
    for ($i = 0; $i < count($rsList); $i++)
    {
      // получаем данные из очереди по одной записи
      $data = (array_key_exists('id', $rsList[$i]) && array_key_exists('param', $rsList[$i])) ? json_decode($rsList[$i]['param'], true) : null;
      // если данных нет, то пропускаем запись
      if ($data === null)
      {
        continue;
      }

      //Установим статус в обработке
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_update',
          'queue' => $cronName,
          'id' => $rsList[$i]['id'],
          'state' => 2
      ]);

      $data['queue_id'] = $rsList[$i]['id'];
      $arUserPath[$data['uuid']][] = $data;
    }

    $log .= "setStatusInJobInQueue:" . number_format(microtime(true) - $s, 4) . "ms;";

    $arUserUuids = array_keys($arUserPath);
    $log .= "array_keys:" . number_format(microtime(true) - $s, 4) . "ms;";

    getData2Db($arUserPath, $arUserUuids, $cache);
    $log .= "getData2Db:" . number_format(microtime(true) - $s, 4) . "ms;";

    $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
    $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
    writeLog(basename(__FILE__), $log);
  }
}

// for ($i = 0; $i < 50; $i++)
work1();