<?php

namespace native\Strings;


class StringHelper
{
  /**
   * Вычленение из строки GET запроса нужной части.
   *
   * @param string $name
   * @param string $link
   *
   * @return string
   */
  static function getGETFromLink($name = '', $link = '')
  {
    if (empty($name)
        || empty($link)
    )
    {
      return '';
    }

    preg_match('/.*\?(.*)/', $link, $matches);
    if (!empty($matches[1]))
    {
      $arPart = explode('&', $matches[1]);
      if (is_array($arPart))
      {
        foreach ($arPart as $key => $value)
        {
          $params = explode('=', $value);
          if ($params[0] == $name)
          {
            return $params[1];
          }
        }
      }

    }

    return '';
  }
}