<?php

namespace HistoryReferrerByOrder;

use api\HashAuth\HashAuth;
use model\Model;
use Rest\Rest;

include_once __DIR__ . '/../autoload.php';

class HistoryReferrerByOrder extends Rest
{
  /**
   * Массив для хранения ошибок.
   *
   * @var array
   */
  public $error = [];

  /**
   * @var array|Model
   */
  public $model = [];

  /**
   * @var mixed - массив переданных от третьего сервиса данных
   */
  protected $request;

  /**
   * SendAPI constructor.
   *
   * @param array $params
   */
  function __construct(array $params = [])
  {
    parent::__construct($params);
    $this->request = $this->getRequest();
    // получаем данные для шардинга
    if (!empty($this->request['time']))
    {
      $shardingOptions = [
          'from' => strtotime($this->request['time']) + 60,
          'to' => strtotime($this->request['time']) - 60
      ];

      $this->model = new Model($shardingOptions, 'l_sequence_4_user', 'time');
    }
    else
    {
      header("HTTP/1.1 400 Bad Request");
      exit;
    }
  }

  /**
   * Поиск заявки по времени.
   *
   * @param string $time
   *
   * @return array
   */
  function findOrder($time = '')
  {
    if (!empty($time))
    {
      // получаем seance по заявке
      $this->model->query("SELECT 
`enter_utm_campaign`,
`enter_utm_term`,
`enter_utm_content`,
`enter_utm_source`,
`enter_utm_medium`,
`enter_referer_domain`,
`enter_referer_link`
FROM " . $this->model->getTable() . "
WHERE (`time` BETWEEN {{from}} AND {{to}})
  AND (`link_text` REGEXP '.*SUCCESS=Y.*' OR `link_text` REGEXP '.*success_FID1=yes.*')
LIMIT 1", [
          'from' => strtotime($time) - 30,
          'to' => strtotime($time) + 40
      ]);
      $data = $this->model->fetch();
      if (!empty($data[0]))
      {
        return $data[0];
      }
    }

    return [];
  }

  /**
   * Получение данных сущности
   */
  function get()
  {
    if (HashAuth::isEqualHash($this->request))
    {
      // проверка параметров запроса
      if (empty($this->request['time']))
      {
        header("HTTP/1.1 400 Bad Request");
        exit;
      }
      else
      {
        // поиск заявки и сеанса для нее
        $answer = $this->findOrder($this->request['time']);
        if (empty($answer))
        {
          $this->error[] = 'Не удалось найти заявку.';
        }

      }

      return [
          'data' => $answer,
          'error' => $this->error
      ];
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