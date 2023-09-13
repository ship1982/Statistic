<?php

/**
 * Разгребает очередь событий (queue_events) и производит основную работу по вставке данных в таблицу event_list.
 * Алгоритм работы таков:
 * 1) выбираются 1000 записей из таблицы очередей
 * 2) сразу после этого всем им выбранным записям ставится статус 2 (в обработке)
 * 3) испольняетя основной код по обработке данных
 * 4) при успешной обработке данных запись удаляется из таблицы очереди, при не успешной, ставится в статус 3
 */
// подулючаем основные файлы из общих библиотек
use services\MainService;

include_once(__DIR__ . '/../../../lib/common/common.php');

// подключаем осонвную библиотеку обработчик
include_once(__DIR__ . '/../models/event.php');

// подлючаем вспомогательные библиотеки
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
    'queue' => 'events',
    'state' => 0
  ]);

  // process data
  if(!empty($answer))
  {
    // set state for processing
    $queueParams = json_decode($answer, true);
    if(!empty($queueParams)
      && is_array($queueParams)
    )
    {
      for ($i=0; $i < $ic = count($queueParams); $i++)
      { 
        if(!empty($queueParams[$i]['id']))
        {
          $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_update',
            'queue' => 'events',
            'id' => $queueParams[$i]['id'],
            'param' => $queueParams[$i]['param'],
            'state' => 2
          ]);
        }
      }
    }

  	// main processing
    event_mainProcess($queueParams);
  }
}

work1();