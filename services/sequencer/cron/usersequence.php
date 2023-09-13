<?php

use services\MainService;

include_once __DIR__ . '/../../../lib/autoload.php';

common_inc('_database');
common_inc('system/cron', 'cron');
common_inc('queue/mysql', 'mysql');
set_time_limit(0);

/**
 * Получение последнего времени пребывания на сайте пользователя, его сеанс и шаг.
 *
 * @param array $uuids
 *
 * @return array
 */
function getUsersLastVisit($uuids = [])
{
  $result = [];
  if (!empty($uuids))
  {
    // получаем список польователей
    $arUuids = array_keys($uuids);
    // для каждого пользователя получаем список последнего захода на сайт
    $objLastVisits = select_db(
        1,
        'user_property', [
        'last_visit',
        'uuid'
    ], [], [], '', [
            'uuid' => $arUuids
        ]
    );
    $arLastVisits = [];
    if (!empty($objLastVisits))
    {
      // подключаем механизм шардинга
      common_inc('sharding');
      while ($arObj = mysqli_fetch_assoc($objLastVisits))
      {
        // строим массив по таблицам
        $arShard = sharding_getShards($arObj['last_visit'], $arObj['last_visit']);
        if (!empty($arShard[0])
            && 1 == count($arShard)
        )
        {
          $arLastVisits[$arShard[0]][] = [
              'uuid' => $arObj['uuid'],
              'time' => (empty($arObj['last_visit']) ? time() : $arObj['last_visit'])
          ];
        }
      }
    }

    // получаем время, шаг и сеанс по каждому пользователю
    if (!empty($arLastVisits))
    {
      common_inc('sharding');
      foreach ($arLastVisits as $shard => $info)
      {
        // получаем соединение с БД
        $arLink = sharding_getConnection($shard, 'l_sequence_4_user');
        if (!empty($arLink['key'])
            && !empty($arLink['connect'])
        )
        {
          $table = query_setTableName(
              $arLink['key'],
              'l_sequence_4_user'
          );

          // получаем необходиыме параметры
          $maxTimeResult = [];
          for ($i = 0; $i < $ic = count($info); $i++)
          {
            $maxTimeResult[$info[$i]['uuid']] = $info[$i]['time'];
          }

          $sql = "SELECT
            `uuid`,
            `time`,
            `step`,
            `seance`
          FROM $table
          WHERE `time` IN ('" . implode("','", $maxTimeResult) . "')
          LIMIT 1000";

          $rs = mysqli_query(
              $arLink['connect'],
              $sql
          );

          if (!empty($rs))
          {
            while ($arTimes = mysqli_fetch_assoc($rs))
            {
              if (!empty($maxTimeResult[$arTimes['uuid']]))
              {
                $result[$arTimes['uuid']] = [
                    'last_time' => $maxTimeResult[$arTimes['uuid']],
                    'last_step' => $arTimes['step'],
                    'seance' => $arTimes['seance']
                ];
              }
            }
          }
        }
      }
    }
  }

  return $result;
}

/**
 * Получение id формы заявки.
 *
 * @param string $inputlink
 *
 * @return int
 */
function isOrder($inputlink = '')
{
  if (empty($inputlink))
  {
    return 0;
  }
  $o = query_db(
      1,
      'l_form2form',
      "SELECT `id` WHERE `id2dirty`='$inputlink'"
  );
  if (!empty($o))
  {
    if ($a = mysqli_fetch_assoc($o))
    {
      return (!empty($a['id']) ? $a['id'] : 0);
    }
  }

  return 0;
}

/**
 * Получение провайдера по uuid.
 *
 * @param $uuid
 *
 * @return null
 */
function getIpsFromUUID($uuid)
{
  if (empty($uuid))
  {
    return null;
  }
  $sql = "SELECT get_ips_from_uuid('$uuid') AS id";
  $r = simple_query($sql);
  if (!empty($r))
  {
    $r = $r->fetch_all(MYSQLI_ASSOC);
    if (!empty($r[0]['id']))
    {
      return $r[0]['id'];
    }
  }
  return null;
}

/**
 * Получение geo данных по uuid.
 *
 * @param $uuid
 *
 * @return null
 */
function getGeoFromUUID($uuid)
{
  if (empty($uuid))
  {
    return null;
  }
  $sql = "SELECT get_geo_from_uuid('$uuid') AS id";
  $r = simple_query($sql);
  if (!empty($r))
  {
    $r = $r->fetch_all(MYSQLI_ASSOC);
    if (!empty($r[0]['id']))
    {
      return $r[0]['id'];
    }
  }
  return null;
}

