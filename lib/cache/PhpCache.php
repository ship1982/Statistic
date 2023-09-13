<?php

namespace cache;

/**
 * Class PhpCache
 * Класс для кэширования в php.
 * @package cache
 */
class PhpCache
{
  /**
   * @var array - переменная кэша
   */
  static protected $cache = [];

  /**
   * Установка кэша.
   *
   * @param string $key
   * @param string $value
   *
   * @return bool
   */
  static function set($key = '', $value = '')
  {
    if (empty($key))
    {
      return false;
    }
    self::$cache[$key] = $value;
    return true;
  }

  /**
   * Получение кэша.
   *
   * @param string $key
   *
   * @return bool|mixed
   */
  static function get($key = '')
  {
    return (empty(self::$cache[$key]) ? false : self::$cache[$key]);
  }

  /**
   * Удаление данных из кэша.
   *
   * @param string $key
   *
   * @return bool
   */
  static function delete($key = '')
  {
    return (!empty(self::$cache[$key]) ? self::$cache[$key] = false : false);
  }
}