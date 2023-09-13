<?php

/**
 * Получаем список служебных переменных из файла __DIR__ . '/../config/variables.php'
 * 
 * @return type
 */

use services\MainService;

if(!function_exists('eventlist_getVariables'))
{
  function eventlist_getVariables()
  {
    if(file_exists(__DIR__ . '/../config/variables.php'))
      return require(__DIR__ . '/../config/variables.php');
    else
      return [];
  }
}

/**
 * Возвращает список полей и используемых над ними действий.
 * По умолчанию используется =.
 * 
 * @return type
 */
if(!function_exists('eventlist_getOperator4WhereClause'))
{
  function eventlist_getOperator4WhereClause()
  {
    if(file_exists(__DIR__ . '/../config/typeOfAction.php'))
      return require __DIR__ . '/../config/typeOfAction.php';
    else
      return [];
  }
}

/**
 * Получаем список полей и их наименований из файла __DIR__ . '/../config/fields.php'
 * 
 * @return type
 */
if(!function_exists('eventlist_getFields'))
{
  function eventlist_getFields()
  {
    if(file_exists(__DIR__ . '/../config/fields.php'))
      return require(__DIR__ . '/../config/fields.php');
    else
      return [];
  }
}

/**
 * trim(strip_tags()).
 * 
 * @param type|string $value - строка для обработки
 * @return type
 */
if(!function_exists('eventlist_striptrim'))
{
  function eventlist_striptrim($value = '')
  {
    return trim(strip_tags($value));
  }
}

/**
 * Основные правила валидации для партнеров.
 * 
 * @return type
 */
if(!function_exists('eventlist_getValidationRule'))
{
  function eventlist_getValidationRule()
  {
    return [
      'name' => [
        'validation' => [
          'empty' => "Поле Партнер не должно быть пустым.",
          'unique' => "Такой партнер уже существует."
        ]
      ],
      'domains' => [
        'validation' => [
          'empty' => "Поле Домены не должно быть пустым.",
        ]
      ]
    ];
  }
}

/**
 * Проверка на обяхательные поля.
 * 
 * @param type|array $data - массив полей для проверки
 * @return type
 */
if(!function_exists('eventlist_validateRequired'))
{
  function eventlist_validateRequired($data = [])
  {
    $error = [];
    $validationRule = eventlist_getValidationRule();
    if(!empty($validationRule)
      && !empty($data)
    )
    {
      foreach ($validationRule as $field => $__data)
      {
        if(empty($data[$field]))
          $error[] = $__data['validation']['empty'];
      }
    }

    return $error;
  }
}

/**
 * Убираем у old полей префикс old_.
 * 
 * @param type|array $old - массив старых значений в полях
 * @return type
 */
if(!function_exists('eventlist_getOldDataWithNewDataKey'))
{
  function eventlist_getOldDataWithNewDataKey($old = [])
  {
    $data = [];
    if(empty($old)) return $data;
    if(is_array($old))
    {
      foreach ($old as $key => $value)
      {
        $newKey = str_replace('old_', '', $key);
        if(!empty($newKey))
          $data[$newKey] = $value;
      }
    }

    return $data;
  }
}

/**
 * Делает запрос в БД, чтобы убедиться, что значение поля уникально.
 * 
 * @param type|array $variables @see getVariables()
 * @param type|string $field - поле для проверки
 * @param type|string $value - значение поля для проверки
 * @param type|array $old - массив полей с уникальными значениями (требуют проверки на уникальность)
 * @return type
 */
if(!function_exists('eventlist_checkUniqueQuery'))
{
  function eventlist_checkUniqueQuery($variables = [], $field = '', $value = '', $old = [])
  {
    $a = [];
    include_once(__DIR__ . '/database.php');
    if(!empty($field)
      || !empty($value)
      || !empty($variables['partnersTable'])
    )
    {
      $additionalWhere = "";
      if(!empty($old[$field]))
        $additionalWhere = " AND `$field`!='$old[$field]' ";

      $sql = "SELECT
          `$field`
        WHERE
          `$field`='$value'
          $additionalWhere";
      
      $o = query_db(
        1,
        $variables['partnersTable'],
        $sql
      );

      if(!empty($o))
      {
        if(!empty($o))
          $a = mysqli_fetch_assoc($o);
      }
    }

    return $a;
  }
}

