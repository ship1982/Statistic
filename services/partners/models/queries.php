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
if(!function_exists('partnerRun'))
{
	function partnerRun($data = [])
	{
		$type = getDataFromService($data, 'action');
		$answer = "[]";
		switch ($type)
		{
			case 'partnerAdd':
				include_once(__DIR__ . '/partners.php');
				$answer = partnersAddAction($data);
				break;
			case 'partnerDelete':
				include_once(__DIR__ . '/partners.php');
				$answer = partnerDeleteAction($data);
				break;
			case 'partnersList':
				include_once(__DIR__ . '/partners.php');
				$answer = partnersShowAction($data);
				break;
			default:
				return $answer;
				break;
		}
		return $answer;
	}
}