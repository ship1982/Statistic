<?php

use services\MainService;

/**
 * Get first time form table.
 *
 * @param string $key - sharding key for table.
 *
 * @return string
 */
function getFirstTime($key = '')
{
  if (function_exists('query_db') && !empty($key))
  {
    $sql = "SELECT `time` WHERE 1=1 ORDER BY `time` LIMIT 1";
    $rs = query_db(
        $key,
        'l_sequence_4_user',
        $sql
    );
    if (!empty($rs) && is_object($rs))
    {
      $data = mysqli_fetch_assoc($rs);
      return $data['time'];
    }
    return '';
  }
  return '';
}

/**
 * Get user type for list of user.
 *
 * @param array $arUserUuids - list of user ids
 *
 * @return array
 */
function getUserTypes($arUserUuids = [])
{
  if (!empty($arUserUuids))
  {
    if (function_exists('query_db'))
    {
      $sql = "SELECT `utype`,`uuid` WHERE `uuid` IN ('" . implode("','", $arUserUuids) . "')";
      $rs = query_db(
          1,
          'user_property',
          $sql
      );
      $array = [];
      while ($data = mysqli_fetch_assoc($rs))
        $array[$data['uuid']] = $data['utype'];

      return $array;
    }
    return [];
  }
  return [];
}

/**
 * Get table id. It is constant.
 *
 * @return string 1
 */
function getId()
{
  return '1';
}

/**
 * Get user type.
 *
 * @param        array @see $userTypes()
 * @param string $uuid - user identifier
 *
 * @return string
 */
function getUserType($userTypes = [], $uuid = '')
{
  return common_setValue($userTypes, $uuid);
}

/**
 * Get time by hour.
 * If pass 2016-10-10 12-02-14, then return 2016-10-10 12-00-00.
 *
 * @param string $time - needed time
 *
 * @return int
 */
function getTime($time = '')
{
  if (!empty($time))
  {
    return strtotime(date('Y-m-d H', $time) . ':00:00');
  }

  return 0;
}

/**
 * Get hour by time.
 *
 * @param string $time - needed time
 *
 * @return string
 */
function getHour($time = '')
{
  if (!empty($time))
  {
    return date('H', $time);
  }
  return 0;
}

/**
 * Return user path, count of steps and average duration by path.
 *
 * @param array $listOfUserPath
 *
 * @return array
 */
function getIndividualPath($listOfUserPath = [])
{
  $array = [];
  $cnt = 0;
  $duration = 0;
  if (!empty($listOfUserPath))
  {
    foreach ($listOfUserPath as $key => $info)
    {
      if ($info['step'] == 1)
      {
        $cnt++;
        if ($cnt != 1)
        {
          $countStep = count($array[$cnt - 1]['steps']);
          $array[$cnt - 1]['count'] = $countStep;
          $array[$cnt - 1]['avg_duration'] = ($duration / $countStep);
          $duration = 0;
        }
      }

      $duration += $info['duration'];
      $array[$cnt]['steps'][] = $info;

    }
    $countStep = count($array[$cnt]['steps']);
    $array[$cnt]['count'] = $countStep;
    $array[$cnt]['avg_duration'] = ($duration / $countStep);
    return $array;
  }
  return [];
}

/**
 * Get list from l_form2form table.
 *
 * @return array
 */
function getFormsComparison()
{
  if (function_exists('query_db'))
  {
    $rs = select_db(
        1,
        'l_form2form',
        [
            'id2dirty',
            'link2link'
        ]
    );

    if (!empty($rs) && is_object($rs))
    {
      $array = [];
      while ($data = mysqli_fetch_assoc($rs))
        $array[$data['id2dirty']] = $data['link2link'];

      return $array;
    }
    return [];
  }
  return [];
}

/**
 * Get list of available form.
 *
 * @return array
 */
function getForms()
{
  if (function_exists('query_db'))
  {
    $rs = select_db(
        1,
        'l_conversion_form',
        [
            'id',
            'url'
        ]
    );

    if (!empty($rs) && is_object($rs))
    {
      $array = [];
      while ($data = mysqli_fetch_assoc($rs))
        $array[$data['url']] = $data['id'];

      return $array;
    }
    return [];
  }
  return [];
}

/**
 * Get path data for aggregation table
 *
 * @param array $path           - @see getIndividualPath()
 * @param array $formComparison - @see getFormsComparison()
 * @param array $forms          - @see getForms()
 *
 * @return array
 */
function getPathData($path = [], $formComparison = [], $forms = [])
{
  $result = [];
  if (!empty($path))
  {
    foreach ($path as $key => $array)
    {
      if (!empty($array['steps']))
      {
        $hashString = '';
        $path4User = [];
        $orderIs = 0;
        $orderStep = 0;
        $result = [
            'hash2sequence' => '',
            'json2sequence' => '',
            'order' => $orderIs,
            'time' => '',
            'onePath' => 1,
            'orderStep' => $orderStep,
            'hour' => '',
            'lastDomain' => ''
        ];
        $arrayData = [];
        if (empty($array['steps']))
        {
          continue;
        }
        $lastDomain = '';
        for ($i = 0; $i < $ic = count($array['steps']); $i++)
        {
          $element = $array['steps'][$i];
          if (!empty($element['link']))
          {
            $link = common_setValue($formComparison, $array['steps'][$i]['link']);
            $order = common_setValue($forms, $link);
            $arrayData = getPathString($element['domain'], $element['link'], $element['step'], $element['duration'], $element['time']);
            $hashString .= $arrayData['string'];
            $path4User[] = $arrayData['array'];
            if ($order)
            {
              $orderStep = $element['step'];
              $orderIs = 1;
            }
          }
          $lastDomain = $element['domain'];
        }
        return [
            'hash2sequence' => md5($hashString),
            'json2sequence' => json_encode($path4User),
            'order' => $orderIs,
            'onePath' => count($path4User),
            'orderStep' => $orderStep,
            'hour' => common_setValue($arrayData, 'hour'),
            'time' => common_setValue($arrayData, 'time'),
            'lastDomain' => $lastDomain
        ];
      }
      return $result;
    }
    return $result;
  }
  return $result;
}

