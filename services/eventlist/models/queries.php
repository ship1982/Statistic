<?php

include_once(__DIR__ . '/../../../lib/common/common.php');

/**
 * Получаем данные с массива по ключу.
 *
 * @param array $data - входной массив.
 * @param string|int|bool $key - ключ из массива.
 * @return mixed
 */
if(!function_exists('getDataFromService'))
{
	function getDataFromService($data = [], $key = '')
	{
		return common_setValue($data, $key);
	}
}

/**
 * Точка входа к функциям сервиса.
 *
 * @param array $data - входные данные.
 * @return string
 */
if(!function_exists('eventlistRun'))
{
	function eventlistRun($data = [])
	{
		$type = getDataFromService($data, 'action');
		$answer = "[]";
		switch ($type)
		{
			case 'eventlist_eventlistList':
				include_once __DIR__ . '/eventlist.php';
				$answer = eventlist_eventlistList($data);
				break;
			case 'eventlist_eventlistCross':
				include_once __DIR__ . '/eventlist.php';
				$answer = eventlist_eventlistCross($data);
				break;
			default:
				return $answer;
				break;
		}
		return $answer;
	}
}