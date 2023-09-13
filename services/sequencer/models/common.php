<?php

/**
 * Get timestamp for firts day of the week
 *
 * @param int $timestamp - time, for wich must calculate new timestamp.
 * @param int $key - key of the database table (sharding key)
 *
 * @return int|string
 */
function getFirstDayOfTheWeek($timesatmp = 0, $key = 0)
{
    $chooseTime = 0;
    if(empty($timesatmp) && !empty($key))
        return $key;
    else if(!empty($timesatmp))
        $chooseTime = $timesatmp;
    
    $newTimestamp = date("U", strtotime("Monday", $chooseTime));

    return $newTimestamp;
}

/**
 * Get timestamp for last day of the week
 *
 * @param int $timestamp - time, for wich must calculate new timestamp.
 * @return int|string
 */
function getLastDayOfTheWeek($startOfWeek = 0)
{
    if(!empty($startOfWeek))
    	return ($startOfWeek + 604800);
}

/**
 * Get timestamp for firts day of the month
 *
 * @param int $timestamp - time, for wich must calculate new timestamp.
 *
 * @return array
 */
function getFirstDayOfTheMonth($timesatmp = 0, $key = 0)
{
    $chooseTime = 0;
    if(empty($timesatmp) && !empty($key))
        $chooseTime = $key;
    else if(!empty($timesatmp))
        $chooseTime = $timesatmp;

    $date = date('c', $chooseTime);
    $month = date('F', $chooseTime);
    $newTimestamp = date("U", strtotime("first day of $date"));

    return [
    	'timestamp' => $newTimestamp,
    	'month' => $month
    ];
}

/**
 * Get timestamp for last day of the month
 *
 * @param int $timestamp - time, for wich must calculate new timestamp.
 *
 * @return array
 */
function getLastDayOfTheMonth($timesatmp = 0)
{
    $chooseTime = 0;
    if(empty($timesatmp) && !empty($key))
        $chooseTime = $key;
    else if(!empty($timesatmp))
        $chooseTime = $timesatmp;
    
    $date = date('c', $chooseTime);
    $month = date('F', $chooseTime);
    $newTimestamp = date("U", strtotime("last day of $date"));

    return [
    	'timestamp' => $newTimestamp,
    	'month' => $month
    ];
}

/**
 * Get key for a tables.
 *
 * @param int $timestamp - time, for wich must calculate new timestamp.
 * @param int $key - key of the database table (sharding key)
 *
 * @return array
 */
function getKey4Table($start = 0, $end = 0)
{
    /** data 4 a month */
    $startMonth = getFirstDayOfTheMonth($start);
    $endMonth = getLastDayOfTheMonth($end);
    if($startMonth['month'] == $endMonth['month'])
        return [$startMonth['timestamp']];
    else
    {
        return [
            date("U", strtotime("first day of " . date('F', $startMonth['timestamp']))),
            date("U", strtotime("first day of " . date('F', $endMonth['timestamp'])))
        ];
    }
}


/**
 * Get data from dirty table
 *
 * @param string $tableLog - name of table log file
 * @param string $pidLog - name of pid log file.
 *
 * @return array
 */
function getFromDirty($tableKey = '', $bitmask = '')
{
    if(empty($bitmask)) return [];
    $resource = [];
    
    $sql = "SELECT
            `id`,
            `time`,
            `uuid`
        WHERE 
            `position` & '$bitmask' != $bitmask
        LIMIT 10000";
    $o = query_db(
        $tableKey,
        'dirty',
        $sql
    );

    if(!empty($o))
        return $o;
    else
        return [];
}

/**
 * Get last user visit.
 * 
 * @param array $list - list of user ids
 * @return array
 */
function getUserList($list = [])
{
    if(empty($list)) return [];
	$sql = "SELECT `last_visit`, `uuid` WHERE `uuid` IN ('" . implode("','", $list) . "')";
	$userIds = [];
    $rsLastVisits = query_db(1, 'user_property', $sql);
    if(!empty($rsLastVisits))
    {
        while ($arLastVisit = mysqli_fetch_assoc($rsLastVisits))
            $userIds[$arLastVisit['uuid']] = $arLastVisit['last_visit'];
    }

    return $userIds;
}

