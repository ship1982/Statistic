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
if(!function_exists('userlistRun'))
{
	function userlistRun($data = [])
	{
		$type = getDataFromService($data, 'action');
		$answer = "[]";
		switch ($type)
		{
			case 'userlist_getList':
				include_once __DIR__ . '/userlist.php';
				$answer = userlist_userlistList($data);
				break;
			case 'userlist_addList':
				include_once __DIR__ . '/userlist.php';
				$answer = userlist_addList($data);
				break;
			default:
				return $answer;
				break;
		}
		return $answer;
	}
}