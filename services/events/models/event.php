<?php

use services\MainService;

/**
 * Функция обработки времени для таблицы event_list.
 * Возвращает массив с форматированным временем вида:
 * YmdH
 * Ymd
 * H
 *
 * @param string $time - timestamp
 *
 * @return array
 */
function event_time_processing($time = '')
{
  $res = [];
  if (empty($time))
  {
    return $res;
  }
  $res['time'] = $time;
  $formattedDateTime = date('YmdH', $time);
  if (!empty($formattedDateTime))
  {
    $res['datetime'] = $formattedDateTime;
    $res['date'] = substr($formattedDateTime, 0, 8);
    $res['hour'] = substr($formattedDateTime, 8, 2);
  }

  return $res;
}

/**
 * Возвращает массив, где $key - его ключ, а $value - его значение.
 *
 * @param string $key   - ключ массива
 * @param string $value - значение массива
 *
 * @return array
 */
function event_setSimpleVariable($key = '', $value = '')
{
  if (!empty($key))
  {
    return (!empty($value) ? [$key => $value] : [$key => '']);
  }

  return [];
}

/**
 * Получаем по ip адресу (long) id для geo и id для isp.
 *
 * @param string $ip - long ip
 *
 * @return array
 */
function event_getLocation($ip = '')
{
  $res = [];
  if (!empty($ip))
  {
    common_inc('geoip');
    $gb = new IPGeoBase();
    $data = $gb->getRecord($ip);
    // получение города
    if (!empty($data['city'])
        && !empty($data['region'])
        && !empty($data['district'])
    )
    {
      require __DIR__ . '/database.php';
      $o = select_db(1, 'list_condition_geo', ['id'], [
          'city' => $data['city'],
          'region' => $data['region'],
          'district' => $data['district']
      ]);
      if ($o)
      {
        if ($a = mysqli_fetch_assoc($o))
        {
          $res['geo'] = $a['id'];
        }
      }
    }
    // получение провайдера
    if (!empty($data['isp']))
    {
      require __DIR__ . '/database.php';
      $o = select_db(1, 'list_condition_ips', ['id'], [
          'ips' => $data['isp'],
      ]);
      if ($o)
      {
        if ($a = mysqli_fetch_assoc($o))
        {
          $res['isp'] = $a['id'];
        }
      }
    }
  }

  return $res;
}

/**
 * Получаем массив utm меток.
 *
 * @param string $query - query строка из url
 *
 * @return array
 */
function event_getUTM($query = '')
{
  $res = [];
  if (empty($query))
  {
    return $res;
  }
  $a = parse_url($query);
  if (!empty($a['query']))
  {
    $b = explode('&', $a['query']);
    if (!empty($b)
        && is_array($b)
    )
    {
      for ($i = 0; $i < $ic = count($b); $i++)
      {
        if (strpos($b[$i], 'utm_source=') !== false)
        {
          $res['utm_source'] = str_replace('utm_source=', '', $b[$i]);
        }
        if (strpos($b[$i], 'utm_medium=') !== false)
        {
          $res['utm_medium'] = str_replace('utm_medium=', '', $b[$i]);
        }
        if (strpos($b[$i], 'utm_term=') !== false)
        {
          $res['utm_term'] = str_replace('utm_term=', '', $b[$i]);
        }
        if (strpos($b[$i], 'utm_content=') !== false)
        {
          $res['utm_content'] = str_replace('utm_content=', '', $b[$i]);
        }
        if (strpos($b[$i], 'utm_campaign=') !== false)
        {
          $res['utm_campaign'] = str_replace('utm_campaign=', '', $b[$i]);
        }
      }
    }
  }

  return $res;
}

/**
 * Возвращает массив, где $key - его ключ, а $value - его декодированное json_decode значение.
 * Используется для преобразование meta информации.
 *
 * @param string $key   - ключ массива
 * @param string $value - значение массива
 *
 * @return array
 */
function event_setSimpleVariableDecide($key = '', $value = '')
{
  if (!empty($key))
  {
    $str = str_replace('u', '\u', $value);
    $array = json_decode('{"t":"' . $str . '"}', true);
    return (!empty($value) ? [$key => $array['t']] : [$key => '']);
  }

  return [];
}

/**
 * Возвращает массив с utm метками и со ссылкой после доменного имени.
 * Например для http://mgts.ru/home/?utm_content=test вернет
 * ['link' => 'home', 'utm_content' => 'test'].
 *
 * @param string $link - url
 *
 * @return array
 */
