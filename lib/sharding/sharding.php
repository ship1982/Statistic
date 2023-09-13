<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 17.07.16
 * Time: 2:20
 */

/**
 * Get first day of the chosen month in timestamp format.
 *
 * @param bool $timestamp - current timestamp.
 * @return int - timestamp.
 */
function sharding_getFirstMonthDay($timestamp = false)
{
    if(empty($timestamp))
        return false;

    $year = (empty($year) ? date('Y', $timestamp) : $year);
    $month = (empty($month) ? date('m', $timestamp) : $month);
    return strtotime(date("$year-$month-01"));
}

/**
 * Get configuration array for mysqli connect.
 * Files with configuration is located in /config/sharding/list.php
 * If configuration is empty then select default configuration.
 * Is located in /config/sharding/default.php.
 *
 * @param bool $timestamp - timestamp for record
 * @param bool $table
 * @return array - configuration for connect to mysqli
 */
function sharding_getConfiguration($timestamp = false, $table = false)
{
    if(!file_exists(__DIR__ . '/../../config/sharding/list.php'))
    {
        include_once (__DIR__ . '/../error/error.php');
        error_show(1, 'sharding', [ 'file' => __FILE__, 'line' => __LINE__, 'function' => __FUNCTION__ ]);
    }

    $_timestamp = sharding_getFirstMonthDay($timestamp);
    $config = require (__DIR__ . '/../../config/sharding/list.php');
    $connection = [];

    if(!empty($config[$table][$_timestamp]))
        $connection = $config[$table][$_timestamp];
    if(empty($connection))
    {
        /** get default connection */
        if(!file_exists(__DIR__ . '/../../config/sharding/default.php'))
        {
            include_once (__DIR__ . '/../error/error.php');
            error_show(2, 'sharding', [
                'file' => __FILE__,
                'line' => __LINE__,
                'function' => __FUNCTION__
            ]);
        }

        $connection = require (__DIR__ . '/../../config/sharding/default.php');

        //если в базе появились новые шард-таблицы, а конфиг list.php не обновился
        if (is_int($_timestamp) && $_timestamp > 1)
        {
          $connection['db_key'] = $_timestamp;
        }
    }

    return $connection;
}

/**
 * Do connection to mysqli and return recourse to mysqli connect.
 *
 * @param bool $timestamp - timestamp for record
 * @param bool $table
 * @return mysqli_result
 */
function sharding_getConnection($timestamp = false, $table = false)
{
    $connection = sharding_getConfiguration($timestamp, $table);
    if(empty($connection['db_user'])
        || empty($connection['db_pass'])
        || empty($connection['db_name'])
        || empty($connection['db_type'])
    )
    {
        include_once (__DIR__ . '/../error/error.php');
        error_show(3, 'sharding', [ 'file' => __FILE__, 'line' => __LINE__, 'function' => __FUNCTION__
        ]);
    }

    if(empty($connection['db_key']))
        $shardKey = false;
    else
        $shardKey = $connection['db_key'];

    $hash = md5($connection['db_user'] . $connection['db_pass'] . $connection['db_name'] . $connection['db_type'] . $shardKey);
    if(!empty($GLOBALS['db_pull'][$hash]))
        return $GLOBALS['db_pull'][$hash];
    else
    {
        $GLOBALS['db_pull'][$hash]['connect'] = mysqli_connect(
            $connection['db_type'],
            $connection['db_user'],
            $connection['db_pass'],
            $connection['db_name']
        );
        if (!$GLOBALS['db_pull'][$hash]['connect'])
        {
            include_once (__DIR__ . '/../error/error.php');
            error_show(4, 'sharding', [ 'file' => __FILE__, 'line' => __LINE__, 'function' => __FUNCTION__ ]);
        }

        mysqli_query($GLOBALS['db_pull'][$hash]['connect'], "SET NAMES 'utf8'");
        $GLOBALS['db_pull'][$hash]['key'] = $shardKey;

        return $GLOBALS['db_pull'][$hash];
    }
}

/**
 * Get a unique id.
 *
 * @param string $stub
 * @return string
 */
function sharding_getUniqueId($stub = 'a')
{
    common_inc('_database');
    $res = select_db(
        1, 'Tickets64', ['*'], [
            'stub' => $stub
    ]);
    if($a = mysqli_fetch_assoc($res))
        return (int) $a['id'];
    else
    {
        include_once (__DIR__ . '/../error/error.php');
        error_show(5, 'sharding', [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]);
    }

    return false;
}

/**
 * Set a unique id.
 *
 * @param string $stub
 * @return mixed
 */
function sharding_setUniqueId($stub = 'a')
{
    common_inc('_database');
    $link = query_lastConnect();
    mysqli_query($link['connect'], 'REPLACE INTO Tickets64 (stub) VALUES (\''.$stub.'\')');
    $a = mysqli_query($link['connect'], 'SELECT LAST_INSERT_ID() as id');
    $b = mysqli_fetch_assoc($a);
    if(empty($b['id']))
    {
        include_once (__DIR__ . '/../error/error.php');
        error_show(5, 'sharding', [
            'file' => __FILE__,
            'line' => __LINE__,
            'function' => __FUNCTION__
        ]);
    }
    return (int) $b['id'];
}

/**
 * Функция возвращает массив ключей, являющихся ключами для шардированных таблиц.
 * Поиск списка шардированных таблиц выполняется в конфиг файле.
 * @param string $table_name - название шардированной таблицы
 * @param int $time_start - начало временного интервала
 * @param int $time_end - конец временного интервала
 * @param bool $add_pref - Если true, то к номерам таблиц будут подставлятся префикс названия таблицы в виде table_name_
 * @return array
 */
