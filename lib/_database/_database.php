<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 18.07.16
 * Time: 16:39
 */

common_inc('sharding');

/**
 * Select query.
 *
 * @param bool   $shardKey - key for sharding.
 * @param        $table    - table for query. In query name of table will be a $table_$shardKey
 * @param array  $select   - select array
 * @param array  $where    - where clause
 * @param array  $sort     - sort clause
 * @param string $limit    - count of records
 * @param array  $in       - where IN clause
 *
 * @return bool|mysqli_result
 */
function select_db($shardKey = false, $table, $select = [], $where = [], $sort = [], $limit = '', $in = array())
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  $notCut = false;
  $table = query_setTableName($link['key'], $table);
  $countSelect = count($select);
  $strSelect = 'SELECT ';
  $strWhere = ' WHERE ';
  $strLimit = '';
  $strSort = '';

  /** select */
  for ($i = 0; $i < $countSelect; $i++)
  {
    if ($select[$i] == '*')
    {
      $notCut = true;
      $strSelect .= "" . $select[$i] . "";
      break;
    }
    $strSelect .= "`" . prepare_db($select[$i]) . "`, ";
  }

  if (!$notCut)
  {
    $strSelect = substr($strSelect, 0, -2) . " FROM `" . prepare_db($table) . "` ";
  }
  else
  {
    $strSelect .= " FROM `" . mysqli_escape_string($link['connect'], $table) . "` ";
  }

  /** where */
  if (!empty($where))
  {
    foreach ($where as $field => $value)
      $strWhere .= "`" . prepare_db($field) . "`='" . prepare_db($value) . "' AND ";

    $strWhere = substr($strWhere, 0, -4);
  }

  /** IN */
  if (!empty($in))
  {
    foreach ($in as $key => $value)
    {
      if (!empty($value))
      {
        $strWhere .= '`' . prepare_db($key) . "` IN ('" . implode("','", $value) . "') ";
      }
    }
  }

  if ($strWhere == ' WHERE ')
  {
    $strWhere = '';
  }

  /** sort */
  if (!empty($sort))
  {
    foreach ($sort as $field => $value)
      $strSort .= prepare_db($field) . ' ' . prepare_db($value) . ", ";

    $strSort = substr(' ORDER BY ' . $strSort, 0, -2);
  }

  /** limit */
  if ($limit)
  {
    $strLimit = ' LIMIT ' . prepare_db($limit);
  }

  /** query */

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $strSelect . $strWhere . $strSort . $strLimit . "\n", FILE_APPEND);
  }

  /*echo $strSelect . $strWhere . $strSort . $strLimit;
  exit;*/

  $obj = mysqli_query($link['connect'], $strSelect . $strWhere . $strSort . $strLimit);

  return $obj;
}

/**
 * Insert query.
 *
 * @param string $shardKey - sharding key
 * @param        $table    - name of table
 * @param        $params   - where clause
 * @param array  $update   - if pass, then query build as INSERT UPDATE.
 *                         if name of filed will start form #, this means then value will not wrapper in '.
 *
 * @return int|string
 */
function insert_db($shardKey = '', $table, $params, $update = [])
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  $table = query_setTableName($link['key'], $table);

  $strInsert = "INSERT INTO `" . $table . "` (";
  /** @var  $id - unique id */
  if (empty($params['id']))
  {
    $id = sharding_setUniqueId();
    $strField = '`id`,';
    $strValues = ' VALUES (\'' . ($id) . '\',';
  }
  else
  {
    $strField = '';
    $strValues = ' VALUES (';
  }

  $strUpdate = '';
  foreach ($params as $field => $value)
  {
    $strField .= "`" . prepare_db($field) . "`,";
    $strValues .= "'" . prepare_db($value) . "',";
  }
  $strField = substr($strField, 0, -1) . ")";
  $strValues = substr($strValues, 0, -1) . ")";

  if (!empty($update))
  {
    $strUpdate = ' ON DUPLICATE KEY UPDATE ';
    foreach ($update as $field => $value)
    {
      if (substr($field, 0, 1) == '#')
      {
        $strUpdate .= "`" . prepare_db(substr($field, 1)) . "` = " . prepare_db($value) . ",";
      }
      else
      {
        $strUpdate .= "`" . prepare_db($field) . "` = '" . prepare_db($value) . "',";
      }
    }
  }
  $strUpdate = substr($strUpdate, 0, -1);

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $strInsert . $strField . $strValues . $strUpdate . "\n", FILE_APPEND);
  }
  // echo $strInsert . $strField . $strValues . $strUpdate . "\n\n";

  $res = mysqli_query($link['connect'], $strInsert . $strField . $strValues . $strUpdate);
  if (!$res)
  {
    if (!empty(DEBUG) && !empty(DEBUG_FILE))
    {
      file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - Error: " . $strInsert . $strField . $strValues . $strUpdate . "\n", FILE_APPEND);
    }
    return false;
  }

  $affectedRow = mysqli_affected_rows($link['connect']);
  if ($affectedRow == 1)
  {
    if (empty($params['id']))
    {
      return $id;
    }
    else
    {
      return 1;
    }
  }
  else if ($affectedRow == 2)
  {
    if (empty($params['id']))
    {
      return (int)$id;
    }
    else
    {
      return $res;
    }
  }
  else
  {
    return false;
  }
}