/**
 * Build sql for user_property table. Update user type and last visit.
 *
 * @param array $userList - @see getFromDirty()
 * @param array $arLastVisit - @see getUserList()
 * @param array $userTypes - @see getUserTypes
 * @return string
 */
function prepareSQL4UpdateUserType($userList = [], $arLastVisit = [], $userTypes = [])
{
	if(empty($userList)) return '';
	$sqlUpdateStart = 'UPDATE `user_property` SET `last_visit`=';
    $sql = '';
    foreach ($arLastVisit as $uuid => $info)
    {
    	$sql .= $sqlUpdateStart;
    	$utype = getUserType(
    		$info,
    		$userList[$uuid]['time'],
    		$userList[$uuid]['c'],
    		$userTypes
    	);
    	$sql .= $userList[$uuid]['time'] . ', `utype`=\'' . $utype . '\' WHERE `uuid`=\'' . $uuid . '\';';
    }
    
    return $sql;
}

/**
 * Get list of user types
 *
 * @return array
 */
function getUserTypes()
{
	common_inc('_database');
	$userTypes = [];
    $rsUserType = query_db(
    	1,
    	'l_sequence_property',
    	"SELECT * WHERE 1=1 ORDER BY `y` DESC, `x`"
    );
    if(!is_bool($rsUserType))
    {
    	while ($arUserType = mysqli_fetch_assoc($rsUserType))
    		$userTypes[$arUserType['id']] = $arUserType;    	
    }
    else
    	exit('Can not end work!');

    return $userTypes;
}

/**
 * Define user type
 *
 * @param int $lvInDb - time from db with user visit
 * @param int $lvInLog - time from log file or dirty table with user visit
 * @param int $userCount - count of visit during a week.
 * @param array @see getUserTypes()
 * @return int
 */
function getUserType($lvInDb = 0, $lvInLog = 0, $userCount = 0, $userTypes = [])
{
	$dayTime = 86400;
	$week = 604800;
	$utype = 2; // default type
	foreach ($userTypes as $type => $array)
	{
		if($lvInDb + ($dayTime * $array['y']) > $lvInLog)
		{
			if($array['x'] <= $userCount)
				$utype = $type;
		}
	}	

	return $utype;
}

/**
 * Execute multy query.
 *
 * @param string $sql - query for execute.
 * @param string $table - table name for query.
 * @return void
 */
function executeSQL($sql = '', $table = '')
{
	if(!empty($sql) && !empty($table))
		return multyQuery_db(1, $table, $sql);
}

/**
 * Get user uuid and last_visit from batabase
 *
 * @param array $params - @see getParams()
 * @return bool|resource
 */
function getUserPropertyData($params = [])
{

    if(!empty($params['limit'])
        && ! empty($params['lastId'])
    )
    {
        $lastId = trim(get_pid($params['lastId'], 'sequencer'));
        $sql = "SELECT `uuid`,`last_visit` WHERE `last_visit` != '0' AND `uuid` > '" . $lastId . "' ORDER BY `uuid` LIMIT " . $params['limit'];
    
        $rsList = query_db(
            1,
            'user_property',
            $sql
        );

        return $rsList;
    }
    else
        return false;
}

/**
 * Define user type for table user_property.
 * This method dont use user count.
 *
 * @param int $lvInDb - time from db with user visit
 * @param array @see getUserTypes()
 * @return int
 */
function getUserTypeWithoutCount($lvInDb = 0, $userTypes = [])
{
    $dayTime = 86400;
    $week = 604800;
    $utype = 2; // default type
    $time = time();
    foreach ($userTypes as $type => $array)
    {
        if($lvInDb + ($dayTime * $array['y']) > $time)
            $utype = $type;
    }

    return $utype;
}

/**
 * Build sql for user_property table. Update user type only.
 *
 * @param string $uuid - user identifier
 * @param string $utype - defined user type
 * @return string
 */
function prepareSQL4UpdateUserTypeDeactivate($uuid = '', $utype = '')
{
    if(empty($uuid) || empty($utype)) return '';
    $sql = 'UPDATE `user_property` SET `utype`=\'' . $utype . '\' WHERE `uuid`=\'' . $uuid . '\';';
    return $sql;
}