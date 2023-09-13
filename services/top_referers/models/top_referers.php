<?php
use model\Model;

common_inc('_fetcher');

function topReferers_timePrepare($to = '', $from = '')
{
  if(empty($to) || empty($from)) return [];

  $to = strtotime($to);
  $from = strtotime($from);

  if (!$to || !$from) return [];

  if ($from > $to)
  {
    $from = $from + $to;
    $to = $from - $to;
    $from = $from - $to;
  }

  return [
      'from' => $from,
      'to' => $to
  ];
}

function topReferers_timeForQuery($timeRange)
{
  if(empty($timeRange)) return [];

  return [
      'from' => date('Ymd', $timeRange['from']),
      'to' => date('Ymd', $timeRange['to'])
  ];
}

function topReferers_sortResult($a, $b)
{
  if ($a['rating'] == $b['rating'])
    return 0;

  return ($a['rating'] < $b['rating']) ? 1 : -1;
}

/**
 * Метод для вывода пар партнерских доменов выбранных пользователем.
 *
 * @param array $partners - массив партнерских доменов выбранных пользователем.
 * @return array
 */
function topReferers_getPairs($partners)
{
  $result = [];
  $partnersLength = count($partners);

  if ($partnersLength > 1)
  {
    for ($i = 1; $i < $partnersLength; $i++)
    {
      $result[] = [
          $partners[0],
          $partners[$i]
      ];
    }
  }

  return $result;
}
/**
 * Подготовка списка партнеров для полнотекстового поиска
 *
 * @param array $partners - массив партнерских доменов выбранных пользователем.
 * @return string
 */
function topReferers_getPartnersQuery($partners)
{
  if (is_array($partners))
  {
    $result = '';
    for ($i = 0; $i < count($partners); $i++)
    {
      $result .= "AND `internal_way` REGEXP ';" . str_replace('.', '\.', $partners[$i]) . ";' ";
    }
    return $result;
  }

  return '';
}
/**
 * Метод для вывода рейтинга рефереров без пересечений.
 *
 * @param array $data - передаваемые данные из сервиса.
 * @return array
 */
