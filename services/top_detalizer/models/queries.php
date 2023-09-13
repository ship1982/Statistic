<?php

include_once(__DIR__ . '/../../../lib/common/common.php');
common_inc('sharding');

function execute($params = [])
{
	$result = [];
	$arTime = normalizeTime($params['from'], $params['to']);
	if(empty($arTime['to'])
		|| empty($arTime['from'])
	)
		return -6;

	$calendar = getCalendarData($params);
	if(!empty($calendar))
	{
		$weekArray = getCalendarArray4Query($calendar, $arTime);
		foreach ($weekArray as $key => $value)
			get($value, $params, $result);
	}
	else
		return -7;

	return json_encode($result);
}

function normalizeTime($from = 0, $to = 0)
{
	if($from < $to)
	{
		return [
			'from' => $from,
			'to' => $to
		];
	}
	else if($from > $to)
	{
		$from = $from + $to;
        $to = $from - $to;
        $from = $from - $to;

		return [
			'from' => $from,
			'to' => $to
		];
	}
	else
	{
		return [
			'from' => $from,
			'to' => $to
		];
	}
}

function get($arHour2Query = [], $where = [], &$result = [])
{
	if(!empty($arHour2Query))
	{
		$link = connect();
		if(!$link)
			return -2;

		$fileds = getFields();
		if(empty($fileds))
			return -3;
		
		$query = '';
		$data = $result = [];
		$additionalSelect = common_setValue($where, 'field');
		$table = common_setValue($where, 'table');
		if(empty($table))
			return -1;
		
		foreach ($arHour2Query as $key => $hour)
		{
			switch ($where['type'])
			{
				case 'region':
                case 'crossCity':
                    $query .= 'SELECT SUM(`count`) AS c, SUM(`count_bot`) AS c_bots, SUM(`count_ad`) AS c_ads, `domain` ' . $additionalSelect . ' FROM `' . $table . '` WHERE `hour` = \''.$hour.'\' AND `domain` IN ('.mysqli_real_escape_string($link, $where['domain']).') AND `time` BETWEEN \''.mysqli_real_escape_string($link, $where['from']).'\' AND \''.mysqli_real_escape_string($link, $where['to']).'\' AND `city` <> \'\' GROUP BY `city`,`domain` UNION ALL ';
                    break;
                case 'provider':
                case 'crossProvider':
                    $query .= 'SELECT SUM(`count`) AS c, SUM(`count_bot`) AS c_bots, SUM(`count_ad`) AS c_ads, `domain` ' . $additionalSelect . ' FROM `' . $table . '` WHERE `hour` = \''.$hour.'\' AND `domain` IN ('.mysqli_real_escape_string($link, $where['domain']).') AND `time` BETWEEN \''.mysqli_real_escape_string($link, $where['from']).'\' AND \''.mysqli_real_escape_string($link, $where['to']).'\' AND `provider` <> \'\' GROUP BY `provider`,`domain` UNION ALL ';
                    break;
                
                default:
                    $query .= 'SELECT SUM(`count`) AS c, SUM(`count_bot`) AS c_bots, SUM(`count_ad`) AS c_ads, ' . implode(',', array_keys($fileds)) . $additionalSelect . ' FROM `' . $table . '` WHERE `hour` = \''.$hour.'\' AND `domain` IN ('.mysqli_real_escape_string($link, $where['domain']).') AND `time` BETWEEN \''.mysqli_real_escape_string($link, $where['from']).'\' AND \''.mysqli_real_escape_string($link, $where['to']).'\' GROUP BY `link`, `domain` UNION ALL ';
                    break;
			}
		}

		$query = substr($query, 0, -10) . ' LIMIT 100';

		// echo $query . "<br><br>";

		$rs = mysqli_query($link, $query);
		if (!empty($rs))
        {
          while($ar = mysqli_fetch_assoc($rs))
          {
            switch ($where['type'])
            {
              case 'region':
              case 'crossCity':
                $result['list'][$ar['city']][] = $ar;
                if(empty($result['count'][$ar['city']]))
                {
                  $result['count'][$ar['city']]['c'] = 0;
                  $result['count'][$ar['city']]['c_bots'] = 0;
                  $result['count'][$ar['city']]['c_ads'] = 0;
                }

                $result['count'][$ar['city']]['c'] += $ar['c'];
                $result['count'][$ar['city']]['c_bots'] += (int)$ar['c_bots'];
                $result['count'][$ar['city']]['c_ads'] += (int)$ar['c_ads'];
                break;
              case 'provider':
              case 'crossProvider':
                $result['list'][$ar['provider']][] = $ar;
                if(empty($result['count'][$ar['provider']]))
                {
                  $result['count'][$ar['provider']]['c'] = 0;
                  $result['count'][$ar['provider']]['c_bots'] = 0;
                  $result['count'][$ar['provider']]['c_ads'] = 0;
                }

                $result['count'][$ar['provider']]['c'] += $ar['c'];
                $result['count'][$ar['provider']]['c_bots'] += (int)$ar['c_bots'];
                $result['count'][$ar['provider']]['c_ads'] += (int)$ar['c_ads'];
                break;
              default:
                if(empty($result[$ar['domain'].'-'.$ar['link']]['c']))
                  $result[$ar['domain'].'-'.$ar['link']] = $ar;
                else
                {
                  $result[$ar['domain'].'-'.$ar['link']]['c'] = $result[$ar['domain'].'-'.$ar['link']]['c'] + $ar['c'];
                  $result[$ar['domain'].'-'.$ar['link']]['c_bots'] = $result[$ar['domain'].'-'.$ar['link']]['c_bots'] + (int)$ar['c_bots'];
                  $result[$ar['domain'].'-'.$ar['link']]['c_ads'] = $result[$ar['domain'].'-'.$ar['link']]['c_ads'] + (int)$ar['c_ads'];
                }
                break;
            }
          }
        }


		return $result;
	}
}

