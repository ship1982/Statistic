<?php

use services\MainService;
use UserProperty\UserProperty;

common_inc('_database');

/**
 * Проверка домена по регулярному выражению.
 *
 * @param string $domain
 * @param int    $pin         - идентификатор партнера
 * @param object $importCache - объект кэша
 *
 * @return bool
 * @internal param string $str - домен для проверки
 */
function import_allow($domain = '', $pin = 0, $importCache)
{
  if (!empty($pin)
      && !empty($domain)
  )
  {
    $state = $importCache->get($pin);
    if (empty($state))
    {
      // получаем из БД данные по партнеру
      $result = [];
      $service = new MainService();
      $answer = $service->query(
          'partners', [
          'state' => 1,
          'id' => $pin,
          'action' => 'partnersList',
          'method' => 'partnerRun'
      ]);

      if (is_string($answer))
      {
        $result = json_decode($answer, JSON_UNESCAPED_UNICODE);
      }

      // проверяем наличие домена
      if (!empty($result['items'])
          && !empty($result['items'][0])
          && !empty($result['items'][0]['domains'])
      )
      {
        // если перечислены домены
        if (strrpos($result['items'][0]['domains'], '\n') !== false)
        {
          $list = explode('\n', $result['items'][0]['domains']);
          if (!empty($list))
          {
            for ($i = 0; $i < $ic = count($list); $i++)
            {
              preg_match('/' . trim($list[$i]) . '/', $domain, $matches);
              if (!empty($matches[0]))
              {
                // запоминаем значение в кэш
                $importCache->set($pin, $domain);
                return true;
              }
            }
          }

          return false;
        }
        else
        {
          // если регулярное выражение задано
          preg_match('/' . $result['items'][0]['domains'] . '/', $domain, $matches);
          if (!empty($matches[0]))
          {
            // запоминаем значение в кэш
            $importCache->set($pin, $domain);
            return true;
          }
        }
      }
    }
    else
    {
      return true;
    }
  }

  return false;
}

/**
 * Check domain in the table,
 * if not exist, then paste it and return id.
 *
 * @param bool $domain     - domain string
 * @param bool $onlySelect - do only select
 *
 * @return int
 */
function import_setDomain($domain = false, $onlySelect = false)
{
  if (empty($domain))
  {
    return -1;
  }
  $res = select_db(
      false,
      'domain',
      ['id'],
      ['name' => $domain]
  );

  if ($onlySelect)
  {
    if ($a = mysqli_fetch_assoc($res))
    {
      return (int)$a['id'];
    }
  }
  else
  {
    if ($a = mysqli_fetch_assoc($res))
    {
      return (int)$a['id'];
    }
    else
    {
      return insert_db(
          false,
          'domain',
          ['name' => $domain]
      );
    }
  }

  return false;
}

/**
 * Check link in the table,
 * if not exist, then paste it and return id.
 *
 * @param string $link - link for checking
 *
 * @return bool|int
 */
function import_setLink($link = '')
{
  if (empty($link))
  {
    return false;
  }
  $res = select_db(
      false,
      'link',
      ['id'],
      ['domain_link' => $link]
  );
  if ($a = mysqli_fetch_assoc($res))
  {
    return (int)$a['id'];
  }
  else
  {
    return insert_db(
        false,
        'link',
        array('domain_link' => $link)
    );
  }
}

/**
 * Установка данных по пользователю.
 *
 * @param array $data
 */
function importUserProperty($data = [])
{
  $userProperty = new UserProperty();
  $data = $userProperty->onBeforeInsert($data);
  $userProperty->insert($data);
  $userProperty->query(" ON DUPLICATE KEY UPDATE `last_visit`='".common_setValue($data, 'time')."'", [], false);
  $userProperty->execute();
}

/**
 * Запись в таблицу с сеансами и uuid.
 *
 * @param array $data
 */
function setInSeanceUser($data = [])
{
  if (!empty($data['uuid'])
      && !empty($data['_mstats'])
  )
  {
    common_inc('_database');
    insertIgnore_db(1, 'seance_user', [
        'seance' => $data['_mstats'],
        'uuid' => $data['uuid']
    ]);
  }
}

