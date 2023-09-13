<?php

namespace queryBuilder\mysql;

use cache\PhpCache;

/**
 * Класс для работы с запросами к MySQL
 */
class QueryBuilder
{
  /**
   * @var array - выражения, которые могут быть использованы для группировки или сортировки
   */
  protected $groupRegexp = [
      '/COUNT\(([a-z0-9]+)\)/',
      '/SUM\(([a-z0-9]+)\)/',
      '/MIN\(([a-z0-9]+)\)/',
      '/MAX\(([a-z0-9]+)\)/',
      '/DISTINCT\(([a-z0-9]+)\)/',
      '/COUNT\(DISTINCT\(([a-z0-9]+)\)\)/'
  ];

  /**
   * @var string - последний хэш (то есть хэш для текущего запроса) на подключение к БД
   */
  protected $lastHash = '';

  /**
   * @var - результат выполнения запроса
   */
  protected $result;

  /**
   * @var string - ошибка в результате запроса
   */
  public $error = '';

  /**
   * @var array - пул соедиенний с mysql
   */
  protected $pool = [];

  /**
   * @var array - массив хэшей для таблиц
   */
  public $multishard;

  /**
   * @var string - текущий запрос к mySQL
   */
  public $sql = '';

  /**
   * @var string - таблица, к которой идет запрос
   */
  public $table = '';

  /**
   * @var array - массив полей таблицы
   */
  public $model = [];

  function setHash($table = '')
  {
    $hash = md5($table);
    $this->lastHash = $hash;
    return $this->lastHash;
  }

  function setConnection($shard = 1, $table = '')
  {
    if (!empty($shard)
        && !empty($table)
    )
    {
      common_inc('sharding');
      $hash = $this->setHash($table);
      $this->multishard[$hash] = $table;
      $link = sharding_getConnection($shard, $table);
      $this->pool[$hash] = $link['connect'];
      PhpCache::set('pool:' . $hash . ':' . $table, $this->pool[$hash]);
      return true;
    }

    return false;
  }

  /**
   * QueryBuilder constructor.
   *
   * @param array  $shard
   * @param string $table
   */
  function __construct($shard = [], $table = '')
  {
    $this->table = $table;
    // запрос к шардированной таблице в рамках одного месяца
    if (1 == count($shard)
        && 1 != $shard[0]
        && !empty($shard[0])
    )
    {
      $this->table .= '_' . $shard[0];
      $this->setConnection($shard[0], $this->table);
    }
    elseif (!empty($shard[0])
        && 1 == $shard[0]
    )
    {
      // запрос к нешардированной таблице
      $this->setConnection($shard[0], $table);
    }
    else
    {
      // для нескольких запросов
      // подключаем шардинг
      for ($i = 0; $i < $ic = count($shard); $i++)
      {
        // получаем подключение к БД
        $this->setConnection($shard[$i], $table . '_' . $shard[$i]);
        $this->table = $table . '_' . $shard[$i];
      }
    }

    // проверяем, есть ли модель и если нет, то получаем ее
    if (empty(PhpCache::get('model:' . $this->lastHash . ':' . $table))
        && !empty($table)
    )
    {
      // получаем модель по последней шардированой таблице
      $this->model[$this->lastHash] = $this->getColumn();
      PhpCache::set('model:' . $this->lastHash . ':' . $this->table, $this->model[$this->lastHash]);
    }

    return PhpCache::get('pool:' . $this->lastHash . ':' . $this->table);
  }