/**
 * Escape string.
 * Get last connection to mysqli.
 *
 * @param            $str
 * @param bool|false $strip - apply strip_tags()
 *
 * @return string
 */
function prepare_db($str, $strip = false)
{
  $link = query_lastConnect();
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  if (!$strip)
  {
    return mysqli_real_escape_string($link['connect'], strip_tags(trim($str)));
  }
  else
  {
    return mysqli_real_escape_string($link['connect'], trim($str));
  }
}

/**
 * Update query.
 *
 * @param string $shardKey - key for sharding
 * @param        $table    - table name
 * @param array  $params   - data for updating
 * @param array  $where    - where clause
 *
 * @return bool
 */
function update_db($shardKey = '', $table, $params = [], $where = [])
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  $table = query_setTableName($link['key'], $table);

  $strQuery = "UPDATE `" . prepare_db($table) . "` SET ";
  if (!empty($where))
  {
    $strQueryWhere = " WHERE ";
  }
  else
  {
    $strQueryWhere = "";
  }

  foreach ($params as $field => $value)
    $strQuery .= "`" . prepare_db($field) . "`='" . prepare_db($value) . "',";

  $strQuery = substr($strQuery, 0, -1);
  foreach ($where as $field => $value)
    $strQueryWhere .= "`" . prepare_db($field) . "`='" . prepare_db($value) . "' AND ";

  $strQueryWhere = substr($strQueryWhere, 0, -5);

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $strQuery . $strQueryWhere . "\n", FILE_APPEND);
  }

  // echo $strQuery . $strQueryWhere; //exit;

  return mysqli_query($link['connect'], $strQuery . $strQueryWhere);
}

/**
 * Delete query.
 *
 * @param string $shardKey - key for sharding
 * @param        $table    - name of table
 * @param        $where    - where clause
 *
 * @return bool
 */
function delete_db($shardKey = '', $table, $where)
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }
  $table = query_setTableName($link['key'], $table);

  $strQuery = "DELETE FROM " . prepare_db($table) . " WHERE ";
  if (!$table && !$where)
  {
    return false;
  }

  foreach ($where as $field => $value)
    $strQuery .= "`" . $field . "`='" . $value . "' AND ";

  $strQuery = substr($strQuery, 0, -5);

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $strQuery . "\n", FILE_APPEND);
  }


  return mysqli_query($link['connect'], $strQuery);
}

/** not 4 sharding!!!
 *
 * @param string $shardKey
 * @param string $table
 * @param string $query
 *
 * @return bool
 */
function multyQuery_db($shardKey = '', $table = '', $query = '')
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }
  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $query . "\n", FILE_APPEND);
  }

  mysqli_multi_query($link['connect'], $query);
  while (mysqli_next_result($link['connect']))
  {
    ;
  }
  return true;
}

function __multyquery($query = '')
{
  $link = sharding_getConnection();
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }
  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $query . "\n", FILE_APPEND);
  }

  mysqli_multi_query($link['connect'], $query);
  while (mysqli_next_result($link['connect']))
  {
    ;
  }
  return true;
}

/**
 * Функция выполняет простой SQL запрос.
 *
 * @param string $query строка запроса
 *
 * @return mysqli_result|bool
 */
function simple_query($query = '')
{
  $link = sharding_getConnection();
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $query . "\n", FILE_APPEND);
  }
  //var_dump($query);
  return mysqli_query($link['connect'], $query);
}

/**
 * Возвращает SQL строку, содержащую условие IN для выборки записей, в виде
 * `field` IN ('4', '5', '6')
 *
 * @param string $field - название поля
 * @param array  $array - нумерованный одномерный массив значений
 *
 * @return string
 */
