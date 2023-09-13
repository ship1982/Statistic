<?php

include_once(__DIR__ . '/../lib/autoload.php');

\tools\Execution::killByMaxExecutionTime(
    basename(__FILE__),
    7200,
    function ($script, $pid, $time) {
      mail("hva@zionec.ru", "Скрипт $script", "Скрипт $script уже запущен.\nЕго pid: $pid.\nВремя исполнения: $time\n");
    }
);

function work1()
{
  /**
   * Здесь выполняется добавление/изменение/удаление значений битовых масок,
   * исходя из изменения таблицы list_sequence_conditions.
   * Это постоянная задача, которая выполняется только для тех записей,
   * статус которых равен 1,2,3 - если статуса нет, то операций не производится.
   */

//Получим конфигурацию
  $GLOBALS['conf'] = common_conf();
  common_inc('system/cron', 'cron');

//Подключим логгер
  common_inc('logger');
  $logger = new logger();

//Подключаем библиотеку работы с БД
  common_inc('_database');

//Подключаем класс парсер групп условий
  common_inc('misk_sequence', 'parse_conditions_to_query');
  $parse_conditions = new parseConditions();

  /**
   * Получаем список всех групп условий.
   * По факту, разрядность битовой маски равна сумме найденных добавленных
   * и изменённых строк.
   */
  $count_group_conditions = 0; //Количество групп условий

  $logger->start();

  $logger->add_event('select_list_sequence_conditions');

  //Выборка всех групп условий
  $group_conditions = $parse_conditions->getAllCondition();

  /**
   * 1. Отделяем новые условия => state = 1.
   * 2. Проходим по всем битовым маскам, выполняем соответсвующий запрос к
   * таблице list_sequence_conditions
   * и добавляем битовый результат к битовой маске в таблице
   * uuids_conditions_bitmaps.
   */
  $conditions_add = [];

  /**
   * Отделяем изменённые условия => state = 2.
   * Принцип почти такой же, как и при добавлении, за исключением, того что нужно
   * не добавлять в битовую маску элемент, а изменять, существующий.
   */
  $conditions_upd = [];

  /**
   * Отделяем удаляемые условия  => state = 3.
   * Здесь принцип иной - нужно просто найти порядковый бит в маске и удалить
   * его для всех записей в таблице uuids_conditions_bitmaps.
   */
  $condition_del = [];

  $logger->add_event('filter_conditions');

  /**
   * Разносим элементы массива в соответствии со статусом
   */
  for ($i = 0; $i < count($group_conditions); $i++)
  {
    if ($group_conditions[$i]['state'] == 1)
    {
      $conditions_add[] = $group_conditions[$i];
      $count_group_conditions++;
    }
    elseif ($group_conditions[$i]['state'] == 2)
    {
      $conditions_upd[] = $group_conditions[$i];
      $count_group_conditions++;
    }
    elseif ($group_conditions[$i]['state'] == 3)
    {
      $condition_del[] = $group_conditions[$i];
    }
  }
  $logger->add_event('end_fill_condition');
  if (empty($conditions_add)
      && empty($conditions_upd)
      && empty($condition_del)
  )
  {
  }
  else
  {
    $parse_conditions->bitmapsJob([
        [
            'condition' => $conditions_add,
            'type' => 1
        ],
        [
            'condition' => $conditions_upd,
            'type' => 2
        ],
        [
            'condition' => $condition_del,
            'type' => 3
        ],
    ], count($group_conditions));
  }


  $memory = number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
  writeLog(basename(__FILE__), $logger->stop($memory));
}

work1();