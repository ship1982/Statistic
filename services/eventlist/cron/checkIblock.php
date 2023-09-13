<?php

/**
 * Алгоритм работы:
 * 1) Смотрит таблицу с очереди и выгружаем по ней список из 1000 последних данных
 * 2) При обработке запоминаем какого юзера мы уже обрабтаывали, чтобы повторно в рамках одной сессии его не выгружать
 * 3) записываем в таблицу событий признак того, включен ли у пользователя adBlock или нет
 * 
 */

// подулючаем основные файлы из общих библиотек
use services\MainService;

include_once __DIR__ . '/../../../lib/autoload.php';

// подключаем основную библиотеку обработчик
include_once __DIR__ . '/../models/eventlist.php';

// подлючаем вспомогательные библиотеки
common_inc('system/cron', 'cron');
set_time_limit(0);

/**
 * @constructor
 */
function work1()
{
	// получаем данные из очереди
  $service = new MainService();
  $answer = $service->query('mysqlqueue', [
    'method' => 'mysqliqueue_get',
    'queue' => 'adblock',
    'state' => 1
  ]);

  // обработка данных
  if(!empty($answer))
  {
    // устанавливаем новое состояние в очереди, чтобы не брать записи повторно
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
            'queue' => 'adblock',
            'id' => $queueParams[$i]['id'],
            'param' => $queueParams[$i]['param'],
            'state' => 2
          ]);
        }
      }
    }
  	// основная функция обработки
    eventlist_updateAdblockInEventList($queueParams);
  }
}
work1();
