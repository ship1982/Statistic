<?php
class execService{
  
  /**
   * Возвращает PID первого найденного процесса, по его имени.
   * @param string $proc_name - название процесса, пример: /misk_sequence_fill_uids.php/
   * @return int|null
   */
  public function get_pid(string $proc_name){
    $pid = null;
    exec("ps ux | awk '$proc_name && !/awk/ {print $2}'", $output, $return);//находим пид процесса
    if(!empty($output[0])){
      $pid = $output[0];
    }
    return (object)['count' => count($output), 'pid' => $pid];
  }
  
  /**
   * Возвращает время работы процесса по его PID, в секундах
   * @param int $pid - идентификатор процесса
   * @return int|null
   */
  public function get_time_from_pid($pid){
    $pid = (int)$pid;
    $time = null;
    exec("ps -eo pid,time | grep $pid | awk '{print $2}'", $output, $return);
    if(!empty($output[0])){
      $time = $this->parse_time_to_sec($output[0]);
    }
    return $time;
  }
  
  /**
   * Выполняет запуск скрипта в консоли, пример: php misk_sequence_fill_uids.php
   * @param string $proc_name - misk_sequence_fill_uids.php
   * @return boolean
   */
  public function run_script(string $proc_name){
    exec("php $proc_name");
    return bool;
  }

  /**
   * Убивает процесс
   * @param int $pid
   * @return boolean
   */
  public function kill_proc($pid){
    $pid = (int)$pid;
    exec("kill ".$pid);
    return true;
  }
  
  /**
   * Возвращает количество секунд пройденного времени
   * @param string $string - строка в виде 00:00 или 00:00:00
   * @return int|null
   */
  public function parse_time_to_sec($string){
    $arr_time = explode(":", $string);
    $res = 0;

    if(count($arr_time) == 2){//00:02 - 
      $res = (int)($arr_time[0] * 60) + (int)$arr_time[1];
    }elseif(count($arr_time) == 3){//21:08:53
      $res = (int)($arr_time[0] * 60 * 60) + (int)($arr_time[1] * 60) + (int)$arr_time[2];
    }else{
      return null;
    }
    
    return $res;
  }
}

