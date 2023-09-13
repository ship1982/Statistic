<?php

include_once(__DIR__ . '/../../../lib/common/common.php');

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
function eventRun($data = [])
{
	$type = getDataFromService($data, 'action');
	$answer = "[]";
	switch ($type)
	{
		case 'eventainerAdd':
			include_once(__DIR__ . '/eventainer.php');
			$answer = eventAddAction($data);
			break;
		case 'eventainerDelete':
			include_once(__DIR__ . '/eventainer.php');
			$answer = eventDeleteAction($data);
			break;
		case 'eventainerList':
			include_once(__DIR__ . '/eventainer.php');
			$answer = eventShowAction($data);
			break;
		default:
			# code...
			break;
	}
	return $answer;
}