<?php

namespace model;

use native\request\Request;
use queryBuilder\mysql\QueryBuilder;
use validation\Validation;

/**
 * Класс для работы с таблицей (моделью)
 */
class Model extends QueryBuilder
{
  /**
   * шард для подключения
   */
  protected $shard;

  /**
   * @var array - массив ошибок
   */
  public $error = [];

  /**
   * @var array - рассчитаные ключи шардинга
   */
  protected $shardKey = [];

  /**
   * @var array - массив значений и полей для модели
   */
  public $options = [];

  /**
   * @var array - список полей (колонки из таблицы). Данный параметр может быть расширен
   */
  public $header = [];

  /**
   * @var bool - признак того, что запрос идет на несколько шардов
   */
  protected $multy = false;

  /**
   * @var array - массив с данными для вставки, если используется меод пакетной вставки
   */
  protected $batchArray = [];

  /**
   * @var int - размер пакета для вставки
   */
  public $batchLimit = 1000;

  /**
   * @var int - считает кол-во записей, что добавляются в массив @see $batchArray
   */
  protected $batchCount = 0;

  /**
   * Model constructor.
   *
   * @param array  $shard
   * @param string $table
   * @param string $type
   */
  public function __construct($shard = [], $table = '', $type = 'time')
  {
    $type = (empty($type) ? 'time' : $type);
    $this->table = $table;
    $this->getShard($shard, $type);
    parent::__construct($this->shardKey, $this->table);
  }

  /**
   * Получает таблицу для модели.
   *
   * @return string
   */
  public function getTable()
  {
    return $this->table;
  }

  /**
   * Возвращает запрос.
   *
   * @return string
   */
  function showQuery()
  {
    return $this->sql;
  }

  /**
   * Устанавливаем шард.
   *
   * @param array $shard
   */
  function setShard($shard = [1])
  {
    $this->shard = $shard;
  }

  /**
   * Получение ключа|ключей шардирования.
   *
   * @param        $shard - ключ для шардинга (строка. Если нужен диапазон, то указывать через :)
   * @param string $type  - тип функции шардинга
   *
   * @return void
   */
  public function getShard($shard, $type = 'time')
  {
    // смотрим конфиг файл на наличие файла с функцией шардинга
    if (file_exists(__DIR__ . "/../../config/model/$type.php"))
    {
      // подключаем файл с функцией
      include_once __DIR__ . "/../../config/model/$type.php";
      // проверяем наличие функции
      if (function_exists('shardChooser'))
      {
        $this->shardKey = shardChooser($this->table, $shard);
      }
      else
      {
        $this->shardKey[] = 1;
      }
    }
    else
    {
      $this->shardKey[] = 1;
    }
  }

  /**
   * Получение полей модели.
   *
   * @return array
   */
  public function getModel()
  {
    $data = [];
    if (!empty($this->shardKey)
        && is_array($this->shardKey)
        && empty($this->header)
    )
    {
      for ($i = 0; $i < $ic = count($this->shardKey); $i++)
      {
        $this->header = $this->getColumn();
        return $this->header;
      }
    }
    else if (!empty($this->header))
    {
      return $this->header;
    }

    return $data;
  }

  /**
   * Добавляет в поля модели необходимые ключи и значения.
   *
   * @param array $params - добавляемые значения
   *
   * @return void
   */
  public function addParam($params = [])
  {
    if (empty($this->header)
        && !empty($params)
    )
    {
      foreach ($params as $key => $param)
      {
        if ($this->isField($key))
        {
          $this->header[$key] = [];
          foreach ($param as $key_p => $value_p)
            $this->header[$key][$key_p] = $value_p;
        }
      }
    }
  }

  /**
   * Получаем данные по модели из request запроса.
   *
   * @param string $type
   *
   * @return void
   */
  public function saveModelFromRequest($type = 'POST')
  {
    $request = new Request();
    switch ($type)
    {
      case 'POST':
        $data = $request->getPost();
        break;
      case 'GET':
        $data = $request->getGet();
        break;
      default:
        $data = $request->getPost();
        break;
    }

    if (!empty($data)
        && is_array($data)
    )
    {
      $avaliableFileds = $this->getModel();
      foreach ($avaliableFileds as $key => $value)
      {
        if (isset($data[$key]))
        {
          $this->options[$key] = common_setValue($data, $key);
        }
      }
    }
  }

  /**
   * Сохранение модели в Request запрос.
   *
   * @param array  $model - модель для сохранения
   * @param string $type  - тип запроса Request
   */
  public function sendModel2Request($model = [], &$type)
  {
    if (!empty($model)
        && is_array($model)
    )
    {
      foreach ($model as $key => $value)
      {
        $type[$key] = common_setValue($model, $key);
      }
    }
  }

  /**
   * Событие, вызываемое перед вставкой данных.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeSave($data = [])
  {
    return $data;
  }

  /**
   * Событие, вызываемое перед обновлением данных.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeEdit($data = [])
  {
    return $data;
  }

  /**
   * Событие на после вставки данных.
   *
   * @param array $data
   *
   * @param array $additional
   *
   * @return array
   */
  function onAfterSave($data = [], $additional = [])
  {
    return $data;
  }

