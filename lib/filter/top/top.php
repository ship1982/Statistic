<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 20.09.16
 * Time: 14:23
 */

/**
 * Prepare query & build it for Top queries.
 *
 * @param string $type - type of query.
 * Will be:
 * link - get query from link table (top_domain_link, top_domain_link_uuid)
 * @param array $where - where clause
 * @return string
 */
function top_prepare($type = 'link', $where = [])
{
    $arrayError = [];
    $strWhere = ' WHERE ';
    $strSelect = 'SELECT SUM(`count`) AS `c`, `domain`';
    $strSort = ' ORDER BY c DESC ';
    $groupBy = ' GROUP BY `link`, `domain` ';
    $strLimit = ' LIMIT 100';

    /** common */
    common_inc('_database');
    if(empty($where['from']) || empty($where['to']))
        $arrayError[] = -1;

    $time = top_timePrepare($where['to'], $where['from']);
    unset($where['from'], $where['to']);

    $strWhere .= '`time` BETWEEN \'' . $time['from'] . '\' AND \'' . $time['to'] . '\' AND ';

    if(!empty($where['IN'])
        && is_array($where['IN'])
    )
    {
      foreach ($where['IN'] as $key => $value)
      {
        if (!empty($value))
        {
          $strWhere .= '`' . prepare_db($key) . "` IN ('" . implode("','", $value) . "') AND ";
        }
        else
        {
          return '';
        }
      }
    }

    unset($where['IN']);

    if(!empty($where))
    {
        foreach ($where as $field => $value)
            $strWhere .= "`" . prepare_db($field) . "`='" . prepare_db($value) . "' AND ";
    }

    $strWhere = substr($strWhere, 0, -5);
    /** end common */

    switch ($type)
    {
        case 'link':
            $table = 'top_domain_link';
            $strSelect = 'SELECT SUM(`count`) AS `c`, SUM(`count_bot`) AS c_bots, SUM(`count_ad`) AS c_ads, `link`, `domain`';
            break;
        case 'region':
            $table = 'top_domain_link_city';
            $strSelect .= ',`city`, SUM(`count_bot`) AS c_bots, SUM(`count_ad`) AS c_ads ';
            $groupBy = ' GROUP BY `city`,`domain` ';
            $strWhere .= ' AND `city` <> \'\'';
            break;
        case 'provider':
            $table = 'top_domain_link_provider';
            $strSelect .= ',`provider`, SUM(`count_bot`) AS c_bots, SUM(`count_ad`) AS c_ads ';
            $groupBy = ' GROUP BY `provider`,`domain` ';
            $strWhere .= ' AND `provider` <> \'\'';
            break;
        case 'cross':
            $table = 'top_domain_link_uuid';
            $strSelect = 'SELECT COUNT(`uuid`) AS `c`, SUM(`is_bot`) AS c_bots, SUM(`ad`) AS c_ads, `link`, `domain`';
            break;
        case 'crossCity':
            $table = 'top_domain_link_city_uuid';
            $strSelect = 'SELECT COUNT(`uuid`) AS `c`, SUM(`is_bot`) AS c_bots, SUM(`ad`) AS c_ads, `domain`,`city` ';
            $groupBy = ' GROUP BY `city`,`domain` ';
            $strWhere .= ' AND `city` <> \'\'';
            break;
        case 'crossProvider':
            $table = 'top_domain_link_provider_uuid';
            $strSelect = 'SELECT COUNT(`uuid`) AS `c`, SUM(`count_bot`) AS c_bots, SUM(`ad`) AS c_ads, `domain`,`provider` ';
            $groupBy = ' GROUP BY `provider`,`domain` ';
            $strWhere .= ' AND `provider` <> \'\'';
            break;
    }

    /** build query */
    if(empty($table)) return [];

    $sql = $strSelect . $strWhere . $groupBy . $strSort . $strLimit;

    return [
        'query' => $sql,
        'table' => $table
    ];
}

/**
 * Do query for a top.
 *
 * @param string $type - type of query
 * @param array $where - where clause
 * @return array|mysqli_result
 */
function top_get($type = 'link', $where = [])
{
    $query = top_prepare($type, $where);
    if(!empty($query['query'])
        && !empty($query['table'])
    )
    {
        common_inc('_database');
        return query_db(
            1,
            $query['table'],
            $query['query']
        );
    }
    else
        return [];
}

/**
 * Return time if array form.
 * If value from bigger then to, then change it.
 *
 * @param int $to - to time
 * @param int $from - from time
 * @return array
 */
function top_timePrepare($to = 0, $from = 0)
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