/**
 * array(10) {
 *       ["time"]=> int(1461014108)
 *       ["pixel_status"]=> string(3) "304"
 *       ["domain"]=> string(18) "dev.mgts.zionec.ru"
 *       ["link"]=> string(26) "http://dev.mgts.zionec.ru/"
 *       ["stat_domain"]=> string(19) "stat.mgts.zionec.ru"
 *       ["pixel"]=> string(0) ""
 *       ["uuid"]=> string(30) "146099008468193787328696256670"
 *       ["os"]=> string(5) "Linux"
 *       ["browser"]=> string(6) "Chrome"
 *       ["ip"]=> string(13) "94.25.229.119"
 * }
 *
 * @param array $data
 * @param       $importCache
 *
 * @return bool|int
 */
function import_set($data = [], &$importCache)
{
  // нужно проверить подходит ли данный домен для парсинга
  if (!empty($data['domain'])
      && !empty($data['partner'])
  )
  {
    $allow = import_allow(
        $data['domain'],
        $data['partner'],
        $importCache
    );

    if ($allow)
    {
      if (empty($data))
      {
        return false;
      }

      // ставим данные по пользователю
      importUserProperty($data);
      /** AdBlock */
      $data['ad'] = import_AdBlockCheck($data);
      $domainUser = import_setDomainByFile($data, $importCache);
      if (-1 === $domainUser)
      {
        return -3;
      }
      $ReferrerByFile = import_setReferrerByFile($data, $importCache);
      if (-1 === $ReferrerByFile)
      {
        return -7;
      }
      $domainCounter = import_setCounterDomain($data, $importCache);
      if (-1 === $domainCounter)
      {
        return -1;
      }
      $linkCounter = import_setCounterLink($data, $importCache);
      if (-1 === $linkCounter)
      {
        return -2;
      }

      $dirtyData = import_setDirtyLog($data, $importCache);
      if (-1 === $dirtyData)
      {
        return -4;
      }
      // запись в таблицу seance_user
      setInSeanceUser($data);
      $setCounterRefDomain = import_setCounterRefDomain($data, $importCache);
      if (-1 === $setCounterRefDomain)
      {
        return -5;
      }
      $setCounterRefLink = import_setCounterRefLink($data, $importCache);
      if (-1 === $setCounterRefLink)
      {
        return -6;
      }
      $setCounterStartRefDomain = import_setCounterStartRefDomain($data, $importCache);
      if (-1 === $setCounterStartRefDomain)
      {
        return -8;
      }
      $setStartCounterRefDomain = import_setStartCounterRefDomain($data);
      if (-1 === $setStartCounterRefDomain)
      {
        return -9;
      }
    }
  }

  return true;
}

/**
 * Insert new domain or increase his counters.
 *
 * @param array $data @see import_set()
 *
 * @param       $importCache
 *
 * @return bool|int|mysqli_result
 */
function import_setCounterDomain($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }
  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }
  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $array['uuid']));

  return insert_db(
      $data['time'],
      'counter_domain', [
      'id' => $array['id'],
      'time' => $array['time'],
      'domain' => common_setValue($array, 'domain', '0'),
      'day' => 1,
      'uuid' => common_setValue($array, 'uuid'),
      'ad' => common_setValue($data, 'ad', 0)
  ], [
          '#day' => '`day`+1',
          '#uuid' => 'VALUES(`uuid`)',
          '#ad' => '`ad` + VALUES(`ad`)'
      ]
  );
}

/**
 * Insert new link or increase his counters.
 *
 * @param array $data @see import_set()
 * @param       $importCache
 *
 * @param       $importCache
 *
 * @return bool|int|mysqli_result
 */
