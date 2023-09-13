<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 24.04.16
 * Time: 17:02
 */

common_inc('_import');

/**
 * Return time if array form.
 * If value from bigger then to, then change it.
 *
 * @param int $to - to time
 * @param int $from - from time
 * @return array
 */
function fetcher_timePrepare($to = 0, $from = 0)
{
    if(empty($to) || empty($from)) return [];

    if($from > $to)
    {
        $from = $from + $to;
        $to = $from - $to;
        $from = $from - $to;
    }

    $time = [
        'from' => $from,
        'to' => $to
    ];

    return $time;
}

/**
 * get data from counter_domain table.
 * @param string $type . default is count. It is mean, that record by domain will be counted (unique visits).
 * If @property $type will be a sum, that means, that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table. If
 * @param array $in
 * @return array
 */
function fetcher_getByDomain($type = 'count', $where = [], $in = [])
{
    if(empty($where['from']) || empty($where['to'])) return [];

    $resultArray = fetcher_queryPrepare(
        3,
        $type,
        $where,
        $in
    );

    return $resultArray;
}

/**
 * Get month start between two dates.
 * This is a start timestamp of month between $time[from] to $time[to].
 *
 * @param array $time array with $time[from] and $time[to]
 * @return array|int
 */
function fetcher_getTimeRange($time = [])
{
    if(empty($time['from']) && empty($time['to'])) return -1;

    $time = fetcher_timePrepare($time['to'], $time['from']);

    $from = fetcher_dayMonthTimestamp($time['from']);
    $to_month = date('m', $time['to']);
    $from_month = date('m', $time['from']);
    $to = fetcher_dayMonthTimestamp($time['to'], $to_month);
    
    $arrayTime[] = $from;

    if((int) date('m', $from) != $to_month)
    {
        do{
            $from_month++;
            $_to = fetcher_dayMonthTimestamp($to, $from_month);
            $arrayTime[] = $_to;
        } while($_to < $to);
    }

    return $arrayTime;
}

/**
 * Return a timestamp of first day in the month.
 *
 * @param bool $timestamp - time for which the timestamp beginning of the month will be generated
 * @param bool $month - if passed, then generation will be executed for this month
 * @return int
 */
function fetcher_dayMonthTimestamp($timestamp = false, $month = false)
{
    if(!$timestamp) return -1;
    if(!$month)
        $month = date('m', $timestamp);

    $year = date('Y', $timestamp);

    return strtotime(date('c', mktime(0, 0, 0, ltrim($month, '0'), 1, $year)));
}

/**
 * Get users from user_log_dir by list of domain and range of time.
 * 
 * @param int $type
 * 1 - then choose form user_by_dir.
 * 2 - then choose user_by_referrer.
 * @param array domains - list of domain
 * @param int _from - time form
 * @param int _to - time to
 * @return array
 */
function fetcher_getUsers($type = 1, $domains = [], $_from = 0, $_to = 0)
{
    /** collect all files by domain */
    common_inc('_import');
    $day = 86400;
    switch ($type)
    {
        case 1:
            $dir = $GLOBALS['conf']['user_log_dir'];
            break;
        case 2:
            $dir = $GLOBALS['conf']['user_ref_dir'];
            break;
        default:
            $dir = $GLOBALS['conf']['user_log_dir'];
            break;
    }

    $resultByDomain = [];
    for($i = 0; $i < $ic = count($domains); $i++)
    {
        $tempArray = [];
        $from = import_dayStartTimestamp($_from);
        do {
            if(file_exists($dir . '/' . $from . '_' . $domains[$i]))
            {
                $handle = fopen($dir . '/' . $from . '_' . $domains[$i], 'r');
                if ($handle)
                {
                    while (($buffer = fgets($handle)) !== false)
                    {
                        $strBuffer = trim($buffer);
                        if(empty($tempArray[$strBuffer]))
                            $tempArray[$strBuffer] = $strBuffer;
                    }
                    fclose($handle);
                }
            }
            $from = $from + $day;
        } while($from <= $_to);

        $resultByDomain[$domains[$i]] = $tempArray;
    }

    return $resultByDomain;
}