/**
 * Проверка на уникальность значения поля в БД.
 * 
 * @param type|string $field - поле ля проверки 
 * @param type|string $value - значение для проверки
 * @param type|array $old - массив полей, которые уникальны
 * @return type
 */
if(!function_exists('eventlist_validateUnique'))
{
  function eventlist_validateUnique($field = '', $value = '', $old = [])
  {
    $error = [];
    $validationRule = eventlist_getValidationRule();
    $variables = eventlist_getVariables();
    if(empty($variables['partnersTable']))
      $error[] = "Непредвиденная ошибка на строке " . __LINE__ . ".";
    else
    {
      if(empty($field)
        || empty($value)
      )
      {
        if(empty($validationRule[$field]))
          $error[] = "Непредвиденная ошибка на строке " . __LINE__ . ".";

        $error[] = $validationRule[$field]['validation']['empty'];
      }

      // get old data
      $oldData = eventlist_getOldDataWithNewDataKey($old);
      $a = eventlist_checkUniqueQuery(
        $variables,
        $field,
        $value,
        $oldData
      );

      if(!empty($a))
        $error[] = $validationRule[$field]['validation']['unique'];
    }
    
    return $error;
  }
}

/**
 * Получает либо строку для sql, либо если задан $dateHour, то будет возвращен массив с ключами form, to по времени.
 * 
 * @param type|array $data - общий массив данных от сервиса
 * @param type|bool $dateHour - параметр, отвечающий за возврат в виде массива или sql строки
 * @return type
 */
if(!function_exists('eventlist_getTimeRange'))
{
  function eventlist_getTimeRange($data = [], $dateHour = false)
  {
    if(empty($data['from'])
      || empty($data['to'])
    )
      return '';

    if($dateHour)
      return [
        'from' => date('Ymd', strtotime($data['from'])),
        'to' => date('Ymd', strtotime($data['to'])) + 86400
      ];
    else
      $sql = " `time` BETWEEN '" . strtotime($data['from']) . "' AND '" . (strtotime($data['to']) + 86400) . "' AND  ";
    
    return $sql;
  }
}

/**
 * Функция для конструктора условия where у запроса.
 * 
 * @param type|array $data - список передаваемых данных
 * @return type
 */
if(!function_exists('eventlist_whereClause'))
{
  function eventlist_whereClause($data = [])
  {
    include_once(__DIR__ . '/database.php');
    $fields = eventlist_getFields();
    $avaliableActions = eventlist_getOperator4WhereClause();
    $strWhere = $timeStr = '';
    if(!empty($data)
      && is_array($data)
    )
    {
      // время обрабатываем отдельно
      if(!empty($data['from'])
        || !empty($data['to'])
      )
      {
        $timeStr = eventlist_getTimeRange($data);
        unset($data['from'], $data['to']);
      }
      foreach ($data as $key => $value)
      {
        // для канала делаем назависимый фильтр
        if('filter_channels' == $key
          && !empty($value)
        )
          $strWhere .= " `utm_medium` IN ('" . implode("','", $value) . "') AND ";
        
        // проверяем, что поле действительно у нас есть
        if(isset($fields[$key]))
        {
          // если значение - массив, то делаем IN констуркцию
          if(!empty($value)
            && is_array($value)
          )
          {
            if(!empty($value))
              $strWhere .= " `$key` IN ('" . implode("','", $value) . "') AND ";
          }
          else
          {
            if(!empty($value) && $key != 'ad' && $key != 'is_bot')
            {
              // получаем значение после обозначения поля (оператор)
              if(!empty($avaliableActions[$key]['operation']))
                $operator = $avaliableActions[$key]['operation'];
              else
                $operator = '=';

              $strWhere .= " `$key` $operator '" . prepare_db($value) . "' AND ";
            }
            else if(('ad' == $key || 'is_bot' == $key) && isset($value) && $value != -1)
              $strWhere .= " `$key` = '" . prepare_db((int) $value) . "' AND ";
          }
        }
      }

      if(!empty($strWhere)
        || !empty($timeStr)
      )
        $strWhere = " WHERE " . substr($timeStr . $strWhere, 0, -5) . " ";
      else
        $strWhere = " WHERE 1=1 ";

      return $strWhere;
    }
  }
}

