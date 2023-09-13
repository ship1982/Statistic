<?php

use services\MainService;

include_once(__DIR__ . '/../lib/autoload.php');
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
  $cronName = 'cron_topPage';
  $s = microtime(true);
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";

  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";

  $howMuchRow = 0;
  $listOfId = [];
  $inserted = [];
  $insertIgnored = [];

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

      $listOfId[] = $arList['id'];
      $time = strtotime(date('Y-m-d', $arList['time']) . ' 00:00:00');

      if (!empty(common_setValue($arList, 'domain'))
          && !empty(common_setValue($arList, 'link'))
          && !empty(common_setValue($arList, 'uuid'))
      )
      {
        /** top by domain and link */
        $inserted[] = [
            'id' => 1,
            'domain' => common_setValue($arList, 'domain'),
            'link' => common_setValue($arList, 'link'),
            'time' => $time,
            'list_uuid' => common_setValue($arList, 'uuid'),
            'count_ad' => common_setValue($arList, 'ad', 0),
            'count' => 1
        ];
        /*insert_db(1, 'top_domain_link', [
            'id' => 1,
            'domain' => common_setValue($arList, 'domain'),
            'link' => common_setValue($arList, 'link'),
            'time' => $time,
            'count' => 1
        ], [
            '#count' => '`count`+1'
        ]);*/

        /** top by domain link uuid */
        /*insertIgnore_db(1, 'top_domain_link_uuid', [
            'id' => 1,
            'domain' => common_setValue($arList, 'domain'),
            'link' => common_setValue($arList, 'link'),
            'uuid' => common_setValue($arList, 'uuid'),
            'time' => $time,
        ]);*/
        $insertIgnored[] = [
            'id' => 1,
            'domain' => common_setValue($arList, 'domain'),
            'link' => common_setValue($arList, 'link'),
            'uuid' => common_setValue($arList, 'uuid'),
            'ad' => common_setValue($arList, 'ad', 0),
            'time' => $time,
        ];
      }

      $howMuchRow++;
    }

    $res_ins1 = query_batchInsert(
        1,
        'top_domain_link',
        $inserted, [
        '#count' => '`count`+1',
        '#list_uuid' => 'CONCAT(`list_uuid`, CHAR(44 USING utf8), VALUES(`list_uuid`))',
        '#count_ad' => 'IF(`count_ad` IS NULL, VALUES(`count_ad`), `count_ad`+VALUES(`count_ad`))'
    ]);

    $res_ins2 = query_batchInsert(
        1,
        'top_domain_link_uuid',
        $insertIgnored, [
    ], true
    );

    for ($i = 0; $i < count($rsList); $i++)
    {
      if ($res_ins1 === true && $res_ins2 === true)
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
    file_put_contents(__DIR__ . '/../../syslog/topPage.log', $log . "\n", FILE_APPEND);
  }
}

for ($i = 0; $i < 20; $i++)
  work1();