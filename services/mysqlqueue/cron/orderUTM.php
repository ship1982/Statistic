<?php

use services\MainService;

include_once(__DIR__ . '/../../../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');
set_time_limit(0);

/**
 * @constructor
 */
function work1()
{
  $s = microtime(true);
  $log = cron_fls('start', $s);

  // get data form queue
  $service = new MainService();
  $answer = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get',
      'queue' => '4620',
      'state' => 0
  ]);

  // process data
  if (!empty($answer))
  {
    // set state for processing
    $queueParams = json_decode($answer, true);
    if (!empty($queueParams)
        && is_array($queueParams)
    )
    {
      for ($i = 0; $i < $ic = count($queueParams); $i++)
      {
        if (!empty($queueParams[$i]['id']))
        {
          $service->query('mysqlqueue', [
              'method' => 'mysqliqueue_update',
              'queue' => '4620',
              'id' => $queueParams[$i]['id'],
              'param' => $queueParams[$i]['param'],
              'state' => 2
          ]);
        }
      }
    }

    $service->query('orderutm', [
        'method' => 'orderutm_get',
        'data' => $answer
    ]);
  }
}

work1();