function array_to_sql_in($field = '', array $array)
{
  if (empty($field) || empty($array))
  {
    return '';
  }

  return "`$field` IN ('" . implode("', '", $array) . "')";
}

/**
 * Возвращает ассоциативный массив результатов,
 * если они есть, либо пустой массив.
 *
 * @param mysqli_result $mysqliResult Объект MySqli результата
 *
 * @return array
 */
function return_mysqli_results($mysqliResult)
{
  return ($mysqliResult
      && property_exists($mysqliResult, 'num_rows')
      && method_exists($mysqliResult, 'fetch_all')
      && $mysqliResult->num_rows > 0
  ) ? $mysqliResult->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * /**
 * Simple query.
 * Query must be without table name.
 * For example:
 * select id, name where id = 3
 * Query also must have where clause.
 * If where clause is not necessary for you, use 1=1.
 * For example:
 * select * where 1=1
 *
 * @param string $shardKey
 * @param string $table - table for query
 * @param string $query - mysqli query
 *
 * @return bool|mysqli_result
 */
function query_db($shardKey = '', $table = '', $query = '')
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  /** get left and right query part */
  $partQuery = explode('WHERE', $query);
  $table = query_setTableName($link['key'], $table);
  if (count($partQuery) != 2)
  {
    include_once(__DIR__ . '/../error/error.php');
    error_show(1, 'database', [
        'file' => __FILE__,
        'line' => __LINE__,
        'function' => __FUNCTION__
    ]);
  }

  $newQuery = $partQuery[0] . 'FROM ' . $table . ' WHERE ' . $partQuery[1];

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $newQuery . "\n", FILE_APPEND);
  }

  // var_dump($newQuery);

  return mysqli_query($link['connect'], $newQuery);
}

/**
 * Execute custom update query.
 *
 * @param string $shardKey      - timestamp of month start
 * @param string $table         - name of table without postfix
 * @param string $query         - query for execution.
 *                              query must have {from} statement, which means that is a placeholder for FROM clasue
 *
 * @return mysqli_result|bool
 */
function updateQuery_db($shardKey = '', $table = '', $query = '')
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  /** get left and right query part */

  $table = query_setTableName($link['key'], $table);
  $sql = preg_replace('/({from})/', '`' . $table . '`', $query);

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $sql . "\n", FILE_APPEND);
  }

  return mysqli_query($link['connect'], $sql);
}

/**
 * Insert ignore query.
 *
 * @param string $shardKey - key for sharding
 * @param string $table    - table
 * @param array  $params   - field clause
 *
 * @return bool|mysqli_result
 */
function insertIgnore_db($shardKey = '', $table = '', $params = [])
{
  $link = sharding_getConnection($shardKey, $table);
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  $table = query_setTableName($link['key'], $table);
  $query = 'INSERT IGNORE INTO ' . $table . '(';
  $strValues = ' VALUES (';
  $strField = '';
  foreach ($params as $field => $value)
  {
    $strField .= "`" . prepare_db($field) . "`,";
    $strValues .= "'" . prepare_db($value) . "',";
  }
  $strField = substr($strField, 0, -1) . ")";
  $strValues = substr($strValues, 0, -1) . ")";

  $query .= $strField . $strValues;

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $query . "\n", FILE_APPEND);
  }

  return mysqli_query($link['connect'], $query);
}

/**
 * Get last connection to mysql.
 *
 * @return mysqli_result
 */
function query_lastConnect()
{
  if (empty($GLOBALS['db_pull']))
  {
    return sharding_getConnection(1);
  }
  else
  {
    return end($GLOBALS['db_pull']);
  }
}

/**
 * Set table name.
 *
 * @param string $shardKey - key for sharding
 * @param string $table    - name of table
 *
 * @return string
 */
function query_setTableName($shardKey = '', $table = '')
{
  return (empty($shardKey) ? $table : $table . '_' . $shardKey);
}

/**
 * Batch insert data in table.
 *
 * @param string $shardKey      - key for sharding @see sharding_getConnection()
 * @param        $table         - table for inserting
 * @param        $params        - numeric (list) array with data to insert.
 *                              Example:
 *                              [
 *                              0=> [
 *                              'id' => 1,
 *                              'name' => 'Vasya'
 *                              ],
 *                              1 => [
 *                              'id' => 2,
 *                              'name' => 'Petya'
 *                              ]
 *                              ]
 *                              Also in batch insert dont use custom auto increment.
 *                              This function does not work with custom auto increment field.
 * @param array  $update        - array for part ON DUPLICATE KEY UPDATE
 *                              if consist #, then it part will be used as value.
 *                              Example:
 *                              '#count' => '`count`+1'
 * @param bool   $ignored       if true, then query will be with INSERT IGNORE
 *
 * @return bool
 */
