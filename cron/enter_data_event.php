<?php

include_once __DIR__ . '/../lib/autoload.php';

/**
 * Скрипт для обновления в таблице event_list данных по
 * - utm меткам
 * - реферерам
 * Данные обновляются следующим образом:
 * 1) берем данные из очереди событий
 * 2) выбираем по данным записям необходимые метрики, которые были при первом шаге посещения
 * 3) дублируем эти данные на всю сессиию далее.
 */
function work()
{
  // подключаем апи для очереди
  $queue = new \QueueEnter\QueueEnterEvents();
  // получаем данные из очереди
  $arQueue = $queue->_list([], [
      'state' => '1'
  ], [], 1000);
  if (!empty($arQueue))
  {
    /**
     * если данные есть, то обрабатываем их
     * собираем данные по всем 1000 записям
     * переводим данные в очереди в обработку
     */
    $arSeanceData = $arQueueIds = [];
    for ($i = 0; $i < $ic = count($arQueue); $i++)
    {
      // получение id очереди
      $arQueueIds[] = $arQueue[$i]['id'];
      // получение параметров пользователя
      if (!empty($arQueue[$i]['param']))
      {
        $arDecodedData = json_decode($arQueue[$i]['param'], true);
      }
      else
      {
        $arDecodedData = [];
      }

      if(!empty($arDecodedData['seance']))
      {
        $arSeanceData[$arDecodedData['seance']] = $arDecodedData['seance'];
      }
    }
    // обновляем данные очереди
    $queue->edit(['state' => '2'], [
        ['id', 'IN', $arQueueIds]
    ], []);

    // получаем параметры для каждого шага
    $sequencer = new \model\Model([$arQueue[0]['time']], 'dirty');
    $arStepOneData = $sequencer->_list([
        'referrer',
        'utm_term',
        'utm_content',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'seance'
    ], [
        ['seance', 'IN', $arSeanceData],
        ['step', '=','1']
    ], [], 1000);

    // если вернулись данные, то обновляем в таблице данные по сессии
    if (!empty($arStepOneData))
    {
      $events = new \EventList\EventList();
      for ($a = 0; $a < $ac = count($arStepOneData); $a++)
      {
        $host = $link = NULL;
        if (!empty($arStepOneData[$a]['referrer']))
        {
          $arLink = parse_url($arStepOneData[$a]['referrer']);
          if (!empty($arLink['host']))
          {
            $host = $arLink['host'];
          }

          if (!empty($arLink['path']))
          {
            $link = $arLink['path'] . common_setValue($arLink, 'query', '');
          }
        }
        $events->edit([
            'enter_referer_link' => $link,
            'enter_referer_domain' => $host,
            'enter_utm_term' => common_setValue($arStepOneData[$a], 'utm_term', NULL),
            'enter_utm_content' => common_setValue($arStepOneData[$a], 'utm_content', NULL),
            'enter_utm_source' => common_setValue($arStepOneData[$a], 'utm_source', NULL),
            'enter_utm_medium' => common_setValue($arStepOneData[$a], 'utm_medium', NULL),
            'enter_utm_campaign' => common_setValue($arStepOneData[$a], 'utm_campaign', NULL),
        ], ['seance' => $arStepOneData[$a]['seance']], []);
      }
    }

    $queue->remove([
        ['id', 'IN', $arQueueIds]
    ]);
  }
}

work();