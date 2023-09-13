<?php

use model\Model;

include_once __DIR__ . '/../../../lib/autoload.php';

function beginWork($timestamp)
{
  if (!file_exists('begin.txt'))
  {
    file_put_contents('begin.txt', $timestamp);
  }
}

function getShardKey($timestamp)
{
  $yearMonth = date('Y-m', $timestamp);
  return strtotime($yearMonth.'-01');
}

function checkMainParams($paramsList)
{
  $success = true;
  if (!is_array($paramsList)
      || empty($paramsList['domain'])
      || empty($paramsList['time'])
      || empty($paramsList['uuid'])
      || !empty($paramsList['_t'])
  )
  {
    $success = false;
  }
  return $success;
}

function isExternalDomain($referrerDomain)
{
  //TODO: проверять по списку партнеров из таблицы `partners_list`
  return !strstr($referrerDomain, 'mts.') && !strstr($referrerDomain, 'mgts.');
}

$startTimeLabel = '';

$queue_topReferrers = new Model([1], 'queue_topReferers');

$data = $queue_topReferrers->_list(
    [
        'id',
        'param'
    ],
    ['state' => 1],
    ['time' => 'ASC'],
    '0,5000'
);

if (is_array($data) && $dataLength = count($data))
{
  //статус "в обработке"
  for ($i = 0; $i < $dataLength; $i++)
  {
    $queue_topReferrers->edit(
        ['state' => 2],
        ['id' => $data[$i]['id']],
        ''
    );
  }

  for ($i = 0; $i < $dataLength; $i++)
  {
    $linksSql = '';
    $linksCountSql = '';

    $paramsList = json_decode($data[$i]['param'], true);
    $success_links = $success_linksCount = true;

    if ($i === 0 && !empty($paramsList['time']))
    {
      $startTimeLabel = $paramsList['time'];
    }

    if (!checkMainParams($paramsList))
    {
      $queue_topReferrers->remove(
          ['id' => $data[$i]['id']]
      );
      continue;
    }

    if (!empty($paramsList['referrer']))
    {
      $datehour = date('Ymd', $paramsList['time']);
      $partnerDomain = str_replace('www.', '', $paramsList['domain']);

      $refComponents = parse_url($paramsList['referrer']);
      if ($refComponents === false || empty($refComponents['host']))
      {
        $queue_topReferrers->edit(
            ['state' => 3],
            ['id' => $data[$i]['id']],
            ''
        );
        continue;
      }

      $refererDomain = idn_to_utf8($refComponents['host']);
      if ($refererDomain === false)
      {
        $queue_topReferrers->edit(
            ['state' => 3],
            ['id' => $data[$i]['id']],
            ''
        );
        continue;
      }

      $refererLink = !empty($refComponents['path']) ? $refComponents['path'] : '/';
      $refererLink .= !empty($refComponents['query']) ? '?'.$refComponents['query'] : '';
      $refererLink .= !empty($refComponents['fragment']) ? '#'.$refComponents['fragment'] : '';

      //учитываются только внешние ссылки
      if ($refererDomain !== $partnerDomain)
      {
        $shardKey = getShardKey($paramsList['time']);

        $_links = new Model([$shardKey], 'topReferers_links');
        $_linksCount = new Model([$shardKey], 'topReferers_linksCount');

        $internalWay = ';' . $partnerDomain . ';';

        if (empty($refererDomain) || preg_match('/(mts\.|mgts\.)/', $refererDomain))
        {
          $internalLinks = json_encode([
              't' => $paramsList['time'],
              'pd' => $partnerDomain,
              'rd' => $refererDomain,
              'rl' => $refererLink
          ]);
          $internalLinks = $_links->prepare('', $internalLinks);

          $externalLinks = "''";
        }
        else
        {
          $internalLinks = "''";

          $externalLinks = json_encode([
              't' => $paramsList['time'],
              'pd' => $partnerDomain,
              'rd' => $refererDomain,
              'rl' => $refererLink
          ]);
          $externalLinks = $_links->prepare('', $externalLinks);
        }

        $linksSql = "(NULL, '{$paramsList['uuid']}', '{$internalWay}', {$internalLinks},  {$externalLinks}, {$paramsList['time']}, {$paramsList['time']})";

        if (!empty($refererLink))
        {
          $hash = md5($datehour . $partnerDomain . $refererDomain . $refererLink);
          $refererLink = $_linksCount->prepare('', $refererLink);

          $linksCountSql = "('{$hash}', {$datehour}, '{$partnerDomain}', {$refererLink},  '{$refererDomain}', 1, {$paramsList['time']})";
        }

        if (!empty($linksSql))
        {
          $success_links = $_links->query("
                    INSERT INTO topReferers_links_{$shardKey} (`id`, `uuid`, `internal_way`, `internal_links`, `external_links`, `time_start`, `time_end`) 
                    VALUES {$linksSql} 
                    ON DUPLICATE KEY UPDATE 
                      internal_way=IF(INSTR(internal_way, VALUES(internal_way)) = 0, CONCAT(internal_way, ' ', VALUES(internal_way)), internal_way), 
                      internal_links=IF(VALUES(internal_links) != '', CONCAT(internal_links, ',', VALUES(internal_links)), internal_links),
                      external_links=IF(VALUES(external_links) != '', CONCAT(external_links, ',', VALUES(external_links)), external_links),
                      time_end = VALUES(time_end);        
                ");

          if (!$success_links)
          {
            file_put_contents(__DIR__."/log/links_{$data[$i]['id']}.error", $_links->error . "\n\n\n" . $linksSql);
          }
        }

        if (!empty($linksCountSql))
        {
          $success_linksCount = $_linksCount->query("
                    INSERT INTO topReferers_linksCount_{$shardKey} (id, datehour, partner_domain, referer_link, referer_domain, frequency, time_label) 
                    VALUES {$linksCountSql} 
                    ON DUPLICATE KEY UPDATE frequency=frequency+1;      
                ");

          if (!$success_linksCount)
          {
            file_put_contents(__DIR__."/log/linksCount_{$data[$i]['id']}.error", $_linksCount->error . "\n\n\n" . $linksCountSql);
          }
        }
      }
    }

    if (!$success_links || !$success_linksCount)
    {
      $queue_topReferrers->edit(
          ['state' => 3],
          ['id' => $data[$i]['id']],
          ''
      );
    }
    else
    {
      $queue_topReferrers->remove(
          ['id' => $data[$i]['id']]
      );
    }
  }
}

if (empty($startTimeLabel))
{
  beginWork(time());
}
else
{
  beginWork($startTimeLabel);
}