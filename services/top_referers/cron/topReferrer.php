<?php

use model\Model;

include_once __DIR__ . '/../../../lib/autoload.php';

function work1()
{
  //$s = microtime(true);
  // определяем переменные
  $var1 = $var2 = [];
  // получаем данные из очереди
  $queue = new \TopReferrers\TopReferrers();
  $arQueue = $queue->_list([
      'id',
      'param'
  ], [
      'state' => 1
  ], [], '0,1000');
  //echo "получение данных из очереди: " . number_format(microtime(true) - $s) . " s\n";

  if (!empty($arQueue))
  {
    // проставляем статус в обработке
    $arIdsQueue = [];
    for ($i = 0; $i < $ic = count($arQueue); $i++)
    {
      $arIdsQueue[] = $arQueue[$i]['id'];
      $arQueue[$i]['param'] = json_decode($arQueue[$i]['param'], true);
    }

    if (!empty($arIdsQueue))
    {
      $queue->edit([
          'state' => 2
      ], [
          ['id', 'IN', $arIdsQueue]
      ], []);
    }

    //echo "изменение данных из очереди: " . number_format(microtime(true) - $s) . " s\n";

    // обработка
    for ($i = 0; $i < $ic = count($arQueue); $i++)
    {
      $item = $arQueue[$i];
      // если в очереди не все параметры, то такую очередь нужно удалить
      if (!is_array($item['param'])
          || empty($item['param']['domain'])
          || empty($item['param']['time'])
          || empty($item['param']['uuid'])
          || !empty($item['param']['_t'])
      )
      {
        $queue->remove(
            ['id' => $item['id']]
        );
        continue;
      }

      if (!empty($item['param']['referrer']))
      {
        $datehour = date('Ymd', $item['param']['time']);
        $partnerDomain = str_replace('www.', '', $item['param']['domain']);

        // если не получилось найти реферера, то ставим в ошибку
        $refComponents = parse_url($item['param']['referrer']);
        if ($refComponents === false
            || empty($refComponents['host'])
        )
        {
          $queue->edit(
              ['state' => 3],
              ['id' => $item['id']],
              []
          );
          continue;
        }

        $refererDomain = idn_to_utf8($refComponents['host']);
        if ($refererDomain === false)
        {
          $queue->edit(
              ['state' => 3],
              ['id' => $item['id']],
              []
          );
          continue;
        }

        $refererLink = !empty($refComponents['path']) ? $refComponents['path'] : '/';
        $refererLink .= !empty($refComponents['query']) ? '?'.$refComponents['query'] : '';
        $refererLink .= !empty($refComponents['fragment']) ? '#'.$refComponents['fragment'] : '';

        //учитываются только внешние ссылки
        if ($refererDomain !== $partnerDomain)
        {
          $internalWay = ';' . $partnerDomain . ';';

          if (empty($refererDomain)
              || preg_match('/(mts\.|mgts\.)/', $refererDomain)
          )
          {
            $internalLinks = json_encode([
                't' => $item['param']['time'],
                'pd' => $partnerDomain,
                'rd' => $refererDomain,
                'rl' => $refererLink
            ]);
            $externalLinks = "";
          }
          else
          {
            $internalLinks = "";
            $externalLinks = json_encode([
                't' => $item['param']['time'],
                'pd' => $partnerDomain,
                'rd' => $refererDomain,
                'rl' => $refererLink
            ]);
          }

          $shardKey = $queue->getShardKey($item['param']['time']);

          // формируем данные для первой очереди
          $var1[$shardKey][] = [
              'uuid' => $item['param']['uuid'],
              'internal_way' => $internalWay,
              'internal_links' => $internalLinks,
              'external_links' => $externalLinks,
              'time_start' => $item['param']['time'],
              'time_end' => $item['param']['time']
          ];

          // формируем данные для второй очереди
          if (!empty($refererLink))
          {
            $hash = md5($datehour . $partnerDomain . $refererDomain . $refererLink);
            $var2[$shardKey][] = [
                'id' => $hash,
                'datehour' => $datehour,
                'partner_domain' => $partnerDomain,
                'referer_link' => $refererLink,
                'referer_domain' => $refererDomain,
                'frequency' => 1,
                'time_label' => $item['param']['time']
            ];
          }
        }
      }
    }

    //echo "обработка данных: " . number_format(microtime(true) - $s) . " s\n";

    // исполняем запрос для первой очереди
    if (!empty($var1))
    {
      foreach ($var1 as $shard => $variables)
      {
        $model = new Model([$shard], 'topReferers_links');
        for ($z = 0; $z < $zc = count($variables); $z++)
        {
          $model->insert($variables[$z]);
          $model->query(" ON DUPLICATE KEY UPDATE 
                      internal_way=IF(INSTR(internal_way, VALUES(internal_way)) = 0, CONCAT(internal_way, ' ', VALUES(internal_way)), internal_way), 
                      internal_links=IF(VALUES(internal_links) != '', CONCAT(internal_links, ',', VALUES(internal_links)), internal_links),
                      external_links=IF(VALUES(external_links) != '', CONCAT(external_links, ',', VALUES(external_links)), external_links),
                      time_end = VALUES(time_end);", [], false);
        }
        $model->multiexecute();
      }
    }
    //echo "первая пачка: " . number_format(microtime(true) - $s) . " s\n";

    // исполняем запрос для второй очереди
    if (!empty($var2))
    {
      foreach ($var2 as $shard => $variables)
      {
        $model = new Model([$shard], 'topReferers_linksCount');
        for ($z = 0; $z < $zc = count($variables); $z++)
        {
          $model->insert($variables[$z]);
          $model->query(" ON DUPLICATE KEY UPDATE frequency=frequency+1; ", [], false);
        }
        $model->multiexecute();
      }
    }
    //echo "первая пачка: " . number_format(microtime(true) - $s) . " s\n";

    if (!empty($arIdsQueue))
    {
      $queue->remove([
          ['id', 'IN', $arIdsQueue],
          ['state', '<>', 3]
      ]);
    }
  }
}

work1();