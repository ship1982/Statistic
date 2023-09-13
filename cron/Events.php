<?php

include_once __DIR__ . '/../lib/autoload.php';

function work1()
{
  $queue = new \queue\queues\QueueEvents();
  $arQueue = $queue->_list([], [
      'state' => 1
  ], ['time' => 'DESC'], 1000);

  if (!empty($arQueue))
  {
    /**
     * если данные есть, то обрабатываем их
     * собираем данные по всем 1000 записям
     * переводим данные в очереди в обработку
     */
    $arQueueIds = $arDecodedData = [];
    for ($i = 0; $i < $ic = count($arQueue); $i++)
    {
      // получение id очереди
      $arQueueIds[] = $arQueue[$i]['id'];
      // получение параметров пользователя
      if (!empty($arQueue[$i]['param']))
      {
        $arDecodedData[] = json_decode($arQueue[$i]['param'], true);
      }
    }

    // обновляем данные очереди
    if (!empty($arQueueIds))
    {
      $queue->edit(['state' => '2'], [
          [
              'id',
              'IN',
              $arQueueIds
          ]
      ], []);
    }

    // обработка данных
    if (!empty($arDecodedData))
    {
      $events = new \EventList\EventList();
      $events->batchSave($arDecodedData, true);
      $events->batchEnd();
    }

    // удаляем данные из очереди
    $queue->remove([
        [
            'id',
            'IN',
            $arQueueIds
        ]
    ]);
  }
}

work1();