/**
 * Возвращает список для селекта для COUNT и для строки. @see eventlist_getSelect
 * 
 * @param type|array $groupFields 
 * @return type
 */
if(!function_exists('eventlist_setSelectGroup'))
{
  function eventlist_setSelectGroup($groupFields = [])
  {
    $result = [];
    if(!empty($groupFields['domaingroup']))
    {
      $result['count'] = 'uuid';
      $result['show'] = 'domain';
    }
    else
    {
      return ['count' => 1,'show' => "`" . implode("`,`", $groupFields) . "`"];
      /*if (empty($groupFields[0]))
      {
        return $result;
      }
      $fields = eventlist_getFields();
      if(isset($fields[$groupFields[0]]))
      {
        $result['count'] = $groupFields[0];
        $result['show'] = $groupFields[0];
      }*/
    }

    return $result;
  }
}

/**
 * Получение строки SELECT запроса.
 * 
 * @param type|array $groupFields - список полей для подсчета
 * @return type
 */
if(!function_exists('eventlist_getSelect'))
{
  function eventlist_getSelect($groupFields = [])
  {
    // если переданы поля для группировки, то мы делаем по ним count
    $fields = eventlist_getFields();
    if(!empty($groupFields)
      && is_array($groupFields)
    )
    {
      $selectFields = eventlist_setSelectGroup($groupFields);
      if(!empty($selectFields['count'])
        && !empty($selectFields['show'])
      )
      {
        return "SELECT COUNT(*) AS cnt, SUM(`is_bot`) AS `c_bots`, SUM(`ad`) AS `c_ads`, " . $selectFields['show'] . " ";
      }
      else
        return "SELECT `" . implode("`,`", array_keys($fields)) . "`";
    }
    else
      return "SELECT `" . implode("`,`", array_keys($fields)) . "`";
  }
}

/**
 * Выбираем лимит для запроса.
 * 
 * @param type|string $data - строка с цифрой.
 * @return type
 */
if(!function_exists('eventlist_getLimits'))
{
  function eventlist_getLimits($data = '')
  {
    return (empty($data) ? ' LIMIT 10' : "LIMIT $data");
  }
}

/**
 * Группировка для запроса.
 * 
 * @param type|array $data 
 * @return type
 */
if(!function_exists('eventlist_getGrouping'))
{
  function eventlist_getGrouping($data = [])
  {
    include_once(__DIR__ . '/database.php');
    $fields = eventlist_getFields();
    $strGroup = '';
    if(!empty($data)
      && is_array($data)
    )
    {
      foreach ($data as $key => $value)
      {
        // проверяем, что поле действительно у нас есть
        if(isset($fields[$value]))
        {
          // если значение - массив, то делаем IN констуркцию
          if(!empty($value))
            $strGroup .= "`" . prepare_db($value) . "`,";              
        }
      }

      if(!empty($strGroup))
        $strGroup = " GROUP BY " . substr($strGroup, 0, -1);

      return $strGroup;
    }
  }
}

/**
 * Функция для установки сортировки по количеству записей.
 * Используется только в случае с GROUP BY.
 * 
 * @param type|array $group - поля для группировки
 * @return type
 */
if(!function_exists('eventlist_getSort'))
{
  function eventlist_getSort($group = [])
  {
    if(empty($group)) return '';
    $strSort = " ORDER BY cnt DESC ";

    return $strSort;
  }
}

