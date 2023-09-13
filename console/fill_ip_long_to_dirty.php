<?php

  require (__DIR__ . '/../lib/common/common.php');
  
  $GLOBALS['conf'] = common_conf();
  
  common_inc('_database');
  common_inc('sharding');
  common_inc('ip_conv');
  common_inc('timer');
  $timer = new timerPrint();
  
  $list_dirty_table = [];//Список шардированных таблиц dirty
  $sel_ip_dirty = [];//Данные из шардированной таблицы dirty
  $cnt_sel = 0;
  
  
  $list_dirty_table_q = simple_query("show tables like 'dirty_%'");
  if($list_dirty_table_q){
    while($row = $list_dirty_table_q->fetch_array(MYSQLI_NUM)){
      $list_dirty_table[] = $row[0];
    }
  }
  
  if(empty($argv[1]))
  {
      echo "\n\nСправка\n\n";
      echo "Укажите название шардированной таблицы, таблицы могут быть следующими:\n";
      print_r($list_dirty_table);
      echo "\n";
      exit;
  }
  
  //Проверим что таблица dirty существует
  if(!in_array($argv[1], $list_dirty_table)){
    print("\r\nТаблицы '".$argv[1]."' не существует, таблицы могут быть следующими:\r\n");
    print_r($list_dirty_table);
    exit;
  }
  
  
  print("\r\nПросматривается таблица: ".$argv[1]."\n");
  $timer->start('get');
  $sel_ip_dirty_query = select_db(1, $argv[1], ['id','ip']);
  $timer->stop('get', "Время выполнения: %time% ms\n");
  
  if($sel_ip_dirty_query){
    $timer->start('get', "Получение резльтата выборки: ");
    $cnt_sel += $sel_ip_dirty_query->num_rows;
    $sel_ip_dirty = $sel_ip_dirty_query->fetch_all(MYSQLI_ASSOC);
    $sel_ip_dirty_query->free_result();
    
    $timer->stop('get', "Время выполнения: %time% ms\n");
    
    //Разобъём на части, чтобы процесс не убивался
    $timer->start('chunk', "Разбивка резльтата выборки: ");
    //$sel_ip_dirty = array_chunk($sel_ip_dirty, 10000);
    $timer->stop('chunk', "Время выполнения: %time% ms\n");
    $arr_result = [];
    
    $tmp_percent_counter = 0;
    $percent_counter = 0;
    
    print('Всего будет обновлено записей: '.count($sel_ip_dirty)."\n");
    $timer->start('change', 'Подготовка/Обновление массива для обновления: ');
    for($i = 0; $i < count($sel_ip_dirty); $i ++){
        $percent_counter = round(($i / count($sel_ip_dirty) * 100), 0);
        
        $update = [
            'ip_long' => ip_conv_ip_to_binary_32(long2ip($sel_ip_dirty[$i]['ip']))
        ];
        
        update_db(1, $argv[1], $update, ['id' => $sel_ip_dirty[$i]['id']]);
      if ($tmp_percent_counter != $percent_counter) {
        $tmp_percent_counter = $percent_counter;
        print("\r\nВыполнено $percent_counter%\r\n");
      }
    }
    $timer->stop('change', "Время выполнения: %time% ms\n");
    
  }else{
    exit("\nНе удалось получить данные из таблицы: ".$argv[1]."\n");
  }
  