function event_getLink($link = '')
{
  if (empty($link))
  {
    return ['link' => ''];
  }
  preg_match('/\/\/([a-z0-9\.\-]*)(.*)\/{0,1}(.*)/', $link, $matches);
  if (!empty($matches[2])
      || !empty($matches[3])
  )
  {
    $link = trim($matches[2] . '/' . $matches[3], '/');
  }
  // получаем utm метки
  if (!empty($matches[3]))
  {
    $res = event_getUTM(trim($matches[3], '/'));
  }

  if (!empty($res))
  {
    return array_merge($res, ['link' => $link]);
  }

  return ['link' => $link];
}

/**
 * Получаем параметр из GET строки url.
 *
 * @param string $name - наименование параметра
 * @param string $link - ссылка (строка) в которой стмотрим
 *
 * @return string
 */
function event_getGETFromLink($name = '', $link = '')
{
  if (empty($name)
      || empty($link)
  )
  {
    return '';
  }

  preg_match('/.*\?(.*)/', $link, $matches);
  if (!empty($matches[1]))
  {
    $arPart = explode('&', $matches[1]);
    if (is_array($arPart))
    {
      foreach ($arPart as $key => $value)
      {
        $params = explode('=', $value);
        if ($params[0] == $name)
        {
          return $params[1];
        }
      }
    }

  }

  return '';
}

/**
 * Получает данные из очереди и пасрит их для подготовки к вставке в БД.
 *
 * @param array $dataQueue - массив данных из очереди
 *
 * @return array
 */