/**
 * Строим sql запрос для таблицы event_list.
 * 
 * @param type|array $data - список данных от сервиса
 * @return type
 */
if(!function_exists('eventlist_queryBuilder'))
{
  function eventlist_queryBuilder($data = [])
  {
    // получем select
    /**
     * если выбрана какая-либо группировка, то автоматически должен происходить подсчет по этой величине
     */
    $countGroup = [];
    if(!empty($data['group']))
      $countGroup = $data['group'];
    $sql = eventlist_getSelect($countGroup);

    // получаем where
    if(!empty($data['filter']))
      $sql .= eventlist_whereClause($data['filter']);

    // получаем группировку
    if(!empty($data['group']))
      $sql .= eventlist_getGrouping($data['group']);

    // делаем сортировку по количеству, если есть группировка
    if(!empty($countGroup))
      $sql .= eventlist_getSort($data['group']);

    // получаем лимиты
    $sql .= eventlist_getLimits($data['limits']);

    return $sql;
  }
}

/**
 * Получение списка партнеров по их id.
 * 
 * @param type|array $ids - список id
 * @return type
 */
if(!function_exists('eventlist_getPartners'))
{
  function eventlist_getPartners($ids = [])
  {
    $partners = [];
    if(empty($ids)) return $partners;
    common_inc('services');
    $service = new MainService();
    $answer = $service->query(
      'partners', [
        'state' => 1,
        'id' => $ids,
        'action' => 'partnersList',
        'method' => 'partnerRun'
    ]);

    if(is_string($answer))
      $result = json_decode($answer, JSON_UNESCAPED_UNICODE);

    if(!empty($result['items']))
    {
      for ($i=0; $i < $ic = count($result['items']); $i++)
        $partners[$result['items'][$i]['id']] = $result['items'][$i]['name'];
    }

    return $partners;
  }
}

/**
 * Получение списка устройств по их id.
 * 
 * @param type|array $devices - id устройств
 * @return type
 */
if(!function_exists('eventlist_getDevice'))
{
  function eventlist_getDevice($devices = [])
  {
    return [
      1 => 'телефон',
      2 => 'планшет',
      3 => 'компьютер'
    ];
  }
}

/**
 * Получение списка городов по их id.
 * 
 * @param type|array $geos - id городов
 * @return type
 */
if(!function_exists('eventlist_getGeo'))
{
  function eventlist_getGeo($geos = [])
  {
    $result = [];
    if(empty($geos)) return [];
    include_once(__DIR__ . '/database.php');
    $sql = "SELECT `city`,
      `id`,
      `region`
      WHERE `id` IN ('".implode("','", $geos)."')";

    $o = query_db(
      1,
      'list_condition_geo',
      $sql
    );

    if(!empty($o))
    {
      while($a = mysqli_fetch_assoc($o))
        $result[$a['id']] = $a['city'] . "($a[region])";
    }

    return $result;
  }
}

/**
 * Получение списка провайдеров по их id.
 * 
 * @param type|array $isp - id провейдеров
 * @return type
 */
if(!function_exists('eventlist_getISP'))
{
  function eventlist_getISP($isp = [])
  {
    $result = [];
    if(empty($isp)) return [];
    include_once(__DIR__ . '/database.php');
    $sql = "SELECT `id`,
      `ips`
      WHERE `id` IN ('".implode("','", $isp)."')";

    $o = query_db(
      1,
      'list_condition_ips',
      $sql
    );

    if(!empty($o))
    {
      while($a = mysqli_fetch_assoc($o))
        $result[$a['id']] = $a['ips'];
    }

    return $result;
  }
}

/**
 * Метод для вывода списка событий.
 * 
 * @param array $data - передаваемые данные из сервиса.
 * @return string
 */
