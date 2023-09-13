<?php

use services\MainService;
use model\Model;

include_once(__DIR__ . '/../lib/autoload.php');
common_inc('_database');
common_inc('system/cron', 'cron');

/**
 * @constructor
 */
function work1()
{
  if (!function_exists('debugMode'))
  {
    function debugMode()
    {
      global $argv;
      if (!empty($argv) && !empty($argv[1]) && $argv[1] === 'debug')
      {
        return true;
      }
      return false;
    }
  }

  if (!function_exists('fillQueryLog'))
  {
    function fillQueryLog($table, $error)
    {
      if (!empty($error))
      {
        return "{$table} - error: {$error}\r\n";
      }
      else
      {
        return "{$table} - ok\r\n";
      }
    }
  }


  $log = "start:" . time() . ";";
  $queryLog = "";

  $service = new MainService();
  $cronName = 'cron_botChecker';
  $s = microtime(true);
  $log .= "prepare:" . number_format(microtime(true) - $s, 4) . "ms;";

  $rsList = $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_get_no_json',
      'queue' => $cronName,
      'state' => 1
  ]);

  $log .= "query dirty:" . number_format(microtime(true) - $s, 4) . "ms;";

  if (!empty($rsList))
  {
    $checkedUUID = [];
    $userProperty = new Model([1], 'user_property');

    for ($i = 0; $i < count($rsList); $i++)
    {
      //РЈСЃС‚Р°РЅРѕРІРёРј СЃС‚Р°С‚СѓСЃ РІ РѕР±СЂР°Р±РѕС‚РєРµ
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_update',
          'queue' => $cronName,
          'id' => $rsList[$i]['id'],
          'state' => 2
      ]);

      $arList = (array_key_exists('id', $rsList[$i]) && array_key_exists('param', $rsList[$i])) ? json_decode($rsList[$i]['param'], true) : null;

      //TODO:debug
      if (debugMode())
      {
        var_dump($arList);
      }

      if (is_array($arList) && !empty($arList['uuid']))
      {
        $update = [];

        $update['os'] = common_setValue($arList, 'os', null);
        $update['browser'] = common_setValue($arList, 'browser', null);
        $update['ip'] = common_setValue($arList, 'ip', null);

        if (!empty($arList['botname']))
        {
          $update['botname'] = $arList['botname'];
          $update['bot'] = 1;
          if ($arList['botname'] == 'unknown')
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
         * РџРѕР»СѓС‡РёРј РїСЂРѕС†РµРЅС‚РЅСѓСЋ РІРµСЂРѕСЏС‚РЅРѕСЃС‚СЊ РЅР°Р»РёС‡РёСЏ Р±РѕС‚Р° РґР»СЏ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
         */
        if ($update['bot'] === 0)
        {
          common_inc('api/repeate_actions', 'repeate_actions');
          $repeateActions = new repeateActions();

          $repeateActions->setter_time_end(time());
          $repeateActions->setter_uuid($arList['uuid']);
          $repeateActions->setter_type_access_site('for_uuid');
          $update['percent_is_bot'] = $repeateActions->getter_isbot_percent();
          $update['is_bot'] = ($update['percent_is_bot'] >= 90) ? 1 : 0;
        }
        else
        {
          $update['percent_is_bot'] = 100;
          $update['is_bot'] = 1;
        }

        $success = $userProperty->edit($update, ['uuid' => $arList['uuid']], []);
        $queryLog .= fillQueryLog('user_property', $userProperty->error);

        if ($success === true)
        {
          //РѕР±РЅРѕРІР»РµРЅРёРµ СЃС‚Р°С‚СѓСЃР° Р±РѕС‚/РЅРµ-Р±РѕС‚ Рё Р°РґР±Р»РѕРє РІ С‚Р°Р±Р»РёС†Р°С… РґР»СЏ РѕС‚С‡РµС‚РѕРІ
          if (!in_array($arList['uuid'], $checkedUUID))
          {
            $modelForUpdate = new Model([1], 'event_list');
            $modelForUpdate->edit($update, ['uuid' => $arList['uuid']], []);
            $queryLog .= fillQueryLog('event_list', $modelForUpdate->error);

            if (!empty($arList['time']))
            {
              $modelForUpdate = new Model([$arList['time']], 'l_sequence_4_user');
              $modelForUpdate->edit($update, ['uuid' => $arList['uuid']], []);
              $queryLog .= fillQueryLog('l_sequence_4_user', $modelForUpdate->error);

              $checkedUUID[] = $arList['uuid'];
            }
          }

          if (!empty($arList['time']))
          {
            $datehour = date('Ymd', $arList['time']);
            $hour = ltrim(date('H', $arList['time']), '0');
            $startDayTime = strtotime(date('Y-m-d', $arList['time']) . ' 00:00:00');
            $startMonthTime = strtotime(date('Y-m', $arList['time']) . '-01 00:00:00');

            $modelForUpdate = new Model([1], 'userlist_online');
            $modelForUpdate->edit($update,
                [
                    'uuid' => $arList['uuid'],
                    'datehour' => $datehour
                ],
                []
            );
            $queryLog .= fillQueryLog('userlist_online', $modelForUpdate->error);

            if (!isset($arList['domain']) || !isset($arList['link']))
            {
              continue;
            }

            if ($update['is_bot'] === 1)
            {
              $domainPrepare = $modelForUpdate->prepare('domain', $arList['domain']);
              $uuidPrepare = "'%" . $modelForUpdate->prepare('list', $arList['uuid'], true) . "%'";

              $modelForUpdate = new Model([1], 'user_diff_by_day');
              $modelForUpdate->query("
                      UPDATE `user_diff_by_day` SET `count_bot` = `count_bot`+1
                      WHERE `datehour` = '{$datehour}'
                      AND `domain` = {$domainPrepare}
                      AND `list` LIKE {$uuidPrepare}
                ");
              $queryLog .= fillQueryLog('user_diff_by_day', $modelForUpdate->error);

              //РѕРїСЂРµРґРµР»РµРЅРёРµ id РґРѕРјРµРЅР° РїРѕ РЅР°Р·РІР°РЅРёСЋ РёР· С‚Р°Р±Р»РёС†С‹ domain
              $domainModel = new Model([1], 'domain');
              $_res = $domainModel->_list(
                  ['id'],
                  ['name' => $arList['domain']],
                  '0,1'
              );
              $domainId = !empty($_res) ? common_setValue($_res[0], 'id') : 0;

              //РѕРїСЂРµРґРµР»РµРЅРёРµ id РґРѕРјРµРЅР° СЂРµС„РµСЂРµСЂР° РїРѕ РЅР°Р·РІР°РЅРёСЋ РёР· С‚Р°Р±Р»РёС†С‹ domain
              if (!empty($arList['referrer']))
              {
                $refComponents = parse_url($arList['referrer']);
                if (!empty($refComponents['host']))
                {
                  $_res = $domainModel->_list(
                      ['id'],
                      ['name' => $refComponents['host']],
                      '0,1'
                  );
                  $refererId = !empty($_res) ? common_setValue($_res[0], 'id') : 0;
                  $refererIdStr = $refererId;
                }
                else
                {
                  $refererId = 0;
                  $refererIdStr = '';
                }
              }
              else
              {
                $refererId = 0;
                $refererIdStr = '';
              }

              if (!empty($arList['start_referrer']))
              {
                $_res = $domainModel->_list(
                    ['id'],
                    ['name' => $arList['start_referrer']],
                    '0,1'
                );
                $startRefererId = !empty($_res) ? common_setValue($_res[0], 'id') : 0;
              }
              else
              {
                $startRefererId = 0;
              }

              $linkModel = new Model([1], 'link');
              $_res = $linkModel->_list(
                  ['id'],
                  ['domain_link' => $arList['link']],
                  '0,1'
              );
              $linkId = !empty($_res) ? common_setValue($_res[0], 'id') : 0;

              if (!empty($domainId) && !empty($linkId))
              {
                /*counter_domain group*/
                $modelForUpdate = new Model([$arList['time']], 'counter_domain');
                $modelForUpdate->query("
                        UPDATE `counter_domain_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {$domainId}
                        AND `uuid` = '{$arList['uuid']}'
                    ");
                $queryLog .= fillQueryLog('counter_domain', $modelForUpdate->error);

                $modelForUpdate = new Model([$arList['time']], 'counter_link');
                $modelForUpdate->query("
                        UPDATE `counter_link_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {$domainId}
                        AND `link` = {$linkId}
                        AND `uuid` = '{$arList['uuid']}'
                    ");
                $queryLog .= fillQueryLog('counter_link', $modelForUpdate->error);

                $modelForUpdate = new Model([$arList['time']], 'counter_ref_domain');
                $modelForUpdate->query("
                        UPDATE `counter_ref_domain_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {$domainId}
                        AND `uuid` = '{$arList['uuid']}'
                        AND `referrer` = {$refererId}
                    ");
                $queryLog .= fillQueryLog('counter_ref_domain', $modelForUpdate->error);

                $modelForUpdate = new Model([$arList['time']], 'counter_ref_link');
                $modelForUpdate->query("
                        UPDATE `counter_ref_link_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {$domainId}
                        AND `link` = {$linkId}
                        AND `uuid` = '{$arList['uuid']}'
                        AND `referrer` = '{$refererIdStr}'
                    ");
                $queryLog .= fillQueryLog('counter_ref_link', $modelForUpdate->error);

                $modelForUpdate = new Model([$arList['time']], 'start_referrer');
                $modelForUpdate->query("
                        UPDATE `start_referrer_{$startMonthTime}` SET `is_bot` = `is_bot`+1
                        WHERE `domain` = {$domainId}
                        AND `uuid` = '{$arList['uuid']}'
                        AND `referrer` = {$startRefererId}
                    ");
                $queryLog .= fillQueryLog('start_referrer', $modelForUpdate->error);

                /*top_detalizer group*/
                $modelForUpdate = new Model([1], 'top_detalizer');
                $modelForUpdate->query("
                          UPDATE `top_detalizer` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `hour`={$hour}
                          AND `domain`={$domainId} 
                          AND `time`={$arList['time']} 
                          AND `link`={$linkId}
                          AND `list_uuid` LIKE {$uuidPrepare}
                      ");
                $queryLog .= fillQueryLog('top_detalizer', $modelForUpdate->error);

                $modelForUpdate = new Model([1], 'top_detalizer_city');
                $modelForUpdate->query("
                          UPDATE `top_detalizer_city` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `hour`={$hour}
                          AND `domain`={$domainId} 
                          AND `time`={$startDayTime} 
                          AND `link`={$linkId}
                          AND `list_uuid` LIKE {$uuidPrepare}
                      ");
                $queryLog .= fillQueryLog('top_detalizer_city', $modelForUpdate->error);

                $modelForUpdate = new Model([1], 'top_detalizer_provider');
                $modelForUpdate->query("
                        UPDATE `top_detalizer_provider` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                        WHERE `hour`={$hour}
                        AND `domain`={$domainId} 
                        AND `time`={$startDayTime} 
                        AND `link`={$linkId}
                        AND `list_uuid` LIKE {$uuidPrepare}
                    ");
                $queryLog .= fillQueryLog('top_detalizer_provider', $modelForUpdate->error);

                /*top_domain_link group*/
                $modelForUpdate = new Model([1], 'top_domain_link');
                $modelForUpdate->query("
                          UPDATE `top_domain_link` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `time`={$startDayTime} 
                          AND `link`={$linkId}
                          AND `domain`={$domainId}
                          AND `list_uuid` LIKE {$uuidPrepare}
                      ");
                $queryLog .= fillQueryLog('top_domain_link', $modelForUpdate->error);

                $modelForUpdate = new Model([1], 'top_domain_link_city');
                $modelForUpdate->query("
                          UPDATE `top_domain_link_city` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                          WHERE `time`={$startDayTime} 
                          AND `link`={$linkId}
                          AND `domain`={$domainId}
                          AND `list_uuid` LIKE {$uuidPrepare}
                      ");
                $queryLog .= fillQueryLog('top_domain_link_city', $modelForUpdate->error);

                $providerTableHash = md5($domainId . $startDayTime . $linkId);
                $modelForUpdate = new Model([1], 'top_domain_link_provider');
                $modelForUpdate->query("
                            UPDATE `top_domain_link_provider` SET `count_bot` = IF(`count_bot` IS NULL, 1, `count_bot`+1) 
                            WHERE `hash`='{$providerTableHash}'
                            AND `list_uuid` LIKE {$uuidPrepare}
                        ");
                $queryLog .= fillQueryLog('top_domain_link_provider', $modelForUpdate->error);

                /*top_domain_link_uuid group*/
                $modelForUpdate = new Model([1], 'top_domain_link_uuid');
                $modelForUpdate->query("
                            UPDATE `top_domain_link_uuid` SET `is_bot` = 1 
                            WHERE `time`={$startDayTime} 
                            AND `link`={$linkId}
                            AND `domain`={$domainId}
                            AND `uuid` ='{$arList['uuid']}'
                        ");
                $queryLog .= fillQueryLog('top_domain_link_uuid', $modelForUpdate->error);

                $modelForUpdate = new Model([1], 'top_domain_link_city_uuid');
                $modelForUpdate->query("
                              UPDATE `top_domain_link_city_uuid` SET `is_bot` = 1 
                              WHERE `time`={$startDayTime} 
                              AND `link`={$linkId}
                              AND `domain`={$domainId}
                              AND `uuid` ='{$arList['uuid']}'
                          ");
                $queryLog .= fillQueryLog('top_domain_link_city_uuid', $modelForUpdate->error);

                $providerTableHash = md5($domainId . $arList['time'] . $linkId . $arList['uuid']);
                $modelForUpdate = new Model([1], 'top_domain_link_provider_uuid');
                $modelForUpdate->query("
                                UPDATE `top_domain_link_provider_uuid` SET `is_bot` = 1 
                                WHERE `hash`='{$providerTableHash}'
                            ");
                $queryLog .= fillQueryLog('top_domain_link_provider_uuid', $modelForUpdate->error);
              }
            }
          }

          //РЈРґР°Р»РёРј Р·Р°РїРёСЃСЊ РёР· РѕС‡РµСЂРµРґРё
          $service->query('mysqlqueue', [
              'method' => 'mysqliqueue_delete',
              'queue' => $cronName,
              'id' => $rsList[$i]['id']
          ]);

          continue;
        }
      }

      //РЈСЃС‚Р°РЅРѕРІРёРј СЃС‚Р°С‚СѓСЃ РІ РЅРµ РѕР±СЂР°Р±РѕС‚Р°РЅРѕ
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_update',
          'queue' => $cronName,
          'id' => $rsList[$i]['id'],
          'state' => 3
      ]);
    }

    $log .= "update:" . number_format(microtime(true) - $s, 4) . "ms;";
    /** set table key if need and set last id */
    $log .= "end:" . number_format(microtime(true) - $s, 4) . "ms;";
    $log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";

    if (debugMode())
    {
      $log .= "\n" . $queryLog;
    }

    file_put_contents(__DIR__ . '/../../syslog/botChecker.log', $log . "\n", FILE_APPEND);
  }
}

work1();