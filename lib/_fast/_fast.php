<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 27.04.16
 * Time: 14:55
 */

common_inc('_fetcher');

/**
 * Get select cluase for a query.
 * 
 * @param type|string $type - type of opration.
 * If count - then will be count distinct uuid
 * If sum, then count all id
 * @return string
 */
function fast_getSelect($type = 'count')
{
  $strSelect = '';
  switch ($type)
  {
    case 'count':
      $strSelect = 'COUNT(DISTINCT `uuid`) AS c, SUM(`is_bot`) AS c_bots, SUM(`ad`) AS c_ads, `domain`';
      break;
    case 'sum':
      $strSelect = 'COUNT(`id`) AS c, SUM(`is_bot`) AS c_bots, SUM(`ad`) AS c_ads, `domain`';
      break;
  }

  return $strSelect;
}

/**
 * Get where cluase for a query.
 * 
 * @param type|array $where - array with field => value pair for a query
 * @return type - where string without where and start with AND 
 */
function fast_getWhere($where = [])
{
  $strWhere = ' AND ';
  if(!empty($where))
  {
    foreach($where as $field => $value)
    {
      if($field == 'from' || $field == 'to') continue;
      $strWhere .= "`$field`='".$value."' AND ";
    }

    $strWhere = substr($strWhere, 0, -5);
  }

  if(strlen($strWhere) == 5)
    $strWhere = '';

  return $strWhere;
}

/**
 * Get IN statement in where clause.
 * 
 * @param type|array $in - array with key and values array.
 * key - is a field in table
 * vales - is a array of data
 * @return string 
 */
function fast_whereIn($in = [])
{
  $strIn = '';
  if(!empty($in))
  {
    foreach ($in as $key => $value)
    {
      if(!empty($value))
        $strIn .= '`'.prepare_db($key)."` IN ('" . implode("','",$value) . "') AND ";
    }
  }
  
  return $strIn;
}

/**
 * Get simple query to dirty table.
 * 
 * @param type|array $time @see fetcher_timePrepare()
 * @param type|string $strSelect - string for select
 * @param type|string $strWhere - string for where
 * @param type|array $in - array @see fast_whereIn()
 * @param type|string $group - field for group clause in query
 * @return type
 */
function fast_query2Dirty($time = [], $strSelect = '',  $strWhere = '', $in = [], $group = '')
{
  $resultArray = [];
  $first = false;
  $strIn = '';
  $arTimer = fetcher_getTimeRange($time);

  if(!empty($arTimer)
    && is_array($arTimer)
  )
  {
    for($i = 0; $i < $ic = count($arTimer); $i++)
    {
      /** @var $in */
      if(!empty($in)
        && !$first
      )
      {
        $first = true;
        $strIn .= fast_whereIn($in);
      }

      $res = query_db(
        $arTimer[$i],
        'dirty',
        "SELECT $strSelect 
        WHERE $strIn 
          `time` BETWEEN '" . prepare_db($time['from']) . "' AND '" . prepare_db($time['to']) . "'
          $strWhere
        GROUP BY `$group`"
      );

      while($a = mysqli_fetch_assoc($res))
        $resultArray[] = $a;
    }
  }

  return $resultArray;
}

/**
 * Get simple query in dirty table.
 * 
 * @param type|string $type default is count. It is mean, that record by domain will be counted (unique visits).
 * If @property $type will be a sum, that means, that record will be summed by day field (all visits)
 * @param type|array $where - where cluase for a query
 * @param type|array $in - IN part in where cluase
 * @param type|string $group - field for group clause
 * @return type
 */
function fast_doSimpleQuery($type = 'count', $where = [], $in = [], $group = '')
{
  if(empty($where['from']) || empty($where['to'])) return [];
  $table = 'dirty';
  
  $time = fetcher_timePrepare(
    $where['from'],
    $where['to']
  );

  $strSelect = fast_getSelect($type);
  $strWhere = fast_getWhere($where);

  $resultArray = fast_query2Dirty(
    $time,
    $strSelect,
    $strWhere,
    $in,
    'domain'
  );

  return $resultArray;
}

/**
 * Get data from dirty table.
 *
 * @param string $type . default is count. It is mean, that record by domain will be counted (unique visits).
 * If @property $type will be a sum, that means, that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table. If
 * @param array $in
 * @return array
 */
function fast_getByDomain($type = 'count', $where = [], $in = [])
{
  return fast_doSimpleQuery(
    $type,
    $where,
    $in,
    'domain'
  );
}

/**
 * Get list of domain, where id of array is a domian id
 * and value of array is a name of domain
 * @return array
 */
function fast_getDomainArray()
{
  $arNameDomain = [];
  $resNameDomain = fetcher_getDomain();
  while($a = mysqli_fetch_assoc($resNameDomain))
    $arNameDomain[$a['id']] = $a['name'];

  return $arNameDomain;
}

/**
 * Diff list of domain.
 * 
 * @param type|array $domains - array with domain ids.
 * @param type|array $arNameDomain - array with names of domain
 * @param type|array $resultByDomain @see fast_userByDomain()
 * @return array
 */