if(!function_exists('eventlist_eventlistList'))
{
  function eventlist_eventlistList($data = [])
  {
    include_once(__DIR__ . '/database.php');
    $variables = eventlist_getVariables();
    $fields = eventlist_getFields();

    if(!empty($data['action']))
      unset($data['action']);

    // строим запрос
    $sql = eventlist_queryBuilder($data);
    // dd($sql);

    // правим название таблицы
    /**
     * В версии MySQL 5.7 не используется index по времени,
     * поэтому при группировки его нужно указывать вручную
     */
    $table = $variables['table'];
    if (!empty($data['group']))
    {
      $table = $variables['table'] . ' FORCE INDEX (`time`) ';
    }

    // исполняем запрос
    $o = query_db(
      1,
      $variables['table'],
      $sql
    );


    $data['items'] = [];
    $data['header'] = $fields;    
    if(!empty($o))
    {
      $partners = $devices = $geos = $isps = [];
      while($a = mysqli_fetch_assoc($o))
      {
        // преобразовываем значения в человески удобные
        // преобразовываем партнера
        if(!empty($a['partner']))
          $partners[$a['partner']] = $a['partner'];

        // устройства
        if(!empty($a['device']))
          $devices[$a['device']] = $a['device'];

        // geo
        if(!empty($a['geo']))
          $geos[$a['geo']] = $a['geo'];

        // isp
        if(!empty($a['isp']))
          $isps[$a['isp']] = $a['isp'];


        $data['items'][] = $a;
      }

      // получаем данные по каждому из массивов
      $partnerList = eventlist_getPartners($partners);
      // получаем устройство
      $deviceList = eventlist_getDevice($devices);
      // получаем geo
      $geoList = eventlist_getGeo($geos);
      // получаем isp
      $ispList = eventlist_getISP($isps);
      // обновляем значения в выборке
      if(!empty($data['items']))
      {
        for ($i=0; $i < $ic = count($data['items']); $i++)
        { 
          // замена партнера
          if(!empty($data['items'][$i]['partner'])
            && !empty($partnerList[$data['items'][$i]['partner']])
          )
            $data['items'][$i]['partner'] = $partnerList[$data['items'][$i]['partner']];

          // замена устройства
          if(!empty($data['items'][$i]['device'])
            && !empty($deviceList[$data['items'][$i]['device']])
          )
            $data['items'][$i]['device'] = $deviceList[$data['items'][$i]['device']];

          // замена geo
          if(!empty($data['items'][$i]['geo'])
            && !empty($geoList[$data['items'][$i]['geo']])
          )
            $data['items'][$i]['geo'] = $geoList[$data['items'][$i]['geo']];

          // замена isp
          if(!empty($data['items'][$i]['isp'])
            && !empty($ispList[$data['items'][$i]['isp']])
          )
            $data['items'][$i]['isp'] = $ispList[$data['items'][$i]['isp']];

          // замена AdBlock
          if(!empty($data['items'][$i]['ad']))
          {
            $data['items'][$i]['ad'] = 'Есть';
          }
          else if (isset($data['items'][$i]['ad']))
          {
            $data['items'][$i]['ad'] = 'Нет';
          }

          // замена Bot
          if(!empty($data['items'][$i]['is_bot']))
            $data['items'][$i]['is_bot'] = 'Да';
          else if (isset($data['items'][$i]['is_bot']))
            $data['items'][$i]['is_bot'] = 'Нет';
        }
      }
    }

    return json_encode($data);
  }
}

/**
 * Получение списка доменов по их id.
 * 
 * @param type|array $list - список id по доменам
 * @return type
 */
if(!function_exists('eventlist_getDomainName'))
{
  function eventlist_getDomainName($list = [])
  {
    $result = [];
    if(!empty($list)
      && is_array($list)
    )
    {
      common_inc('_database');
      $o = select_db(
        1,
        'domain',
        ['name'],
        [], [], '', ['id' => $list]
      );

      if(!empty($o))
      {
        while ($a = mysqli_fetch_assoc($o)) {
          $result[] = $a['name'];
        }
      }
    }

    return $result;
  }
}

/**
 * Получаем список названий групп по временному промежутку и названий доменов.
 * 
 * @param type|array $data - общий список дат
 * @return type
 */
