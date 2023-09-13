<?php

use services\MainService;

include_once(__DIR__ . '/../../../lib/autoload.php');
common_inc('geoip');
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
  $cronName = 'cron_topDetalizer';
  $s = microtime(true);
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";
  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";

  $howMuchRow = 0;
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
      if (!empty($arList['domain'])
          && !empty($arList['link'])
      )
      {
        $inserted[] = [
            'id' => '1',
            'domain' => common_setValue($arList, 'domain'),
            'link' => common_setValue($arList, 'link'),
            'time' => common_setValue($arList, 'time'),
            'hour' => ltrim(date('H', $arList['time']), '0'),
            'week' => ltrim(date('w', $arList['time']), '0'),
            'list_uuid' => common_setValue($arList, 'uuid'),
            'count_ad' => common_setValue($arList, 'ad', 0),
            'count' => 1
        ];
      }
      $howMuchRow++;
    }
    $res_ins = query_batchInsert(
        1,
        'top_detalizer',
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
    file_put_contents(__DIR__ . '/../../../../syslog/topDetalizer.log', $log . "\n", FILE_APPEND);
  }
}

for ($i = 0; $i < 20; $i++)
  work1();