function query_batchInsert($shardKey = '', $table, $params, $update = [], $ignored = false)
{
  if (empty($params))
  {
    return false;
  }
  $link = sharding_getConnection(
      $shardKey,
      $table
  );
  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  $table = query_setTableName(
      $link['key'],
      $table
  );

  if ($ignored)
  {
    $strInsert = "INSERT IGNORE INTO `" . $table . "` (";
  }
  else
  {
    $strInsert = "INSERT INTO `" . $table . "` (";
  }

  // get list of fields
  $strField = '';
  if (!empty($params)
      && is_array($params)
  )
  {
    if (!empty($params[0]))
    {
      foreach ($params[0] as $field => $trash)
        $strField .= "`" . prepare_db($field) . "`,";
    }
  }
  $strField = substr($strField, 0, -1) . ') ';

  // get list of values
  $strValues = ' VALUES ';
  for ($i = 0; $i < $ic = count($params); $i++)
  {
    if (!empty($params[$i])
        && is_array($params[$i])
    )
    {
      $strValues .= "('" . implode("','", array_map('prepare_db', $params[$i])) . "'),";
    }
  }
  $strValues = substr($strValues, 0, -1);

  $strUpdate = '';

  if (!empty($update))
  {
    $strUpdate = ' ON DUPLICATE KEY UPDATE ';
    foreach ($update as $field => $value)
    {
      if (substr($field, 0, 1) == '#')
      {
        $strUpdate .= "`" . prepare_db(substr($field, 1)) . "` = " . prepare_db($value) . ",";
      }
      else
      {
        $strUpdate .= "`" . prepare_db($field) . "` = '" . prepare_db($value) . "',";
      }
    }
  }
  $strUpdate = substr($strUpdate, 0, -1);

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $strInsert . $strField . $strValues . $strUpdate . "\n", FILE_APPEND);
  }
  // echo $strInsert . $strField . $strValues . $strUpdate;

  $res = mysqli_query($link['connect'], $strInsert . $strField . $strValues . $strUpdate);
  if (!$res)
  {
    return false;
  }
  else
  {
    return true;
  }
}

/**
 * Функция для вставки данных из массива $params методом REPLACE INTO
 *
 * @param string $shardKey      - ключ шардирования
 * @param        $table         - название таблицы
 * @param array  $params        - массив для вставки данных
 *                              Example:
 *                              [
 *                              0=> [
 *                              'id' => 1,
 *                              'name' => 'Vasya'
 *                              ],
 *                              1 => [
 *                              'id' => 2,
 *                              'name' => 'Petya'
 *                              ]
 *                              ]
 *                              Данная функция не работает с полями атоинкремента.
 *
 * @return bool
 */
function query_batchInsertReplace($shardKey = '', $table, $params = [])
{
  if (empty($params))
  {
    return false;
  }

  // получаем объект для подключения к БД
  $link = sharding_getConnection(
      $shardKey,
      $table
  );

  if (!$link['connect'])
  {
    if (function_exists('common_appLog'))
    {
      $file = __FILE__;
      $line = __LINE__;
      $function = __FUNCTION__;
      common_appLog($file, $line, $function, 'connect_db() вернула false');
    }
    else
    {
      return false;
    }
  }

  // устанавливаем значение таблицы
  $table = query_setTableName(
      $link['key'],
      $table
  );

  $sql = "REPLACE INTO `" . $table . "` (";

  // получаем список полей
  if (!empty($params)
      && is_array($params)
  )
  {
    if (!empty($params[0]))
    {
      foreach ($params[0] as $field => $trash)
        $sql .= "`" . prepare_db($field) . "`,";
    }
  }
  $sql = substr($sql, 0, -1) . ') VALUES ';

  // получаем список значений
  for ($i = 0; $i < $ic = count($params); $i++)
  {
    if (is_array($params[$i]))
    {
      $sql .= "('" . implode("','", array_map('prepare_db', $params[$i])) . "'),";
    }
  }
  $sql = substr($sql, 0, -1);

  if (!empty(DEBUG) && !empty(DEBUG_FILE))
  {
    file_put_contents(DEBUG_FILE, date('Y-m-d H:i:s') . " - " . $sql . "\n", FILE_APPEND);
  }

  // выполняем запрос
  $res = mysqli_query(
      $link['connect'],
      $sql
  );
  if (!$res)
  {
    return false;
  }
  else
  {
    return true;
  }
}