/**
 * Map domain as 
 * id => name
 * 
 * @return array
 */
function fetcher_mapDomain()
{
    /** get domain array */
    $arNameDomain = [];
    $resNameDomain = fetcher_getDomain();
    while($a = mysqli_fetch_assoc($resNameDomain))
        $arNameDomain[$a['id']] = $a['name'];

    return $arNameDomain;
}

/**
 * Diff users by domain and do cross for all variants of domain.
 * 
 * @param array $domain - list of domian.
 * @param array $time - time array
 * @return array
 */
function fetcher_diffUserByDomain($type = 1, $domains = [], $time = [])
{
    $arNameDomain = fetcher_mapDomain();
    $time = fetcher_timePrepare($time['to'], $time['from']);
    $resultByDomain = fetcher_getUsers(
        $type,
        $domains,
        $time['from'],
        $time['to']
    );

    /** diff all domains */
    $resultArray = [];
    $allVariants = fetcher_getAllVariants($domains);

    $keyDomain = [];
    for($i = 0; $i < $ic = count($allVariants); $i++)
    {
        $keyDomain[$i] = '';
        $resultArray[$i] = [];
        $hasEmpty = false;
        for($j = 0; $j < $ij = count($allVariants[$i]); $j++)
        {
            $keyDomain[$i] .= $arNameDomain[$allVariants[$i][$j]] . ' & ';
            if(empty($resultArray[$i]) && !empty($resultByDomain[$allVariants[$i][$j]]))
                $d = $resultByDomain[$allVariants[$i][$j]];
            else
                $d = $resultArray[$i];

            if(!empty($allVariants[$i][$j + 1]) && !empty($resultByDomain[$allVariants[$i][$j + 1]]))
                $resultArray[$i] = fetcher_arrayIntersect(
                    (array) $d,
                    (array) $resultByDomain[$allVariants[$i][$j + 1]]
                );

            if(empty($resultArray[$i]))
                $hasEmpty = true;
        }
        if($hasEmpty)
            $resultArray[$i] = [];

        $keyDomain[$i] = substr($keyDomain[$i], 0, -3);
    }

    return [
        'keyDomain' => $keyDomain,
        'resultArray' => $resultArray
    ];
}

/**
 * Compare users from domains.
 *
 * @param array $domains - array of domains
 * @param array $time - time to compare
 * @param bool|int $uuids
 * @param bool $withoutBots - для вывода пересечений без выборки по ботам/адблокам
 * @return array|bool
 */
function fetcher_compareByDomain($domains = [], $time = [], $uuids = false, $withoutBots = false)
{
    if(empty($time['from']) || empty($time['to']) || empty($domains)) return false;

    $arResult = fetcher_diffUserByDomain(
        1,
        $domains,
        $time
    );

    /** prepare result */
    $result = [];
    if (!$withoutBots)
    {
      //результат с учетом выборки по ботам/адблокам
      list($resultBots, $resultAds) = fetcher_getDataBotAds($arResult['resultArray'], $time['from'], $time['to']);

      if (!$uuids)
      {
        foreach ($arResult['resultArray'] as $key => $value)
        {
          $result[$arResult['keyDomain'][$key]]['c'] = count($arResult['resultArray'][$key]);

          $result[$arResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key])) ? $resultBots[$key]['countBots'] : 0;
          $result[$arResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key])) ? $resultBots[$key]['percentBots'] : 0;
          $result[$arResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key])) ? $resultAds[$key]['countAds'] : 0;
          $result[$arResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key])) ? $resultAds[$key]['percentAds'] : 0;
        }
      }
      else
      {
        foreach ($arResult['resultArray'] as $key => $value)
        {
          $result[$arResult['keyDomain'][$key]]['c'] = $arResult['resultArray'][$key];

          $result[$arResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key])) ? $resultBots[$key]['countBots'] : 0;
          $result[$arResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key])) ? $resultBots[$key]['percentBots'] : 0;
          $result[$arResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key])) ? $resultAds[$key]['countAds'] : 0;
          $result[$arResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key])) ? $resultAds[$key]['percentAds'] : 0;
        }
      }
    }
    else
    {
      //результат без учета выборки по ботам/адблокам
      if(!$uuids)
      {
        foreach($arResult['resultArray'] as $key => $value)
          $result[$arResult['keyDomain'][$key]] = count($arResult['resultArray'][$key]);
      }
      else
      {
        foreach($arResult['resultArray'] as $key => $value)
          $result[$arResult['keyDomain'][$key]] = $arResult['resultArray'][$key];
      }
    }

    return $result;
}

