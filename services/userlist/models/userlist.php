<?php

/**
 * Получаем список служебных переменных из файла __DIR__ . '/../config/variables.php'
 * 
 * @return type
 */
if(!function_exists('userlist_getVariables'))
{
  function userlist_getVariables()
  {
    if(file_exists(__DIR__ . '/../config/variables.php'))
      return require(__DIR__ . '/../config/variables.php');
    else
      return [];
  }
}

/**
 * Получаем список полей и их наименований из файла __DIR__ . '/../config/fields.php'
 * 
 * @return type
 */
if(!function_exists('userlist_getFields'))
{
  function userlist_getFields()
  {
    if(file_exists(__DIR__ . '/../config/fields.php'))
      return require(__DIR__ . '/../config/fields.php');
    else
      return [];
  }
}

/**
 * trim(strip_tags()).
 * 
 * @param type|string $value - строка для обработки
 * @return type
 */
if(!function_exists('userlist_striptrim'))
{
  function userlist_striptrim($value = '')
  {
    return trim(strip_tags($value));
  }
}

/**
 * Метод для вывода списка событий.
 * 
 * @param type|array $data - передаваемые данные из сервиса.
 * @return type
 */
if(!function_exists('userlist_userlistList'))
{
  function userlist_userlistList($data = [])
  {
    include_once(__DIR__ . '/database.php');
    $variables = userlist_getVariables();
    $fields = userlist_getFields();

    $result = [];

    if(!empty($data['action']))
      unset($data['action']);

    // получаем список данных по ключу
    if(!empty($data)
      && is_array($data)
      && !empty($variables['atable'])
    )
    {
      $sql = "SELECT `hash`,`list`,`datehour`,`partner`,`domain`, `count_bot`,`count_ad` WHERE ";
      // для времени подсчет должен быть свой
      if(!empty($data['datehour']))
      {
        if(strpos($data['datehour'], ':') !== false)
        {
          list($from, $to) = explode(':', $data['datehour']);
          $sql .= " `datehour` BETWEEN '$from' AND '$to' AND ";
        }

        // удаляем параметр
        unset($data['datehour']);
      }

      // формируем условие where
      foreach ($data as $key => $value)
      {
        if(is_array($value))
          $sql .= " `$key` IN ('" . implode("','", $value) . "') AND ";
        else
          $sql .= " `$key`='$value' AND ";
      }

      $sql = substr($sql, 0, -5);

      $o = query_db(
        1,
        $variables['atable'],
        $sql
      );

      if(!empty($o))
      {
        while ($a = mysqli_fetch_assoc($o))
          $result['items'][] = $a;
      }
    }

    return json_encode($result);
  }
}

/**
 * Сравнивает переданные ключи в массиве (данные, переданные сервису) и возвращает лишь те, которые указаны в конфигурационном файле.
 * 
 * @param type|array $data - массив переданных сервисом данных
 * @return type
 */
if(!function_exists('userlist_validateField'))
{
  function userlist_validateField($data = [])
  {
    $fields = userlist_getFields();
    if(empty($data)) return [];
    if(is_array($data))
    {
      foreach ($data as $key => $value)
      {
        // проверяем, что значения у нас есть
        if(!empty($fields[$key]))
          $result[$key] = $value;
      }
    }

    return $result;
  }
}

/**
 * Функция добавляет запись в таблицу.
 * 
 * @param type|array $data - список полей для вставки
 * @return type
 */
if(!function_exists('userlist_addNode'))
{
  function userlist_addNode($data = [])
  {
    if(empty($data)) return false;
    include_once __DIR__ . '/database.php';
    $variables = userlist_getVariables();
    $fields = userlist_getFields();
    // если есть таблица
    if(!empty($variables['table']))
    {
      $sql = "INSERT INTO `$variables[table]` (`" . implode("`,`", $fields) . "`) VALUES (";
      foreach ($data as $key => $value)
      {
        if(!empty($fields[$key]))
          $sql .= "'$value',";
      }

      $sql = substr($sql, 0, -1) . ")";

      $answer = simple_query($sql);
    }
  }
}

/**
 * Функция для вставки данных с проверкой.
 * 
 * @param type|array $data - список полей для вставки
 * @return type
 */
if(!function_exists('userlist_addList'))
{
  function userlist_addList($data = [])
  {
    if(empty($data)) return false;
    $data = userlist_validateField($data);
    if(!empty($data))
    {
      // если все поля присутсвуют, то делаем запрос на добавление
      return userlist_addNode($data);
    }

    return false;
  }
}

/**
 * Обновляем статус у записей.
 * 
 * @param type|array $ids - массив записей
 * @param type|string $state - желаемый статус
 * @return type
 */
