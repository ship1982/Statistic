<?php

/**
 * Разгребает очередь событий (queue_events) и производит основную работу по вставке данных в таблицу event_list.
 * Алгоритм работы таков:
 * 1) выбираются 1000 записей из таблицы очередей
 * 2) сразу после этого всем им выбранным записям ставится статус 2 (в обработке)
 * 3) испольняетя основной код по обработке данных
 * 4) при успешной обработке данных запись удаляется из таблицы очереди, при не успешной, ставится в статус 3
 *
 * Параметры в событиях:
 * mstat('send','b2c_lk', 'login', 'error_login', 'ЛОГИН'); - где
 * send - служебное слово, определяющее, что данных хит есть событие
 * b2c_lk - тип события
 * login - категория события
 * error_login - метка (лэйбл) события
 * ЛОГИН - значение события
 */

// подулючаем основные файлы из общих библиотек
use services\MainService;

include_once __DIR__ . '/../../../lib/autoload.php';

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
  // get data form queue
  //$s = microtime(true);
  $service = new MainService();
  $answer = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get',
      'queue' => 'events',
      'state' => '1'
  ]);
  //echo "получение данных из очереди: " . number_format(microtime(true) - $s) . " ms\n";

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
              'queue' => 'events',
              'id' => $queueParams[$i]['id'],
              'param' => $queueParams[$i]['param'],
              'state' => 2
          ]);
        }
      }
    }

    //echo "изменение данных в очереди: " . number_format(microtime(true) - $s) . " ms\n";

    // main processing
    event_mainProcess($queueParams);
  }
}

work1();