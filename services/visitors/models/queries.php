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
if(!function_exists('visitorsRun'))
{
	function visitorsRun($data = [])
	{
		$type = getDataFromService($data, 'action');
		$answer = "[]";
		switch ($type)
		{
			case 'visitors_visitorsList':
				include_once __DIR__ . '/visitors.php';
				$answer = visitors_visitorsList($data);
				break;
			/*case 'visitors_visitorsCross':
				include_once __DIR__ . '/visitors.php';
				$answer = visitors_visitorsCross($data);
				break;*/
			default:
				return $answer;
				break;
		}
		return $answer;
	}
}