/**
 * Return all combination for array.
 * example [1,2,3] - 123,12,13,23
 *
 * @param array $array
 * @return array
 */
function fetcher_getAllVariants($array = [])
{
    if(empty($array[1])) return [0 => $array];
    $res = [];
    for ($i = 1; $i < pow(2, count($array)); ++$i) {
        $pre = [];
        for ($j = 0; $j < count($array); ++$j) {
            if ($i & pow(2, $j)) {
                $pre[] = $array[$j];
            };
        };

        if(count($pre) > 1)
            $res[] = $pre;
    };

    return $res;
};

/**
 * Array intersect.
 *
 * @param $b
 * @param $a
 * @return array
 */
function fetcher_arrayIntersect($b, $a)
{
    if(is_string($a)
        || empty($a)
        || empty($b)
    )
        return [];

    $d = [];
    if(count($a) > count($b))
    {
        foreach ($b as $i)
            if (isset($a[$i]))
                $d[$i] = $i;
    }
    else
    {
        foreach ($a as $i)
            if (isset($b[$i]))
                $d[$i] = $i;
    }

    return $d;
}

/**
 * Do query to MySQL tables.
 * 
 * @param int $qtype - type of query.
 * 1 - $table start_referrer'
 * 2 - $table counter_ref_domain
 * 3 - $table counter_domain
 * @param int $type - do query with sum or count select definition.
 * @param array $where - where clause.
 * @param array $in - where IN clause
 */
