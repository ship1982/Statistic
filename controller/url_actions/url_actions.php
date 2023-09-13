<?php

use model\Model;
use services\MainService;
use native\arrays\ArrayHelper;

$layout = 'statistic';

function findReferer($urlActionsParams, $referer)
{
  for ($i = 0; $i < $count = count($urlActionsParams); $i++)
  {
    if (strstr($urlActionsParams[$i]['referer'], $referer))
    {
      return $urlActionsParams[$i]['referer'];
    }
  }

  return 'Неизвестный URL';
}

function showUrlActions()
{
  if (empty($_POST))
  {
    common_setView(
        'url_actions/startPage',[
            'result' => []
        ]
    );
    exit();
  }

  $urlActionsParams = [
    [
        'referer' => 'https://mgts.ru/home/internet/actions/439260/',
        'source' => 'https://mgts.ru/home/id/',
        'name' => 'Интернет GPON и Цифровое ТВ по летней цене! 299 руб.'
    ],
    /*[
        'referer' => 'http://www.mts.ru/dom/gpon/discount/archive/2017/summer2017/',
        'source' => '',
        'name' => 'Интернет GPON и Цифровое ТВ по летней цене! 299 руб.'
    ],*/
    [
        'referer' => 'https://mgts.ru/home/internet/actions/602428/',
        'source' => 'https://mgts.ru/home/id/',
        'name' => 'GPON – Домашний Интернет на космической скорости! 200 Мбит за 490 руб.'
    ],
    /*[
        'referer' => 'http://www.mts.ru/dom/gpon/discount/osen2017/',
        'source' => '',
        'name' => 'GPON – Домашний Интернет на космической скорости! 200 Мбит за 490 руб.'
    ],*/
    [
        'referer' => 'https://cosmosinternet.mgts.ru/',
        'source' => 'https://mgts.ru/home/id/',
        'name' => 'GPON – Домашний Интернет на космической скорости! 200 Мбит за 490 руб.'
    ],
    [
        'referer' => 'http://cosmosinternet.mts.ru/',
        'source' => 'https://mgts.ru/home/id/',
        'name' => 'GPON – Домашний Интернет на космической скорости! 200 Мбит за 490 руб.'
    ]
  ];

  $urlActionsReferers = ArrayHelper::map($urlActionsParams, '', 'referer');

  $urlActionsSql = '';
  for ($i = 0; $i < count($urlActionsParams); $i++)
  {
    $arr = parse_url($urlActionsParams[$i]['referer']);
    $refDomain = $arr['host'];
    $refLink = trim($arr['path'], '/');

    if (!empty($refLink))
    {
      $urlActionsSql .= "`referer_domain`='{$refDomain}' AND `referer_link` LIKE '%{$refLink}%' ";
    }
    else
    {
      $urlActionsSql .= "`referer_domain`='{$refDomain}' AND `referer_link`='{$refLink}' ";
    }

    if (!empty($urlActionsParams[$i]['source']))
    {
      $arr = parse_url($urlActionsParams[$i]['source']);
      $srcDomain = $arr['host'];
      $srcLink = trim($arr['path'], '/');

      $urlActionsSql .= "AND `domain_text`='{$srcDomain}' AND `link_text` LIKE '%{$srcLink}%' ";
    }

    $urlActionsSql .= "OR \n";
  }
  $urlActionsSql = '('.substr($urlActionsSql, 0, -5).')';

  $providers = [
      'Акадо' => [
        'akado'
      ],
      'Билайн' => [
        'beeline',
        'vimpelcom'
      ],
      'Он-лайм' => [
        'onlime'
      ],
      'МГТС' => [
        'mgts'
      ],
  ];

  $period = [
      /*'2017-01-01',
      '2017-02-01',
      '2017-03-01',
      '2017-04-01',*/
      '2017-05-01',
      '2017-06-01',
      '2017-07-01',
      '2017-08-01',
      '2017-09-01',
      '2017-10-01',
      '2017-11-01',
      '2017-12-01'
  ];

  //построение массива Название провайдера => [id провайдера]
  $ipsModel = new Model([1], 'list_condition_ips');
  $providerIdsNames = [];

  foreach ($providers as $providerName => $strIds)
  {
    $query = 'SELECT `id` FROM `list_condition_ips` WHERE ';
    for ($i = 0; $i < count($strIds); $i++)
    {
      $query .= "`ips` LIKE '%{$strIds[$i]}%' OR ";
    }
    $query = substr($query, 0, -4);
    $success = $ipsModel->query($query);
    if ($success === true)
    {
      $data = $ipsModel->fetch();
      if (!empty($data))
      {
        $ids = ArrayHelper::map($data, '', 'id');
        $providerIdsNames += array_fill_keys($ids, $providerName);
      }
    }
  }

  $resultColNames = ['Провайдер', 'URL-акция'];

  $resultByIps = [];
  $resultByIpsName = [];

  if (!empty($_POST['seances']))
  {
    $queryUnion = '';
    for ($i = 0; $i < count($period); $i++)
    {
      $shardKey = strtotime($period[$i]);
      $sequenceModel = new Model([$shardKey], 'l_sequence_4_user');
      $query = "
      SELECT `ips`, `referer_domain`, `referer_link`, COUNT(`seance`) AS `cnt`
      FROM l_sequence_4_user_{$shardKey}
      WHERE {$urlActionsSql}
      GROUP BY `ips`, `referer_domain`, `referer_link`
    ";

     /*$queryUnion .= "
      `seance` IN (SELECT DISTINCT(`seance`)\n
      FROM l_sequence_4_user_{$shardKey}\n
      WHERE {$urlActionsSql}) OR \n\n";*/

      /*$queryUnion .= "
      (SELECT COUNT(DISTINCT(`seance`)) 
      FROM l_sequence_4_user_{$shardKey}
      WHERE {$urlActionsSql}) UNION ALL \n";*/

      $success = $sequenceModel->query($query);

      if ($success === true)
      {
        $data = $sequenceModel->fetch();
        if (!empty($data))
        {
          for ($j = 0; $j < $count = count($data); $j++)
          {
            $ips = $data[$j]['ips'];
            $rd = $data[$j]['referer_domain'];
            $rl = $data[$j]['referer_link'];
            $cnt = (int)$data[$j]['cnt'];

            $url = $rd . '/' . $rl;
            $urlAction = findReferer($urlActionsParams, $url);

            //группировка по id-провайдеров
            $keyByIps = md5($ips . $urlAction);
            if (empty($resultByIps[$keyByIps]))
            {
              $resultByIps[$keyByIps] = [
                  'provider' => $ips,
                  'url' => $urlAction,
                  'cnt' => $cnt
              ];
            }
            else
            {
              $resultByIps[$keyByIps]['cnt'] += $cnt;
            }

            //группировка по названиям провайдеров
            $ipsName = common_setValue($providerIdsNames, $ips, 'Остальные');
            $keyByIpsName = md5($ipsName . $urlAction);
            if (empty($resultByIpsName[$keyByIpsName]))
            {
              $resultByIpsName[$keyByIpsName] = [
                  'provider' => $ipsName,
                  'url' => $urlAction,
                  'cnt' => $cnt
              ];
            }
            else
            {
              $resultByIpsName[$keyByIpsName]['cnt'] += $cnt;
            }
          }
        }
      }
    }

    $resultColNames[] = 'Сеансы';
  }
  elseif (!empty($_POST['events']))
  {
    $urlActionsSql = str_replace('`domain_text`', '`domain`', $urlActionsSql);
    $urlActionsSql = str_replace('`link_text`', '`link`', $urlActionsSql);
    $urlActionsSql = str_replace("`referer_link`=''", "`referer_link` LIKE '%/%'", $urlActionsSql);

    if ((int)$_POST['events'] === 1)
    {
      $queryEvents = "(`event_value` LIKE '%internet%' OR `event_label` LIKE '%internet%')";
      $resultColNames[] = 'Цель: Заявка Интернет';
    }
    else
    {
      $queryEvents = "(
         `event_value` LIKE '%phone%' OR `event_label` LIKE '%phone%' OR
         `event_value` LIKE '%TV%' OR `event_label` LIKE '%TV%' OR
         `event_value` LIKE '%MVNO%' OR `event_label` LIKE '%MVNO%' OR
         `event_value` LIKE '%nOPS%' OR `event_label` LIKE '%nOPS%' OR
         `event_value` LIKE '%nVideo%' OR `event_label` LIKE '%nVideo%'
      )";
      $resultColNames[] = 'Цель: Остальные заявки (ОС, MVNO и др.)';
    }

    $eventsModel = new Model([1], 'event_list');
    $query = "
      SELECT `isp`, `referer_domain`, `referer_link`, COUNT(*) AS `cnt`
      FROM `event_list`
      WHERE {$urlActionsSql}
      AND (`event_category`='zayavka_b2c' OR `event_type`='zayavka_b2c') 
      AND {$queryEvents}
      GROUP BY `isp`, `referer_domain`, `referer_link`
    ";

    $success = $eventsModel->query($query);

    if ($success === true)
    {
      $data = $eventsModel->fetch();
      if (!empty($data))
      {
        for ($j = 0; $j < $count = count($data); $j++)
        {
          $ips = $data[$j]['isp'];
          $rd = $data[$j]['referer_domain'];
          $rl = $data[$j]['referer_link'];
          $cnt = (int)$data[$j]['cnt'];

          $url = $rd . $rl;
          $urlAction = findReferer($urlActionsParams, $url);

          //группировка по id-провайдеров
          $keyByIps = md5($ips . $urlAction);
          if (empty($resultByIps[$keyByIps]))
          {
            $resultByIps[$keyByIps] = [
                'provider' => $ips,
                'url' => $urlAction,
                'cnt' => $cnt
            ];
          }
          else
          {
            $resultByIps[$keyByIps]['cnt'] += $cnt;
          }

          //группировка по названиям провайдеров
          $ipsName = common_setValue($providerIdsNames, $ips, 'Остальные');
          $keyByIpsName = md5($ipsName . $urlAction);
          if (empty($resultByIpsName[$keyByIpsName]))
          {
            $resultByIpsName[$keyByIpsName] = [
                'provider' => $ipsName,
                'url' => $urlAction,
                'cnt' => $cnt
            ];
          }
          else
          {
            $resultByIpsName[$keyByIpsName]['cnt'] += $cnt;
          }
        }
      }
    }
  }

  uasort(
      $resultByIpsName,
      function ($a, $b)
      {
        if ($a['url'] == $b['url'] && $a['provider'] < $b['provider'])
        {
          return -1;
        }
        elseif ($a['url'] == $b['url'] && $a['provider'] >= $b['provider'])
        {
          return 1;
        }
        elseif ($a['url'] < $b['url'])
        {
          return -1;
        }
        elseif ($a['url'] >= $b['url'])
        {
          return 1;
        }

        return 0;
      }
  );

  common_setView(
      'url_actions/startPage', [
          'result' => $resultByIpsName,
          'resultColNames' => $resultColNames
      ]
  );
}
?>