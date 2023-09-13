<?php

/**
 * Скрипт по добавлению глобального идентификатора пользователя.
 * Алгоритм работы:
 * 1 - Из очерди берем данные по uuid и event_value.
 * 2 - проверяем, что значение - это логин. (Если event_valuе имели вид телефона начинающегося на +7, 7 или 8 и далее
 * цифры).
 * 3 - если значение не логин, то выходим из алгоритма и удаляем данные очереди
 * 4 - если значение логин, то пишем в таблицу многие ко многим данные. (uuid, login),
 * где uuid - ид пользователя,
 * login - логин пользователя,
 * 5 - получившуюся строку с предыдущего шага мы вставляем в события на необходимый id (он будет в очереди на шаге 1)
 * 6 - 2 глобальных идентификтора вставляем в таблицу секвенсора через очередь (могуть быть длинные update, и лучше их
 * делать фоном). Обновляем данные только в рамках месяца (id глобальных идентификатором не меняются)
 */

include_once __DIR__ . '/../../../lib/autoload.php';

/**
 * Мастер функция для работы крона
 */
function work1()
{
  $queue = new \queue\queues\QueueEvents();
  $user = new \UserLogin\UserLogin();
  $events = new \EventList\EventList();
  $ptv = new \ptv\PTV();
  $queue4Sequencer = new \queue\queues\QueueSequencerLogin();
  //$s = microtime(true);
  // получение данных из очереди
  $arQueue = $queue->_list([], [
      'state' => '4'
  ], [
      'time' => 'DESC'
  ], "1000");
  //echo "выборка из очереди: " . number_format(microtime(true) - $s) . " s\n";

  // если есть данные очереди
  $arId = [];
  if (!empty($arQueue))
  {
    for ($i = 0; $i < $ic = count($arQueue); $i++)
    {
      if (!empty($arQueue[$i]['param']))
      {
        $jsonParam = json_decode($arQueue[$i]['param'], true);
        if (!empty($jsonParam))
        {
          $arQueue[$i]['param'] = $jsonParam;
        }
      }
      // записываем id очереди
      $arId[] = $arQueue[$i]['id'];
    }
    //echo "выборка из очереди: " . number_format(microtime(true) - $s) . " s\n";

    // меняем статус
    $queue->edit(['state' => '5'],
        [
            [
                'id',
                'in',
                $arId
            ]
        ], []);
    //echo "изменение данных в очереди: " . number_format(microtime(true) - $s) . " s\n";

    $XMLClient = new \tools\XMLClient();
    $PTVHelper = new \ptv\PTVHelper();
    $configPTV = setConfig('ptv/ptv');
    // обрабатываем получившиеся данные
    for ($i = 0; $i < $ic = count($arQueue); $i++)
    {
      // смотрим данные по значению event_value
      if (!empty($arQueue[$i]['param']['event_value'])
          && !empty($arQueue[$i]['param']['event_type'])
          && 'b2c_lk' == $arQueue[$i]['param']['event_type']
          && !empty($arQueue[$i]['param']['event_category'])
          && 'login' == $arQueue[$i]['param']['event_category']
      )
      {
        // если телефон
        if ($queue->isPhone($arQueue[$i]['param']['event_value'])
            && !empty($arQueue[$i]['param']['uuid'])
        )
        {
          $login = $arQueue[$i]['param']['event_value'];
          // готовим данные
          $insert = [
              'md5uuid' => md5($arQueue[$i]['param']['uuid']),
              'md5login' => md5($login),
              'login' => $login,
              'uuid' => $arQueue[$i]['param']['uuid']
          ];
          $user->save($insert, []);
          // записываем данные в таблицу event_list
          $events->edit($insert, [
              'seance' => $arQueue[$i]['param']['_mstats']
          ], []);

          if (!empty($login))
          {
            $ptvRequest = $PTVHelper->prepareRequest($login);
            $ptvResponse = $XMLClient->request($configPTV['host'], $configPTV['user'], $configPTV['password'], $ptvRequest);
            // Коннект к PTV
            $ptvDataArray = $PTVHelper->parseResponse($ptvResponse, true);
            $ptvDataArray['md5uuid'] = md5($arQueue[$i]['param']['uuid']);
            $ptvDataArray['md5login'] = md5($login);

            $ptv->save($ptvDataArray, []);
          }
        }
        else
        {
          $insert = [
              'md5uuid' => md5($arQueue[$i]['param']['uuid']),
              'md5login' => md5($arQueue[$i]['param']['uuid'])
          ];
          // если нет логина
          if (!empty($arQueue[$i]['param']['id']))
          {
            $events->edit($insert, [
                'id' => $arQueue[$i]['param']['id']
            ], []);
          }
        }
      }
      else
      {
        $insert = [
            'md5uuid' => md5($arQueue[$i]['param']['uuid']),
            'md5login' => md5($arQueue[$i]['param']['uuid']),
        ];
        // если нет логина
        if (!empty($arQueue[$i]['param']['id']))
        {
          $events->edit($insert, [
              'id' => $arQueue[$i]['param']['id']
          ], []);
        }
      }

      // вставку в секвенсор делаем через отдельную очередь и делаем через сеанс
      $queue4Sequencer->save([
          'id' => md5(time() . uniqid()),
          'state' => '1',
          'param' => json_encode(array_merge(
              $insert,
              ['seance' => $arQueue[$i]['param']['_mstats']]
          )),
          'time' => time()
      ]);
    }

    //echo "обработка всех записей: " . number_format(microtime(true) - $s) . " s\n";
  }

  // удаляем данные из очереди
  if (!empty($arId))
  {
    $queue->remove([
        [
            'id',
            'in',
            $arId
        ],
        [
            'state',
            '=',
            '5'
        ]
    ]);
  }
}

work1();