function fetcher_queryPrepare($qtype = 1, $type = 'count', $where = [], $in = [])
{
    if(empty($where['from']) || empty($where['to'])) return [];

    if($type == 'count')
    {
      $botsAds = ', SUM(IF(`is_bot` > 0, 1, 0)) AS c_bots, SUM(IF(`ad` > 0, 1, 0)) AS c_ads';
    }
    else //if ($type == 'sum')
    {
      $botsAds = ', SUM(`is_bot`) AS c_bots, SUM(`ad`) AS c_ads';
    }

    switch ($qtype)
    {
        case 1:
            $table = 'start_referrer';
            $addSelect = $botsAds.', `referrer`';
            $groupBy = ' `referrer` ';
            $keyInArray = 'referrer';
            break;
        case 2:
            $table = 'counter_ref_domain';
            $addSelect = $botsAds.', `referrer`';
            $groupBy = ' `referrer` ';
            $keyInArray = 'referrer';
            break;
        case 3:
            $table = 'counter_domain';
            $addSelect = $botsAds.', `domain`';
            $groupBy = ' `domain` ';
            $keyInArray = 'domain';
            break;
        
        default:
            $table = 'start_referrer';
            $addSelect = $botsAds.', `referrer`';
            $groupBy = ' `referrer` ';
            $keyInArray = 'domain';
            break;
    }

    $time = fetcher_timePrepare($where['to'], $where['from']);
    $strSelect = '';
    if($type == 'count')
    {
      $strSelect = 'COUNT(DISTINCT `id`) AS c' . $addSelect;
    }
    elseif ($type == 'sum')
    {
      $strSelect = 'SUM(`day`) AS c' . $addSelect;
    }

    $strWhere = ' AND ';
    if(!empty($where))
    {
        foreach($where as $field => $value)
        {
            if($field == 'from' || $field == 'to') continue;
            $strWhere .= '`'.$field.'`=\''.$value.'\' AND ';
        }

        $strWhere = substr($strWhere, 0, -5);
    }
    if(strlen($strWhere) == 5)
        $strWhere = '';

    $resultArray = [];
    $first = false;
    $strIn = '';
    $arTimer = fetcher_getTimeRange($time);
    for($i = 0; $i < $ic = count($arTimer); $i++)
    {
        /** @var $in */
        if(!empty($in) && !$first)
        {
            $first = true;
            foreach ($in as $key => $value)
            {
                if(!empty($value))
                    $strIn .= '`'.prepare_db($key)."` IN ('" . implode("','",$value) . "') AND ";
            }
        }

        $query = 'SELECT ' . $strSelect . ' WHERE ' . $strIn .' `time` BETWEEN \''.prepare_db($time['from']).'\' AND \''.prepare_db($time['to']).'\' '.$strWhere . ' GROUP BY ' . $groupBy;

        $res = query_db(
            $arTimer[$i],
            $table,
            $query
        );

        while($a = mysqli_fetch_assoc($res))
            $resultArray[] = $a;
    }

    /** group ny month */
    $groupResult = [];
    foreach ($resultArray as $key => $arDomain)
    {
        if(!empty($arDomain[$keyInArray]))
        {

            if(empty($groupResult[$arDomain[$keyInArray]])) {
                $groupResult[$arDomain[$keyInArray]]['c'] = 0;
                $groupResult[$arDomain[$keyInArray]]['c_bots'] = 0;
                $groupResult[$arDomain[$keyInArray]]['c_ads'] = 0;
            }
            if(empty($groupResult[$arDomain[$keyInArray]]['c'])) {
                $groupResult[$arDomain[$keyInArray]]['c'] = 0;
            }
            if(empty($groupResult[$arDomain[$keyInArray]]['c_bots'])) {
                $groupResult[$arDomain[$keyInArray]]['c_bots'] = 0;
            }
            if(empty($groupResult[$arDomain[$keyInArray]]['c_ads'])) {
                $groupResult[$arDomain[$keyInArray]]['c_ads'] = 0;
            }

            $groupResult[$arDomain[$keyInArray]]['c'] += $arDomain['c'];
            $groupResult[$arDomain[$keyInArray]]['c_bots'] += $arDomain['c_bots'];
            $groupResult[$arDomain[$keyInArray]]['c_ads'] += $arDomain['c_ads'];
        }
    }

    $resultArray = [];

    foreach ($groupResult as $key => $count)
    {
        $resultArray[] = [
            $keyInArray => $key,
            'c' => $count['c'],
            'c_bots' => $count['c_bots'],
            'c_ads' => $count['c_ads']
        ];
    }

    return $resultArray;
}

/**
 * Get data from start_referrer table.
 *
 * @param string $type . default is count.
 * It is mean, that record by referrer will be counted (unique visits).
 * If @property $type will be a sum, that means,
 * that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table.
 * @param array $in
 * @return array
 */
function fetcher_getStartReferrer($type = 'count', $where = [], $in = [])
{
    if(empty($where['from']) || empty($where['to'])) return [];
    
    $resultArray = fetcher_queryPrepare(
        1,
        $type,
        $where,
        $in
    );

    return $resultArray;
}

/**
 * Get data from counter_ref_domain table.
 *
 * @param string $type . default is count.
 * It is mean, that record by referrer will be counted (unique visits).
 * If @property $type will be a sum, that means,
 * that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table.
 * @param array $in
 * @return array
 */