function event_parseDataFromQueue($dataQueue = [])
{
  $result = [];
  if (!empty($dataQueue['param'])
      && is_string($dataQueue['param'])
  )
  {
    $timeResult = [];
    $domainResult = [];
    $linkResult = [];
    $stepResult = [];
    $seanceResult = [];
    $etimeResult = [];
    $ecategoryResult = [];
    $elabelResult = [];
    $evalueResult = [];
    $descriptionResult = [];
    $titleResult = [];
    $keywordsResult = [];
    $uuidResult = [];
    $deviceResult = [];
    $ipResult = [];
    $locationResult = [];
    $partnerResult = [];
    $utmCompaign = [];
    $utmContent = [];
    $utmTerm = [];
    $utmMedium = [];
    $utmSource = [];
    $arCookie = [];
    $arJs = [];
    $adResult = [];
    $ipLongResult = [];
    $arReferrer = [];
    $param = json_decode($dataQueue['param'], true);
    if (!empty($param))
    {
      // time
      if (!empty($param['time']))
      {
        $timeResult = event_time_processing($param['time']);
      }
      // domain
      if (!empty($param['domain']))
      {
        $domainResult = event_setSimpleVariable('domain', $param['domain']);
      }
      // link
      if (!empty($param['link']))
      {
        $linkResult = event_getLink(common_setValue($param, 'link'));
        // UTM
        if (!empty($linkResult))
        {
          $utmCompaign['utm_campaign'] = event_getGETFromLink('utm_campaign', $linkResult['link']);
          $utmContent['utm_content'] = event_getGETFromLink('utm_content', $linkResult['link']);
          $utmTerm['utm_term'] = event_getGETFromLink('utm_term', $linkResult['link']);
          $utmMedium['utm_medium'] = event_getGETFromLink('utm_medium', $linkResult['link']);
          $utmSource['utm_source'] = event_getGETFromLink('utm_source', $linkResult['link']);
        }
      }
      // step by hit
      if (!empty($param['_c']))
      {
        $stepResult = event_setSimpleVariable('step_by_hit', $param['_c']);
      }
      // partner
      if (!empty($param['pin']))
      {
        $partnerResult = event_setSimpleVariable('partner', $param['pin']);
      }
      // step by hit
      if (!empty($param['_mstats']))
      {
        $seanceResult = event_setSimpleVariable('seance', $param['_mstats']);
      }
      // event_type
      if (!empty($param['event_type']))
      {
        $etimeResult = event_setSimpleVariable('event_type', $param['event_type']);
      }
      // event_category
      if (!empty($param['event_category']))
      {
        $ecategoryResult = event_setSimpleVariable('event_category', $param['event_category']);
      }
      // event_label
      if (!empty($param['event_label']))
      {
        $elabelResult = event_setSimpleVariable('event_label', $param['event_label']);
      }
      // event_value
      if (!empty($param['event_value']))
      {
        $evalueResult = event_setSimpleVariable('event_value', $param['event_value']);
      }
      // description
      if (!empty($param['description']))
      {
        $descriptionResult = event_setSimpleVariableDecide('description', $param['description']);
      }
      // title
      if (!empty($param['title']))
      {
        $titleResult = event_setSimpleVariableDecide('title', $param['title']);
      }
      // keywords
      if (!empty($param['keywords']))
      {
        $keywordsResult = event_setSimpleVariableDecide('keywords', $param['keywords']);
      }
      // uuid
      if (!empty($param['uuid']))
      {
        $uuidResult = event_setSimpleVariable('uuid', $param['uuid']);
      }
      // device
      if (!empty($param['device']))
      {
        $deviceResult = event_setSimpleVariable('device', $param['device']);
      }
      // cookie
      if (!empty($param['cookies']))
      {
        $arCookie = event_setSimpleVariable('cookies', $param['cookies']);
      }
      // js
      if (!empty($param['javascript']))
      {
        $arJs = event_setSimpleVariable('javascript', $param['javascript']);
      }
      // ip
      if (!empty($param['ip']))
      {
        $ipResult = event_setSimpleVariable('ip', $param['ip']);
      }
      // ad
      if (!empty($param['ad']))
      {
        $adResult = event_setSimpleVariable('ad', $param['ad']);
      }
      // ip_long
      if (!empty($param['ip_long']))
      {
        $ipLongResult = event_setSimpleVariable('ip_long', $param['ip_long']);
        $ipResult = event_setSimpleVariable('ip', $param['ip']);
      }
      // partner
      if (!empty($param['partner']))
      {
        $partnerResult = event_setSimpleVariable('partner', $param['partner']);
      }
      // location and isp
      if (!empty($param['ip']))
      {
        $locationResult = event_getLocation($param['ip']);
      }
      // location and isp
      if (!empty($param['referrer']))
      {
        $linkReferrer = $param['referrer'];
        $arRefererrerComponent = parse_url($linkReferrer);
        $arReferrer['referer_domain'] = str_replace('www.','', common_setValue($arRefererrerComponent, 'host'));
        $arReferrer['referer_link'] = ltrim(common_setValue($arRefererrerComponent, 'path'), '/') . '?' . common_setValue($arRefererrerComponent, 'query');
      }
      else
      {
        $arReferrer['referer_domain'] = '';
        $arReferrer['referer_link'] = '';
      }

      // sum all data
      $result = array_merge(
          $timeResult,
          $domainResult,
          $linkResult,
          $stepResult,
          $seanceResult,
          $etimeResult,
          $ecategoryResult,
          $elabelResult,
          $evalueResult,
          $descriptionResult,
          $titleResult,
          $keywordsResult,
          $uuidResult,
          $deviceResult,
          $ipResult,
          $ipLongResult,
          $locationResult,
          $partnerResult,
          $utmCompaign,
          $utmContent,
          $utmTerm,
          $utmMedium,
          $utmSource,
          $adResult,
          $arCookie,
          $arJs,
          $arReferrer
      );
    }

    return $result;
  }

  return [];
}

/**
 * Вставляет данные в таблицу событий.
 *
 * @param array $data - @see event_parseDataFromQueue()
 *
 * @return int
 */
function event_setData($data = [])
{
  if (!empty($data))
  {
    include_once __DIR__ . '/database.php';
    return insert_db(1, 'event_list', $data);
  }

  return 0;
}

/**
 * Удаляет данные из очереди в случае успешной обработки или же помечает их статусом 3 в случае неудачи.
 *
 * @param array $queue         - массив очереди
 * @param bool  $processResult -  результат @see event_setData()
 *
 * @return bool
 */
function event_setStatusQueue($queue = [], $processResult = false)
{
  if (!empty($queue))
  {
    // подключаем API сервисов
    $service = new MainService();
    if (!$processResult)
    {
      // если не удалось вставить запись
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_update',
          'queue' => 'events',
          'id' => $queue['id'],
          'state' => 3
      ]);

      return true;
    }
    else
    {
      // если запись успешно обработана
      // добавляем в данные очереди id записи
      $id['id'] = $processResult;
      $param = '';
      if (!empty($queue['param']))
      {
        $json = json_decode($queue['param'], true);
        if (!empty($json))
        {
          $param = array_merge(
              (array)$id,
              $json
          );
        }
        else
        {
          $param = $json;
        }
      }
      $service->query('mysqlqueue', [
          'method' => 'mysqliqueue_update',
          'queue' => 'events',
          'param' => json_encode($param),
          'id' => $queue['id'],
          'state' => 4
      ]);

      return true;
    }
  }

  return false;
}

