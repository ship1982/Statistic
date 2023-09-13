<?php

include_once(__DIR__ . '/../../../lib/common/common.php');
include_once(__DIR__ . '/fetcher.php');
common_inc('sharding');

/**
 * Get data from array by key.
 *
 * @param array $data - input array.
 * @param string|int|bool $key - key from array.
 * @return mixed
 */
function getDataFromService($data = [], $key = '')
{
	return common_setValue($data, $key);
}

/**
 * Singleton by service.
 *
 * @param array $data - input data.
 * @return string
 */
function getByType($data = [])
{
	$type = getDataFromService($data, 'user_type');
	$countOfPath = getDataFromService($data, 'count_of_path');
	$conversion = getDataFromService($data, 'conversion');
	$time = getDataFromService($data, 'time');
	$lastDomain = getDataFromService($data, 'lastDomain');

	$rs = queryBuilder(
		$type,
		$countOfPath,
		$conversion,
		$time,
		$lastDomain
	);

	return $rs;
}