function fast_diffDomain($domains = [], $arNameDomain = [], $resultByDomain = [])
{
  $resultArray = [];
  if(empty($domains)
    && empty($arNameDomain)
    && empty($resultByDomain)
  )
    return $resultArray;

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

  unset($resultByDomain, $arNameDomain, $allVariants);

  /** prepare result */
  $result = [];
  foreach($resultArray as $key => $value)
    $result[$keyDomain[$key]] = count($resultArray[$key]);

  return $result;
}

/**
 * Compare users from domains.
 *
 * @param array $domains - array of domains
 * @param array $time - time to compare
 * @return array|bool
 */
function fast_compareByDomain($domains = [], $where = [])
{
  if(empty($where['from'])
    || empty($where['to'])
    || empty($domains)
  ) return [];

  $time = fetcher_timePrepare(
    $where['from'],
    $where['to']
  );

  /** collect all users by domain */
  $resultByDomain = fast_userByDomain($domains, $time);

  /** get domain array */
  $arNameDomain = fast_getDomainArray();

  $result = fast_diffDomain(
    $domains,
    $arNameDomain,
    $resultByDomain
  );

  return $result;
}

/**
 * Get user for field.
 * 
 * @param type|string $field - field for query
 * @param type|array $time @see fetcher_timePrepare()
 * @param type|array $domains - array with domain ids.
 * @return type
 */
function fast_userByField($field = '', $time = [], $domains = [])
{
  if(empty($time)
    || empty($field)
  )
    return [];
  
  $table = 'dirty';
  $arTimer = fetcher_getTimeRange($time);

  $result = [];
  for($i = 0; $i < $ic = count($arTimer); $i++)
  {
    $res = query_db(
      $arTimer[$i],
      $table,
      "SELECT DISTINCT `uuid`,`" . prepare_db($field) . "`
      WHERE
        `time` BETWEEN '" . prepare_db($time['from']) . "' AND '" . prepare_db($time['to']) . "'
        AND `" . prepare_db($field) . "` IN ('" . implode("','", $domains) . "')"
    );

    while($a = mysqli_fetch_assoc($res))
    {
      if(empty($result[$a[$field]]))
        $result[$a[$field]] = $a['uuid'];
      else
      {
        if(!empty($a['uuid']))
          $result[$a[$field]] = array_merge(
            (array) $result[$a[$field]],
            (array) $a['uuid']
          );
      }
    }
  }

  return $result;
}

/**
 * Get all users by domain during a time.
 *
 * @param array $domains - list of domains
 * @param array $time - array of time. [from] and [to]
 * @return array
 */
function fast_userByDomain($domains = [], $where = [])
{
  if(empty($where['from'])
    || empty($where['to'])
    || empty($domains)
  )
    return [];

  $time = fetcher_timePrepare(
    $where['from'],
    $where['to']
  );

  $result = fast_userByField(
    'domain',
    $time,
    $domains
  );

  return $result;
}

/**
 * Get data for start_referrer from dirty table.
 *
 * @param string $type . default is count. It is mean, that record by referrer will be counted (unique visits).
 * If @property $type will be a sum, that means, that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table. If
 * @param array $in
 * @return array
 */
function fast_getStartReferrer($type = 'count', $where = [], $in = [])
{
  return fast_doSimpleQuery(
    $type,
    $where,
    $in,
    'start_referrer'
  );
}

/**
 * Compare users from referrer.
 *
 * @param array $referrer - array of domains
 * @param array $time - time to compare
 * @return array
 */
function fast_compareByReferrer($referrer = [], $where = [])
{
  if(empty($time['from']) || empty($time['to']) || empty($referrer)) return [];

  $time = fetcher_timePrepare(
    $where['from'],
    $where['to']
  );

  /** collect all files by domain */
  $resultByReferrer = [];
  $resultByReferrer = fast_userByReferrer($referrer, $time);


  /** get domain array */
  $arNameDomain = fast_getDomainArray();

  /** get all referrer  */
  $result = fast_diffDomain(
    $referrer,
    $arNameDomain,
    $resultByReferrer
  );

  return $result;
}

/**
 * Get all users by referrer during a time.
 *
 * @param array $domains - list of domains
 * @param array $time - array of time. [from] and [to]
 * @return array|bool
 */
function fast_userByReferrer($domains = [], $where = [])
{
  if(empty($time['from'])
    || empty($time['to']) 
    || empty($domains)
  )
    return [];

  $time = fetcher_timePrepare(
    $where['from'],
    $where['to']
  );

  $result = fast_userByField(
    'enum_referrer',
    $time,
    $domains
  );

  return $result;
}

/**
 * Get data from dirty table about counter by domain.
 *
 * @param string $type . default is count. It is mean, that record by referrer will be counted (unique visits).
 * If @property $type will be a sum, that means, that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table. If
 * @param array $in
 * @return array
 */
function fast_getAllReferrer($type = 'count', $where = [], $in = [])
{
  return fast_doSimpleQuery(
    $type,
    $where,
    $in,
    'referrer'
  );
}