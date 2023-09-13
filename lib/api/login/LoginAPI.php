<?php

namespace api\login;

use Rest\Rest;
use UserLogin\UserLogin;

class LoginAPI extends Rest
{
  /**
   * @var UserLogin
   */
  public $model;

  /**
   * LoginAPI constructor.
   *
   * @param array $params
   */
  function __construct(array $params = [])
  {
    parent::__construct($params);
    $this->model = new UserLogin();
  }

  /**
   * Получение записей.
   *
   * @return array
   */
  function get()
  {
    $this->body = $this->getRequest();
    $this->prepareGet();
    $this->model->execute();
    $data = $this->model->fetch();
    return $data;
  }

  /**
   * Удаление записей.
   *
   * @return array
   */
  function del()
  {
    // TODO: Implement del() method.
  }

  /**
   * Обновление записей.
   *
   * @return array
   */
  function put()
  {
    // TODO: Implement put() method.
  }

  /**
   * Вставка записи.
   *
   * @return array
   */
  function post()
  {
    // TODO: Implement post() method.
  }
}