<?php

include_once(__DIR__ . '/error.php');
$GLOBALS['error'] = new ErrorHandler();

/**
 * Get select clause for query.
 *
 * @return string
 */
function getSelect()
{
	return "SELECT SUM(`count`) as count, SUM(`count_bot`) as `c_bots`, SUM(`count_ad`) as `c_ads` , `time`, `json2sequence` ";
}

/**
 * Get from clause for query.
 *
 * @return string
 */
function getFrom()
{
	return "FROM `l_sequence_agregation` ";
}

/**
 * Compare user type form filter with user type from DB.
 *
 * @param array $type - user type by filter.
 * @return array
 */
function setDBUserTypes($type = [])
{
	$array = [];
	if(!empty($type)
		&& is_array($type)
	)
	{
		for ($i=0; $i < $ic = count($type); $i++)
		{ 
			if($type[$i] == 2
				|| $type[$i] == 4
			)
			{
				$array[1] = 1;
				$array[2] = 2;
				$array[3] = 3;
			}
			elseif($type[$i] == 1
				|| $type[$i] == 3
			)
				$array[4] = 4;
		}
	}
	if(empty($array))
		$GLOBALS['error']->setCode(1);

	return $array;
}

/**
 * Get sql query for conversion filter.
 *
 * @param array $conversion - conversion filter.
 * @return string
 */
function getConversion($conversion = [])
{
	$sql = '';
	if(empty($conversion)) return $sql;
	switch ($conversion[0])
	{
		case '1':
			$sql = " `orderStep`=1 OR `orderStep`=2 ";
			break;
		case '3':
			$sql = " `orderStep`=`onePath` ";
			break;
		case '2':
			$sql = " `orderStep`<`onePath` AND `orderStep`>2 ";
			break;
	}

	return $sql;
}

/**
 * Get additional filter for user type.
 * User that have order.
 *
 * @param array $userTypes - user type from filter.
 * @return string
 */
function getAdditionalFilterByType($userTypes = [])
{
	if(!empty($userTypes))
	{
		if((in_array(3, $userTypes)
			|| in_array(4, $userTypes))
			&& (!in_array(1, $userTypes)
				&& !in_array(2, $userTypes))
		)
		{
			return " `orderStep`>0 AND ";
		}
	}

	return "";
}

/**
 * Get where clause for query.
 * 
 * @param array $type - user type by filter
 * @param bool $count - if true, then get user path, that contain 2 or more paths
 * @param array $conversion - conversion by filter.
 * @param array $time - @see getTimeFromFilter()
 * @return string
 */
function getWhere($type = [], $count = false, $conversion = [], $time = [], $lastDomain = '')
{
	$where = getTimeFromFilter($time);
	if(empty($where))
		$GLOBALS['error']->setCode(2);

	if(!empty($type)
		&& is_array($type)
	)
	{
		$userTypes = setDBUserTypes($type);
		$where .= " `usertype` IN ('" . implode("','", $userTypes) . "') AND " . getAdditionalFilterByType($type);
	}
	else
		$GLOBALS['error']->setCode(1);
	
	if($count)
		$where .= " `onePath` > 1 AND ";
	
	if(!empty($conversion[0]))
		$where .= getConversion($conversion) . " AND ";

	// lastDomain
	if(!empty($lastDomain[0]))
		$where .= " `lastDomain`='$lastDomain[0]' AND ";

	$where = substr($where, 0, -5);

	return $where;
}

/**
 * Get limit query
 *
 * @param int $limit - count of rows
 * @return string
 */
function getLimit($limit = 100)
{
	return " LIMIT $limit";
}

/**
 * Get order query
 *
 * @return string
 */
function getOrder()
{
	return " ORDER BY `count` DESC ";
}

/**
 * Get connect to database.
 * 
 * @return resuorse|bool
 */
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

		if(!$link)
		{
			$GLOBALS['error']->setCode(3);
			return false;
		}
		mysqli_query($link, "SET NAMES 'utf8'");
		return $link;
	}

	$GLOBALS['error']->setCode(3);

	return false;
}

/**
 * Set result data from service by needed format
 *
 * @param array $data - array of data from service.
 * @param string $format - output fromat
 * @return mixed
 */
function formatOutput($data = [], $format = 'JSON')
{
	if(empty($data)) return '';
	switch ($format)
	{
		case 'JSON':
			$formatted = json_encode($data);
			break;
	}

	return $formatted;
}

/**
 * Execute sql
 *
 * @param string $sql - sql for executing
 * @return @see formatOutput()
 */
function queryExecute($sql = '')
{
	$result = [];
	$link = connect();
	if(!empty($link)
		&& !empty($sql)
	)
	{
		$rs = mysqli_query($link, $sql);
		if(!empty($rs)
			&& is_object($rs)
		)
		{
			while ($array = mysqli_fetch_assoc($rs))
				$result[] = $array;
		}
	}

	if(!empty($GLOBALS['error']->codes))
		return formatOutput(['error' => $GLOBALS['error']->show()]);

	return formatOutput($result);
}

/**
 * Get time query.
 *
 * @param array $time @see queryBuilder()
 * @return string
 */
function getTimeFromFilter($time = [])
{
	if(!empty($time)
		&& !empty($time['from'])
		&& !empty($time['to'])
	)
	{
		return " WHERE `time` BETWEEN $time[from] AND $time[to] AND ";
	}

	return false;
}

/**
 * Set group by clause.
 *
 * @param array $group array of fields for grouping
 * @return string
 */
function getGroupBy($group = [])
{
	$sql = '';
	if(empty($group)) return $sql;
	if(is_array($group))
	{
		$sql .= " GROUP BY ";
		for ($i=0; $i < $ic = count($group); $i++)
			$sql .= "`$group[$i]`, ";
	}

	return substr($sql, 0, -2);
}

/**
 * Create sql for query.
 * 
 * @param array $type - user type by filter
 * @param bool $count - if true, then get user path, that contain 2 or more paths
 * @param array $conversion - conversion by filter.
 * @param array $time - time from and to ['from'=>'','to'=>'']
 * @return string
 */
function queryBuilder($type = [], $count = false, $conversion = [], $time = [], $lastDomain = [])
{
	$sql = getSelect();
	$sql .= getFrom();
	$sql .= getWhere($type,
		$count,
		$conversion,
		$time,
		$lastDomain
	);
	$sql .= getGroupBy(['hash2sequence', 'time', 'json2sequence']);
	$sql .= getOrder();
	$sql .= getLimit();

	return queryExecute($sql);
}