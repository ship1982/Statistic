<?php

/**
 * Алгоритм работы.
 * 1) Берем данные из очереди, после чего ставим их в обработку
 * 2) если в данных есть сеанс, то обновляем данные за текущий месяц по этому сеансу,
 * если время добавления меньше чем час,
 * как наступили другие сутки и эти другие сутки попадают на таблицу предыдущего месяца,
 * то необходимо обновить данные в предыдущем месяце.
 * 3) после удаляем данные из очереди.
 */
include_once __DIR__ . '/../../../lib/autoload.php';

/**
 * Мастер скрипт
 */
function work()
{
  $cacheVariable = [];
  $queue = new \queue\queues\QueueSequencerLogin();
  // получаем данные из таблицы очереди
  $arQueue = $queue->_list([], [
      'state' => '1'
  ], [
      'time' => 'DESC'
  ], "1000");

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

    // меняем статус
    $queue->edit(['state' => '2'], [
        [
            'id',
            'in',
            $arId
        ]
    ], []);

    // основная обработка
    if (!empty($arQueue))
    {
      for ($i = 0; $i < $ic = count($arQueue); $i++)
      {
        if (!empty($arQueue[$i]['param']['seance']))
        {
          // данные на текущий месяц
          $time = date("Y-m-01") . ' 00:00:00';
          // если класса нет в кэше, то добавить его туда
          if (empty($cacheVariable[$time]))
          {
            // инициализуруем класс
            $sequencer = new \model\Model([strtotime($time)], 'l_sequence_4_user');
            $cacheVariable[$time] = $sequencer;
          }

          // если данные за предыдущий месяц
          if (time() - 3600 <= $arQueue[$i]['time']
              && $time < date("Y-m-01", $arQueue[$i]['time']) . " 00:00:00"
          )
          {
            $time = strtotime($time, 'last month');
            // если класса нет в кэше, то добавить его туда
            if (!empty($cacheVariable[$time]))
            {
              // инициализуруем класс
              $sequencer = new \model\Model([strtotime($time)], 'l_sequence_4_user');
              $cacheVariable[$time] = $sequencer;
            }
          }

          // делаем вставку, если класс есть
          if (!empty($cacheVariable[$time]))
          {
            $cacheVariable[$time]->edit($arQueue[$i]['param'], [
                'seance' => $arQueue[$i]['param']['seance']
            ], []);
          }
        }
      }
    }

    // удаляем данные из очереди
    $queue->remove([
        [
            'id',
            'in',
            $arId
        ]
    ]);
  }
}

work();