function import_setCounterLink($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }
  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }
  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  if (empty($data['link']))
  {
    return false;
  }

  $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $data['link'] . $array['uuid']));
  $url = import_setLink($data['link']);

  return insert_db(
      $data['time'],
      'counter_link', [
      'id' => $array['id'],
      'time' => $array['time'],
      'domain' => common_setValue($array, 'domain', '0'),
      'link' => (empty($url) ? '0' : $url),
      'day' => 1,
      'uuid' => common_setValue($array, 'uuid'),
      'ad' => common_setValue($data, 'ad', 0)
  ], [
          '#day' => '`day`+1',
          '#uuid' => 'VALUES(`uuid`)',
          '#ad' => '`ad` + VALUES(`ad`)'
      ]
  );
}

/**
 * Insert new link or increase his counters for referrer.
 *
 * @param array $data @see import_set()
 *
 * @param       $importCache
 *
 * @return bool|int|mysqli_result
 */
function import_setCounterRefLink($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }
  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }
  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  if (!empty($data['referrer']))
  {
    $domain = $importCache->get('referrer_' . $data['referrer']);
    if (empty($domain))
    {
      $array['referrer'] = import_setDomain(import_parseDomain($data['referrer']), true);
      $importCache->set('referrer_' . $data['referrer'], $array['referrer']);
    }
    else
    {
      $array['referrer'] = $domain;
    }
  }
  else
  {
    $array['referrer'] = false;
  }

  if (!empty($data['link']))
  {
    $array['link'] = import_setLink($data['link']);
  }
  else
  {
    return false;
  }

  $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $data['link'] . $array['uuid'] . $array['referrer']));

  return insert_db(
      $data['time'],
      'counter_ref_link', [
      'id' => $array['id'],
      'time' => $array['time'],
      'domain' => common_setValue($array, 'domain', '0'),
      'link' => common_setValue($array, 'link', '0'),
      'day' => 1,
      'referrer' => common_setValue($array, 'referrer'),
      'uuid' => common_setValue($array, 'uuid'),
      'ad' => common_setValue($data, 'ad', 0)
  ], [
          '#day' => '`day`+1',
          '#uuid' => 'VALUES(`uuid`)',
          '#ad' => '`ad` + VALUES(`ad`)'
      ]
  );
}

/**
 * Insert new row in referrer table by domain.
 *
 * @param array $data @see import_set()
 *
 * @param ImportCache $importCache
 *
 * @return bool|int|mysqli_result
 */
function import_setCounterRefDomain($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }
  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }
  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  if (!empty($data['referrer']))
  {
    $domain = $importCache->get('referrer_' . $data['referrer']);
    if (empty($domain))
    {
      $array['referrer'] = import_setDomain(import_parseDomain($data['referrer']));
      $importCache->set('referrer_' . $data['referrer'], $array['referrer']);
    }
    else
    {
      $array['referrer'] = $domain;
    }
  }
  else
  {
    $array['referrer'] = false;
  }

  $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $array['uuid'] . $array['referrer']));

  return insert_db(
      $data['time'],
      'counter_ref_domain', [
      'id' => $array['id'],
      'time' => $array['time'],
      'domain' => (empty($array['domain']) ? '0' : $array['domain']),
      'day' => 1,
      'referrer' => (empty($array['referrer']) ? '0' : $array['referrer']),
      'uuid' => common_setValue($array, 'uuid'),
      'ad' => common_setValue($data, 'ad', 0)
  ], [
          '#day' => '`day`+1',
          '#uuid' => 'VALUES(`uuid`)',
          '#ad' => '`ad` + VALUES(`ad`)'
      ]
  );
}

/**
 * Returns a beginning of given day of the month,
 * which present by current timestamp.
 *
 * @param bool $timestamp - timestamp, for which calculate a timestamp
 *
 * @return int
 */
function import_dayStartTimestamp($timestamp = false)
{
  if (empty($timestamp))
  {
    return -1;
  }
  $year = date('Y', $timestamp);
  $month = date('m', $timestamp);
  $day = date('d', $timestamp);
  return strtotime(date('c', mktime(0, 0, 0, $month, $day, $year)));
}