if(!function_exists('userlist_updateState'))
{
  function userlist_updateState($ids = [], $state = '2')
  {
    if(empty($ids)) return false;
    include_once(__DIR__ . '/database.php');
    $variables = userlist_getVariables();
    
    if(!empty($variables['table']))
    {
      if(is_array($ids))
      {
        $sql = "UPDATE `$variables[table]`
          SET `state`='$state'
          WHERE `id` IN ('" . implode("','", $ids) . "')";

        $a = simple_query($sql);

        return true;
      }
    }

    return false;
  }
}

/**
 * Получаем данные из таблицы онлайн юзеров.
 * 
 * @return type
 */
function userlist_getOnlineData()
{
  $result = [];
  $variables = userlist_getVariables();
  include_once(__DIR__ . '/database.php');
  if(!empty($variables['table'])
    && !empty($variables['limit'])
  )
  {
    $sql = "SELECT * 
    WHERE `state` = 1
    LIMIT $variables[limit]";

    $o = query_db(
      1,
      $variables['table'],
      $sql
    );

    if(!empty($o))
    {
      while($a = mysqli_fetch_assoc($o))
      {
        $result['data'][] = $a;
        $result['ids'][] = $a['id'];
      }
    }
  }

  return $result;
}

/**
 * Функция для подготовки данных для вставки в агрегирующую таблицу.
 * 
 * @param type|array $data - список данных из @see userlist_getOnlineData()
 * @return type
 */
function userlist_prepareData4AggergationTable($data = [])
{
  $result = [];
  if(!empty($data)
    && is_array($data)
  )
  {

    for ($i=0; $i < $ic = count($data); $i++)
    { 
      // собираем id пользователей и данные по партнеру
      // создаем формальный ключ
      if(!empty($data[$i]['datehour']))
      {
        $key = md5($data[$i]['datehour'] . $data[$i]['partner'] . $data[$i]['domain']); // ключ имеет строгий порядок. в БД он хранится именно в таком порядке
        // добавляем uuid в массив
        $result[$key]['list'][$data[$i]['uuid']] = $data[$i]['uuid'];

        // добавляем все сотальные ключи
        $result[$key]['hash'] = $key;
        $result[$key]['datehour'] = $data[$i]['datehour'];
        $result[$key]['partner'] = $data[$i]['partner'];
        $result[$key]['domain'] = $data[$i]['domain'];
      }
    }
  }

  return $result;
}

/**
 * Конвертирует массив uuids в строку.
 * 
 * @param type|array $data - список данных
 * @return type
 */
function userlist_prepareUUIDSList($data = [])
{
  if(empty($data)
    || !is_array($data)
  )
    return [];

  foreach ($data as $id => $info)
  {
    // если не пустой список uuids, то формируем его как строку, для разбития по explode
    if(!empty($info['list']))
      $data[$id]['list'] = implode(",", $info['list']);
  }

  return array_values($data);
}

/**
 * Непосредственная вставка данных в агрегирующую таблицу.
 * 
 * @param type|array $data - список данных для вставки
 * @return type
 */
function userlist_addNode2AggregationTable($data = [])
{
  $result = false;
  if(empty($data)
    || !is_array($data)
  )
    return $result;

  include_once __DIR__ . '/database.php';
  $variables = userlist_getVariables();

  if(!empty($variables['atable']))
  {
    $result = query_batchInsertReplace(
      1,
      $variables['atable'],
      $data
    );
  }

  return $result;
}

/**
 * Основная функция добавления записей в агрегирующую таблицу.
 */
function userlist_addInAggergationTable()
{  
  // выбираем все данные (с учетом лимита)
  while($result = userlist_getOnlineData())
  {
    // подготавливаем данные для вставки
    $preparedData = userlist_prepareData4AggergationTable($result['data']);
    // получаем данные из агругирующей таблицы по id
    if(!empty($preparedData))
    {
      foreach ($preparedData as $id => $info)
      {
        // получаем список пользователей за нужный период
        $resUserList = userlist_userlistList(['hash' => $id]);
        if(!empty($resUserList)
          && is_string($resUserList)
        )
        {
          $userList = json_decode($resUserList, JSON_UNESCAPED_UNICODE);
          if(!empty($userList['items']))
          {
            // мержим данные с только что полученными
            for ($i=0; $i < $ic = count($userList['items']); $i++)
            { 
              if(!empty($userList['items'][$i]['list']))
              {
                $userArray = explode(",", $userList['items'][$i]['list']);
                $userListMerged = array_merge(
                  $userArray,
                  array_values($preparedData[$id]['list'])
                );

                $preparedData[$id]['list'] = array_unique($userListMerged);
              }
            }
          }
        }
      }

      // добавляем данные в агрегирующую таблицу
      $finalData = userlist_prepareUUIDSList($preparedData);
      $resultData = userlist_addNode2AggregationTable($finalData);

      // меняем статус у записей, если добавление прошло успешно
      if($resultData)
        userlist_updateState(
          $result['ids'],
          3
        );
    }
  }
}