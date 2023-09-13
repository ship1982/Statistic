<?php

/**
 * Находит все возможные варианты перечисления в массиве.
 * Например: [1,2,3] - 123,12,13,23
 * 
 * @param array $array - массив для нахождения возможныых вариантов
 * @return array
 */
function array_getAllVariants($array = [])
{
	if(empty($array[1])) return [0 => $array];
  $res = [];
  for ($i = 1; $i < pow(2, count($array)); ++$i)
  {
    $pre = [];
    for ($j = 0; $j < count($array); ++$j)
    {
      if ($i & pow(2, $j))
        $pre[] = $array[$j];
    }

    if(count($pre) > 1)
      $res[] = $pre;
  }

  return $res;
}

/**
 * Array intersect.
 *
 * @param $b - первый массив
 * @param $a - второй массив
 * @return array
 */
function array_arrayIntersect($b, $a)
{
  if(is_string($a)
    || empty($a)
    || empty($b)
  )
    return [];

  $d = [];
  if(count($a) > count($b))
  {
    foreach ($b as $i)
      if (isset($a[$i]))
        $d[$i] = $i;
  }
  else
  {
    foreach ($a as $i)
      if (isset($b[$i]))
        $d[$i] = $i;
  }

  return $d;
}

/**
 * Производит поиск данных в массиве по чате ключа.
 * 
 * @param type|string $part - часть ключа
 * @param type|array $data - массив данных
 * @return type
 */
function array_getDataByPartOfKey($part = '', $data = [])
{
  $res = [];
  $__data = [];
  if(empty($part)
    || empty($data)
  )
    return $res;

  foreach ($data as $key => $value)
  {
    if(strpos($key, 'old_') !== false)
      $__data[$key] = $value;
  }

  return $__data;
}

/**
 * Собирает массив из массива.
 * Пример:
 * array(
 * 0 => array(id => 1, name => 'name1'),
 * 1 => array(id => 2, name => 'name2')
 * )
 * если вызвать: array_mapping(array, 'id', ['name' => 1, 'id' => 1]);
 * то на выходе будет:
 * array(
 * 1 => array(name => name1, id => 1),
 * 2 => array(name => name2, id => 2)
 * )
 * 
 * @param type|array $array - взодной массив
 * @param type|string $key - ключ, который будет выступать ключом в новом массиве
 * @param type|mixed $kyes - ключи, которые будут включены в новый массив
 * @return type
 */
function array_mapping($array = [], $key = '', $kyes)
{
  $result = [];
  if(empty($array)
    || empty($key)
  )
    return $array;

  foreach ($array as $k => $v)
  {
    $temp = '';
    if(is_array($kyes))
      $temp = array_intersect_key($v, $kyes);
    else
      $temp = (empty($v[$kyes]) ? '' : $v[$kyes]);

    if(!empty($v[$key]))
      $result[$v[$key]] = $temp;
  }

  return $result;
}