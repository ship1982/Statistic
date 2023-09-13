<?php

namespace native\request;

/**
 * Класс для работы с переменными SERVER, GET, POST, $_SESSION, $_COOKIE
 * @var $actual - содержит последний выбранный массив
 */
class Request
{
  /**
   * @var array - последний полученный массив
   */
  protected $actual = [];

  /**
   * Получаем массив $_SERVER.
   *
   * @return array
   */
  function getServer()
  {
    $this->actual = $_SERVER;
    return $this->actual;
  }

  /**
   * Получаем массив $_POST.
   *
   * @return array
   */
  function getPost()
  {
    $this->actual = $_POST;
    return $this->actual;
  }

  /**
   * Получаем массив $_GET.
   *
   * @return array
   */
  public function getGet()
  {
    $this->actual = $_GET;
    return $this->actual;
  }

  /**
   * Получаем массив $_SESSION.
   *
   * @return array
   */
  function getSession()
  {
    $this->actual = $_SESSION;
    return $this->actual;
  }

  /**
   * Получаем массив $_COOKIE.
   *
   * @return array
   */
  function getCookie()
  {
    $this->actual = $_COOKIE;
    return $this->actual;
  }

  /**
   * Получаем значение из массива.
   *
   * @param string $name - ключ, по  которому искать
   *
   * @return array
   */
  function get($name = '')
  {
    if (!empty($this->actual))
    {
      if (!empty($this->actual[$name]))
      {
        return $this->actual[$name];
      }
      else
      {
        return [];
      }
    }

    return [];
  }

  /**
   * Провереям, что пришел AJAX.
   *
   * @return bool
   */
  function isAjax()
  {
    $this->getServer();
    $header = $this->get('HTTP_X_REQUESTED_WITH');
    if (isset($header)
        && $header === 'XMLHttpRequest'
    )
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}