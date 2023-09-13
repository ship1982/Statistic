<?php

namespace SequencerAPI;

use api\HashAuth\HashAuth;
use model\Model;
use Rest\Rest;

/**
 * Class SequencerAPI
 * API для поиска данных на основе заявки по пользователям.
 */
class SequencerAPI extends Rest
{
  /**
   * @var array|Model
   */
  public $model = [];

  /**
   * @var - массив ошибок
   */
  protected $error;

  /**
   * @var mixed - массив переданных от третьего сервиса данных
   */
  protected $request;

  /**
   * @var - ответ от API
   */
  protected $answer = [];

  /**
   * SequencerAPI constructor.
   *
   * @param array $params
   */
  function __construct(array $params = [])
  {
    parent::__construct($params);
    $this->request = $this->getRequest();
    // получаем данные для шардинга
    if (!empty($this->request['from'])
        && !empty($this->request['to'])
    )
    {
      $shardingOptions = [
          'from' => strtotime($this->request['from']),
          'to' => strtotime($this->request['to'])
      ];
      $this->model = new Model($shardingOptions, 'l_sequence_4_user', 'time');
    }
    else
    {
      echo "{'error':'Не передан диаппазон выборки.'}";
      exit;
    }
  }

  /**
   * Формирование select
   */
  function getSelect()
  {
    $wasAggregation = false;
    if (!empty($this->request['aggregation']))
    {
      $wasAggregation = true;
      $this->model->getAggregation($this->request['aggregation']);
    }

    if (!empty($this->request['field']))
    {
      if ($wasAggregation)
      {
        $this->model->addSelect($this->request['field']);
      }
      else
      {
        $this->model->select($this->request['field']);
      }
    }
  }

