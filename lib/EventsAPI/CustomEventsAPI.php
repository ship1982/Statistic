<?php

namespace EventsAPI;

use GoogleChannels\GoogleChannels;
use model\Model;
use Rest\Rest;

class CustomEventsAPI extends Rest
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
   * Обертка для разбора условия where.
   *
   * @param array $data
   */
  function wrapperCreateRecursiveFilterData($data = [])
  {
    if (!empty($data)) $this->model->sql .= '(';
    $this->createRecursiveFilterData($data);
    $this->model->trimWhere();
  }

  /**
   * Установка оператора.
   *
   * @param string $operator
   */
  function addOperator($operator = 'AND')
  {
    switch ($operator)
    {
      case 'AND':
        $this->model->addAnd();
        break;
      case 'OR':
        $this->model->addOr();
        break;
      default:
        $this->model->addAnd();
    }
  }

  /**
   * Рекурсивный разбор условия where.
   *
   * @param array $data
   */
  function createRecursiveFilterData($data = [])
  {
    // если нет поля, значит мы запрашиваем группу.
    $operator = 'AND';
    foreach ($data as $key => $filter)
    {
      if (is_array($filter)
          && empty($filter['field']))
      {
        //echo "массив <br>";
        $this->model->addLeftBracket();
        $this->createRecursiveFilterData($filter);
      }
      else if (isset($filter['field'])
          && isset($filter['operator'])
      )
      {
        $value = (@!isset($filter['value']) ? NULL : $filter['value']);
        //echo "условие <br>";
        $this->model->addWhere($filter['operator'], [$filter['field'] => $value]);
        if (!empty($filter['logic']))
        {
          $this->addOperator($filter['logic']);
        }
      }
      else
      {
        //echo "оператор <br>";
        $operator = $filter;
      }
    }
    // echo "ушли <br>";
    $this->model->trimWhere();
    $this->model->addRightBracket();
    if (!empty($operator))
    {
      $this->addOperator($operator);
    }
  }

  /**
   * Формирование фильтра
   */
  function getFilter()
  {
    if (isset($this->body['filter'])
        && !empty($this->body['filter'])
        && is_array($this->body['filter'])
    )
    {
      $this->model->where();
      $this->wrapperCreateRecursiveFilterData($this->body['filter']);

      /**
       * Фильтр по каналу, если он передан
       */
      if (!empty($this->body['channel']))
      {
        $googleChannel = new GoogleChannels();
        $sql = $googleChannel->getFilterByGroup($this->body['channel']);
        if (!empty($sql)
            && $sql != '()'
        )
        {
          $this->model->query(' AND ' . $sql, [], false);
        }
      }
    }
  }

  /**
   * Формирвоание группировки
   */
  function getGroup()
  {
    if (!empty($this->body['group']) && is_array($this->body['group']))
    {
      $this->model->group($this->body['group']);
      $this->getHaving();
    }
  }

  /**
   * Формирование сортировки
   */
  function getSort()
  {
    if (!empty($this->body['sort']) && is_array($this->body['sort']))
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
      $this->model->limit(intval($this->body['offset']['offset'] ?? 0), intval($this->body['offset']['count'] ?? 10000));
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
    // echo $this->model->sql; die;
  }

  /**
   * Получение данных сущности
   */
  function get()
  {
    // TODO: Implement get() method.
    $this->body = $this->getRequest();
    $this->prepareGet();
    $this->model->execute();
    $data = $this->model->fetch();
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