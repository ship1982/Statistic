<?php

/**
 * include common
 */
include_once(__DIR__ . '/../../common/common.php');

/**
 * Set bitmask value for application.
 * 
 * @param type $count - count of element form config
 * @param type $key - index of needed element
 * @return type binary string
 */
function queue_setBitmask($count = 0, $key = 0)
{
	if(empty($count)) return 0;
	$bitmask = str_repeat('0', $count);
	$bitmask[$key] = 1;
	return bindec(strrev($bitmask));
}

/**
 * Get bitmask for application.
 * 
 * @param type|string $service - name of application form config file.
 * @return type @see queue_setBitmask()
 */
function queue_getBitmask($service = '')
{
	if(empty($service))
	{
		include_once (__DIR__ . '/../../error/error.php');
    error_show(1, 'queue/mysql', ['line' => __LINE__, 'file' => __FILE__, 'function' => __FUNCTION__ ]);
	}
	$config = setConfig('queue/mysql/mysql');
	if(empty($config))
	{
		include_once (__DIR__ . '/../../error/error.php');
    error_show(2, 'queue/mysql', ['line' => __LINE__, 'file' => __FILE__, 'function' => __FUNCTION__ ]);
	}

	$configKeys = array_flip($config);
	if(empty($configKeys[$service])
		&& !is_int($configKeys[$service])
	)
	{
		include_once (__DIR__ . '/../../error/error.php');
    error_show(3, 'queue/mysql', ['line' => __LINE__, 'file' => __FILE__, 'function' => __FUNCTION__ ]);
	}

	$countOfObject = count($configKeys);
	return queue_setBitmask(
		$countOfObject,
		$configKeys[$service]
	);
}

/**
 * Update bitmask position in queue table.
 * 
 * @param type|array $listOfids - list of ids then must be updated
 * @param type|string $key - key form table if it sharded
 * @param type|string $bitmask - value of bitmask @see queue_setBitmask()
 * @param type|string $table - table of queue
 * @return type bool
 */
function queue_setPosition($listOfids = [], $key = '', $bitmask = '', $table = 'dirty')
{
	if(empty($listOfids)
		|| empty($table)
		|| empty($key)
	)
		return false;

	if(function_exists('updateQuery_db'))
	{
		updateQuery_db(
		    $key,
		    $table,
		    "UPDATE {from} SET `position` = `position` | '$bitmask' WHERE `id` IN (" . implode(',', $listOfids) . ")"
		);

		return true;
	}

	return false;
}