  /**
   * Сброс переменных после исполнениии пачи запросов.
   */
  function clearBatchVariables()
  {
    $this->batchCount = 0;
    $this->batchArray = [];
  }

  /**
   * Исполнение пачки запросов на вставку
   */
  function batchEnd()
  {
    if (!empty($this->batchArray)
        && empty($this->sql)
    )
    {
      $this->sql = "INSERT IGNORE INTO $this->table (";
      $keys = [];
      if (!empty($this->batchArray[0]))
      {
        $firstFields = $this->onBeforeSave($this->batchArray[0]);
        // ключи таблицы
        for ($i = 0; $i < 1; $i++)
        {
          foreach ($firstFields as $key => $value)
          {
            if ($this->isField($key))
            {
              $keys[$key] = $key;
              $this->sql .= "`$key`,";
            }
          }
        }

        // значения
        $this->sql = substr($this->sql, 0, -1) . ") VALUES ";
        if (!empty($keys)
            && is_array($keys)
        )
        {
          for ($i = 0; $i < $ic = count($this->batchArray); $i++)
          {
            if (!empty($firstFields)
                && 0 === $i
            )
            {
              $this->batchArray[0] = $firstFields;
            }
            else
            {
              $this->batchArray[$i] = $this->onBeforeSave($this->batchArray[$i]);
            }

            $this->sql .= "(";
            foreach ($keys as $key => $value)
            {
              $this->sql .= $this->prepare($key, $this->batchArray[$i][$key]) . ",";
            }
            $this->sql = substr($this->sql, 0, -1) . "),";
          }
          $this->sql = substr($this->sql, 0, -1);
        }
      }
    }

    // исполняем запрос
    if (!empty($this->sql))
    {
      $this->execute();
    }

    $this->onAfterSave($this->batchArray);

    $this->clearBatchVariables();
  }

  /**
   * Пакетная вставка данных.
   *
   * @param array $data
   * @param bool  $recursive
   */
  function batchSave($data = [], $recursive = false)
  {
    if ($recursive)
    {
      $this->batchCount = count($data);
      $this->batchArray = $data;
    }
    else
    {
      $this->batchArray[] = $data;
      $this->batchCount++;
    }

    while ($this->batchCount >= $this->batchLimit)
    {
      $this->batchEnd();
      $this->batchCount -= $this->batchLimit;
      if ($this->batchCount < 0)
      {
        $this->batchCount = 0;
      }
    }
  }

  /**
   * Сохраненние модели.
   *
   * @param array $data - список полей для сохранения
   * @param array $rule - правила валидации
   *
   * @param bool  $dontDeleteId
   *
   * @return int|array
   */
  public function save($data = [], $rule = [], $dontDeleteId = false)
  {
    // предобработка данных
    $data = $this->onBeforeSave($data);
    // подключаем валидатор
    $validate = new Validation($rule, $this->table);
    if ($validate->validate($data))
    {
      // валидация прошла
      if (!$dontDeleteId)
      {
        if (isset($data['id'])
            && empty($data['id'])
        )
        {
          unset($data['id']);
        }
      }

      $this->insert($data);
      $this->execute();
      $id = (!$dontDeleteId) ? $this->getLastId() : $data['id'];
      $this->onAfterSave($data, ['id' => $id]);
      return $id;
    }
    else
    {
      // валидация с ошибкой
      return $validate->showError();
    }
  }

  /**
   * Изменение модели.
   *
   * @param array  $data   - список полей для изменения
   * @param array  $filter - поля для фильтрации
   * @param        $rule   - правила валидации
   *
   * @return bool|array
   */
  public function edit($data = [], $filter = [], $rule)
  {
    // подключаем валидатор
    $validate = new Validation($rule, $this->table);
    if ($validate->validate($data))
    {
      // валидация прошла
      if (!empty($data['id']))
      {
        unset($data['id']);
      }
      $this->update($data, $filter);
      return $this->execute();
    }
    else
    {
      // валидация с ошибкой
      return $validate->showError();
    }
  }

  /**
   * Удаление данных модели.
   *
   * @param array $filter - список полей для фильтрации
   *
   * @return void
   */
  public function remove($filter = [])
  {
    $this->delete($filter);
    $this->execute();
  }

  /**
   * Получение данных по модели.
   *
   * @param array  $fields - список полей, которые нужно вернуть
   * @param array  $filter - список полей для фильтрации
   * @param array  $sort   - список полей для сортировки
   * @param        $limit  - ограничение на количество записей
   *
   * @return array
   */
  public function _list($fields = [], $filter = [], $sort = [], $limit = '0,8000')
  {
    $this->select($fields);
    $this->from();
    if (!empty($filter))
    {
      $this->where($filter);
    }

    if (!empty($sort))
    {
      $this->order($sort);
    }

    if (!empty($limit))
    {
      $this->limit($limit);
    }
    $this->execute();

    $data = $this->fetch();
    if (empty($data))
    {
      return [];
    }
    else
    {
      $this->options = $data;
      return $this->options;
    }
  }
}