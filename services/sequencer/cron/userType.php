<?php

use services\MainService;

include_once(__DIR__ . '/../../../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');
common_inc('queue/mysql', 'mysql');
include_once(__DIR__ . '/../models/common.php');
set_time_limit(0);

/**
 * Local config function.
 *
 * @return array
 */
function getParams()
{
  return [
      'key' => 'tableUserTypeSequencer',
      'lastId' => 'sequencerUserType',
      'monthPeriod' => 2690743
  ];
}

/**
 * Set new table key if need. If table not exist rows to work.
 *
 * @return void
 */
function close($counter = 1, $lid = 0, $params = [])
{
  /** сделать проверку, когда месяц заканчивается ровно по недели */
  if (!empty($lid)
      && !empty($params['monthPeriod'])
      && !empty($params['key'])
  )
  {
    $t = strtotime(date('Y-m-1', $lid));
    if ($t == date('Y-m-1', time()))
    {
      exit;
    }
    if ($counter > 1)
    {
      /** last month table name */
      setTableKey($t, $params['key'], 'sequencer');
    }
  }
}

/**
 * @constructor
 */
function work1()
{
  $log = "start:" . time() . ";";

  $service = new MainService();
  $cronName = 'cron_userType';

  $s = microtime(true);
  $params = getParams();
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";
  $monthPeriod = 2690743;
  /** get information about usertypes */
  $userTypes = getUserTypes();
  /*
  $rsList = getFromDirty(
    trim(getTableKey($params['key'], 'sequencer')),
    $bitmask
  );
  */
  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";

  // sum hits for a week
  $userList = [];
  $howMuchRow = 0;
  if (!empty($rsList))
  {
    $listOfId = [];
    for ($i = 0; $i < count($rsList); $i++)
    {
      $arInfo = (array_key_exists('id', $rsList[$i]) && array_key_exists('param', $rsList[$i])) ? json_decode($rsList[$i]['param'], true) : null;
      if ($arInfo === null)
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
      $listOfId[] = $arInfo['id'];
      if (empty($userList[$arInfo['uuid']]))
      {
        $userList[$arInfo['uuid']]['c'] = 1;
      }
      else
      {
        $userList[$arInfo['uuid']]['c']++;
      }

      $userList[$arInfo['uuid']]['time'] = $arInfo['time'];

      $howMuchRow++;
    }
  }
  $log .= "collect data:" . number_format(microtime(true) - $s, 4) . "ms;";
  $arLastVisit = getUserList(array_keys($userList));
  $log .= "get data for insert:" . number_format(microtime(true) - $s, 4) . "ms;";
  /** don't work */
  if (!empty($arLastVisit))
  {
    $sql = prepareSQL4UpdateUserType(
        $userList,
        $arLastVisit,
        $userTypes
    );
    $res_ins = executeSQL($sql, 'user_property');

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
        if (!$arLastVisit)
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
  }
  $log .= "insert/update:" . number_format(microtime(true) - $s, 4) . "ms;";

  $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
  $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
  writeLog(basename(__FILE__), $log);
}

for ($i = 0; $i < 1; $i++)
  work1();