/**
 * Get information from user path.
 *
 * @param string $domain   - id of domain
 * @param string $link     - id of link
 * @param string $step     - step in path
 * @param string $duration - duration of user path
 * @param string $time     - time for path (last time)
 *
 * @return array
 */
function getPathString($domain = '', $link = '', $step = '', $duration = '', $time = '')
{
  return [
      'string' => $domain . $link,
      'time' => getTime($time),
      'hour' => getHour($time),
      'array' => [
          'domain' => $domain,
          'link' => $link,
          'step' => $step,
          'duration' => $duration
      ]
  ];
}

/**
 * Get data for insert to aggregation table.
 *
 * @param array $arUserPath
 * @param array $arUserUuids
 *
 * @return void
 */
function getData2Db($arUserPath = [], $arUserUuids = [], &$TempCache)
{
  // $s = microtime(true);
  $array = [];
  $userTypes = getUserTypes($arUserUuids);
  // echo '$userTypes' . number_format(microtime(true) - $s, 4) . "ms\n";
  if (empty($TempCache->cache['formcomparison']))
  {
    $formComparison = getFormsComparison();
    $TempCache->cache['formcomparison'] = $formComparison;
  }
  else
  {
    $formComparison = $TempCache->cache['formcomparison'];
  }

  // echo '$formComparison' . number_format(microtime(true) - $s, 4) . "ms\n";
  if (empty($TempCache->cache['getForms']))
  {
    $forms = getForms();
    $TempCache->cache['getForms'] = $forms;
  }
  else
  {
    $forms = $TempCache->cache['getForms'];
  }

  // echo '$forms' . number_format(microtime(true) - $s, 4) . "ms\n";

  if (!empty($arUserPath))
  {
    $queue_ids = [];
    $array['count_ad'] = 0;
    foreach ($arUserPath as $uuid => $info)
    {
      $array['id'] = getId();
      // echo 'getId' . number_format(microtime(true) - $s, 4) . "ms\n";
      $array['usertype'] = getUserType($userTypes, $uuid);
      // echo 'getUserType' . number_format(microtime(true) - $s, 4) . "ms\n";
      $path = getIndividualPath($info);
      // echo 'getIndividualPath' . number_format(microtime(true) - $s, 4) . "ms\n";
      $aggregationData = getPathData($path, $formComparison, $forms);
      // echo 'getPathData' . number_format(microtime(true) - $s, 4) . "ms\n";
      $array = array_merge($array, $aggregationData, ['count' => 1]);
      // echo 'array_merge' . number_format(microtime(true) - $s, 4) . "ms\n";

      //в рамках цепочки действий статус адблок фиксируется по последнему действию пользователя
      $array['count_ad'] += (int)common_setValue(end($info), 'ad', 0);

      //Получим идентификаторы строк очереди, которые нужно отметить
      for ($i = 0; $i < count($info); $i++)
      {
        $queue_ids[] = $info[$i]['queue_id'];
      }
      // executeInsert($array, $queue_ids);
      // echo 'executeInsert' . number_format(microtime(true) - $s, 4) . "ms\n";
    }
    query_batchInsert(false, 'l_sequence_agregation', [$array], [
        '#count' => '`count`+1',
        '#count_ad' => 'IF(`count_ad` IS NULL, VALUES(`count_ad`), `count_ad`+VALUES(`count_ad`))'
    ]);
    change_stat_queue($queue_ids, true);
  }

  return;
}

/**
 * Execute insert.
 *
 * @param array $data
 * @param array $queue_ids
 *
 * @return void
 * @internal param $array @see getPathData().
 *
 */
function executeInsert($data = [], $queue_ids = [])
{
  if (!empty($data))
  {

    $res_ins = insert_db(
        1,
        'l_sequence_agregation',
        $data, [
            '#count' => '`count`+1'
        ]
    );

    //Изменим статус очереди
    change_stat_queue($queue_ids, $res_ins);
  }
}

/**
 * Обновляет затронутые строки очереди, в зависимости от статуса
 *
 * @param array   $queue_ids - массив идентификаторов дляя строк в очереди
 * @param boolean $status    0- статус
 *
 * @return void
 */
function change_stat_queue($queue_ids = [], $status = false)
{
  if (!is_array($queue_ids) || empty($queue_ids))
  {
    return;
  }

  $service = new MainService();
  $cronName = 'cron_sequenceAgregation';

  for ($i = 0; $i < count($queue_ids); $i++)
  {
    //Если получили идентификатор вставленной строки
    if ($status)
    {
      //Удалим запись из очереди
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_delete',
          'queue' => $cronName,
          'id' => $queue_ids[$i]
      ]);
    }
    else
    {
      //Установим статус в не обработано
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_update',
          'queue' => $cronName,
          'id' => $queue_ids[$i],
          'state' => 3
      ]);
    }
  }

  return;
}