/**
 * Write a file with user by domain by day
 * A unique of record is defined by day @see import_dayStartTimestamp
 * Name of file is a $time . '_' . $domain_id
 * File is located in @property $GLOBALS['conf']['user_log_dir'].
 *
 * @param array $data @see import_set()
 *
 * @param  ImportCache $importCache
 *
 * @return int
 */
function import_setDomainByFile($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }
  $array = [];

  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }
  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $array['uuid']));

  $id = $importCache->get('id_' . $array['id']);
  if (empty($id))
  {
    $res = select_db(
        $data['time'],
        'counter_domain',
        ['id'],
        ['id' => $array['id']]
    );
    $a = mysqli_fetch_assoc($res);
    $importCache->set('id_' . $array['id'], $a['id']);
  }

  if (!empty($a))
  {
    return true;
  }
  else
  {
    return file_put_contents($GLOBALS['conf']['user_log_dir'] . '/' . $array['time'] . '_' . $array['domain'], $array['uuid'] . "\n", FILE_APPEND);
  }
}

/**
 * Write a file with user by referrer by day.
 * Name of file is a $time . '_' . md5($referrer)
 * File is located in @property $GLOBALS['conf']['user_ref_dir']
 *
 * @param array $data @see import_set()
 *
 * @param       $importCache
 *
 * @return int
 */
function import_setReferrerByFile($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }

  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }

  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  if (!empty($data['referrer']))
  {
    $referrer = $importCache->get('referrer_' . $data['referrer']);
    if (empty($referrer))
    {
      $array['referrer'] = import_setDomain(import_parseDomain($data['referrer']));
      $importCache->set('referrer_' . $data['referrer'], $array['referrer']);
    }
    else
    {
      $array['referrer'] = $referrer;
    }
  }
  else
  {
    $array['referrer'] = false;
  }

  if (!empty($data['start_referrer']))
  {
    $referrer = $importCache->get('referrer_' . $data['start_referrer']);
    if (empty($referrer))
    {
      $array['start_referrer'] = import_setDomain($data['start_referrer']);
      $importCache->set('referrer_' . $data['start_referrer'], $array['start_referrer']);
    }
    else
    {
      $array['start_referrer'] = $referrer;
    }
  }
  else
  {
    $array['start_referrer'] = false;
  }

  if (!empty($array['domain']))
  {
    $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $array['uuid'] . $array['referrer']));

    $id = $importCache->get('id_' . $array['id']);
    if (empty($id))
    {
      $res = select_db(
          $data['time'],
          'counter_ref_domain',
          ['id'],
          ['id' => $array['id']]
      );
      $a = mysqli_fetch_assoc($res);
      $importCache->set('id_' . $array['id'], $a['id']);
    }

    if (!empty($a))
    {
      return true;
    }
    else
    {
      if ($array['referrer'] > 0)
      {
        return file_put_contents($GLOBALS['conf']['user_ref_dir'] . '/' . $array['time'] . '_' . $array['referrer'], $array['uuid'] . "\n", FILE_APPEND);
      }
      else
      {
        return true;
      }
    }
  }
  else
  {
    return true;
  }

  return -1;
}

/**
 * Write a dirty log in database.
 *
 * @param array $data @see import_set()
 *
 * @param       $importCache
 *
 * @return bool|int|string
 */