if(!function_exists('topReferers_count'))
{
  function topReferers_count($data = [])
  {
    $timeRange = topReferers_timePrepare($data['filter']['to'], $data['filter']['from']);
    $shardKeys = fetcher_getTimeRange($timeRange);

    $timeForQuery = topReferers_timeForQuery($timeRange);
    $partnersForQuery =  "('" . implode("','", $data['filter']['partner_domains']) . "')";

    switch ($data['filter']['report_type'])
    {
      case 2:
        $isReportByDomains = false;
        $groupFields = '`referer_domain`, `referer_link`';
        $conditionFirst = "AND INSTR(`referer_domain`, 'mts.ru')=0 AND INSTR(`referer_domain`, 'mgts.ru')=0";
        $conditionSecond = "";
        break;

      case 3:
        $isReportByDomains = true;
        $groupFields = '`referer_domain`';
        $conditionFirst = "AND (INSTR(`referer_domain`, 'mts.ru')!=0 OR INSTR(`referer_domain`, 'mgts.ru')!=0)";
        $conditionSecond = "AND `referer_domain` NOT IN {$partnersForQuery}";
        break;

      case 4:
        $isReportByDomains = false;
        $groupFields = '`referer_domain`, `referer_link`';
        $conditionFirst = "AND (INSTR(`referer_domain`, 'mts.ru')!=0 OR INSTR(`referer_domain`, 'mgts.ru')!=0)";
        $conditionSecond = "AND `referer_domain` NOT IN {$partnersForQuery}";
        break;

      case 1:
      default:
        $isReportByDomains = true;
        $groupFields = '`referer_domain`';
        $conditionFirst = "AND INSTR(`referer_domain`, 'mts.ru')=0 AND INSTR(`referer_domain`, 'mgts.ru')=0";
        $conditionSecond = "";
        break;
    }

    $result = [];
    for ($i = 0; $i < count($shardKeys); $i++)
    {
      $referersTable = "`topReferers_linksCount_{$shardKeys[$i]}`";
      $model = new Model([$shardKeys[$i]], $referersTable, '');

      $model->query("
        SELECT {$groupFields}, SUM(`frequency`) AS `rating` 
        FROM {$referersTable}
        WHERE `partner_domain` IN {$partnersForQuery}
          AND `datehour` >= '{$timeForQuery['from']}'
          AND `datehour` <= '{$timeForQuery['to']}'
          {$conditionFirst}
          {$conditionSecond}
        GROUP BY {$groupFields} 
        ORDER BY `rating` DESC;  
      ");

      $fetchResult = $model->fetch();

      if (!empty($fetchResult))
      {
        $dataLength = count($fetchResult);
        for ($j = 0; $j < $dataLength; $j++)
        {
          if ($isReportByDomains)
            $key = md5($fetchResult[$j]['referer_domain']);
          else
            $key = md5($fetchResult[$j]['referer_domain'] . $fetchResult[$j]['referer_link']);

          if (empty($result[$key]))
            $result[$key] = [
                'referer_domain' => $fetchResult[$j]['referer_domain'],
                'referer_link' => !$isReportByDomains ? $fetchResult[$j]['referer_link'] : '',
                'rating' => (int)$fetchResult[$j]['rating']
            ];
          else
            $result[$key]['rating'] += (int)$fetchResult[$j]['rating'];
        }
      }
    }

    uasort($result, 'topReferers_sortResult');
    return array_slice($result, 0, $data['filter']['count']);
  }
}

/**
 * Метод для вывода рейтинга рефереров по пересечениям.
 *
 * @param array $data - передаваемые данные из сервиса.
 * @return array
 */
if(!function_exists('topReferers_cross'))
{
  function topReferers_cross($data = [])
  {
    $timeRange = topReferers_timePrepare($data['filter']['to'], $data['filter']['from']);
    $shardKeys = fetcher_getTimeRange($timeRange);

    $partnersQuery =  topReferers_getPartnersQuery($data['filter']['partner_domains']);
    $partnersList = $data['filter']['partner_domains'];

    switch ($data['filter']['report_type'])
    {
      case 2:
        $isReportByDomains = false;
        $resultField = 'external_links';
        break;

      case 3:
        $isReportByDomains = true;
        $resultField = 'internal_links';
        break;

      case 4:
        $isReportByDomains = false;
        $resultField = 'internal_links';
        break;

      case 1:
      default:
        $isReportByDomains = true;
        $resultField = 'external_links';
        break;
    }

    $result = [];

    for ($i = 0; $i < count($shardKeys); $i++)
    {
      $referersTable = "`topReferers_links_{$shardKeys[$i]}`";
      $model = new Model([], $referersTable, '');

      $model->query("
        SELECT `{$resultField}` 
        FROM {$referersTable} 
        WHERE `{$resultField}` != ''
          AND `time_start` <= {$timeRange['to']} 
          AND `time_end` >= {$timeRange['from']}
          {$partnersQuery}
          ORDER BY `id`; 
      ");

      $fetchResult = $model->fetch();

      if (!empty($fetchResult))
      {
        $dataLength = count($fetchResult);
        for ($j = 0; $j < $dataLength; $j++)
        {
          $cellJson = '[' . trim($fetchResult[$j][$resultField], ',') . ']';
          $cellResult = json_decode($cellJson, true);

          for ($k = 0; $k < count($cellResult); $k++)
          {
            if (!in_array($cellResult[$k]['pd'], $partnersList))
            {
              continue;
            }

            $t = (int) $cellResult[$k]['t'];
            if ($t < $timeRange['from'] || $t > $timeRange['to'])
            {
              continue;
            }

            if (!empty($cellResult[$k]['rd']))
            {
              if ($isReportByDomains)
                $key = md5($cellResult[$k]['rd']);
              else
                $key = md5($cellResult[$k]['rd'] . $cellResult[$k]['rl']);

              if (empty($result[$key]))
                $result[$key] = [
                    'referer_domain' => $cellResult[$k]['rd'],
                    'referer_link' => $cellResult[$k]['rl'],
                    'rating' => 1
                ];
              else
                $result[$key]['rating']++;
            }
          }
        }
      }
    }

    uasort($result, 'topReferers_sortResult');
    return array_slice($result, 0, $data['filter']['count']);
  }
}
?>