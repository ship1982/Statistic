<?php

namespace BotAPI;

use api\HashAuth\HashAuth;
use model\Model;
use Rest\Rest;

/**
 * Class BotAPI
 * API для получения данных о том, о том, является ли пользователь ботом или нет.
 *
 * @package BotAPI
 */
class BotAPI extends Rest
{
  /**
   * @var array - данные запроса
   */
  protected $body = [];

  /**
   * @var Model
   */
  public $model;

  /**
   * @var Model
   */
  public $seance;

  /**
   * BotAPI constructor.
   *
   * @param array $params
   */
  function __construct(array $params = [])
  {
    parent::__construct($params);
    $this->seance = new Model([1], 'seance_user', false);
    $this->model = new Model([1], 'user_property', false);
  }

  /**
   * Возврашает seance.
   *
   * @return string
   */
  function getSeance()
  {
    if (!empty($this->body['filter']))
    {
      foreach ($this->body['filter'] as $index => $filter)
      {
        if (!empty($filter['filed'])
        && 'seance' === $filter['filed']
        && !empty($filter['value'])
        )
        {
          return $filter['value'];
        }
      }
    }

    return '';
  }

  /**
   * Получение данных сущности
   */
  function get()
  {
    $data = [];
    $this->body = $this->getRequest();
    if (HashAuth::isEqualHash($this->body))
    {
      // поиск в массиве для фильтрации seance
      // делам запрос в таблицу dirty
      $seance = $this->getSeance();
      if (!empty($seance))
      {
        $arUuid = $this->seance->_list(['uuid'],['seance' => $seance]);
        if (!empty($arUuid[0])
            && !empty($arUuid[0]['uuid'])
        )
        {
          $this->getSelect();
          $this->model->from();
          $this->model->where([
              ['uuid','=',$arUuid[0]['uuid']]
          ]);
          $this->model->execute();
          $data = $this->model->fetch();
        }
      }
    }
    else
    {
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }

    return $data;
  }

  /**
   * Добавление данных сущности
   */
  function post()
  {
    // TODO: Implement post() method.
  }

  /**
   * Удаление данных сущности
   */
  function del()
  {
    // TODO: Implement del() method.
  }

  /**
   * Обновление данных сущности
   */
  function put()
  {
    // TODO: Implement put() method.
  }
}