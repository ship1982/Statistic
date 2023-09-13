<?php

use services\MainService;

include_once(__DIR__ . '/../../../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');
common_inc('queue/mysql', 'mysql');
set_time_limit(0);

/**
 * @constructor
 */
function work1()
{
  $log = "start:" . time() . ";";
  $service = new MainService();
  $cronName = 'cron_form2link';
  $s = microtime(true);
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";
  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";

  $howMuchRow = $lid = 0;
  $listOfId = [];
  $__listOfId = [];

  if (!empty($rsList))
  {
    for ($i = 0; $i < count($rsList); $i++)
    {
      $arList = (array_key_exists('id', $rsList[$i]) && array_key_exists('param', $rsList[$i])) ? json_decode($rsList[$i]['param'], true) : null;
      if ($arList === null)
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

      $__listOfId[] = $arList['id'];
      $listOfId[] = $arList['link'];
      $howMuchRow++;
    }
    $log .= "collect ids:" . number_format(microtime(true) - $s, 4) . "ms;";

    $sql = "SELECT
      `id`,
      `domain_link`
    WHERE `id` IN ('" . implode("','", $listOfId) . "')
      AND `domain_link` LIKE '%success_FID1=yes%'";
    $result = query_db(
        1,
        'link',
        $sql
    );

    $inserted = [];
    while ($arrayDone = mysqli_fetch_assoc($result))
    {
      if (!empty($arrayDone['domain_link'])
          && !empty($arrayDone['id'])
      )
      {
        $link = strtok($arrayDone['domain_link'], '?');
        preg_match('/https?:\/\/[a-z0-9\.\-]*(.*)/', $link, $matches);
        if (!empty($matches[1]))
        {
          $inserted[] = [
              'id' => 1,
              'link2link' => $matches[1],
              'id2dirty' => $arrayDone['id']
          ];
        }
      }
    }

    $res_ins = query_batchInsert(
        1,
        'l_form2form',
        $inserted
    );

    for ($i = 0; $i < count($rsList); $i++)
    {
      if ($res_ins === true)
      {
        //Удалим запись из очереди
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_delete',
            'queue' => $cronName,
            'id' => $rsList[$i]['id']
        ]);
      }
      else
      {
        if (empty(return_mysqli_results($result)))
        {
          //Удалим запись из очереди
          $service->query('mysqlqueue', [
              'method' => 'mysqliqueue_delete',
              'queue' => $cronName,
              'id' => $rsList[$i]['id']
          ]);
        }
        else
        {
          //Установим статус в не обработано
          $service->query('mysqlqueue', [
              'method' => 'mysqliqueue_update',
              'queue' => $cronName,
              'id' => $rsList[$i]['id'],
              'state' => 3
          ]);
        }
      }
    }
    $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
    $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
    writeLog(basename(__FILE__), $log);
  }
}

for ($i = 0; $i < 20; $i++)
  work1();