function connect()
{
	$config = require( __DIR__ . '/../config/db.php');
	if(!empty($config['db_user'])
		&& !empty($config['db_pass'])
		&& !empty($config['db_name'])
		&& !empty($config['db_type'])
	)
	{
		$link = mysqli_connect(
			$config['db_type'],
			$config['db_user'],
			$config['db_pass'],
			$config['db_name']
		);

		if(!$link) return -5;
		mysqli_query($link, "SET NAMES 'utf8'");
		return $link;
	}

	return -4;
}

function getFields()
{
	$fields = require( __DIR__ . '/../config/fields.php');
	if(empty($fields))
		return [];

	$needField = [];
	foreach ($fields as $key => $value) {
		if(!empty($value))
			$needField['`'.$key.'`'] = $value;
	}

	return $needField;
}

function getCalendarData($params = [])
{
	$result = [];
	if(!empty($params['calendar']))
		$result = json_decode($params['calendar'], true);

	return $result;
}

function getCalendarArray4Query($calendar = [], $arTime = [])
{
	$result = [];
	if(!empty($calendar) && is_array($calendar))
	{
		$listOfWeek = getDayOfWeek($arTime);
		foreach ($listOfWeek as $numberWeek)
		{
          if (!empty($calendar[$numberWeek]))
          {
            $wn = $numberWeek;
            $weekArray = [];
            $endTime = 0;
            $startTime = 0;
            if (strpos($calendar[$wn], '-') !== false)
              list($endTime, $startTime) = explode('-',$calendar[$wn]);
            if(empty($startTime))
            {
              $result[$wn] = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23];
            }
            else
            {
              if($endTime <= $startTime)
              {
                $to = (int) $endTime;
                while($to <= $startTime)
                {
                  $weekArray[] = $to;
                  $to++;
                }
                $result[$wn] = $weekArray;
              }
              else
              {
                $to = (int) $endTime;
                while($to <= 23)
                {
                  $weekArray[] = $to;
                  $to++;
                }
                $to = 0;
                while($to <= $startTime)
                {
                  $weekArray[] = $to;
                  $to++;
                }
                $result[$wn] = $weekArray;
              }
            }
          }
		}
	}

	return $result;
}

/** get list of week */
function getDayOfWeek($arTime = [])
{
	$week = 604800;
	$day = 86400;
	$result = [];
	/** less then week */
	if($arTime['to'] - $arTime['from'] < $week)
	{
		/** get start week */
		$to = $arTime['from'];
		
		while($to <= $arTime['to'])
		{
			$start = date('w', $to) - 1;
			if($start == -1)
				$start = 6;
			$result[] = $start;
			$to += $day;
		}

		return $result;
	}

	return [1,2,3,4,5,6,7];
}