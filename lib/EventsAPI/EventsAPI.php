<?php

namespace EventsAPI;

use api\HashAuth\HashAuth;
use model\Model;
use Rest\Rest;

class EventsAPI extends Rest
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
   * EventsAPI constructor.
   *
   * @param array $params
   */
  function __construct(array $params = [])
  {
    parent::__construct($params);
    $this->model = new Model([1], 'event_list', false);
  }

  /**
   * Формирование select
   */
  function getSelect()
  {
    $wasAggregation = false;
    if (!empty($this->body['aggregation']))
    {
      $wasAggregation = true;
      $this->model->getAggregation($this->body['aggregation']);
    }

    if (!empty($this->body['field']))
    {
      if ($wasAggregation)
      {
        $this->model->addSelect($this->body['field']);
      }
      else
      {
        $this->model->select($this->body['field']);
      }
    }
  }

  /**
   * Формирование временного интервала
   */
  function timeWork()
  {
    if(!empty($this->body['from']))
    {
      $this->model->addWhere('>=', ['time' => strtotime($this->body['from'])]);
      $this->model->addAnd();
    }

    if(!empty($this->body['to']))
    {
      $this->model->addWhere('<=', ['time' => strtotime($this->body['to'])]);
    }
  }

  /**
   * Формирование фильтра
   */
  function getFilter()
  {
    $this->model->where();
    $this->timeWork();
    if (!empty($this->body['filter'])
        && is_array($this->body['filter'])
    )
    {    
      for ($i = 0; $i < $ic = count($this->body['filter']); $i++)
      {
        // dd($this->body['filter'][$i]);
        if (!empty($this->body['filter'][$i]['filed'])
            && !empty($this->body['filter'][$i]['operand'])
            && isset($this->body['filter'][$i]['value'])
        )
        {
          $this->model->addAnd();
          $this->model->addWhere(
              $this->body['filter'][$i]['operand'], [
              $this->body['filter'][$i]['filed'] => $this->body['filter'][$i]['value']
          ]);
        }
      }
    }
  }

  /**
   * Формирвоание группировки
   */
  function getGroup()
  {
    if (!empty($this->body['group'])
        && is_array($this->body['group'])
    )
    {
      $this->model->group($this->body['group']);
    }
  }

  /**
   * Формирование сортировки
   */
  function getSort()
  {
    if (!empty($this->body['sort'])
        && is_array($this->body['sort'])
    )
    {
      $this->model->order($this->body['sort']);
    }
  }

  /**
   * Формирование лимитов
   */
  function getLimits()
  {
    if (!empty($this->body['offset']))
    {
      $this->model->limit(
          common_setValue($this->body['offset'], 'offset'),
          common_setValue($this->body['offset'], 'count')
      );
    }
    else
    {
      $this->model->limit(10000);
    }
  }

  /**
   * Формирование запроса на select данных
   */
  function prepareGet()
  {
    $this->getSelect();
    $this->model->from();
    $this->getFilter();
    $this->getGroup();
    $this->getSort();
    $this->getLimits();
  }

  /**
   * Получение данных сущности
   */
  function get()
  {
    $this->body = $this->getRequest();
    if (HashAuth::isEqualHash($this->body))
    {
      $this->prepareGet();
      $this->model->execute();
      $data = $this->model->fetch();
      return $data;
    }
    else
    {
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }
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