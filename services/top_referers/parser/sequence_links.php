<?php
use model\Model;

//автозагрузка
include_once __DIR__ . '/../../../lib/autoload.php';

include_once 'parserSetup.php';

/**
 * @param $setup ParserSetup
 * @return void
 */
function work(&$setup)
{
  $config = $setup->getConfig();

  if (empty($config)) //если парсить нечего, прекращаем работу
  {
    exit();
  }

  $sequence = new Model([], 'l_sequence_4_user_' . $config['shard_key'], '');

  $_links = new Model([$config['shard_key']], 'topReferers_links');
  $_linksCount = new Model([$config['shard_key']], 'topReferers_linksCount', '');

  $sequence->query("
    SELECT `domain_text`, `id`, `time`, `uuid`, `referer_link`, `referer_domain`
    FROM l_sequence_4_user_{$config['shard_key']} 
    WHERE domain_text != referer_domain 
    AND id > {$config['last_id']}
    AND time < {$config['end_label']}
    ORDER BY id
    LIMIT 0, 200
  ");

  $data = $sequence->fetch();

  if (is_array($data) && $dataLength = count($data))
  {
    $linksSql = '';
    $linksCountSql = '';
    $lastId = 0;

    for ($i = 0; $i < $dataLength; $i++)
    {
      $paramsList = $data[$i];
      $lastId = $paramsList['id'];

      $datehour = date('Ymd', $paramsList['time']);
      $partnerDomain = str_replace('www.', '', $paramsList['domain_text']);
      $refererDomain = str_replace('www.', '', $paramsList['referer_domain']);


      if ($refererDomain !== $partnerDomain)
      {
        if (!empty($refererDomain))
        {
          $refererLink = '/' . $paramsList['referer_link'];
        }
        else
        {
          $refererLink = '';
        }

        $internalWay = ';' . $partnerDomain . ';';

        if (empty($refererDomain) || preg_match('/(mts\.ru|mgts\.ru)/', $refererDomain))
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

        $linksSql .= "(NULL, '{$paramsList['uuid']}', '{$internalWay}', {$internalLinks},  {$externalLinks}, {$paramsList['time']}, {$paramsList['time']}),";

        if (!empty($refererLink))
        {
          $hash = md5($datehour . $partnerDomain . $refererDomain . $refererLink);
          $refererLink = $_linksCount->prepare('', $refererLink);

          $linksCountSql .= "('{$hash}', {$datehour}, '{$partnerDomain}', {$refererLink},  '{$refererDomain}', 1, {$paramsList['time']}),";
        }
      }
    }

    if (!empty($linksSql))
    {
      $linksSql = ParserSetup::cropSql($linksSql);
      $success = $_links->query("
                    INSERT INTO topReferers_links_{$config['shard_key']} (`id`, `uuid`, `internal_way`, `internal_links`, `external_links`, `time_start`, `time_end`) 
                    VALUES {$linksSql} 
                    ON DUPLICATE KEY UPDATE 
                      internal_way=IF(INSTR(internal_way, VALUES(internal_way)) = 0, CONCAT(internal_way, ' ', VALUES(internal_way)), internal_way), 
                      internal_links=IF(VALUES(internal_links) != '', CONCAT(internal_links, ',', VALUES(internal_links)), internal_links),
                      external_links=IF(VALUES(external_links) != '', CONCAT(external_links, ',', VALUES(external_links)), external_links),
                      time_end = VALUES(time_end);        
                ");

      if (!$success)
      {
        file_put_contents(__DIR__."/log/links_{$lastId}.error", $_links->error);
      }
    }

    if (!empty($linksCountSql))
    {
      $linksCountSql = ParserSetup::cropSql($linksCountSql);
      $success = $_linksCount->query("
                    INSERT INTO topReferers_linksCount_{$config['shard_key']} (id, datehour, partner_domain, referer_link, referer_domain, frequency, time_label) 
                    VALUES {$linksCountSql} 
                    ON DUPLICATE KEY UPDATE frequency=frequency+1;      
                ");

      if (!$success)
      {
        file_put_contents(__DIR__."/log/linksCount_{$lastId}.error", $_linksCount->error);
      }
    }

    if (!empty($lastId))
    {
      $setup->saveStep($config['shard_key'], $lastId);
    }
  }
  else
  {
    $setup->saveStep($config['shard_key'], $config['last_id'], '1');
  }
}

$setup = new ParserSetup();
for ($i = 0; $i < 500; $i++)
{
  work($setup);
}
?>