<?php

use model\Model;

include_once(__DIR__ . '/../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');

/**
 * @constructor
 */
function work1()
{
  $s = microtime(true);
  $queue = new \queue\queues\QueueBotChecker();
  $rsList = $queue->_list([
      'id',
      'time',
      'param'
  ], [
      [
          'state',
          '=',
          1
      ]
  ], [], 1000);
  echo "get data from queue: " . number_format(microtime(true) - $s) . " s\n";
  $arQueueIds = [];
  if (!empty($rsList))
  {
    // инициализация классов
    common_inc('api/repeate_actions', 'repeate_actions');
    $repeateActions = new repeateActions();
    $userProperty = new \UserProperty\UserProperty();
    $modelEvent = new \EventList\EventList();
    $modelUserlistOnline = new \UserlistOnline\UserlistOnline();
    $modelUserDiffByDay = new Model([1], 'user_diff_by_day');
    $modelTopDetalizer = new Model([1], 'top_detalizer');
    $modelTopDetalizerCity = new Model([1], 'top_detalizer_city');
    $modelTopDetalizerProvider = new Model([1], 'top_detalizer_provider');
    $modelTopDomainLink = new Model([1], 'top_domain_link');
    $modelTopDomainLinkCity = new Model([1], 'top_domain_link_city');
    $modelTopDomainLinkProvider = new Model([1], 'top_domain_link_provider');
    $modelTopDomainLinkUuid = new Model([1], 'top_domain_link_uuid');
    $modelTopDomainLinkCityUuid = new Model([1], 'top_domain_link_city_uuid');
    $modelTopDomainLinkProviderUuid = new Model([1], 'top_domain_link_provider_uuid');
    $executedArray = [];
    $arListDomain = [];
    $arListReferrer = [];
    $arStartReferrer = [];
    $arListLink = [];

    for ($i = 0; $i < $ic = count($rsList); $i++)
    {
      $arQueueIds[] = $rsList[$i]['id'];
      $rsList[$i]['param'] = json_decode($rsList[$i]['param'], true);
    }

    if (!empty($arQueueIds))
    {
      $queue->edit([
          'state' => 2
      ], [
          [
              'id',
              'IN',
              $arQueueIds
          ]
      ], []);
    }
    echo "update queue: " . number_format(microtime(true) - $s) . " s\n";

    $checkedUUID = [];
    for ($i = 0; $i < $ic = count($rsList); $i++)
    {
      $arData = $rsList[$i];
      if (is_array($arData)
          && !empty($arData['param']['uuid'])
      )
      {
        // получаем список id для доменов
        if (!empty($arData['param']['domain']))
        {
          $arListDomain[$arData['param']['domain']] = $arData['param']['domain'];
        }

        // получение рефереров
        if (!empty($arData['param']['referrer']))
        {
          $refComponents = parse_url($arData['param']['referrer']);
          $arListReferrer[$refComponents['host']] = $refComponents['host'];
        }

        // получаем первого реферера
        if (!empty($arData['param']['start_referrer']))
        {
          $arStartReferrer[$arData['param']['start_referrer']] = $arData['param']['start_referrer'];
        }

        // получам ссылку
        if (!empty($arData['param']['link']))
        {
          $arListLink[$arData['param']['link']] = $arData['param']['link'];
        }
      }
    }
    echo "collect: " . number_format(microtime(true) - $s) . " s\n";

    // получаем id по ссылкам и доменам
    $arDomains = $arReferrers = $arStartReferrers = $arLink = [];
    if (!empty($arListDomain))
    {
      $domains = new \Domain\Domain();
      $arDomains = $domains->_list([
          'id',
          'name'
      ], [
          [
              'name',
              'IN',
              $arListDomain
          ]
      ]);

      if (!empty($arDomains))
      {
        $arDomains = \native\arrays\ArrayHelper::map($arDomains, 'name', 'id');
      }
    }

    if (!empty($arListReferrer))
    {
      $domains = new \Domain\Domain();
      $arReferrers = $domains->_list([
          'id',
          'name'
      ], [
          [
              'name',
              'IN',
              $arListReferrer
          ]
      ]);

      if (!empty($arReferrers))
      {
        $arReferrers = \native\arrays\ArrayHelper::map($arReferrers, 'name', 'id');
      }
    }

    if (!empty($arStartReferrer))
    {
      $domains = new \Domain\Domain();
      $arStartReferrer = $domains->_list([
          'id',
          'name'
      ], [
          [
              'name',
              'IN',
              $arStartReferrer
          ]
      ]);

      if (!empty($arStartReferrer))
      {
        $arStartReferrer = \native\arrays\ArrayHelper::map($arStartReferrer, 'name', 'id');
      }
    }

    if (!empty($arListLink))
    {
      $links = new \Link\Link();
      $arLink = $links->_list([
          'id',
          'domain_link'
      ], [
          [
              'domain_link',
              'IN',
              $arListLink
          ]
      ]);

      if (!empty($arLink))
      {
        $arLink = \native\arrays\ArrayHelper::map($arLink, 'domain_link', 'id');
      }
    }
    echo "get collect: " . number_format(microtime(true) - $s) . " s\n";

    // когда получили данные, строим запросы на обработку
    for ($i = 0; $i < $ic = count($rsList); $i++)
    {
      $arData = $rsList[$i]['param'];
      if (is_array($arData)
          && !empty($arData['uuid'])
      )
      {
        $update = [];
        $update['os'] = common_setValue($arData, 'os', null);
        $update['browser'] = common_setValue($arData, 'browser', null);
        $update['ip'] = common_setValue($arData, 'ip', null);

        // проверка на бота по коссвенным признакам (по юзерагенту)
        if (!empty($arData['botname']))
        {
          $update['botname'] = $arData['botname'];
          $update['bot'] = 1;
          if ($arData['botname'] == 'unknown')
          {
            $update['useful'] = 0;
          }
          else
          {
            $update['useful'] = 1;
          }
        }
        else
        {
          $update['botname'] = null;
          $update['bot'] = 0;
          $update['useful'] = 0;
        }

        /**
         * Получим процентную вероятность наличия бота для пользователя
         */
        if ($update['bot'] === 0)
        {
          $repeateActions->setter_time_end(time());
          $repeateActions->setter_uuid($arData['uuid']);
          $repeateActions->setter_type_access_site('for_uuid');
          $update['percent_is_bot'] = $repeateActions->getter_isbot_percent();
          $update['is_bot'] = ($update['percent_is_bot'] >= 90) ? 1 : 0;
        }
        else
        {
          $update['percent_is_bot'] = 100;
          $update['is_bot'] = 1;
        }

        $userProperty->update($update, [
            'uuid' => $arData['uuid']
        ]);
        $userProperty->query(";", [], false);

        // обновление статуса бот/не-бот и адблок в таблицах для отчетов
        if (empty($checkedUUID[$arData['uuid']]))
        {
          $modelEvent->update($update, [
              'uuid' => $arData['uuid']
          ]);
          $modelEvent->query(";", [], false);

          if (!empty($arData['time']))
          {
            $modelForUpdate = new Model([$arData['time']], 'l_sequence_4_user');
            $modelForUpdate->update($update, [
                'uuid' => $arData['uuid']
            ]);
            $modelForUpdate->query(";", [], false);
            @$executedArray[$modelForUpdate->getTable()] .= $modelForUpdate->showQuery();
          }
        }

        if (!empty($arData['time']))
        {
          $datehour = date('Ymd', $arData['time']);
          $hour = ltrim(date('H', $arData['time']), '0');
          $startDayTime = strtotime(date('Y-m-d', $arData['time']) . ' 00:00:00');
          $startMonthTime = strtotime(date('Y-m', $arData['time']) . '-01 00:00:00');
          $modelUserlistOnline->update($update, [
              'uuid' => $arData['uuid'],
              'datehour' => $datehour
          ]);
          $modelUserlistOnline->query(";", [], false);
          if (!isset($arData['domain'])
              || !isset($arData['link'])
          )
          {
            continue;
          }

          if (1 === $update['is_bot'])
          {
            $modelUserDiffByDay->query("UPDATE `user_diff_by_day` SET `count_bot` = `count_bot`+1
                      WHERE `datehour` = {{datehour}}
                      AND `domain` = {{domain}}
                      AND `list` LIKE {{list}};", [
                'domain' => $arData['domain'],
                'datehour' => $datehour,
                'list' => '%' . $arData['uuid'] . '%'
            ], false);
          }

          $domainId = (empty($arDomains[$arData['domain']]) ? 0 : $arDomains[$arData['domain']]);
          $linkId = (empty($arLink[$arData['link']]) ? 0 : $arLink[$arData['link']]);
          $refererId = (empty($arReferrers[$arData['referrer']]) ? 0 : $arReferrers[$arData['referrer']]);
          $startRefererId = (empty($arStartReferrer[$arData['referrer']]) ? 0 : $arStartReferrer[$arData['referrer']]);
          $refererIdStr = (0 === $refererId ? '' : $refererId);

          if (!empty($domainId)
              && !empty($linkId)
          )
          {
            if (empty($checkedUUID[$arData['uuid']]))
            {
              /*counter_domain group*/
              $modelForUpdate = new Model([$arData['time']], 'counter_domain');
              $modelForUpdate->query("UPDATE `counter_domain_{$startMonthTime}` SET `is_bot` = `is_bot`+1
              WHERE `domain` = {{domain}}
              AND `uuid` = {{uuid}};", [
                  'domain' => $domainId,
                  'uuid' => $arData['uuid']
              ], false);
              @$executedArray[$modelForUpdate->getTable()] .= $modelForUpdate->showQuery();

              $modelForUpdate = new Model([$arData['time']], 'counter_link');
              $modelForUpdate->query("
                        UPDATE `counter_link_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {{domain}}
                        AND `link` = {{link}}
                        AND `uuid` = {{uuid}};
                    ", [
                  'domain' => $domainId,
                  'link' => $linkId,
                  'uuid' => $arData['uuid']
              ], false);
              @$executedArray[$modelForUpdate->getTable()] .= $modelForUpdate->showQuery();

              $modelForUpdate = new Model([$arData['time']], 'counter_ref_domain');
              $modelForUpdate->query("
                        UPDATE `counter_ref_domain_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {{domain}}
                        AND `uuid` = {{uuid}}
                        AND `referrer` = {{referrer}};
                    ", [
                  'domain' => $domainId,
                  'uuid' => $arData['uuid'],
                  'referrer' => $refererId
              ], false);
              @$executedArray[$modelForUpdate->getTable()] .= $modelForUpdate->showQuery();

              $modelForUpdate = new Model([$arData['time']], 'counter_ref_link');
              $modelForUpdate->query("
                        UPDATE `counter_ref_link_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {{domain}}
                        AND `link` = {{link}}
                        AND `uuid` = {{uuid}}
                        AND `referrer` = {{referrer}};
                    ", [
                  'domain' => $domainId,
                  'link' => $linkId,
                  'uuid' => $arData['uuid'],
                  'referrer' => $refererIdStr
              ], false);
              @$executedArray[$modelForUpdate->getTable()] .= $modelForUpdate->showQuery();

              $modelForUpdate = new Model([$arData['time']], 'start_referrer');
              $modelForUpdate->query("
                        UPDATE `start_referrer_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {{domain}}
                        AND `uuid` = {{uuid}}
                        AND `referrer` = {{referrer}};
                    ", [
                  'domain' => $domainId,
                  'uuid' => $arData['uuid'],
                  'referrer' => $startRefererId
              ], false);
              @$executedArray[$modelForUpdate->getTable()] .= $modelForUpdate->showQuery();

              /* top_detalizer group */
              $modelTopDetalizer->query("
                          UPDATE `top_detalizer` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `hour`={{hour}}
                          AND `domain`={{domain}}
                          AND `time`={{time}}
                          AND `link`={{link}}
                          AND `list_uuid` LIKE {{list_uuid}};
                      ", [
                  'hour' => $hour,
                  'domain' => $domainId,
                  'time' => $arData['time'],
                  'link' => $linkId,
                  'list_uuid' => '%' . $arData['uuid'] . '%'
              ], false);

              $modelTopDetalizerCity->query("
                          UPDATE `top_detalizer_city` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `hour`={{hour}}
                          AND `domain`={{domain}}
                          AND `time`={{time}}
                          AND `link`={{link}}
                          AND `list_uuid` LIKE {{list_uuid}};
                      ", [
                  'hour' => $hour,
                  'domain' => $domainId,
                  'time' => $startDayTime,
                  'link' => $linkId,
                  'list_uuid' => '%' . $arData['uuid'] . '%'
              ], false);

              $modelTopDetalizerProvider->query("
                        UPDATE `top_detalizer_provider` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                        WHERE `hour`={{hour}}
                        AND `domain`={{domain}}
                        AND `time`={{time}}
                        AND `link`={{link}}
                        AND `list_uuid` LIKE {{list_uuid}};
                    ", [
                  'hour' => $hour,
                  'domain' => $domainId,
                  'time' => $startDayTime,
                  'link' => $linkId,
                  'list_uuid' => '%' . $arData['uuid'] . '%'
              ], false);

              /*top_domain_link group*/
              $modelTopDomainLink->query("
                          UPDATE `top_domain_link` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `time`={{time}}
                          AND `link`={{link}}
                          AND `domain`={{domain}}
                          AND `list_uuid` LIKE {{list_uuid}};
                      ", [
                  'time' => $startDayTime,
                  'link' => $linkId,
                  'domain' => $domainId,
                  'list_uuid' => '%' . $arData['uuid'] . '%'
              ], false);

              $modelTopDomainLinkCity->query("
                          UPDATE `top_domain_link_city` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `time`={{time}}
                          AND `link`={{link}}
                          AND `domain`={{domain}}
                          AND `list_uuid` LIKE {{list_uuid}};
                      ", [
                  'time' => $startDayTime,
                  'link' => $linkId,
                  'domain' => $domainId,
                  'list_uuid' => '%' . $arData['uuid'] . '%'
              ], false);

              $providerTableHash = md5($domainId . $startDayTime . $linkId);
              $modelTopDomainLinkProvider->query("
                            UPDATE `top_domain_link_provider` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                            WHERE `hash`={{hash}}
                            AND `list_uuid` LIKE {{list_uuid}};
                        ", [
                  'hash' => $providerTableHash,
                  'list_uuid' => '%' . $arData['uuid'] . '%'
              ], false);

              /*top_domain_link_uuid group*/
              $modelTopDomainLinkUuid->query("
                            UPDATE `top_domain_link_uuid` SET `is_bot` = 1 
                            WHERE `time`={{time}}
                            AND `link`={{link}}
                            AND `domain`={{domain}}
                            AND `uuid` ={{uuid}};
                        ", [
                  'time' => $startDayTime,
                  'link' => $linkId,
                  'domain' => $domainId,
                  'uuid' => $arData['uuid']
              ], false);

              $modelTopDomainLinkCityUuid->query("
                              UPDATE `top_domain_link_city_uuid` SET `is_bot` = 1 
                              WHERE `time`={{time}} 
                              AND `link`={{link}}
                              AND `domain`={{domain}}
                              AND `uuid` ={{uuid}};
                          ", [
                  'time' => $startDayTime,
                  'link' => $linkId,
                  'domain' => $domainId,
                  'uuid' => $arData['uuid']
              ], false);

              $providerTableHash = md5($domainId . $arData['time'] . $linkId . $arData['uuid']);
              $modelTopDomainLinkProviderUuid->query("
                                UPDATE `top_domain_link_provider_uuid` SET `is_bot` = 1 
                                WHERE `hash`={{hash}};
                            ", [
                  'hash' => $providerTableHash
              ], false);
            }
          }
        }
        $checkedUUID[$arData['uuid']] = $arData['uuid'];
      }
    }
    echo "setQuery: " . number_format(microtime(true) - $s) . " s\n";

    // формируем 1 запрос из всех
    $sql = '';
    $sql .= $modelEvent->showQuery();
    $sql .= $userProperty->showQuery();
    $sql .= $modelUserDiffByDay->showQuery();
    $sql .= $modelTopDetalizer->showQuery();
    $sql .= $modelTopDetalizerCity->showQuery();
    $sql .= $modelTopDetalizerProvider->showQuery();
    $sql .= $modelTopDomainLink->showQuery();
    $sql .= $modelTopDomainLinkCity->showQuery();
    $sql .= $modelTopDomainLinkProvider->showQuery();
    $sql .= $modelTopDomainLinkUuid->showQuery();
    $sql .= $modelTopDomainLinkCityUuid->showQuery();
    $sql .= $modelTopDomainLinkProviderUuid->showQuery();
    foreach ($executedArray as $table => $query)
    {
      $sql .= $query;
    }

    // исполняем все запросы
    if (!empty($sql))
    {
      $sql = str_replace("\n", '', $sql);
      $sql = preg_replace("/\s{2,}/", " ", $sql);
      __multyquery($sql);
    }

    echo "end: " . number_format(microtime(true) - $s) . " s\n";
    $queue->remove([['id','IN', $arQueueIds]]);
  }
}

work1();