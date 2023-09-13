<?php

namespace CampaignsAPI;

use api\HashAuth\HashAuth;
use model\Model;
use Rest\Rest;

class CampaignsAPI extends Rest
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
   * ошибки в процессе обработки параметров из URL
   *
   * @var array
   */
  public $errors = [];

  /**
   * массив допустимых utm-меток
   *
   * @var array
   */
  private $utmLabels = [
    'utm_campaign',
    'utm_content',
    'utm_term',
    'utm_medium',
    'utm_source'
  ];

  /**
   * массив событий
   *
   * @var array
   */
  private $targetEvents = [];

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
   * разбор UTM-меток для sql-запроса
   *
   * @return string
   */
  private function parseUtmLabels()
  {
    $queryPart = '';

    for ($i = 0; $i < count($this->utmLabels); $i++)
    {
      $label = $this->utmLabels[$i];
      if (!empty($this->body[$label]))
      {
        $queryPart .= "`{$label}`='{$this->body[$label]}' AND ";
      }
    }

    if (empty($queryPart))
    {
      $this->errors[] = 'Не указаны UTM-метки рекламной кампании';
    }

    return $queryPart;
  }

  /**
   * разбор URL целевых страниц для sql-запроса
   *
   * @return string
   */
  private function parseTargets()
  {
    $queryPart = '';

    if (!empty($this->body['targets']))
    {
      $targets = explode(',', $this->body['targets']);
      for ($i = 0; $i < count($targets); $i++)
      {
        $urlArray = parse_url($targets[$i]);
        if ($urlArray === false)
        {
          continue;
        }

        $domain = $urlArray['host'];
        $link = trim($urlArray['path'], '/');
        $queryPart .= "`domain`='{$domain}' AND `link`='{$link}' OR ";
      }
    }

    if (empty($queryPart))
    {
      $this->errors[] = 'Не указаны цели рекламной кампании';
    }
    else
    {
      $queryPart = "(" . substr($queryPart, 0, -4) . ") AND ";
    }

    return $queryPart;
  }

  /**
   * разбор JSON-строки с событиями (целевые действия/целевые заявки/нецелевые заявки) для sql-запроса
   * формат {{'name':'наименование(ключ) события', 'event_category':'', 'event_type':'', 'event_label':'', 'event_value':''}, ...}
   *
   * @return string
   */
  private function parseEvents()
  {
    $queryPart = '';

    if (!empty($this->body['events']))
    {
      $eventsArray = json_decode($this->body['events'], true);
      if (is_array($eventsArray))
      {
        for ($i = 0; $i < count($eventsArray); $i++)
        {
          $event = $eventsArray[$i];

          //заполнение $this->targetEvents значениями для форматирования результатов запроса
          $name = $event['name'];
          $sign = md5($event['event_category'].$event['event_type'].$event['event_label'].$event['event_value']);
          $this->targetEvents[$sign] = $name;

          //формирование запроса
          $queryPart .= "`event_category`='{$event['event_category']}' AND `event_type`='{$event['event_type']}' AND `event_label`='{$event['event_label']}' AND `event_value`='{$event['event_value']}' OR ";
        }
      }
      else
      {
        $this->errors[] = 'Ошибочный формат данных для событий рекламной кампании';
      }
    }

    if (empty($queryPart))
    {
      $this->errors[] = 'Не указаны целевые события рекламной кампании';
    }
    else
    {
      $queryPart = "(" . substr($queryPart, 0, -4) . ") AND ";
    }

    return $queryPart;
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
      $whereString = '';
      //добавление в запрос UTM-меток
      $whereString .= $this->parseUtmLabels();
      //добавление в запрос URL целевых страниц
      $whereString .= $this->parseTargets();
      //добавление в запрос целевых событий
      $whereString .= $this->parseEvents();
      //добавление в запрос целевых событий

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