  /**
   * Конвертирует значение перменной в соответсвии с типом.
   *
   * @param string $field
   * @param string $value
   * @param bool   $withoutQuotes
   *
   * @return int|string
   */
  function prepare($field = '', $value = '', $withoutQuotes = false)
  {
    if (is_string($value)
        && $value != $this->table
    )
    {
      if (!$withoutQuotes)
      {
        return "'" . mysqli_real_escape_string(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table), $value) . "'";
      }
      else
      {
        return mysqli_real_escape_string(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table), $value);
      }
    }
    if (is_numeric($value))
    {
      return $value;
    }
    if (is_null($value))
    {
      return 'NULL';
    }

    return '';
  }

  /**
   * Получение колонок таблицы.
   *
   * @return array
   */
  function getColumn()
  {
    $data = [];
    if (!empty(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table)))
    {
      $result = mysqli_query(
          PhpCache::get('pool:' . $this->lastHash . ':' . $this->table),
          "SHOW COLUMNS FROM $this->table"
      );

      if (!empty($result))
      {
        while ($a = mysqli_fetch_assoc($result))
        {
          if (!empty($a['Field']))
          {
            $data[$a['Field']] = $a['Field'];
          }
        }
      }
    }

    return $data;
  }

  /**
   * Проверка на то, есть ли поле в списке колонок таблицы.
   *
   * @param string $field - проверяемой поле
   *
   * @return bool
   */
  function isField($field = '')
  {
    return (empty(PhpCache::get('model:' . $this->lastHash . ':' . $this->table)[$field]) ? false : true);
  }

  /**
   * select часть запроса.
   *
   * @param array $select - массив полей для отображения. Если массив пустой, то берем все колонки
   *
   * @return void
   */
  function select($select = [])
  {
    $this->sql = 'SELECT';
    // если не передан select
    if (empty($select))
    {
      $this->sql .= ' * ';
      return;
    }

    // если передан массив
    if (is_array($select))
    {
      $fields = [];
      foreach ($select as $field)
      {
        if ($this->isField($field))
        {
          $fields[] = $field;
        }
      }

      if (!empty($fields))
      {
        $this->sql .= ' `' . implode('`, `', $fields) . '`';
      }
    }
  }

  /**
   * from секия запроса.
   *
   * @return void
   */
  function from()
  {
    if (empty($this->table))
    {
      return;
    }

    if (is_string($this->table))
    {
      $this->sql .= " FROM `{$this->table}`";
    }
  }

  /**
   * Выбор способа вызова where функции.
   * Можно вызывать так:
   * $qb->where('partner', '=', 171);
   * $qb->where(['partner' => 171, 'domain' => 'vital.mgts.zionec.ru']);
   * $qb->where([
   *  ['partner', '=', 171],
   *  ['domain', '=', 'vital.mgts.zionec.ru']
   * ]);
   *
   * @param $name
   * @param $operand
   * @param $value
   */
  function checkWhereMethod($name, $operand, $value)
  {
    // если 3 аргумента
    if ((!empty($name) && is_string($name))
        && !empty($operand)
    )
    {
      $this->addWhere($operand, [$name => $value]);
    }
    // если 1 аргумент, то считаем кол-во параемтров в нем
    if (is_array($name))
    {
      // если передаем с операндом (3 аргумента)
      if (!empty($name[0])
          && 3 == count($name[0])
      )
      {
        for ($i = 0; $i < $ic = count($name); $i++)
        {
          if (isset($name[$i][0])
              && isset($name[$i][1])
              && isset($name[$i][2])
          )
          {
            if ($this->addWhere($name[$i][1], [$name[$i][0] => $name[$i][2]]))
            {
              $this->addAnd();
            }
          }
        }

        $this->sql = substr($this->sql, 0, -5);
      }
      else
      {
        // старый стандартный вызов
        foreach ($name as $key => $value)
        {
          if ($this->isField($key))
          {
            $this->sql .= "`" . $key . "`=" . $this->prepare($key, $value) . " AND ";
          }
        }

        $this->sql = substr($this->sql, 0, -5);
      }
    }
  }

  /**
   * Where clause.
   *
   * @param $name
   * @param $operand
   * @param $value
   */
  function where($name = '', $operand = '', $value = 0)
  {
    $this->sql .= ' WHERE ';
    if (!empty($name))
    {
      $this->checkWhereMethod($name, $operand, $value);
    }
  }

  /**
   * То же самое, что и where, только без WHERE слова.
   *
   * @param string $name
   * @param string $operand
   * @param int    $value
   */
  function addWhereMulti($name = '', $operand = '', $value = 0)
  {
    if (!empty($name))
    {
      $this->checkWhereMethod($name, $operand, $value);
    }
  }

  /**
   * Определяет по первому элементу массива тип данных в масисве для запросов IN.
   *
   * @param array $array
   *
   * @return string
   */
  function getTypeByFirstArrayElement($array = [])
  {
    $type = '';
    if (!empty($array))
    {
      $type = 'string';
      foreach ($array as $val)
      {
        if (is_numeric($val)
            && $val <= 18446744073709551615
        )
        {
          return 'integer';
        }
        else if (is_string($val))
        {
          return 'string';
        }
        break;
      }
    }

    return $type;
  }

  /**
   * Добавляет условие в $this->sql
   *
   * @param string       $field    - название поля
   * @param string|array $value    - значение
   * @param string       $operator - оператор сравнения
   *
   * @return string
   */
  function getWhereOperator($field, $value, $operator = '=')
  {
    $sql = '';
    switch (mb_convert_case($operator, MB_CASE_UPPER, "UTF-8"))
    {
      case '=':
      case '!=':
      case '>':
      case '<':
      case '<=':
      case '>=':
      case '<>':
        if (NULL === $value)
        {
          switch ($operator)
          {
            case '=': $operator = ' IS NULL ';break;
            case '!=': $operator = ' IS NOT NULL ';break;
            case '<>': $operator = ' IS NOT NULL ';break;
            default: $operator = ' IS NULL ';
          }
        }
        $sql = " `$field` $operator {$this->prepare($field, $value)}";
        break;
      case 'IN':
        // так как массив может быть и ассоциативным
        $type = $this->getTypeByFirstArrayElement($value);
        switch ($type)
        {
          case 'string':
            $sql = "`$field` IN ('" . implode("','", $value) . "')";
            break;
          case 'integer':
            $sql = "`$field` IN (" . implode(",", $value) . ")";
            break;
        }
        break;
      case 'NOTIN':
        // так как массив может быть и ассоциативным
        $type = $this->getTypeByFirstArrayElement($value);
        switch ($type)
        {
          case 'string':
            $sql = "`$field` NOT IN ('" . implode("','", $value) . "')";
            break;
          case 'integer':
            $sql = "`$field` NOT IN (" . implode(",", $value) . ")";
            break;
        }
        break;
      case 'LIKE':
        $sql = "`$field` LIKE {$this->prepare($field, '%'.$value.'%')}";
        break;
      case 'LIKEL':
        $sql = "`$field` LIKE {$this->prepare($field, '%'.$value)}";
        break;
      case 'LIKER':
        $sql = "`$field`` LIKE {$this->prepare($field, $value.'%')}";
        break;
      case 'NOTLIKE':
        $sql = "`$field` NOT LIKE {$this->prepare($field, '%'.$value.'%')}";
        break;
      case 'REGEXP':
        $sql = "match(`$field`,{$this->prepare($field, $value)}) = 1";
        break;
      case 'NOREGEXP':
        $sql = "match(`$field`,{$this->prepare($field, $value)}) = 0";
        break;
      case 'BETWEEN':
        if (is_string($value))
        {
          $value = explode('-', $value);
        }
        if (
            array_key_exists(0, $value)
            &&
            array_key_exists(1, $value)
            &&
            is_scalar($value[0])
            &&
            is_scalar($value[1])
        )
        {
          $sql = "(`$field` BETWEEN {$this->prepare($field, $value[0])} AND " . $this->prepare($field, $value[1]) . ")";
        }
        break;
      /*case 'IP':
        $ipConv = new IpConv();
        $sql = "`$field` = {$ipConv->ip_conv_ip_to_binary_32($value)}";
        break;*/
      /*case 'IPMASK':
        $ipConv = new IpConv();
        $value = $ipConv->conv_cidr_to_interval($value);
        $sql = "(`$field` BETWEEN {$this->prepare($field, $value[0])} AND " . ((array_key_exists(1, $value)) ? $this->prepare($field, $value[1]) : '') . ")";
        break;*/
      case 'DATEINTERVAL':
        if (is_string($value))
        {
          $value = preg_replace('/[\["\]]/', '', $value);
          $value = explode(',', $value);
        }
        if (array_key_exists(0, $value) && array_key_exists(1, $value))
        {
          $dateStart = (string)strtotime($value[0]);
          $dateEnd = (string)(strtotime((!empty($value[1])) ? $value[1] : $dateStart) + 86399);
          $sql = "(`$field` BETWEEN {$this->prepare($field, $dateStart)} AND {$this->prepare($field, $dateEnd)})";
        }
        break;
      default:
        $sql = "`$field` = {$this->prepare($field, $value)}";
        break;
    }

    return $sql;
  }

  /**
   * Добавляет одно условие в фильтр с необходимым операндом.
   *
   * @param string $operator
   * @param array  $clause
   *
   * @return bool
   */
  function addWhere($operator = '=', $clause = [])
  {
    if (empty($clause))
    {
      return false;
    }
    foreach ($clause as $key => $value)
    {
      if ($this->isField($key))
      {
        $this->sql .= $this->getWhereOperator($key, $value, $operator);
        return true;
      }
    }

    return false;
  }

  /**
   * Добавляет левую открывающую скобку в SQL запрос
   */
  function addLeftBracket()
  {
    $this->sql .= '(';
  }

  /**
   * Добавляет правую закрывающую скобку в SQL запрос
   */
  function addRightBracket()
  {
    $this->sql .= ')';
  }

  /**
   * Обрезает в строке на конце AND или OR.
   */
  function trimWhere()
  {
    $this->sql = rtrim(trim($this->sql), 'AND');
    $this->sql = rtrim(trim($this->sql), 'OR');
  }

  /**
   * Добавляет OR часть к запросу.
   */
  function addOr()
  {
    $this->sql .= ' OR ';
  }

  /**
   * Добавляет XOR часть к запросу.
   */
  function addXor()
  {
    $this->sql .= ' XOR ';
  }

  /**
   * Добавляет AND часть к запросу.
   */
  function addAnd()
  {
    $this->sql .= ' AND ';
  }

  /**
   * Получает возможные операнды для order.
   *
   * @param string $operator
   *
   * @return string
   */
  function getOrderOperator($operator = 'ASC')
  {
    switch ($operator)
    {
      case 'ASC':
      case 'DESC':
        return $operator;
      default:
        return 'ASC';
    }
  }

  /**
   * ORDER clause.
   *
   * @param array $order
   */
  function order($order = [])
  {
    if (!empty($order)
        && is_array($order)
    )
    {
      $this->sql .= ' ORDER BY ';
      foreach ($order as $key => $operator)
      {
        if ($this->isField($key))
        {
          $this->sql .= $key . " " . $this->getOrderOperator($operator) . ",";
        }
        else
        {
          for ($i = 0; $i < $ic = count($this->groupRegexp); $i++)
          {
            preg_match_all($this->groupRegexp[$i], $key, $matchFiled);
            if (!empty($matchFiled)
                && !empty($matchFiled[1])
                && !empty($matchFiled[1][0])
                && $this->isField($matchFiled[1][0])
            )
            {
              $this->sql .= $key . " " . $this->getOrderOperator($operator) . ",";
            }
          }
        }
      }
      $this->sql = substr($this->sql, 0, -1);
    }
  }

  /**
   * Лимит для запросов.
   *
   * @param int $offset
   * @param int $count
   */
  function limit($offset = 0, $count = 0)
  {
    if ($offset && $count)
    {
      $this->sql .= " LIMIT {$offset}, {$count}";
    }
    elseif ($offset && !$count)
    {
      $this->sql .= " LIMIT {$offset}";
    }
    elseif ($count)
    {
      $this->sql .= " LIMIT {$count}";
    }
  }

  /**
   * Подготовка запроса по шаблону.
   *
   * @param string $template
   * @param array  $data
   */
  function prepareQuery($template = '', $data = [])
  {
    if (!empty($template)
        && is_array($data)
    )
    {
      // смотрим все переданные параметры и пробуем заменить данные в шаблоне
      foreach ($data as $key => $value)
      {
        if (strpos($template, '{{' . $key . '}}') !== false)
        {
          $template = str_replace('{{' . $key . '}}', $this->prepare($key, $value), $template);
        }
      }

      $this->sql .= $template;
    }
  }

  /**
   * Запрос по шаблону.
   *
   * @param string $template
   * @param array  $data
   * @param bool   $execute
   *
   * @return bool - возвращает false в случае ошибки выполнения mysqli_query или отсутствия соединения
   */
  function query($template = '', $data = [], $execute = true)
  {
    $this->prepareQuery($template, $data);
    if ($execute)
    {
      // dd($this->sql);
      return $this->execute();
    }
  }

  /**
   * Запрос на вставку данных.
   *
   * @param array $data
   */
  function insert(array $data = [])
  {
    if (!empty($data)
        && is_array($data)
    )
    {
      $this->sql .= "INSERT IGNORE INTO $this->table (";
      $keys = []; // массив для хранения ключей
      foreach ($data as $key => $value)
      {
        if ($this->isField($key))
        {
          $keys[$key] = $value;
          $this->sql .= "`$key`,";
        }
      }


      $this->sql = substr($this->sql, 0, -1) . ") VALUES (";
      if (!empty($keys)
          && is_array($keys)
      )
      {
        foreach ($keys as $key => $value)
        {
          if (is_bool($value))
          {
            $this->sql .= (int)$value . ",";
          }
          else
          {
            $this->sql .= $this->prepare($key, $value) . ",";
          }
        }

        $this->sql = substr($this->sql, 0, -1) . ")";
      }
    }
  }

  /**
   * Секция UNION ALL запроса.
   */
  function union()
  {
    $this->sql .= " UNION ALL ";
  }

  /**
   * Проверка агрегирующих операторов.
   *
   * @param string $operand
   * @param string $filed
   *
   * @return string
   */
  function checkAggregationOperand($operand = 'count', $filed = '')
  {
    $res = '';
    if (!empty($operand))
    {
      switch ($operand)
      {
        case 'sum':
          $res = "SUM($filed) as sum,";
          break;
        case 'count':
          $res = "COUNT($filed) as cnt,";
          break;
        case 'min':
          $res = "MIN($filed) as mi,";
          break;
        case 'max':
          $res = "MAX($filed) as ma,";
          break;
        case 'countDistinct':
          $res = "COUNT(DISTINCT($filed)) as cnt,";
          break;
        case 'distinct':
          $res = "DISTINCT($filed) as dis,";
          break;
      }
    }

    return $res;
  }

  /**
   * Добавление аггрегирующих данных.
   *
   * @param array $aggregation
   * @param bool  $distinct
   */
  function getAggregation($aggregation = [], $distinct = false)
  {
    $this->sql .= "SELECT" . ($distinct ? ' DISTINCT' : ' ');
    if (!empty($aggregation) && is_array($aggregation))
    {
      foreach ($aggregation as $aggregationData)
      {
        foreach ($aggregationData as $operand => $field)
        {
          $this->sql .= $this->checkAggregationOperand($operand, $field);
        }
      }

      $this->sql = substr($this->sql, 0, -1);
    }
  }

  /**
   * Добавление условия select.
   *
   * @param array $field
   * @param bool  $notCheck
   */
  function addSelect($field = [], $notCheck = false)
  {
    // если передан массив
    $this->sql .= ',';
    if (!empty($field) && is_array($field))
    {
      for ($i = 0; $i < $ic = count($field); $i++)
      {
        if ($notCheck)
        {
          $this->sql .= " " . $field[$i] . ",";
        }
        else
        {
          if ($this->isField($field[$i]))
          {
            $this->sql .= "`" . $field[$i] . "`,";
          }
        }
      }
    }

    $this->sql = substr($this->sql, 0, -1) . " ";
  }

  /**
   * Группировка данных.
   *
   * @param array $group
   */
  function group(array $group = [])
  {
    if (!empty($group))
    {
      $groupFields = [];
      foreach ($group as $groupField)
      {
        if ($this->isField($groupField))
        {
          $groupFields[] = $groupField;
        }
        else
        {
          for ($i = 0; $i < $ic = count($this->groupRegexp); $i++)
          {
            preg_match_all($this->groupRegexp[$i], $groupField, $matchFiled);
            if (!empty($matchFiled)
                && !empty($matchFiled[1])
                && !empty($matchFiled[1][0])
                && $this->isField($matchFiled[1][0])
            )
            {
              $groupFields[] = $groupField;
            }
          }
        }
      }

      if (!empty($groupFields))
      {
        $this->sql .= ' GROUP BY ' . implode(', ', $groupFields);
      }
    }
  }

  function having(array $having = [])
  {
    if (empty($having))
    {
      return;
    }

    $this->sql .= ' HAVING `' . implode('`, `', $having) . '`';
  }

  /**
   * Запрос на вставку, если инсерт будет делаться через SET.
   *
   * @return void
   */
  function __insert()
  {
    $this->sql = "INSERT IGNORE INTO $this->table";
  }

  /**
   * Блок SET запросов INSERT|UPDATE.
   *
   * @param array $data - массив данных для вставки|обновления
   *
   * @param bool  $withoutSet
   *
   * @return void
   */
  function addSet($data = [], $withoutSet = false)
  {
    if (!empty($data)
        && is_array($data)
    )
    {
      if (!$withoutSet)
      {
        $this->sql .= ' SET ';
      }
      foreach ($data as $key => $value)
      {
        if ($this->isField($key))
        {
          $this->sql .= "`" . $key . "`=" . $this->prepare($key, $value) . ",";
        }
      }
      $this->sql = substr($this->sql, 0, -1);
    }
  }

  /**
   * Запрос на удаление записи из БД.
   *
   * @param array $data - массив данных для поиска записей.
   *
   * @return void
   */
  function delete($data = [])
  {
    $this->sql .= "DELETE FROM $this->table ";
    if (!empty($data)
        && is_array($data)
    )
    {
      $this->where($data);
    }
  }

  /**
   * Запрос на обновление.
   *
   * @param array $data   - список полей для обновления
   * @param array $filter - список полей для фильтрации
   *
   * @return void
   */
  function update($data = [], $filter = [])
  {
    $this->sql .= "UPDATE $this->table ";
    if (!empty($data)
        && is_array($data)
    )
    {
      $this->addSet($data);
      $this->where($filter);
    }
  }

  /**
   * Исполняет запрос.
   *
   * @return bool - возвращает false в случае ошибки выполнения mysqli_query или отсутствия соединения
   */
  function execute()
  {
    // для запроса к нескольким таблицам
    if (!empty($this->multishard)
        && 1 < count($this->multishard)
    )
    {
      // TODO: предусмотреть возможность для COUNT, MIN, MAX, GROUP BY делать аггрегирование данных
      $result = [];
      // сохраняем эталонный запрос для замены на таблицы
      $query = $this->sql;
      $lastTable = end($this->multishard);
      foreach ($this->multishard as $hash => $table)
      {
        // заменяем таблицы под шард
        $this->sql = str_replace($lastTable, $table, $query);
        if (!empty(DEBUG) && !empty(DEBUG_FILE))
        {
          file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $this->sql . "\n", FILE_APPEND);
        }
        $result[] = mysqli_query(
            PhpCache::get('pool:' . $hash . ':' . $table),
            $this->sql
        );
        $this->sql = '';
      }
      $this->sql = '';
      $this->result = $result;
      unset($result);

      //TODO: добавить сохранение ошибок MySQL
      return true;
    }
    else
    {
      // для запросов к одной таблице
      if (!empty(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table)))
      {
        $result = mysqli_query(
            PhpCache::get('pool:' . $this->lastHash . ':' . $this->table),
            $this->sql
        );
        if (!empty(DEBUG) && !empty(DEBUG_FILE))
        {
          file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $this->sql . "\n", FILE_APPEND);
        }

        $this->result = $result;
        $this->sql = '';

        if ($result === false)
        {
          $this->error = mysqli_error(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table));
          return false;
        }
        else
        {
          $this->error = '';
          return true;
        }
      }
    }

    $this->error = 'Нет соединения с MySQL';
    return false;
  }

  function multiexecute()
  {
    // для запросов к одной таблице
    if (!empty(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table)))
    {
      $result = mysqli_multi_query(
          PhpCache::get('pool:' . $this->lastHash . ':' . $this->table),
          $this->sql
      );
      if (!empty(DEBUG) && !empty(DEBUG_FILE))
      {
        file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $this->sql . "\n", FILE_APPEND);
      }

      $this->result = $result;
      $this->sql = '';
      while (mysqli_next_result(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table)))
      {
        ;
      }

      if ($result === false)
      {
        $this->error = mysqli_error(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table));
        return false;
      }
      else
      {
        $this->error = '';
        return true;
      }
    }
  }

  /**
   * Получает массив из результата запроса.
   *
   * @return array
   */
  function fetch()
  {
    $data = [];
    // если массив (запрос к нескольким таблицам) то суммируем результат
    if (!empty($this->result)
        && is_array($this->result)
    )
    {
      for ($i = 0; $i < $ic = count($this->result); $i++)
      {
        if (!empty($this->result[$i]))
        {
          while ($a = mysqli_fetch_assoc($this->result[$i]))
            $data[] = $a;
        }
      }
    }
    else
    {
      if ($this->result)
      {
        while ($a = mysqli_fetch_assoc($this->result))
          $data[] = $a;
      }
    }

    // освобождаем память
    unset($this->result);

    return $data;
  }

  /**
   * Поучаем последнюю вставленную запись.
   *
   * @return int
   */
  function getLastId()
  {
    if (!empty(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table)))
    {
      $result = mysqli_insert_id(PhpCache::get('pool:' . $this->lastHash . ':' . $this->table));
      return $result;
    }

    return 0;
  }
}