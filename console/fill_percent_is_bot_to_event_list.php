<?php
error_reporting(E_ALL);
/**
 * Проходит по всем записям таблицы user_property и обновляет поле percent_is_bot,
 * устанавливая в это поле процент вероятности того, что пользователь являтеся ботом.
 * Время выполнения примерно 10 минут.
 */

  require (__DIR__ . '/../lib/common/common.php');
  
  $GLOBALS['conf'] = common_conf();
  
  common_inc('_database');
  common_inc('timer');
  common_inc('api/repeate_actions', 'repeate_actions');
  
  $timer = new timerPrint();
  $timer->start('work');
  $tmp_percent_counter = 0;
  $percent_counter = 0;

  /**
   * Счётчик текущей позиции строк
   */
  $counterSelfRows = 0;
  /**
   * Количество строк за один запрос
   */
  $partRows = 1000;
  
  /**
   * Т.к. процедура затратная, съедает многовато памяти даже для одного запуска.
   * Делаем так:
   * Получаем количество записей в таблице и выбираем по частям,
   * и для этих частей уже аккуратненько делаем апдейты, чтобы в памяти меньше данных хранилось.
   */

    //Получим общее колиество записей
    $timer->start('getCountRows');
      $mysqliResult1 = simple_query('SELECT COUNT(0) FROM `user_property`');
      $countRows = (int)common_getVariable(return_mysqli_results($mysqliResult1), ['0', 'COUNT(0)'], 0);
    $timer->stop('getCountRows', "Время подсчёта строк: %time% ms\n");
    
    while($counterSelfRows < ($countRows - $partRows)) {
      $timer->start('get');
        $mysqliResult2 = select_db(1, 'user_property', ['uuid','percent_is_bot'], [/*'uuid'=> '03340C0A096F28594C5FC45C02120403'*/], [], "$counterSelfRows, $partRows");
        $users = return_mysqli_results($mysqliResult2);
      $timer->stop('get', "Время получения данных: %time% ms\n");
      
      $cntUsers = count($users);
      
      $percent_counter = round(($counterSelfRows / $countRows * 100), 1);
          
      $timer->start('update_field');
        for($i=0; $i<$cntUsers; $i++){

          $uuid = common_getVariable($users, [$i, 'uuid'], '');
          if(empty($uuid)){continue;}

          $repeateActions = new repeateActions();
          $repeateActions->setter_time_end(time());
          $repeateActions->setter_type_access_site('for_uuid');
          $repeateActions->setter_uuid($uuid);        

          $percent_is_bot = $repeateActions->getter_isbot_percent();
          $is_bot = ($percent_is_bot >= 90)?1:0;

          $resUpd = update_db(1, 'user_property', ['percent_is_bot' => $percent_is_bot, 'is_bot' => $is_bot], ['uuid' => $uuid]);
          //$resUpd = simple_query("UPDATE `user_property` SET `percent_is_bot` = '$percent_is_bot', `is_bot` = '$is_bot'");
          //var_dump(['uuid' => $uuid, 'percent_is_bot' => $percent_is_bot, 'is_bot' => $is_bot, 'res_upd' => $resUpd, 'params' => $repeateActions->get_params()]);

        }
      $timer->stop('update_field', "Время обновления поля для пакета строк: %time% ms\n");
      
      if ($tmp_percent_counter != $percent_counter) {
        $tmp_percent_counter = $percent_counter;
        print("\r\nВыполнено $percent_counter%, обработано строк: $counterSelfRows из $countRows. \r\n");
      }
      $counterSelfRows += $partRows;
    }
  
  $timer->stop('work', "Время выполнения скрипта: %time% ms\n");
