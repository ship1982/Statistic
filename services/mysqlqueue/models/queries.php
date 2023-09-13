<?php

include_once(__DIR__ . '/../../../lib/common/common.php');

if (!function_exists('getDBConnection'))
{
  /**
   * Connection to db.
   *
   * @param type !function_exists('getDBConnection')
   *
   * @return void
   */
  function getDBConnection()
  {
    common_inc('_database');
  }
}

if (!function_exists('getTable'))
{
  /**
   * Get table name for queue.
   *
   * @param string $queue - name of queue.
   *
   * @return string
   */
  function getTable($queue = '')
  {
    if (empty($queue))
    {
      return '';
    }
    return 'queue_' . $queue;
  }
}

if (!function_exists('mysqliqueue_set'))
{
  /**
   * Set queue data.
   *
   * @param array $data - array with keys:
   *                    `queue` - name of queue
   *                    `state` - state of queue
   *                    `param` - data for queue
   *
   * @return string
   */
  function mysqliqueue_set($data = [])
  {
    $key = '';
    $queue = common_setValue($data, 'queue');
    $table = getTable($queue);
    if (!empty($table))
    {
      $param = common_setValue($data, 'param');
      $state = common_setValue($data, 'state', 0);
      $id = $key = md5(uniqid() . rand(0, 1000));
      getDBConnection();
      insert_db(
          1,
          $table, [
              'id' => $id,
              'state' => $state,
              'param' => $param,
              'time' => (empty($data['time']) ? time() : $data['time'])
          ]
      );
    }

    return json_encode(['queue' => $key]);
  }
}

if (!function_exists('mysqliqueue_update'))
{
  /**
   * Update queue data.
   *
   * @param array $data - array with keys:
   *                    `queue` - name of queue
   *                    `id` - id of queue
   *                    `state` - state of queue
   *                    `param` - data for queue
   *
   * @return string
   */
  function mysqliqueue_update($data = [])
  {
    $id = $key = common_setValue($data, 'id');
    $queue = common_setValue($data, 'queue');
    $table = getTable($queue);
    if (!empty($table))
    {
      $param = common_setValue($data, 'param');
      $state = common_setValue($data, 'state');
      $update = [
          'state' => $state,
          'param' => $param,
          'time' => time()
      ];
      // если param пустой, то не надо это поле обновлять
      if (empty($update['param']))
      {
        unset($update['param']);
      }

      getDBConnection();
      update_db(
          1,
          $table, $update, [
              'id' => $id
          ]
      );
    }

    return json_encode(['queue' => $key]);
  }
}

if (!function_exists('mysqliqueue_delete'))
{
  /**
   * Delete queue data.
   *
   * @param array $data - array with keys:
   *                    `queue` - name of queue
   *                    `id` - id of queue
   *
   * @return bool
   */
  function mysqliqueue_delete($data = [])
  {
    $id = common_setValue($data, 'id');
    $queue = common_setValue($data, 'queue');
    $table = getTable($queue);
    if (!empty($table))
    {
      getDBConnection();
      delete_db(
          1,
          $table, [
              'id' => $id
          ]
      );
    }

    return true;
  }
}

if (!function_exists('mysqliqueue_get'))
{
  /**
   * Get data form queue.
   *
   * @param array $data - array with keys:
   *                    `queue` - name of queue
   *                    `state` - state of queue
   *                    `param` - data for queue
   *
   * @return string
   */
  function mysqliqueue_get($data = [])
  {
    $queue = common_setValue($data, 'queue');
    $state = common_getVariable($data, ['state'], 0);
    $where = common_getVariable($data, ['where'], '1=1');
    $limit = common_getVariable($data, ['limit'], 1000);

    if (empty($queue))
    {
      return "[]";
    }
    $table = getTable($queue);
    getDBConnection();
    $sql = "SELECT * WHERE $where AND `state` = '$state' ORDER BY `time` LIMIT $limit";

    $o = query_db(1, $table, $sql);
    $data = [];
    if (!empty($o))
    {
      while ($a = mysqli_fetch_assoc($o))
        $data[] = $a;
    }

    return json_encode($data);
  }
}

if (!function_exists('mysqliqueue_get_no_json'))
{
  /**
   * Возвращает 1000 записей из очереди без преобразования в JSON строку
   *
   * @param array $data - Массив параметров с ключами
   *                    'queue' - Название очереди
   *                    'state' - Статус обработки записей в очереди
   *
   * @return array
   */
  function mysqliqueue_get_no_json($data = [])
  {
    $queue = common_setValue($data, 'queue');
    $state = common_getVariable($data, ['state'], 0);
    $where = common_getVariable($data, ['where'], '1=1');
    $limit = common_getVariable($data, ['limit'], 1000);


    if (empty($queue))
    {
      return [];
    }
    $table = getTable($queue);
    getDBConnection();

    $sql = "SELECT * WHERE $where AND `state` = '$state' ORDER BY `time` LIMIT $limit";
    $o = query_db(1, $table, $sql);

    return return_mysqli_results($o);
  }
}

if (!function_exists('mysqliqueue_get_first_time'))
{
  /**
   * Возвращает время первой записи в очереди
   *
   * @param array $data - Массив параметров с ключами
   *                    'queue' - Название очереди
   *                    'state' - Статус обработки записей в очереди
   *
   * @return int
   */
  function mysqliqueue_get_first_time($data = [])
  {
    $queue = common_setValue($data, 'queue');
    $state = common_setValue($data, 'state');

    if (!empty($state))
    {
      $where = "`state` = '$state'";
    }
    else
    {
      $where = "1=1";
    }

    if (empty($queue))
    {
      return "[]";
    }
    $table = getTable($queue);
    getDBConnection();
    $sql = "SELECT MIN(`time`) AS `time` WHERE $where LIMIT 0,1";
    $o = query_db(1, $table, $sql);

    $time = return_mysqli_results($o);

    return common_getVariable($time, [
        '0',
        'time'
    ], 0);
  }
}