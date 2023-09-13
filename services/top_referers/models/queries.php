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
if(!function_exists('topReferersRun'))
{
  function topReferersRun($data = [])
  {
    $type = getDataFromService($data, 'action');
    $answer = "[]";
    switch ($type)
    {
      case 'topReferers_count':
        include_once __DIR__ . '/top_referers.php';
        $answer = topReferers_count($data);
        break;
      case 'topReferers_cross':
        include_once __DIR__ . '/top_referers.php';
        $answer = topReferers_cross($data);
        break;
      default:
        return $answer;
        break;
    }
    return $answer;
  }
}