/**
 * Функция возвращает массив с данными для статистики МГСТ
 *
 * @param JSON string $stat_data
 *
 * @return array|null
 */
function event_parseParamMGTS($stat_data)
{
  $stat_data = json_decode($stat_data, true);
  if (empty($stat_data) || !is_array($stat_data))
  {
    return null;
  }

  return [
      'internet' => common_getVariable($stat_data, ['internet'], 0),
      'internet_price' => common_getVariable($stat_data, [
          'internet_data',
          'price'
      ], 0),
      'internet_rate_num' => common_getVariable($stat_data, [
          'internet_data',
          'rate_num'
      ], ''),
      'internet_rate_name' => common_getVariable($stat_data, [
          'internet_data',
          'rate_name'
      ], ''),
      'telephone' => common_getVariable($stat_data, ['telephone'], 0),
      'tv' => common_getVariable($stat_data, ['tv'], 0),
      'tv_price' => common_getVariable($stat_data, [
          'tv_data',
          'price'
      ], 0),
      'tv_rate_num' => common_getVariable($stat_data, [
          'tv_data',
          'rate_num'
      ], ''),
      'tv_rate_name' => common_getVariable($stat_data, [
          'tv_data',
          'rate_name'
      ], ''),
      'mobile' => common_getVariable($stat_data, ['mobile'], 0),
      'mobile_price' => common_getVariable($stat_data, [
          'mobile_data',
          'price'
      ], 0),
      'mobile_rate_num' => common_getVariable($stat_data, [
          'mobile_data',
          'rate_num'
      ], ''),
      'mobile_rate_name' => common_getVariable($stat_data, [
          'mobile_data',
          'rate_name'
      ], ''),
      'serv_v' => common_getVariable($stat_data, ['serv_v'], 0),
      'serv_s' => common_getVariable($stat_data, ['serv_s'], 0),
      'summ' => common_getVariable($stat_data, ['summ'], 0),
      'discount' => common_getVariable($stat_data, ['discount'], 0)
  ];
}

/**
 * constructor.
 *
 * @param array $data
 *
 * @return void
 */
function event_mainProcess($data = [])
{
  if (!empty($data)
      && is_array($data)
  )
  {
    // подключаем сервис
    $service = new MainService();
    $googleChannel = new \GoogleChannels\GoogleChannels();
    for ($i = 0; $i < $ic = count($data); $i++)
    {
      $result = event_parseDataFromQueue($data[$i]);
      $partner = common_getVariable($result, ['partner'], 0);
      $stat_data = [];
      switch ($partner)
      {
        case 1:
          //Статистика для МГТС
          $stat_data = (array_key_exists('event_value', $result)) ? event_parseParamMGTS($result['event_value']) : $stat_data;
          break;
      }

      // Если получили данные по статистике
      if (!empty($stat_data))
      {
        $result = array_replace($result, $stat_data);
        unset($result['event_value']);
      }

      /**
       * определяем канал.
       * 1) получаем все каналы
       * 2) ведем поиск, пока не найдем первый удовлетворяющий
       */
      $result['channel'] = NULL;
      if (!empty($googleChannel->config))
      {
        foreach ($googleChannel->config as $channel => $channelName)
        {
          if ($googleChannel->checkPhpCondition(
              $googleChannel->startCondition('php', $googleChannel->channel[$channel]),
              $result
          ))
          {
            $result['channel'] = $channel;
            break;
          }
        }
      }

      $insertedRowId = event_setData($result);

      if (!empty($insertedRowId)
          && !empty($result['seance'])
      )
      {
        // добавляем данные а очередь на создание данных по первому входу пользователя
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_set',
            'queue' => 'cron_enterEventlist',
            'state' => '1',
            'param' => json_encode([
                'seance' => $result['seance'],
                'id' => $insertedRowId
            ])
        ]);
      }

      // вставляем данные в таблицу по списку пользователей
      $service->query(
          'userlist', [
          'id' => md5(uniqid() . time()),
          'partner' => common_setValue($result, 'partner'),
          'datehour' => common_setValue($result, 'date'),
          'domain' => common_setValue($result, 'domain'),
          'uuid' => common_setValue($result, 'uuid'),
          'state' => 1,
          'action' => 'userlist_addList',
          'method' => 'userlistRun'
      ]);

      if (!empty($data[$i]['id']))
      {
        event_setStatusQueue(
            $data[$i],
            $insertedRowId
        );
      }
    }
  }
}