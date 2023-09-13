<?php

use services\MainService;

include_once __DIR__ . '/../lib/autoload.php';

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
  $cronName = 'cron_ipChecker';

  $s = microtime(true);
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";

  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";
  $gb = new IPGeoBase();
  $howMuchRow = $lid = 0;
  $listOfId = [];
  $inserted = [];
  $res_ins = null;
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
      $data = $gb->getRecord($arList['ip']);

      $inserted[] = [
          'uuid' => $arList['uuid'],
          'ip' => $arList['ip'],
          'city' => common_setValue($data, 'city'),
          'region' => common_setValue($data, 'region'),
          'district' => common_setValue($data, 'district'),
          'isp' => common_setValue($data, 'isp')
      ];
      $howMuchRow++;
    }

    $res_ins = query_batchInsert(
        1,
        'ip_base',
        $inserted, [], true
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
  }

  /**
   * Если успешно произведена вставка в ip_base,
   * то обновим таблицы list_condition_geo
   * если этих данных там ещё нет.
   */
  if ($res_ins)
  {
    for ($i = 0; $i < count($inserted); $i++)
    {
      //$str_insert_ips = [];
      $str_insert_get = [];
      if (!empty($inserted[$i]))
      {
        //Добавим запись если она не пуста
        /*if (!empty($inserted[$i]['isp']))
        {
          $str_insert_ips[] = [
              'org_name' => $inserted[$i]['isp']
          ];
        }*/

        //Добавим запись, если она не пуста
        if (!empty($inserted[$i]['city'])
            || !empty($inserted[$i]['region'])
            || !empty($inserted[$i]['district'])
        )
        {
          $str_insert_get[] = [
              'city' => $inserted[$i]['city'],
              'region' => $inserted[$i]['region'],
              'district' => $inserted[$i]['district']
          ];
        }
      }
    }
    /*query_batchInsert(
        1,
        'ripe_ips',
        $str_insert_ips, [], true
    );*/
    query_batchInsert(
        1,
        'list_condition_geo',
        $str_insert_get, [], true
    );
  }

  $log .= "insert:" . number_format(microtime(true) - $s, 4) . "ms;";
  $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
  $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
  file_put_contents(__DIR__ . '/../../syslog/ipChecker.log', $log . "\n", FILE_APPEND);
}

for ($i = 0; $i < 100; $i++)
  work1();