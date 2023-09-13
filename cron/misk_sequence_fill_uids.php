<?php

include_once(__DIR__ . '/../lib/autoload.php');

//Количество секунд, разрешённых для выполнения скрипта
$seconds = 3600;//Поставим пока час
$script_name = 'misk_sequence_fill_uids.php';

common_inc('exec');
$exec = new execService();

$obj_pid = $exec->get_pid("/$script_name/");

//Если такой процесс уже запущен, то выясняем время его работы
if ($obj_pid->pid !== NULL && $obj_pid->count > 1)
{
  $time = $exec->get_time_from_pid($obj_pid->pid);
  //Если процесс работает больше положенного
  if ($time !== NULL && $time > $seconds)
  {
    //Убиваем старый процесс
    $exec->kill_proc($obj_pid->pid);
    //Запускаем новый
    $exec->run_script($script_name);
  }
  else
  {
    exit("Данный процесс уже запущен. PID:" . $obj_pid->pid . ". Время работы: $time секунд.\r\n");
  }
}

function work1()
{
  /**
   * Заполняет табличку uuids_conditions_bitmaps идентификаторами пользователей,
   * которые ещё не присутствуют в таблице.
   * И устанавливает для нового uid битовую маску, исходя из результатов запросов
   * для этой маски.
   * Это постоянная задача, которая выполняется только для новых пользователей uuids.
   */

  //Получим конфигурацию
  $GLOBALS['conf'] = common_conf();
  //common_inc('system/cron', 'cron');

  //Подключим логгер
  //common_inc('logger');
  //$logger = new logger();


  //Подключаем класс парсер групп условий
  common_inc('misk_sequence', 'parse_conditions_to_query');
  $parse_conditions = new parseConditions();

  //$logger->start();


  /**
   * Получаем всех пользователей за ближайшие три месяца
   * Добавляем uuids в таблицу uuids_conditions_bitmaps, только для тех, которых
   * ещё нет в таблице, для этого можно применить INSERT IGNORE
   */

  /**
   * Выполняем нужные действия
   */
  $parse_conditions->fufucb();


//$memory = number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
//writeLog(basename(__FILE__), $logger->stop($memory));
}

work1();