  /**
   * Формирование фильтра
   */
  function getFilter()
  {
    $this->model->where();
    $this->timeWork();
    if (!empty($this->request['filter'])
        && is_array($this->request['filter'])
    )
    {
      $this->model->addAnd();
      for ($i = 0; $i < $ic = count($this->request['filter']); $i++)
      {
        if (!empty($this->request['filter'][$i]['field'])
            && !empty($this->request['filter'][$i]['operand'])
            && isset($this->request['filter'][$i]['value'])
        )
        {
          $this->model->addWhere(
              $this->request['filter'][$i]['operand'], [
              $this->request['filter'][$i]['field'] => common_setValue($this->request['filter'][$i], 'value')
          ]);

          if (!empty($this->request['filter'][$i]['link']))
          {
            switch ($this->request['filter'][$i]['link'])
            {
              case 'AND':
                $this->model->addAnd();
                break;
              case 'OR':
                $this->model->addOr();
                break;
            }
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
    if (!empty($this->request['group'])
        && is_array($this->request['group'])
    )
    {
      $this->model->group($this->request['group']);
    }
  }

  /**
   * Формирование сортировки
   */
  function getSort()
  {
    if (!empty($this->request['sort'])
        && is_array($this->request['sort'])
    )
    {
      $this->model->order($this->request['sort']);
    }
  }

  /**
   * Формирование лимитов
   */
  function getLimits()
  {
    if (!empty($this->request['offset']))
    {
      $this->model->limit(
          common_setValue($this->request['offset'], 'offset'),
          common_setValue($this->request['offset'], 'count')
      );
    }
    else
    {
      $this->model->limit(10000);
    }
  }

  /**
   * Формирование временного интервала
   */
  function timeWork()
  {
    if (!empty($this->request['from']))
    {
      $this->model->addWhere('>=', ['time' => strtotime($this->request['from'])]);
      $this->model->addAnd();
    }

    if (!empty($this->request['to']))
    {
      $this->model->addWhere('<=', ['time' => strtotime($this->request['to'])]);
    }
  }

  /**
   * Добавляет к запросу where оператор связи.
   *
   * @param string $operator
   */
  function checkLinkOperator($operator = 'AND')
  {
    switch ($operator)
    {
      case 'AND':
        $this->model->addAnd();
        break;
      case 'OR':
        $this->model->addOr();
        break;
    }
  }

  /**
   * Формирование фильтра дополнительных параметров.
   */
  function getAdditionalFilter()
  {
    if (!empty($this->request['filter'])
        && is_array($this->request['filter'])
    )
    {
      for ($i = 0; $i < $ic = count($this->request['filter']); $i++)
      {
        if (!empty($this->request['filter'][$i]['field'])
            && !empty($this->request['filter'][$i]['operand'])
            && isset($this->request['filter'][$i]['value'])
        )
        {
          $this->model->addAnd();
          $this->model->addWhere(
              $this->request['filter'][$i]['operand'], [
              $this->request['filter'][$i]['field'] => common_setValue($this->request['filter'][$i], 'value')
          ]);
        }
        // оператор связи
        if (!empty($this->request['filter'][$i]['link']))
        {
          $this->checkLinkOperator($this->request['filter'][$i]['link']);
        }
      }
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
      $this->model->query("SELECT `seance`, `uuid` FROM ".$this->model->getTable()." WHERE `time` BETWEEN {{from}} AND {{to}} AND (`link_text` REGEXP '.*SUCCESS=Y.*' OR `link_text` REGEXP '.*success_FID1=yes.*') LIMIT 1", [
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
   * Получение данных по сеансу.
   *
   * @param array $data
   */
  function getSeanceData($data = [])
  {
    // если получили seance
    if (!empty($data['seance'])
        && !empty($data['uuid'])
    )
    {
      // получаем значения, что переданы в фильтре
      if (!empty($this->request['field']))
      {
        $this->model->select($this->request['field']);
        $this->model->from();
        $this->model->where(['seance' => $data['seance']]);
        $this->getAdditionalFilter();
        $this->model->execute();
        $answer = $this->model->fetch();
        if (!empty($answer)
            && is_array($answer)
        )
        {
          for ($i = 0; $i < $ic = count($answer); $i++)
          {
            $this->answer['order'][] = $answer[$i];
          }
        }
      }
    }
  }

  /**
   * Получение данных по пользователю за промежуток времени.
   *
   * @param array $data
   */
  function getHistoryData($data = [])
  {
    if (!empty($data['uuid']))
    {
      $this->model->select($this->request['field']);
      $this->model->from();
      $this->model->where(['uuid' => $data['uuid']]);
      $this->getAdditionalFilter();
      $this->model->addAnd();
      $this->model->addWhere('<=', ['time' => strtotime($this->request['to'])]);
      $this->model->addAnd();
      $this->model->addWhere('>=', ['time' => strtotime($this->request['from'])]);
      $this->model->execute();
      $answer = $this->model->fetch();
      if (!empty($answer[0]))
      {
        for ($i = 0; $i < $ic = count($answer); $i++)
        {
          $this->answer['history'][] = $answer[$i];
        }
      }
    }
  }

  /**
   * Функция для поиска данных по одной заявки.
   *
   * @return mixed
   */
  function searchOrders()
  {
    if (!empty($this->request['additional']['ids']))
    {
      for ($i = 0; $i < $ic = count($this->request['additional']['ids']); $i++)
      {
        $data = $this->findOrder($this->request['additional']['ids'][$i]);
        $this->getSeanceData($data);
        $this->getHistoryData($data);
      }
    }

    return $this->answer;
  }

  /**
   * Основная функция обработчик.
   *
   * @return mixed
   */
  function mainProcess()
  {
    return $this->searchOrders();
  }

  /**
   * Обычный запрос к таблице секвенсор.
   *
   * @return array
   */
  function getDefault()
  {
    $this->getSelect();
    $this->model->from();
    $this->getFilter();
    $this->getSort();
    $this->getGroup();
    $this->getLimits();
    $this->model->execute();
    $data = $this->model->fetch();
    return $data;
  }

  /**
   * Получение данных сущности
   */
  function get()
  {
    $answer = [];
    if (HashAuth::isEqualHash($this->request))
    {
      // проверяем действие
      if (!empty($this->request['additional']['act']))
      {
        switch ($this->request['additional']['act'])
        {
          case 'getHistoryByOrder':
            $answer = $this->mainProcess();
            break;
          case 'getDefault':
            $answer = $this->getDefault();
            break;
        }
      }
      else
      {
        $this->error[] = "Не передано действие для API.";
      }

      if (!empty($this->error))
      {
        $answer['error'] = $this->error;
      }

      return $answer;
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