function sharding_getShardTableList($table_name = '', int $time_start = 0, int $time_end = 0, $add_pref = false) {
	
		$arr_sharding = [];
		
		//Если начальная дата больше конечной, то поменяем их местами
		if($time_start > $time_end){
			list($time_start, $time_end)= [$time_end, $time_start];
		}
	
		$arr_time_start = [];
		$arr_time_end = [];

		//Получим список всех шардированных таблиц, которые будет филтровать по времени
		if (!empty($GLOBALS['conf']) && is_file($GLOBALS['conf']['root'] . '/config/sharding/list.php')) {
				$arr_sharding = include($GLOBALS['conf']['root'] . '/config/sharding/list.php');

				$arr_sharding = (!empty($arr_sharding[$table_name])) ? array_keys($arr_sharding[$table_name]) : [];
		}

/*
		$list_tables = [];

		for ($i = 0; $i < count($arr_sharding); $i++) {
				//Вычисляем максимально приближенное к минимуму слева
				if ($arr_sharding[$i] <= $time_start) {
						$arr_time_start[] = $arr_sharding[$i];
				}
		}

		for ($i = 0; $i < count($arr_sharding); $i++) {
				//Вычисляем, начиная от приближенного минимума, до возможного максимума
			/**
			 * Если значение элемента массива меньше $time_end
			 * и значение элемента массива больше или равно $time_start
			 * или значение элемента массива равен самому приближённому к минимуму.
			 */
    /*
				if ($arr_sharding[$i] <= $time_end && ($arr_sharding[$i] >= $time_start || $arr_sharding[$i] == max($arr_time_start))) {
						$arr_time_end[] = $arr_sharding[$i];
				}
		}
*/
		$list_tables = sharding_getArrLimit($arr_sharding, $time_start, $time_end);
    
    //Если необходимо добавить перфикс названия таблицы
    if($add_pref){
      $list_tables = [];
      for($i=0; $i < count($arr_time_end); $i++){
        $list_tables[] = $table_name.'_'.$arr_time_end[$i];
      }
    }
		
		return $list_tables;
}

/**
 * Функция возвращает массив, содержащий названия шардированных таблиц по
 * началу названия таблицы.
 * Поиск в бд выполняется в виде: show tables like '$shard_name%'
 * @param string $shard_name - начало названия шардированной таблицы.
 * @param int $time_start - начало временного интервала
 * @param int $time_end - конец временного интервала
 * @param boolean $ret_num - если true, то результирующий массив будет содержать
 * только постфиксы названия шардированной таблицы.
 * @return array
 */
function sharding_getShardTableListFromDb(string $shard_name = '', int $time_start = 0, int $time_end = 0, $ret_pref = false){
  if(empty($shard_name)){return [];}
  
  $mysqliResult = simple_query("show tables like '$shard_name%'");
  $shard_list_tables = [];
  $shard_list_num = [];
  
  if(!$mysqliResult){return [];}
  
  //Получим общий список шардированной таблицы
  while($row = $mysqliResult->fetch_array(MYSQLI_NUM)){
    $shard_list_tables[] = $row[0];
    $num_shard = explode($shard_name, $row[0]);
    if(!empty($num_shard) && array_key_exists(1, $num_shard)){
      $shard_list_num[] = $num_shard[1];
    }
  }
  
  if($time_start == 0 && $time_end == 0){
    return (!$ret_pref)?$shard_list_tables:$shard_list_num;
  }else{
    $data = sharding_getArrLimit($shard_list_num, $time_start, $time_end);
    $data2 = [];
    for($i = 0; $i < count($data); $i++){
      $data2[] = $shard_name.$data[$i];
    }
    return $data2;
  }
}

/**
 * 
 * @param array $arr
 * @param int $start
 * @param int $end
 * @return array
 */
function sharding_getArrLimit($arr, int $start, int $end){
  $arr_start = [];
  $arr_end = [];
  
  for ($i = 0; $i < count($arr); $i++) {
    //Вычисляем максимально приближенное к минимуму слева
    if ($arr[$i] <= $start) {
        $arr_start[] = $arr[$i];
    }
  }
  
  for ($i = 0; $i < count($arr); $i++) {
    //Вычисляем, начиная от приближенного минимума, до возможного максимума
    /**
    * Если значение элемента массива меньше $end
    * и значение элемента массива больше или равно $start
    * или значение элемента массива равен самому приближённому к минимуму.
    */
     if ($arr[$i] <= $end && ($arr[$i] >= $start || $arr[$i] == max($arr_start))) {
         $arr_end[] = $arr[$i];
     }
  }
  //var_dump([$arr, $start, $end, $arr_start, $arr_end]);
  return $arr_end;
}

/**
 * Возвращает список постфиксов для диаппазона дат.
 *
 * @param int $timeFrom
 * @param int $timeTo
 *
 * @return array
 */
function sharding_getShards($timeFrom = 0, $timeTo = 0)
{
  $result = [];
  $monthPeriod = 86400 * 32; // чтобы наверняка
  // определяем начало месяца отчета
  $startTime = strtotime(date("Y-m-01", (int)$timeFrom));
  // определеям конец периода
  $endTime = strtotime(date("Y-m-01", (int)$timeTo));
  // получаем между датами периоды
  if ($startTime == $endTime)
  {
    return [$startTime];
  }
  else
  {
    $compare = $startTime;
    $result[] = $compare;
    do
    {
      $compare += $monthPeriod;
      $compare = strtotime(date("Y-m-01", $compare));
      $result[] = $compare;
    } while ($compare < $endTime);
    if (!in_array($endTime, $result))
    {
      $result[] = $timeTo;
    }

    return $result;
  }
}