if(!function_exists('eventlist_getDomainByGroup'))
{
  function eventlist_getDomainByGroup($data = [])
  {
    $result = [];
    if(!empty($data['groupdomain'])
      && is_array($data['groupdomain'])
    )
    {
      // получаем домены для групп
      common_inc('groupFilter');
      $o = gf_get([], ['id' => $data['groupdomain']]);
      if(!empty($o))
      {
        while ($a = mysqli_fetch_assoc($o))
        {
          $result['select'][$a['id']] = json_decode($a['value']);
          $result['name'][$a['id']] = $a['name'];
        }
      }

      // получаем названия доменов
      if(!empty($result))
      {
        foreach ($result['select'] as $group => $list)
          $result['select'][$group] = eventlist_getDomainName($list);
      }
    }

    return $result;
  }
}

/**
 * Получение списка пользователей за промежуток времени.
 * 
 * @param type|array $time - временной промежуток (from, to)
 * @param type|array $group - список групп
 * @return type
 */
if(!function_exists('eventlist_getAllUser'))
{
  function eventlist_getAllUser($time = [], $group = [])
  {
    $result = [];
    if(!empty($time['from'])
      && !empty($time['to'])
      && !empty($group)
    )
    {
      common_inc('services');
      $service = new MainService();
      $answerUserByPeriod = $service->query('userlist', [
        'action' => 'userlist_getList',
        'method' => 'userlistRun',
        'datehour' => common_setValue($time, 'from') . ':' .
          common_setValue($time, 'to'),
        'domain' => $group
      ]);

      if(!empty($answerUserByPeriod)
        && is_string($answerUserByPeriod)
      )
        $result = json_decode($answerUserByPeriod, true);
    }

    return $result;
  }
}

/**
 * Разбиение списка пользователей за конкретные сутки.
 * 
 * @param type|array $list список данных от @see eventlist_getAllUser
 * @return type
 */
if(!function_exists('eventlist_getUsersByGroup'))
{
  function eventlist_getUsersByGroup($list = [])
  {
    $result = [];
    for ($i=0; $i < $ic = count($list); $i++)
    { 
      // получаем всех пользователей
      if(!empty($list[$i]['list']))
        $usersUnique = explode(',', $list[$i]['list']);

      $result = array_merge(
        (array) $usersUnique,
        (array) $result
      );
    }

    return $result;
  }
}

/**
 * Основная функция для поиска пересечений и вывода как общего количества так и кол-ва по пересечению.
 * 
 * @param type|array $data - данные от сервиса
 * @return type
 */
if(!function_exists('eventlist_eventlistCross'))
{
  function eventlist_eventlistCross($data = [])
  {
    $result = [];
    $answer = [];
    $domainList = eventlist_getDomainByGroup($data);
    $time = eventlist_getTimeRange($data, true);
    // для каждой группы получаем список юзеров
    if(is_array($domainList['select']))
    {
      foreach ($domainList['select'] as $group => $domains)
      {
        $result[$group] = eventlist_getAllUser(
          $time,
          $domains
        );

        // получаем список пользователей за период
        if (!empty($result[$group]['items']))
        {
          $result[$group] = eventlist_getUsersByGroup($result[$group]['items']);
        }
      }

      // производим сравнение по группам
      common_inc('native/array', 'array');
      $resultArray = [];
      $allVariants = array_getAllVariants(array_keys($result));
      $keyDomain = [];
      for($i = 0; $i < $ic = count($allVariants); $i++)
      {
        $keyDomain[$i] = '';
        $resultArray[$i] = [];
        $hasEmpty = false;
        for($j = 0; $j < $ij = count($allVariants[$i]); $j++)
        {
          $keyDomain[$i] .= $domainList['name'][$allVariants[$i][$j]] . ' & ';
          if(empty($result[$i]) && !empty($result[$allVariants[$i][$j]]))
            $d = $result[$allVariants[$i][$j]];
          else
            $d = $resultArray[$i];

          if(!empty($allVariants[$i][$j + 1])
            && !empty($result[$allVariants[$i][$j + 1]])
          )
          {
            $resultArray[$i] = array_arrayIntersect(
              (array) $d,
              (array) $result[$allVariants[$i][$j + 1]]
            );
          }

          if(empty($resultArray[$i]))
            $hasEmpty = true;
        }

        if($hasEmpty)
          $resultArray[$i] = [];

        $keyDomain[$i] = substr($keyDomain[$i], 0, -3);
      }

      // записываем общее кол-во по группам
      if(!empty($result)
        && is_array($result)
      )
      {
        foreach ($result as $group => $users)
          $answer['all'][$domainList['name'][$group]] = count($result[$group]); // общее кол-во юзеров
      }

      $answer['diff'] = [];

      // записываем результат сравнения
      foreach($resultArray as $key => $value)
        $answer['diff'][$keyDomain[$key]] = count($resultArray[$key]);

      return json_encode($answer);
    }
  }
}

