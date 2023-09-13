<?php
/**
 * Получаем список служебных переменных из файла __DIR__ . '/../config/variables.php'
 *
 * @return type
 */
if(!function_exists('visitors_getVariables'))
{
    function visitors_getVariables()
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
if(!function_exists('visitors_getOperator4WhereClause'))
{
    function visitors_getOperator4WhereClause()
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
if(!function_exists('visitors_getFields'))
{
    function visitors_getFields()
    {
        if(file_exists(__DIR__ . '/../config/fields.php'))
            return require(__DIR__ . '/../config/fields.php');
        else
            return [];
    }
}

/**
 * Получает либо строку для sql, либо если задан $dateHour, то будет возвращен массив с ключами form, to по времени.
 *
 * @param type|array $data - общий массив данных от сервиса
 * @param type|bool $dateHour - параметр, отвечающий за возврат в виде массива или sql строки
 * @return type
 */
if(!function_exists('visitors_getTimeRange'))
{
    function visitors_getTimeRange($data = [], $dateHour = false)
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
if(!function_exists('visitors_whereClause'))
{
    function visitors_whereClause($data = [])
    {
        $fields = visitors_getFields();
        $avaliableActions = visitors_getOperator4WhereClause();
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
                $timeStr = visitors_getTimeRange($data);
                unset($data['from'], $data['to']);
            }
            foreach ($data as $key => $value)
            {
                // для канала делаем назависимый фильтр
                if('filter_channels' == $key
                    && !empty($value)
                )
                {
                  $googleChannels = new \GoogleChannels\GoogleChannels();
                  $strWhere .= $googleChannels->getFilterByGroup($value) . " AND ";
                }

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
                        if(!empty($value))
                        {
                            // получаем значение после обозначения поля (оператор)
                            if(!empty($avaliableActions[$key]['operation']))
                                $operator = $avaliableActions[$key]['operation'];
                            else
                                $operator = '=';

                            $strWhere .= " `$key` $operator '" . prepare_db($value) . "' AND ";
                        }
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

        return '';
    }
}

/**
 * Возвращает список для селекта для COUNT и для строки. @see eventlist_getSelect
 *
 * @param type|array $groupFields
 * @return type
 */
if(!function_exists('visitors_setSelectGroup'))
{
    function visitors_setSelectGroup($groupFields = [])
    {
        $result = [];
        if(!empty($groupFields['domaingroup']))
        {
            $result['count'] = 'uuid';
            $result['show'] = 'domain';
        }
        else
        {
            if(empty($groupFields[0])) return $result;

            $fields = visitors_getFields();
            if(isset($fields[$groupFields[0]]))
            {
                $result['count'] = $groupFields[0];
                $result['show'] = $groupFields[0];
            }
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
if(!function_exists('visitors_getSelect'))
{
    function visitors_getSelect($groupFields = [])
    {
        // если переданы поля для группировки, то мы делаем по ним count
        $fields = visitors_getFields();
        if(!empty($groupFields)
            && is_array($groupFields)
        )
        {
            $selectFields = visitors_setSelectGroup($groupFields);
            if(!empty($selectFields['count'])
                && !empty($selectFields['show'])
            )
                return "SELECT COUNT(`" . $selectFields['count'] . "`) AS cnt, `" . $selectFields['show'] . "`, SUM(`is_bot`) AS count_bot, SUM(`ad`) AS count_ad ";
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
if(!function_exists('visitors_getLimits'))
{
    function visitors_getLimits($data = '')
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
if(!function_exists('visitors_getGrouping'))
{
    function visitors_getGrouping($data = [])
    {
        $fields = visitors_getFields();
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
if(!function_exists('visitors_getSort'))
{
    function visitors_getSort($group = [])
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
if(!function_exists('visitors_queryBuilder'))
{
    function visitors_queryBuilder($data = [])
    {
        // получем select
        /**
         * если выбрана какая-либо группировка, то автоматически должен происходить подсчет по этой величине
         */
        $countGroup = [];
        if(!empty($data['group']))
            $countGroup = $data['group'];
        $sql = visitors_getSelect($countGroup);

        // получаем where
        if(!empty($data['filter']))
            $sql .= visitors_whereClause($data['filter']);

        // получаем группировку
        if(!empty($data['group']))
            $sql .= visitors_getGrouping($data['group']);

        // делаем сортировку по количеству, если есть группировка
        if(!empty($countGroup))
            $sql .= visitors_getSort($data['group']);

        // получаем лимиты
        $sql .= visitors_getLimits($data['limits']);

        return $sql;
    }
}

/**
 * Получение списка устройств по их id.
 *
 * @param type|array $devices - id устройств
 * @return type
 */
if(!function_exists('visitors_getDevice'))
{
    function visitors_getDevice($devices = [])
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
if(!function_exists('visitors_getGeo'))
{
    function visitors_getGeo($geos = [])
    {
        $result = [];
        if(empty($geos)) return [];

        $sql = "SELECT `city`, `id`, `region` 
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
if(!function_exists('visitors_getIPS'))
{
    function visitors_getIPS($ips = [])
    {
        $result = [];
        if(empty($ips)) return [];

        $sql = "SELECT `id`, `ips` 
        WHERE `id` IN ('".implode("','", $ips)."')";

        $o = query_db(
            1,
            'ripe_isp',
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

function visitors_timePrepare($to = '', $from = '')
{
    if(empty($to) || empty($from)) return [];

    $to = strtotime($to);
    $from = strtotime($from);

    if (!$to || !$from) return [];

    return [
        'from' => $from,
        'to' => $to
    ];
}

/**
 * Метод для вывода списка событий.
 *
 * @param type|array $data - передаваемые данные из сервиса.
 * @return type
 */
if(!function_exists('visitors_visitorsList')) {
    function visitors_visitorsList($data = [])
    {
        common_inc('_fetcher');

        $variables = visitors_getVariables();
        $fields = visitors_getFields();
        $groupKey = !empty($data['group'][0]) ? $data['group'][0] : false;

        $data['header'] = $fields;

        if(!empty($data['action']))
            unset($data['action']);

        $time = visitors_timePrepare($data['filter']['to'], $data['filter']['from']);
        $arTimer = fetcher_getTimeRange($time);

        $resultArray = [];
        $devices = $geos = $ipses = [];
        if (is_array($arTimer)) {
            // строим запрос
            $query = visitors_queryBuilder($data);
            // dd($arTimer);

            for ($i = 0; $i < $ic = count($arTimer); $i++) {
                // исполняем запрос
                $res = query_db(
                    $arTimer[$i],
                    $variables['table'],
                    $query
                );

                if (!empty($res)) {
                    while ($a = mysqli_fetch_assoc($res)) {
                        // преобразовываем значения в человески удобные
                        // устройства
                        if (!empty($a['device']))
                            $devices[$a['device']] = $a['device'];

                        // geo
                        if (!empty($a['geo']))
                            $geos[$a['geo']] = $a['geo'];

                        // isp
                        if (!empty($a['ips']))
                            $ipses[$a['ips']] = $a['ips'];

                        //если задано поле для группировки
                        if (!empty($groupKey)) {
                            if (empty($resultArray[$a[$groupKey]]))
                                $resultArray[$a[$groupKey]] = 0;

                            $resultArray[$a[$groupKey]] += intval($a['cnt']);
                        } else {
                            $resultArray[] = $a;
                        }
                    }
                }
            }
        }

        if(count($resultArray))
        {
            if (!empty($groupKey)) {
                $data['items'] = [];
                foreach ($resultArray as $key => $value) {
                    $data['items'][] = [
                        $groupKey => $key,
                        'cnt' => $value
                    ];
                }
            } else {
                $data['items'] = $resultArray;
            }

            // получаем устройство
            $deviceList = visitors_getDevice($devices);
            // получаем geo
            $geoList = visitors_getGeo($geos);
            // получаем isp
            $ipsList = visitors_getIPS($ipses);
            // обновляем значения в выборке
            for ($i=0; $i < $ic = count($data['items']); $i++)
            {
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
                if(!empty($data['items'][$i]['ips'])
                    && !empty($ipsList[$data['items'][$i]['ips']])
                )
                    $data['items'][$i]['ips'] = $ipsList[$data['items'][$i]['ips']];

                //бот
                if(!empty($data['items'][$i]['is_bot']))
                {
                  $data['items'][$i]['is_bot'] = 'Да';
                }
                else
                {
                  $data['items'][$i]['is_bot'] = 'Нет';
                }

                //адблок
                if(!empty($data['items'][$i]['ad']))
                {
                  $data['items'][$i]['ad'] = 'Есть';
                }
                else
                {
                  $data['items'][$i]['ad'] = 'Нет';
                }
            }
        }

        return json_encode($data);
    }
}
?>