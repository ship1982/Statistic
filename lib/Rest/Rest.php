<?php

namespace Rest;

/**
 * Class Rest
 * Класс для работы с методами API
 */
abstract class Rest
{
  /**
   * @var array|\GuzzleHttp\Client
   */
  public $client = [];

  /**
   * Массив заголовков.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * Тело запроса.
   *
   * @var array
   */
  protected $body = [];

  /**
   * @var - модель для запроса
   */
  public $model;

  /**
   * Содержит метод запроса. (PUT, GET, POST, DELETE)
   *
   * @return string
   */
  function getMethod()
  {
    return $_SERVER['REQUEST_METHOD'];
  }

  /**
   * Метод для определения метоа запроса.
   *
   * @return array
   */
  function chooseMethod()
  {
    $method = $this->getMethod();
    switch ($method)
    {
      case 'DELETE':
        return $this->del();
      case 'GET':
        return $this->get();
      case 'POST':
        return $this->post();
      case 'PUT':
        return $this->put();
    }

    return [];
  }

  /**
   * Получает тело запроса.
   *
   * @return mixed
   */
  function getBody()
  {
    $this->body = file_get_contents('php://input');
    return $this->body;
  }

  /**
   * Получить тело запроса, если оно передано в json.
   *
   * @return mixed|string
   */
  function fromJSON()
  {
    $answer = $this->getBody();
    if (is_string($answer))
    {
      return json_decode($answer, true);
    }

    return '';
  }

  /**
   * Получает ссылку запроса.
   *
   * @return string
   */
  function getUri()
  {
    return common_get_url_host() . $_SERVER['REQUEST_URI'];
  }

  /**
   * Получает заголовки запроса.
   *
   * @return array
   */
  function getHeaders()
  {
    $curl = curl_init();
    curl_setopt_array($curl, array(
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL => $this->getUri()
        )
    );
    $arHeaders = explode("\n", curl_exec($curl));
    if (!empty($arHeaders))
    {
      for ($i = 0; $i < $ic = count($arHeaders); $i++)
      {
        $arPart = explode(":", $arHeaders[$i]);
        if (is_array($arPart))
        {
          // парсим http
          if ($i == 0)
          {
            $arHeadStatus = explode(' ', $arPart[0]);
            if (!empty($arHeadStatus))
            {
              $this->headers['http'] = $arHeadStatus[0];
              $this->headers['status'] = $arHeadStatus[1];
              $this->headers['answer'] = trim($arHeadStatus[2], "\r");
            }
          }
          else
          {
            if (!empty($arPart[0])
                && isset($arPart[1])
            )
            {
              $this->headers[trim($arPart[0], "\r")] = str_replace([
                  "\r",
                  " "
              ], [], $arPart[1]);
            }
          }
        }
      }
    }
    return $this->headers;
  }

  /**
   * Получает заголовок по ключу.
   *
   * @param string $key
   *
   * @return mixed|string
   */
  function getHeader($key = '')
  {
    if (!empty($key))
    {
      if (empty($this->headers))
      {
        $this->getHeaders();
      }

      return (empty($this->headers[$key]) ? '' : $this->headers[$key]);
    }

    return '';
  }

  /**
   * Получает последнюю часть url.
   * Там может быть как id так и любой другой параметр.
   *
   * @return mixed
   */
  function getId()
  {
    $uri = $this->getUri();
    $arPath = explode('/', trim($uri, '/'));
    return end($arPath);
  }

  /**
   * Мастер функция.
   *
   * @return array
   */
  function execute()
  {
    return $this->chooseMethod();
  }

  /**
   * Преобразует массив в JSON.
   *
   * @param array $params
   *
   * @return string
   */
  function toJSON($params = [])
  {
    header('Content-Type: application/json');
    if (is_array($params))
    {
      return json_encode($params, true);
    }

    return '';
  }

  /**
   * Получаем массив GET.
   *
   * @return mixed
   */
  function getRequest()
  {
    return (empty($_GET) ? [] : $_GET);
  }

  /**
   * Инициализация объекта $this->client.
   *
   * @param array $params
   */
  function init($params = [])
  {
    $this->client = new \GuzzleHttp\Client($params);
  }

  function __construct($params = [])
  {
    $this->client = new \GuzzleHttp\Client($params);
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
        if (!empty($this->body['filter'][$i]['filed'])
            && !empty($this->body['filter'][$i]['operand'])
            && isset($this->body['filter'][$i]['value'])
        )
        {
          $this->model->addWhere(
              $this->body['filter'][$i]['operand'], [
              $this->body['filter'][$i]['filed'] => $this->body['filter'][$i]['value']
          ]);
          if (!empty($this->body['filter'][$i+1]))
          {
            $this->model->addAnd();
          }
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

    function getHaving() {
        if (!empty($this->body['having']) && is_array($this->body['having'])) {
            $this->model->having($this->body['having']);
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
   * Получение записей.
   *
   * @return array
   */
  abstract function get();

  /**
   * Удаление записей.
   *
   * @return array
   */
  abstract function del();

  /**
   * Обновление записей.
   *
   * @return array
   */
  abstract function put();

  /**
   * Вставка записи.
   *
   * @return array
   */
  abstract function post();
}