<?php

/**
 * Вовзращает массив значений из POST/GET/REQUEST запроса
 * @param array $keys - полученные параметры из запроса
 * @param string $type [POST|GET]
 * @return array
 */
function common_api_getInput($keys = [], $type = 'POST')
{
	switch ($type)
	{
		case 'POST': $t = $_POST; break;
		case 'GET': $t = $_GET; break;
		default: $t = $_REQUEST; break;
	}
	$data = [];
	if(empty($t)
		|| empty($keys)
	) return $data;
	
	if(is_array($keys))
	{
		for ($i=0; $i < $ic = count($keys); $i++)
		{ 
			if(!empty($t[$keys[$i]]))
				$data[$keys[$i]] = $t[$keys[$i]];
		}
	}

	return $data;
}