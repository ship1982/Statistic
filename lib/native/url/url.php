<?php

/**
 * Получает ссылку без гет параметров.
 *
 * @return string
 */
function url_getURLWithoutGet()
{
  $url = strtok($_SERVER['REQUEST_URI'], '?');
  return $url;
}

/**
 * Плучает значение по из GET по ключу.
 *
 * @param string $key - искомый ключ
 *
 * @return string
 */
function url_getURLPartByGet($key = '')
{
  if (empty($_GET)
      || empty($key)
  )
  {
    return '';
  }

  return (empty($_GET[$key]) ? '' : $_GET[$key]);
}

/**
 * Получаем часть url по индексу в ссылке.
 * Например для адреса /main/setting/pixel/1/ если указать индекс 3, то будет возвражено 1, так как нумерация идет с 0.
 *
 * @param $index - индекс
 *
 * @return string
 */
function url_getPartOfURL($index = -1)
{
  if (-1 == $index)
  {
    return '';
  }
  $url = url_getURLWithoutGet();
  $arPart = explode("/", trim($url, '/'));
  return (empty($arPart[$index]) ? '' : $arPart[$index]);
}

/**
 * Убираем из url часть до индекса.
 *
 * @param $index - индекс
 *
 * @return string
 */
function url_getUrl4Index($index = -1)
{
  $res = '';
  $i = 0;
  if (-1 == $index)
  {
    return $res;
  }
  $url = url_getURLWithoutGet();
  $arPart = explode("/", trim($url, '/'));
  if (!empty($arPart)
      && is_array($arPart)
  )
  {
    while ($i < $index)
    {
      $res .= '/' . $arPart[$i];
      $i++;
    }
  }

  return $res . '/';
}