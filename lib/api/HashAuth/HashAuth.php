<?php

namespace api\HashAuth;

/**
 * Class HashAuth
 * Класс для реализации метода авторизации по хэшу.
 * Алгоритм работы:
 * 1 - получаем данные, что переданы через API
 * 2 - хэшируем эти данные и добавляем к ним строку секрета (в конфиге лежит)
 * 3 - если хэши совпали, то данные валидны
 *
 * @package api\HashAuth
 */
class HashAuth
{
  /**
   * Получаем секрет из конфига.
   *
   * @return mixed
   */
  static function getSecret()
  {
    return require __DIR__ . '/../../../config/api/authHash.php';
  }

  /**
   * Хэширующая функция.
   *
   * @param $item
   * @param $key
   *
   * @return string
   */
  static function hash($item, $key)
  {
    if (!empty($key)
        && !empty($item)
    )
    {
      return md5($key . $item);
    }
    return '';
  }

  /**
   * Механизм создания хэширующей строки для стравнения.
   *
   * @param array $data
   *
   * @return string
   */
  static function setHashByData($data = [])
  {
    if (!empty($data))
    {
      return sha1(array_walk_recursive($data, ['\api\HashAuth\HashAuth','hash']). self::getSecret());
    }

    return '';
  }

  /**
   * Проверка валидности хэша.
   *
   * @param array $data
   *
   * @return bool
   */
  static function isEqualHash($data = [])
  {
    if (!empty($data['hash']))
    {
      $receiveHash = $data['hash'];
      unset($data['hash']);
      $hash = self::setHashByData($data);
      if ($hash === $receiveHash)
      {
        return true;
      }
    }

    return false;
  }
}