function import_setDirtyLog($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }

  if (empty($data['time']))
  {
    return -1;
  }

  if (!empty($data['domain']))
  {
    $data['domain_text'] = $data['domain'];
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $data['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $data['domain']);
    }
    else
    {
      $data['domain'] = $domain;
    }
  }
  else
  {
    $data['domain'] = false;
  }

  $data['enum_referrer'] = false;

  if (!empty($data['referrer']))
  {
    $domain = $importCache->get('referrer_' . $data['referrer']);
    if (empty($domain))
    {
      $data['enum_referrer'] = import_setDomain(import_parseDomain($data['referrer']), true);
      $importCache->set('referrer_' . $data['referrer'], $data['enum_referrer']);
    }
    else
    {
      $data['enum_referrer'] = $domain;
    }
  }

  // domain_text and link_text
  if (!empty($data['link']))
  {
    $data['link_text'] = $data['link'];
  }

  // сеанс
  if (!empty($data['_mstats']))
  {
    $data['seance'] = $data['_mstats'];
  }

  if (!empty($data['link']))
  {
    $data['link'] = import_setLink($data['link']);
  }
  else
  {
    $data['link'] = false;
  }

  if (!empty($data['start_referrer']))
  {
    $domain = $importCache->get('referrer_' . $data['start_referrer']);
    if (empty($domain))
    {
      $data['start_referrer'] = import_setDomain($data['start_referrer'], true);
      $importCache->set('referrer_' . $data['start_referrer'], $data['start_referrer']);
    }
    else
    {
      $data['start_referrer'] = $domain;
    }
  }
  else
  {
    $data['start_referrer'] = false;
  }

  $array = [];
  $rsColumns = query_db(
      $data['time'],
      'dirty',
      'SHOW COLUMNS WHERE 1=1'
  );
  while ($arColumns = mysqli_fetch_assoc($rsColumns))
  {
    if ($arColumns['Field'] == 'id')
    {
      continue;
    }
    if (!empty($data[$arColumns['Field']]))
    {
      $array[$arColumns['Field']] = $data[$arColumns['Field']];
    }
  }

  $res_insert = insert_db(
      $data['time'],
      'dirty',
      $array
  );

  if (is_int($res_insert) && !empty($res_insert))
  {
    $array['id'] = $res_insert;
  }
  else
  {
    return $res_insert;
  }

  //Параметра ad (адблок) нет в dirty поэтому заполняется отдельно
  $array['ad'] = common_setValue($data, 'ad', 0);

  /**
   * Заполняем очереди
   */
  $service = new MainService();


  //Добавим в очередь ipChecker
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_ipChecker',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'uuid',
          'ip',
          'id',
          'time'
      ])
  ]);

  //Добавим в очередь userType
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_userType',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'id',
          'time',
          'uuid'
      ])
  ]);

  //Добавим в очередь topCity
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_topCity',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'uuid',
          'id',
          'domain',
          'time',
          'link',
          'ad'
      ])
  ]);

  //Добавим в очередь topPage
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_topPage',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'uuid',
          'id',
          'domain',
          'time',
          'link',
          'ad'
      ])
  ]);

  //Добавим в очередь topProvider
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_topProvider',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'uuid',
          'id',
          'domain',
          'time',
          'link',
          'ad'
      ])
  ]);

  //Добавим в очередь usersequence
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_usersequence',
      'state' => 1,
      'time' => (empty($array['time']) ? time() : $array['time']),
      'param' => prepare_data_to_queue($array, [
          'link',
          'id',
          'domain',
          'time',
          'ip',
          'ip_long',
          'uuid',
          'referrer',
          'utm_source',
          'utm_medium',
          'utm_campaign',
          'utm_content',
          'utm_term',
          'device',
          'domain_text',
          'link_text',
          'seance',
          'ad'
      ])
  ]);

  //Добавим в очередь topDetalizer
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_topDetalizer',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'id',
          'uuid',
          'domain',
          'link',
          'time',
          'ad'
      ])
  ]);

  //Добавим в очередь topDetalizerCity
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_topDetalizerCity',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'id',
          'uuid',
          'domain',
          'link',
          'time',
          'ad'
      ])
  ]);

  //Добавим в очередь topDetalizerProvider
  $service->query('mysqlqueue', [
      'method' => 'mysqliqueue_set',
      'queue' => 'cron_topDetalizerProvider',
      'state' => 1,
      'param' => prepare_data_to_queue($array, [
          'id',
          'uuid',
          'domain',
          'link',
          'time',
          'ad'
      ])
  ]);
  return $res_insert;
}

/**
 * Return a domain by link.
 *
 * @param bool $link - url for parsing
 *
 * @return string
 */
function import_parseDomain($link = false)
{
  if (!$link)
  {
    return false;
  }
  $c = explode('/', $link);
  return (!empty($c[2]) ? $c[2] : false);
}