/**
 * Получение id формы.
 *
 * @param string $link
 *
 * @return int
 */
function getFormId($link = '')
{
  $id = 0;
  if (empty($link))
  {
    return $id;
  }
  $trimmedLink = strtok($link, '?');
  if (empty($trimmedLink))
  {
    return $id;
  }
  $sql = "SELECT id WHERE url = '/$trimmedLink'";
  $o = query_db(
      1,
      'l_conversion_form',
      $sql
  );

  if (!empty($o))
  {
    $a = mysqli_fetch_assoc($o);
    if (!empty($a['id']))
    {
      return $a['id'];
    }
  }

  return $id;
}

/**
 * @constructor
 */
function work1()
{
  $log = "start:" . time() . ";";
  $s = microtime(true);

  $sessionTime = 1800;

  // получаем данные из очереди
  $service = new MainService();
  $cronName = 'cron_usersequence';
  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "get data form queue:" . number_format(microtime(true) - $s, 4) . "ms;";

  $howMuchRow = $lid = 0;
  $duration = [];
  $currentTime = [];
  $step = [];
  $arLastId = [];
  $listUuids = [];
  $listLastVisit = [];
  $listOfIds = [];

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

      // запоминаем список пользователей
      $listUuids[$arList['uuid']] = $arList;
    }

    // если в выборке есть пользователи, то смотрим время последнего их захода
    if (!empty($listUuids))
    {
      $listLastVisit = getUsersLastVisit($listUuids);
    }

    // идем циклом по выборке из очереди
    for ($i = 0; $i < count($rsList); $i++)
    {
      // получаем данные из очереди из JSON
      $arList = (array_key_exists('id', $rsList[$i]) && array_key_exists('param', $rsList[$i])) ? json_decode($rsList[$i]['param'], true) : null;
      $listOfIds[] = $arList['id'];
      $currentTime[$arList['uuid']] = $arList['time'];
      $uuid = common_setValue($arList, 'uuid');
      $domain = common_setValue($arList, 'domain');
      $seance = common_setValue($arList, 'seance');
      $link = common_setValue($arList, 'link');
      $domain_text = common_setValue($arList, 'domain_text');
      $link_text = common_setValue($arList, 'link_text');
      preg_match('/https?:\/\/([a-z0-9\.\-]*)(.*)/', $arList['referrer'], $matches);
      $refererDomain = common_setValue($matches, 1);
      $refererLink = strtok(trim(common_setValue($matches, 2), '/'), '?');
      $utm_source = $arList['utm_source'];
      $utm_medium = $arList['utm_medium'];
      $utm_campaign = $arList['utm_campaign'];
      $utm_content = $arList['utm_content'];
      $utm_term = $arList['utm_term'];
      $order = isOrder($link);
      $device = $arList['device'];
      $form = getFormId($link_text);
      $ips = (!empty($arList['ip']) ? getIpsFromUUID($arList['ip']) : 0);
      $geo = getGeoFromUUID($uuid);
      $ip = $arList['ip_long'];
      $ad = common_setValue($arList, 'ad', 0);

      // step
      if (empty($step[$arList['uuid']]))
      {
        if (!empty($listLastVisit[$arList['uuid']]['last_time'])
            && $currentTime[$arList['uuid']] - $listLastVisit[$arList['uuid']]['last_time'] > $sessionTime
        )
        {
          $listLastVisit[$arList['uuid']]['last_step'] = 1;
          if (empty($arList['seance']))
          {
            $listLastVisit[$arList['uuid']]['seance'] = strtoupper(md5(uniqid() . $arList['time']));
          }
          else
          {
            $listLastVisit[$arList['uuid']]['seance'] = $seance;
          }

          $step[$arList['uuid']] = 1;
        }
        else if (!empty($listLastVisit[$arList['uuid']]['last_time'])
            && $currentTime[$arList['uuid']] - $listLastVisit[$arList['uuid']]['last_time'] <= $sessionTime
        )
        {
          $step[$arList['uuid']] = (!empty($listLastVisit[$arList['uuid']]['last_step']) ? ($listLastVisit[$arList['uuid']]['last_step'] + 1) : 1);
        }
        else
        {
          $listLastVisit[$arList['uuid']]['last_step'] = 1;

          if (empty($arList['seance']))
          {
            $listLastVisit[$arList['uuid']]['seance'] = strtoupper(md5(uniqid() . $arList['time']));
          }
          else
          {
            $listLastVisit[$arList['uuid']]['seance'] = $seance;
          }

          $step[$arList['uuid']] = 1;
        }
      }
      else
      {
        $step[$arList['uuid']]++;
        if (empty($seance))
        {
          $listLastVisit[$arList['uuid']]['seance'] = strtoupper(md5(uniqid() . $arList['time']));
        }
        else
        {
          $listLastVisit[$arList['uuid']]['seance'] = $seance;
        }
      }

      $data4db = [
          'time' => $currentTime[$arList['uuid']],
          'device' => $device,
          'hour' => date('H', $currentTime[$arList['uuid']]),
          'uuid' => $uuid,
          'link' => $link,
          'domain' => $domain,
          'domain_text' => $domain_text,
          'link_text' => $link_text,
          'step' => $step[$arList['uuid']],
          'duration' => 1,
          'utm_source' => $utm_source,
          'utm_medium' => $utm_medium,
          'utm_campaign' => $utm_campaign,
          'utm_content' => $utm_content,
          'utm_term' => $utm_term,
          'referer_domain' => $refererDomain,
          'referer_link' => $refererLink,
          'seance' => (empty($seance) ? $listLastVisit[$arList['uuid']]['seance'] : $seance),
          'order' => $order,
          'form' => $form,
          'ips' => (empty($ips) ? 0 : $ips),
          'geo' => $geo,
          'ip' => $ip,
          'ad' => $ad
      ];
      $lastId = insert_db($arList['time'], 'l_sequence_4_user', $data4db);

      //Если запись добавлена, то добавляем запись в очередь cron_sequenceAgregation
      if ($lastId)
      {
        $service = new MainService();

        //Добавим в очередь sequenceAgregation        
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_set',
            'queue' => 'cron_sequenceAgregation',
            'state' => 1,
            'time' => $data4db['time'],
            'param' => json_encode([
                'time' => $data4db['time'],
                'uuid' => $data4db['uuid'],
                'domain' => $data4db['domain'],
                'link' => $data4db['link'],
                'domain_text' => $data4db['domain_text'],
                'link_text' => $data4db['link_text'],
                'step' => $data4db['step'],
                'duration' => $data4db['duration'],
                'ad' => $ad
            ])
        ]);

        // также необходимо добавить запись для добавления в таблицу,
        // откуда потом будут строится данные по первому шагу пользователя
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_set',
            'queue' => 'cron_enterSequencer',
            'state' => 1,
            'time' => $data4db['time'],
            'param' => json_encode([
                'uuid' => $data4db['uuid'],
                'seance' => $data4db['seance'],
                'id' => $lastId
            ])
        ]);
      }

      // last id
      $arLastId[$arList['uuid']] = $lastId;

      // last duration
      if (empty($listLastVisit[$arList['uuid']]['last_time']))
      {
        $duration[$arList['uuid']] = 1;
      }
      else
      {
        $duration[$arList['uuid']] = $currentTime[$arList['uuid']] - $listLastVisit[$arList['uuid']]['last_time'];
      }

      // check by session
      if ($duration[$arList['uuid']] > $sessionTime)
      {
        $step[$arList['uuid']] = 0;
        $update = [
            'step' => 1,
            'duration' => 1
        ];
      }
      else
      {
        $update = [
            'duration' => $duration[$arList['uuid']]
        ];
      }

      $stat_update = update_db(
          $arList['time'],
          'l_sequence_4_user', $update, [
          'id' => $arLastId[$arList['uuid']]
      ]);

      // previous time
      $listLastVisit[$arList['uuid']]['last_time'] = $currentTime[$arList['uuid']];

      $howMuchRow++;

      if ($stat_update === true)
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
        if (empty($listLastVisit))
        {
          //Установим статус в не удалось найти данные
          $service->query('mysqlqueue', [
              'method' => 'mysqliqueue_update',
              'queue' => $cronName,
              'id' => $rsList[$i]['id'],
              'state' => 4
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
    $log .= "precess:" . number_format(microtime(true) - $s, 4) . "ms;";

    /**
     * Вставляем uuid в таблицу uuids_conditions_bitmaps_queue,
     * Чтобы в дальнейшем отработал обработчик,
     * обновления битовых масок результатов проверки групп условий.
     */
    if (!empty($rsList))
    {
      for ($i = 0; $i < count($rsList); $i++)
      {
        // получение массива данных из очереди
        $arList = (array_key_exists('id', $rsList[$i]) && array_key_exists('param', $rsList[$i])) ? json_decode($rsList[$i]['param'], true) : null;

        $data4queue = [
            'uuid' => $arList['uuid']
        ];

        insertIgnore_db(
            1,
            'uuids_conditions_bitmaps_queue',
            $data4queue
        );
      }
    }
  }
  $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
  $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
  writeLog(basename(__FILE__), $log);
}

for ($i = 0; $i < 20; $i++)
  work1();