/**
 * Если у пользователя стоит adblock, то вернет true.
 * 
 * @param type|string $uuid - идетификатор пользователя
 * @return type
 */
if(!function_exists('eventlist_isAdblock'))
{
  function eventlist_isAdblock($uuid = '')
  {
    if(empty($uuid)) return false;
    common_inc('_database');

    $sql = "SELECT `id` WHERE `uuid`='$uuid'";
    $o = query_db(
      1,
      'user_property',
      $sql
    );

    if(!empty($o))
    {
      $a = mysqli_fetch_assoc($o);
      if(!empty($a)
        && 1 == $a
      )
        return true;
    }

    return false;
  }
}

/**
 * Обновляет таблицу сбытий по id.
 * 
 * @param type|array $data - передаваемые аргуметны для обновления. Id передается в этом же массиве.
 * @return type
 */
if(!function_exists('eventlist_updateListById'))
{
  function eventlist_updateListById($data = [])
  {
    $state = false;
    if(empty($data['id'])) return $state;
    $id = $data['id'];
    // удаляем, так как нам не нужно обновлять это поле
    unset($data['id']);
    $variables = eventlist_getVariables();
    $fields = eventlist_getFields();
    include_once __DIR__ . '/database.php';
    if(!empty($variables['table']))
    {
      $update = []; // массив для данных, по которым будет произведено обновление
      foreach ($data as $key => $value)
      {
        if(isset($fields[$key]))
          $update[$key] = $value;
      }

      // сам запрос
      $state = update_db(
        1,
        $variables['table'],
        $update,
        ['id' => $id]
      );
    }
    return $state;
  }
}

/**
 * Обновляет данные по пользователю в таблице событий по наличию у него adBlock.
 * 
 * @param type|array $data - данные из очереди
 * @return type
 */
if(!function_exists('eventlist_updateAdblockInEventList'))
{
  function eventlist_updateAdblockInEventList($data = [])
  {
    if(!is_array($data) || empty($data)) return false;
    
      for ($i=0; $i < $ic = count($data); $i++)
      {
        // если есть параметры, то декодируем их
        if(!empty($data[$i]['param']))
        {
          $jsonData = json_decode($data[$i]['param'], true);
          if(!empty($jsonData['uuid'])
            && !empty($jsonData['id'])
          )
          {
            // делаем запрос к данным по пользователю
            $state = eventlist_isAdblock($jsonData['uuid']);
            $answer = eventlist_updateListById([
              'id' => $jsonData['id'],
              'ad' => $state
            ]);

            // подключаем API для сервисов
            common_inc('services');
            $service = new MainService();
            if(!empty($answer))
            {
              // удаляем запись при успешной вставки
              $service->query('mysqlqueue', [
                'method' => 'mysqliqueue_delete',
                'queue' => 'adblock',
                'id' => $data[$i]['id']
              ]);
            }
            else
            {
              // если не удалось вставить запись
              $service->query('mysqlqueue', [
                'method' => 'mysqliqueue_update',
                'queue' => 'adblock',
                'id' => $data[$i]['id'],
                'state' => 3
              ]);
            }
          }
        }
      }

    return true;
  }
}