function fetcher_getAllReferrer($type = 'count', $where = [], $in = [])
{
    if(empty($where['from']) || empty($where['to'])) return [];
    
    $resultArray = fetcher_queryPrepare(
        2,
        $type,
        $where,
        $in
    );

    return $resultArray;
}

/**
 * get domains from table
 * @param array $where
 * @return bool|mysqli_result
 */
function fetcher_getDomain($where = [])
{
    return select_db(1, 'domain',['id','name'], $where);
}

/**
 * Get links from table.
 *
 * @param array $where  where clause.
 * @return mysqli_result
 */
function fetcher_getLink($where = [])
{
    $in = [];
    if(!empty($where['IN']))
    {
        $in = $where['IN'];
        unset($where['IN']);
    }

    return select_db(1, 'link',['id','domain_link'], $where, [], '', $in);
}

/**
 * Compare users from referrer
 * @param array $domains - array of domains
 * @param array $time - time to compare
 * @return array|bool
 */
function fetcher_compareByReferrer($domains = [], $time = [])
{
    if(empty($time['from']) || empty($time['to']) || empty($domains)) return false;

    $arResult = fetcher_diffUserByDomain(
        2,
        $domains,
        $time
    );

    list($resultBots, $resultAds) = fetcher_getDataBotAds($arResult['resultArray'], $time['from'], $time['to']);

    /** prepare result */
    foreach($arResult['resultArray'] as $key => $value)
    {
        $result[$arResult['keyDomain'][$key]]['c'] = count($arResult['resultArray'][$key]);

        $result[$arResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key]))?$resultBots[$key]['countBots']:0;
        $result[$arResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key]))?$resultBots[$key]['percentBots']:0;
        $result[$arResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key]))?$resultAds[$key]['countAds']:0;
        $result[$arResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key]))?$resultAds[$key]['percentAds']:0;

        $arResult['keyDomain'][$key] = $arResult['resultArray'][$key] = '';
    }

    return $result;
}

/**
 * Get users from user_log_dir by list of domain and range of time.
 * 
 * @param int $type
 * 1 - then choose form user_by_dir.
 * 2 - then choose user_by_referrer.
 * @param array domains - list of domain
 * @param int _from - time form
 * @param int _to - time to
 * @return array
 */
function fetcher_getUsersByGroup($type = 1, $groupList = [], $_from = 0, $_to = 0)
{
    /** collect domain by group */
    common_inc('_import');
    $day = 86400;
    switch ($type)
    {
        case 1:
            $dir = $GLOBALS['conf']['user_log_dir'];
            break;
        case 2:
            $dir = $GLOBALS['conf']['user_ref_dir'];
            break;
        default:
            $dir = $GLOBALS['conf']['user_log_dir'];
            break;
    }

    $groups = [];
    $tempArray = [];
    $resultByDomain = [];
    foreach ($groupList as $domain => $groupName)
    {
        /** collect group */
        if(!in_array($groupName, $groups))
            $groups[] = $groupName;

        $from = import_dayStartTimestamp($_from);
        do {
            if(file_exists($dir . '/' . $from . '_' . $domain))
            {
                $handle = fopen($dir . '/' . $from . '_' . $domain, 'r');
                if ($handle)
                {
                    while (($buffer = fgets($handle)) !== false)
                    {
                        $strBuffer = trim($buffer);
                        if(empty($tempArray[$groupName][$strBuffer]))
                            $tempArray[$groupName][$strBuffer] = $strBuffer;
                    }
                    fclose($handle);
                }
            }
            $from = $from + $day;
        } while($from <= $_to);

        $resultByDomain[$groupName] = (!empty($tempArray[$groupName]) ? $tempArray[$groupName] : []);
    }

    return [
        'groups' => $groups,
        'resultByDomain' => $resultByDomain
    ];
}

