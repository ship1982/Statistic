<?php

namespace native\arrays;

class ArrayHelper
{
  /**
   * Находит все возможные варианты перечисления в массиве.
   * Например: [1,2,3] - 123,12,13,23
   *
   * @param array $array - массив для нахождения возможныых вариантов
   *
   * @return array
   */
  static function getAllVariants($array = [])
  {
    if (empty($array[1]))
    {
      return [0 => $array];
    }
    $res = [];
    for ($i = 1; $i < pow(2, count($array)); ++$i)
    {
      $pre = [];
      for ($j = 0; $j < count($array); ++$j)
      {
        if ($i & pow(2, $j))
        {
          $pre[] = $array[$j];
        }
      }

      if (count($pre) > 1)
      {
        $res[] = $pre;
      }
    }

    return $res;
  }

  /**
   * Находит все возможные варианты перечисления в массиве, с помощью функции getAllVariants,
   * и возвращает массив, в виде: [0 => '1 & 2', 1 => '2 & 3', 2 => '1 & 3', 4 => '1 & 2 & 3']
   *
   * @param array $array
   *
   * @return array
   */
  static function getAllVariantsAsKeys($array = [])
  {
    $variants = self::getAllVariants($array);

    $result = [];
    for ($i = 0; $i < count($variants); $i++)
    {
      $variant = implode('&', $variants[$i]);
      if (!empty($variant))
      {
        $result[] = $variant;
      }
    }
    return $result;
  }

  /**
   * Array intersect.
   *
   * @param $b - первый массив
   * @param $a - второй массив
   *
   * @return array
   */
  static function arrayIntersect($b, $a)
  {
    if (is_string($a)
        || empty($a)
        || empty($b)
    )
    {
      return [];
    }

    $d = [];
    if (count($a) > count($b))
    {
      foreach ($b as $i)
        if (isset($a[$i]))
        {
          $d[$i] = $i;
        }
    }
    else
    {
      foreach ($a as $i)
        if (isset($b[$i]))
        {
          $d[$i] = $i;
        }
    }

    return $d;
  }

  /**
   * Собирает массив из массива.
   * Пример:
   * array(
   * 0 => array(id => 1, name => 'name1'),
   * 1 => array(id => 2, name => 'name2')
   * )
   * если вызвать: ArrayHelper::map(array, 'id', ['name' => 1, 'id' => 1]);
   * то на выходе будет:
   * array(
   * 1 => array(name => name1, id => 1),
   * 2 => array(name => name2, id => 2)
   * )
   *
   * @param array  $array - взодной массив
   * @param string $key   - ключ, который будет выступать ключом в новом массиве
   * @param mixed  $keys  - ключи, которые будут включены в новый массив
   *
   * @return array
   */
  static function map($array = [], $key = '', $keys)
  {
    $result = [];
    if (empty($array))
    {
      return $array;
    }

    foreach ($array as $k => $v)
    {
      if (is_array($keys))
      {
        $temp = array_intersect_key($v, $keys);
      }
      else
      {
        $temp = (empty($v[$keys]) ? '' : $v[$keys]);
      }

      // обработка без ключа
      if (empty($key))
      {
        $result[] = $temp;
      }
      else
      {
        if (!empty($v[$key]))
        {
          $result[$v[$key]] = $temp;
        }
      }
    }

    return $result;
  }

  /**
   * Возвращает процентное значение одногочисла от другого,
   * пример:всего 100 спичек, мы взяли 10 => взяли 10%
   *
   * @param $count - Число, из которого, расчитывается процент
   * @param $value - Число, по которому расчитываем процент
   *
   * @return float|int
   */
  static function percent_from_number($count, $value)
  {
    if ($count <= 0)
    {
      return 0;
    }
    if ($value <= 0)
    {
      return 0;
    }
    return round((float)$value / (float)$count * 100, 2);
  }

  /**
   * Получение из массива необходимых ключей.
   *
   * @param array $array
   * @param array $keys
   *
   * @return array
   */
  static function getKeysFromArray($array = [], $keys = [])
  {
    if (empty($array)
        || empty($keys)
    )
    {
      return [];
    }

    $data = [];
    foreach ($array as $key => $value)
    {
      if (isset($keys[$key]))
      {
        $data[$key] = $value;
      }
    }

    return $data;
  }
}