/**
 * Insert new row in start_referrer table by domain.
 *
 * @param array $data @see import_set()
 *
 * @param       $importCache
 *
 * @return bool|int|mysqli_result
 */
function import_setCounterStartRefDomain($data = [], &$importCache)
{
  if (empty($data))
  {
    return -1;
  }

  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $domain = $importCache->get('domain_' . $data['domain']);
    if (empty($domain))
    {
      $array['domain'] = import_setDomain($data['domain']);
      $importCache->set('domain_' . $data['domain'], $array['domain']);
    }
    else
    {
      $array['domain'] = $domain;
    }
  }
  else
  {
    $array['domain'] = false;
  }

  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  if (!empty($data['start_referrer']))
  {
    $domain = $importCache->get('referrer_' . $data['start_referrer']);
    if (empty($domain))
    {
      $array['start_referrer'] = import_setDomain($data['start_referrer'], true);
      $importCache->set('referrer_' . $data['start_referrer'], $array['start_referrer']);
    }
    else
    {
      $array['start_referrer'] = $domain;
    }
  }
  else
  {
    $array['start_referrer'] = false;
  }

  if (!empty($array['start_referrer']))
  {
    $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $array['uuid'] . $array['start_referrer']));

    $res = select_db(
        $data['time'],
        'start_referrer',
        ['id'],
        ['id' => $array['id']]
    );

    if ($a = mysqli_fetch_assoc($res))
    {
      return true;
    }
    else
    {
      return file_put_contents($GLOBALS['conf']['user_ref_dir'] . '/start_' . $array['time'] . '_' . $array['start_referrer'], $array['uuid'] . "\n", FILE_APPEND);
    }
  }
  else
  {
    return true;
  }
}

/**
 * Insert new row in start_referrer table by domain.
 *
 * @param array $data @see import_set()
 *
 * @return bool|int|mysqli_result
 */
function import_setStartCounterRefDomain($data = [])
{
  if (empty($data))
  {
    return -1;
  }
  $array = [];
  if (!empty($data['time']))
  {
    $array['time'] = import_dayStartTimestamp($data['time']);
  }
  else
  {
    $array['time'] = false;
  }
  if (!empty($data['domain']))
  {
    $array['domain'] = import_setDomain($data['domain']);
  }
  else
  {
    $array['domain'] = false;
  }
  if (!empty($data['uuid']))
  {
    $array['uuid'] = $data['uuid'];
  }
  else
  {
    $array['uuid'] = false;
  }

  if (!empty($data['start_referrer']))
  {
    $array['start_referrer'] = import_setDomain($data['start_referrer'], true);
  }
  else
  {
    $array['start_referrer'] = false;
  }

  $array['id'] = md5(common_setValue($data, '_mstats') . md5($array['domain'] . $array['uuid'] . $array['start_referrer']));

  return insert_db(
      $data['time'],
      'start_referrer', [
      'id' => $array['id'],
      'time' => $array['time'],
      'domain' => common_setValue($array, 'domain', '0'),
      'day' => 1,
      'referrer' => common_setValue($array, 'start_referrer', '0'),
      'uuid' => common_setValue($array, 'uuid'),
      'ad' => common_setValue($data, 'ad', 0)
  ], [
          '#day' => '`day`+1',
          '#uuid' => 'VALUES(`uuid`)',
          '#ad' => '`ad` + VALUES(`ad`)'
      ]
  );
}

/**
 * Set AdBlock sign.
 *
 * @param array $data - @see import_set
 *
 * @return int
 */
function import_AdBlockCheck($data = [])
{
  if (empty($data['uuid']))
  {
    return 0;
  }
  if (empty($data['ad']))
  {
    $params = [
        'id' => 1,
        'uuid' => $data['uuid'],
        'ad' => 0
    ];
  }
  else
  {
    $params = [
        'id' => 1,
        'uuid' => $data['uuid'],
        'ad' => 1
    ];
  }

  common_inc('_database');
  insert_db(
      1,
      'user_property',
      $params,
      [
          'uuid' => $params['uuid'],
          'ad' => $params['ad']
      ]
  );
  return $params['ad'];
}