function fetcher_getDataBotAds($array = [], $timeStart = 0, $timeEnd = 0){
    common_inc('api/repeate_actions', 'repeate_actions');
    $repeateActions = new repeateActions();
    $repeateActions->setter_time_end(time());


    $resultBots = [];
    $resultAds = [];

    //Функция fetcher_compareGroupReferrer передает ассоциативный массив, остальные вроде нумерованный
    foreach($array as $key => $val){
        //Для каждого пересечения получаем данные о состоянии ботов
        $resultBots[$key] = $repeateActions->get_status_bot_manual($val, $timeStart, $timeEnd);
        //Для каждого пересечения получаем данные о состоянии адблока
        $resultAds[$key] = $repeateActions->get_probability_percent_ads($val);
    }

    return [$resultBots, $resultAds];
}

/**
 * Compare by group.
 * Compare all domains in group.
 *
 * @param array $groupList - list of group
 * @param array $time - time to compare
 * @param bool|int $uuid
 * @param bool $withoutBots - для вывода пересечений без выборки по ботам/адблокам
 * @return array
 */
function fetcher_compareGroupDomain($groupList = [], $time = [], $uuid = false, $withoutBots = false)
{
    if(empty($time['from']) || empty($time['to']) || empty($groupList)) return [];

    $time = fetcher_timePrepare($time['to'], $time['from']);

    /** collect domain by group */
    $arResult = fetcher_getUsersByGroup(
        1,
        $groupList,
        $time['from'],
        $time['to']
    );

    /** diff all domains */    
    $arDataResult = fetcher_diffUserByGroupResult(
        $arResult['groups'],
        $arResult['resultByDomain']
    );

    /** prepare result */
    $result = [];

    if (!$withoutBots)
    {
      //результат с учетом выборки по ботам/адблокам
      list($resultBots, $resultAds) = fetcher_getDataBotAds($arDataResult['resultArray'], $time['from'], $time['to']);

      if (!$uuid)
      {
        foreach ($arDataResult['resultArray'] as $key => $value)
          $result[$arDataResult['keyDomain'][$key]]['c'] = count($arDataResult['resultArray'][$key]);

        $result[$arDataResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key])) ? $resultBots[$key]['countBots'] : 0;
        $result[$arDataResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key])) ? $resultBots[$key]['percentBots'] : 0;
        $result[$arDataResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key])) ? $resultAds[$key]['countAds'] : 0;
        $result[$arDataResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key])) ? $resultAds[$key]['percentAds'] : 0;

        return $result;
      }
      else
      {
        foreach ($arDataResult['resultArray'] as $key => $value)
          $result[$arDataResult['keyDomain'][$key]]['c'] = $arDataResult['resultArray'][$key];

        $result[$arDataResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key])) ? $resultBots[$key]['countBots'] : 0;
        $result[$arDataResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key])) ? $resultBots[$key]['percentBots'] : 0;
        $result[$arDataResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key])) ? $resultAds[$key]['countAds'] : 0;
        $result[$arDataResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key])) ? $resultAds[$key]['percentAds'] : 0;

        return end($result);
      }
    }
    else
    {
      //результат без учета выборки по ботам/адблокам
      if(!$uuid)
      {
        foreach($arDataResult['resultArray'] as $key => $value)
          $result[$arDataResult['keyDomain'][$key]] = count($arDataResult['resultArray'][$key]);

        return $result;
      }
      else
      {
        foreach($arDataResult['resultArray'] as $key => $value)
          $result[$arDataResult['keyDomain'][$key]] = $arDataResult['resultArray'][$key];

        return end($result);
      }
    }
}

/**
 * Compare by group.
 * Compare all domains in group (referrer).
 *
 * @param array $groupList - list of group
 * @param array $time - time to compare
 * @return array
 */
