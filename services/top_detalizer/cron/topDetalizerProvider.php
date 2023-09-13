<?php

use services\MainService;

include_once(__DIR__ . '/../../../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');
common_inc('queue/mysql', 'mysql');

/**
 * @constructor
 */
function work1()
{
  $log = "start:" . time() . ";";
  $service = new MainService();
  $cronName = 'cron_topDetalizerProvider';
  $s = microtime(true);
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";
  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";

  $howMuchRow = $lid = 0;
  $arUUIDS = [];
  $data = [];
  $listOfIds = [];
  $inserted = [];

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

      $listOfIds[] = $arList['id'];

      if (!empty(common_setValue($arList, 'domain'))
          && !empty(common_setValue($arList, 'link'))
          && !empty(common_setValue($arList, 'uuid'))
      )
      {
        $arUUIDS[] = $arList['uuid'];
        $data[$arList['uuid']] = $arList;
      }

      $howMuchRow++;
    }
    $log .= "process uuids:" . number_format(microtime(true) - $s, 4) . "ms;";

    /** get query for city */
    $rsCity = query_db(1,
        'ip_base',
        'SELECT DISTINCT(`uuid`) as uuid, isp WHERE uuid IN (\'' . implode('\',\'', $arUUIDS) . '\')'
    );
    $log .= "query uuids:" . number_format(microtime(true) - $s, 4) . "ms;";

    while ($arCity = mysqli_fetch_assoc($rsCity))
    {
      if (!empty($arCity['uuid']) && !empty($data[$arCity['uuid']]))
      {
        $time = strtotime(date('Y-m-d', $data[$arCity['uuid']]['time']) . ' 00:00:00');
        $hour = ltrim(date('H', $data[$arCity['uuid']]['time']), '0');
        $week = ltrim(date('w', $data[$arCity['uuid']]['time']), '0');
        /** top by domain and link */
        $inserted[] = [
            'id' => 1,
            'domain' => common_setValue($data[$arCity['uuid']], 'domain'),
            'link' => common_setValue($data[$arCity['uuid']], 'link'),
            'provider' => common_setValue($arCity, 'isp'),
            'time' => $time,
            'count' => 1,
            'hour' => (empty($hour) ? '0' : $hour),
            'week' => (empty($week) ? '0' : $week),
            'list_uuid' => common_setValue($arCity, 'uuid'),
            'count_ad' => common_setValue($data[$arCity['uuid']], 'ad', 0),
        ];
      }
    }
    $res_ins = query_batchInsert(
        1,
        'top_detalizer_provider',
        $inserted, [
            '#count' => '`count`+1',
            '#list_uuid' => 'CONCAT(`list_uuid`, CHAR(44 USING utf8), VALUES(`list_uuid`))',
            '#count_ad' => 'IF(`count_ad` IS NULL, VALUES(`count_ad`), `count_ad`+VALUES(`count_ad`))'
        ]
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
        //Установим статус в не обработано
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_update',
            'queue' => $cronName,
            'id' => $rsList[$i]['id'],
            'state' => 3
        ]);
      }
    }

    $log .= "insert:" . number_format(microtime(true) - $s, 4) . "ms;";
    $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
    $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
    file_put_contents(__DIR__ . '/../../../../syslog/topDetalizerProvider.log', $log . "\n", FILE_APPEND);
  }
}

for ($i = 0; $i < 20; $i++)
  work1();