function fetcher_compareGroupReferrer($groupList = [], $time = [], $uuid = false)
{
    if(empty($time['from']) || empty($time['to']) || empty($groupList)) return [];

    $time = fetcher_timePrepare($time['to'], $time['from']);

    $arResult = fetcher_getUsersByGroup(
        2,
        $groupList,
        $time['from'],
        $time['to']
    );

    /** diff all domains */
    $arDataResult = fetcher_diffUserByGroupResult(
        $arResult['groups'],
        $arResult['resultByDomain']
    );

    list($resultBots, $resultAds) = fetcher_getDataBotAds($arDataResult['resultArray'], $time['from'], $time['to']);

    $result = [];
    if(!$uuid)
    {
        foreach($arDataResult['resultArray'] as $key => $value)
            $result[$arDataResult['keyDomain'][$key]]['c'] = count($arDataResult['resultArray'][$key]);

            $result[$arDataResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key]))?$resultBots[$key]['countBots']:0;
            $result[$arDataResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key]))?$resultBots[$key]['percentBots']:0;
            $result[$arDataResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key]))?$resultAds[$key]['countAds']:0;
            $result[$arDataResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key]))?$resultAds[$key]['percentAds']:0;

        return $result;
    }
    else
    {
        foreach($arDataResult['resultArray'] as $key => $value)
            $result[$arDataResult['keyDomain'][$key]]['c'] = $arDataResult['resultArray'][$key];

            $result[$arDataResult['keyDomain'][$key]]['c_bots_c'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('countBots', $resultBots[$key]))?$resultBots[$key]['countBots']:0;
            $result[$arDataResult['keyDomain'][$key]]['c_bots_p'] = (!empty($resultBots) && !empty($resultBots[$key]) && array_key_exists('percentBots', $resultBots[$key]))?$resultBots[$key]['percentBots']:0;
            $result[$arDataResult['keyDomain'][$key]]['c_ads_c'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('countAds', $resultAds[$key]))?$resultAds[$key]['countAds']:0;
            $result[$arDataResult['keyDomain'][$key]]['c_ads_p'] = (!empty($resultAds) && !empty($resultAds[$key]) && array_key_exists('percentAds', $resultAds[$key]))?$resultAds[$key]['percentAds']:0;

        return end($result);
    }
}

/**
 * Get result of comparison user by group
 *
 * @param array $group - list of group
 * @param array $resultByDomain @see fetcher_getUsersByGroup
 * @return array
 */
function fetcher_diffUserByGroupResult($groups = [], $resultByDomain = [])
{
    $resultArray = [];
    $allVariants = fetcher_getAllVariants($groups);
    
    /** if one element */
    if(!empty($allVariants[0]) && count($allVariants[0]) == 1)
    {
        $resultArray[$allVariants[0][0]] = $resultByDomain[$allVariants[0][0]];
        $keyDomain[$allVariants[0][0]] = $allVariants[0][0];
    }
    else
    {
        /** if multiple */
        $keyDomain = [];
        for($i = 0; $i < $ic = count($allVariants); $i++)
        {
            $d = [];
            $keyDomain[$i] = '';
            $resultArray[$i] = [];
            $hasEmpty = false;
            for($j = 0; $j < $ij = count($allVariants[$i]); $j++)
            {
                $keyDomain[$i] .= $allVariants[$i][$j] . ' & ';
                
                if(empty($resultArray[$i]) && !empty($resultByDomain[$allVariants[$i][$j]]))
                    $d = $resultByDomain[$allVariants[$i][$j]];
                else
                    $d = $resultArray[$i];

                if(!empty($allVariants[$i][$j + 1]) && !empty($resultByDomain[$allVariants[$i][$j + 1]]))
                    $resultArray[$i] = fetcher_arrayIntersect(
                        (array) $d,
                        (array) $resultByDomain[$allVariants[$i][$j + 1]]
                    );

                if(empty($resultArray[$i]))
                    $hasEmpty = true;
            }
            if($hasEmpty)
                $resultArray[$i] = [];

            $keyDomain[$i] = substr($keyDomain[$i], 0, -3);
        }
    }

    return [
        'keyDomain' => $keyDomain,
        'resultArray' => $resultArray
    ];
}