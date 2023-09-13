<?php
/**
 * Раз появилась возможность заюзать 
 */

//ini_set('xdebug.default_enable', 'On');
//ini_set('xdebug.show_exception_trace', 'On');
//ini_set('xdebug.show_local_vars', '1');
//ini_set('xdebug.max_nesting_level', '50');
//ini_set('xdebug.var_display_max_depth', '6');
//ini_set('xdebug.dump_once', 'On');
//ini_set('xdebug.dump_globals', 'On');
//ini_set('xdebug.dump_undefined', 'On');
//ini_set('xdebug.dump.REQUEST', '*');
 
//ini_set('xdebug.dump.SERVER', 'REQUEST_METHOD,REQUEST_URI,HTTP_USER_AGENT');


common_inc('ip_conv');
common_inc('_database');
common_inc('sharding');

/**
 * Класс выполняет сбор данных частотности обращений к страницам
 * Что использует:
 * @uses ip_conv
 * @uses _database;
 * @uses sharding
 * @uses common;
 */
class repeateActions{
  /**
   * @staticvar int - Максимально допустимый интервал для выборки данных из БД в секундах
   */
  const MAX_PERIOD_INTERVAL_SEC = 5259486;//5259486 - два месяца
  /**
   * @staticvar int - Количество секунд в часе
   */
  const SEC_IN_HOUR = 3600;
  /**
   * @staticvar int - Количество секунд для проверки частоты второго пункта
   */
  const SEC_FOR_RATE_TEN = 10;
  /**
   * @staticvar int - Количество секунд для проверки частоты третьего пункта
   */
  const SEC_FOR_RATE_TWENTY= 20;
  /**
   * @staticvar int - Количество секунд в одной минуте
   */
  const SEC_IN_ONE_MINUT = 60;
  /**
   * @staticvar int - Количество секунд в двух минутах
   */
  const SEC_IN_TWO_MINUT = 120;
  /**
   * @staticvar int - Количество секунд в пяти минутах
   */
  const SEC_IN_FIVE_MINUT = 300;
  /**
   * 1493120000
   * @var int $this->timeStart - Начало интервала времени
   */
  private $timeStart = null;
  /**
   * 1493121381
   * @var int $this->timeEnd - Конец интервала времени
   */
  private $timeEnd = null;
  /**
   * 0%
   * @var float - процент вероятности того, что uuid или ip бот
   */
  private $isBotPercent = 0;
  /**
   * l_sequence_4_user_1485896400
   * l_sequence_4_user_1488315600
   * l_sequence_4_user_1490994000
   * @var array - список названий таблиц для шардированной таблицы l_sequence_4_user
   */
  private $shard_list_l_sequence = [];
  /**
   * 000177faae7a0cdd08db0fac9ffc44bc
   * @var string $this->UUID - UUID пользователя
   */
  private $UUID = null;
  /**
   * 192.168.0.1 | 2002:c0a8::0001
   * @var string $this->IP - IP адрес
   */
  private $IP = null;
  
  /**
   * ['for_uuid' | 'for_ip']
   * @var string - тип ограничения, для выборки данных,
   * при проверке обращения к сайту одного UUID или IP
   */
  private $typeAccessSite = null;


  /**
   * Метод возвращает данные из шардированной таблицы l_sequence_4_user,
   * если данные ещё получены не были, то делается запрос к БД.
   *
   * @param string $type - ['for_uuid' | 'for_ip'] тип ограничения, для выборки данных
   * @param int $seconds - количество секунд, которыми будет ограничена выборкаданных, в случае,
   * если парметры $this->timeStart == 0 && $this->timeEnd == 0.
   * @return array
   */
  private function get_data_l_sequence_4_user($type = '', $seconds = 0){
    $orderbyAsc = '`time`';
    $limit = 10000;
    $timeInterval = "(`time` BETWEEN '{$this->timeStart}' AND '{$this->timeEnd}')";

    switch ($type) {
      case 'for_uuid':
        if(empty($this->UUID) || !is_scalar($this->UUID)){return [];}
        if($this->timeStart == 0 && $this->timeEnd == 0){
          $timeInterval = "(`time` BETWEEN unix_timestamp(NOW() - INTERVAL ".$seconds." SECOND) AND unix_timestamp(NOW()))";
        }
        $where = "AND (`uuid` = '{$this->UUID}')";
        break;
      case 'for_ip':
        if(empty($this->IP) || !is_scalar($this->IP)){return [];}
        if($this->timeStart == 0 && $this->timeEnd == 0){
          $timeInterval = "(`time` BETWEEN unix_timestamp(NOW() - INTERVAL ".$seconds." SECOND) AND unix_timestamp(NOW()))";
        }
        $where = "AND (`ip` = '{$this->IP}')";
        break;
      default:
        $where = "";
    }
    if($where === ''){return [];}

    $listTables = $this->get_shard_l_sequencer_4_user();

    //Построим общий SQL запрос для шардированных таблиц
    $query = '';
    for($i = 0; $i < count($listTables); $i++){
      $query .= ((!empty($query))?' UNION ALL ':'')."(SELECT `time`, `uuid`, `ip` FROM `{$listTables[$i]}` WHERE $timeInterval $where)";
    }
    $query .= " ORDER BY $orderbyAsc";
    $query .= ($limit > 0)?" LIMIT 0, $limit":'';
    
    //if($type !== 'for_uuid' && $type !== 'for_ip'){
      //print($query);exit;
    //}
    
    $mysqliResult = simple_query($query);
    $data = return_mysqli_results($mysqliResult);

    return $data;
  }

  /**
   * Метод преобразует данные, и возвращает массив, где ключом является временной интервал.
   * @param array $data - массив данных из шардированныой таблицы l_sequence_4_user
   * @param int $timeInterval - количество секунд, на которые следует разделить данные.
   * @return array
   */
  private function group_time_interval($data = [], int $timeInterval = 0){
    if(!is_array($data) || empty($data)){return [];}

    $dataRes = [];
    /**
     * Предполагается что данные отсортированы по полю time.
     * Принцип преобразования данных таков:
     * Смотрим самую первую запись, получаем из неё начальное время.
     * Извлекаем данные в интервале от начального времени(ВремяНач), до ВремяКон,
     * где ВремяКон = ВремяНач + ВремяИнтервал,
     * и сохраняем в результирующем массиве с ключом в виде: ВремяНач_ВремяКон.
     * Далее создаётся новый ключ, где ВремяНач = ВремяСледЗаписи.
     */
    $keyInterval = '';
    $timeStart = 0;
    $timeEnd = 0;
    for($i = 0; $i < count($data); $i++){
      if(!array_key_exists($i, $data) || !array_key_exists('time', $data[$i])){break;}

      if(empty($keyInterval)){
        $timeStart = $data[0]['time'];
      }elseif($data[$i]['time'] > $timeEnd){
        $timeStart = $data[$i]['time'];
      }

      $timeEnd = $timeStart + $timeInterval;
      $keyInterval = $timeStart.'_'.$timeEnd;
      $dataRes[$keyInterval][] = $data[$i];
    }
    return $dataRes;
  }

  /**
   * Устанавливает значение свойства $this->isBotPercent = $percentIsBot,
   * если в списке интервалов найдены массивы с данными.
   * Если значение больше 100, то останется 100.
   * @param array $data - Вложенный массив,
   * в котором, ключами являются временные интервала в виде ВремяНач_ВремяКон,
   * а значениями: строки данных, относящиеся к этомуинтервалу.
   * @param float $percentIsBot - Процент, прибавляемый к вероятности наличия бота,
   * в случае положительного результата.
   * @return float
   */
  private function test_data_interval($data = [], float $percentIsBot = 0){
    $maxCount = 0;
    foreach($data as $timeInterval => $rows){
      $countRows = count($rows);
      $maxCount = ($countRows > $maxCount)?$countRows:$maxCount;
    }
    $this->isBotPercent = ($maxCount > 1)?($this->isBotPercent + $percentIsBot):$this->isBotPercent;
    $this->isBotPercent = ($this->isBotPercent > 100)?100:$this->isBotPercent;
  
    return ($maxCount > 1)?($percentIsBot):0;
  }

  /**
   * Устанавливает значение свойства $this->isBotPercent = 100,
   * если uuid явялется ботом.
   * Наличие идентификаторов узерагентов как ботов - 100%
   * @return float | null
   */
  private function get_is_bot(){
    if(empty($this->UUID) || !is_scalar($this->UUID)){return null;}

    $query = "SELECT `bot` WHERE `uuid` = '{$this->UUID}' LIMIT 0,1";
    $mysqliResult = query_db(1, '`user_property`', $query);
    $data = return_mysqli_results($mysqliResult);
    $res = (empty($data))?null:common_getVariable($data, [0,'bot'], 0);
    $this->isBotPercent = ($res == 1)?100:$this->isBotPercent;
    
    return $res;
  }
  
  /**
   * Если найдены записи, с указанной частотой времени,
   * то в $this->isBotPercent будет добавлен процент вероятности наличия бота.
   * Частота обращений  UUID в среднем за 5 минут.
   * Если в среднем больше чем 1 раз в 10 сек то + 30% к вероятности того, что это бот.
   * @return array
   */
  private function get_frequent_for_uuid(){
    $data = $this->get_data_l_sequence_4_user('for_uuid', self::SEC_IN_ONE_MINUT);
    $groupData = $this->group_time_interval($data, self::SEC_FOR_RATE_TEN);
    $percent = $this->test_data_interval($groupData, 33.33);
    return ['data' => $groupData, 'percent' => $percent];
  }

  /**
   * Если найдены записи, с указанной частотой времени,
   * то в $this->isBotPercent будет добавлен процент вероятности наличия бота.
   * Частота обращений  IP в среднем за 2 минуты.
   * Если в среднем больше чем 1 раз в 20 сек то + 30% к вероятности того, что это бот.
   * @return array
   */
  private function get_frequent_for_ip(){
    $data = $this->get_data_l_sequence_4_user('for_ip', self::SEC_IN_TWO_MINUT);
    $groupData = $this->group_time_interval($data, self::SEC_FOR_RATE_TWENTY);
    $percent = $this->test_data_interval($groupData, 33.33);
    return ['data' => $groupData, 'percent' => $percent];
  }

  /**
   * Если найдены записи, с указанной частотой времени,
   * то в $this->isBotPercent будет добавлен процент вероятности наличия бота.
   * Обращения к сайту одного UUID или IP на протяжении более 12 часов с интервалом не более 3х часов.
   * @return array
   */
  private function get_access_site(){
    $data = $this->get_data_l_sequence_4_user($this->typeAccessSite, self::SEC_IN_HOUR * 12);
    $groupData = $this->group_time_interval($data, self::SEC_IN_HOUR * 3);
    $percent = $this->test_data_interval($groupData, 33.33);
    return ['data' => $groupData, 'percent' => $percent];
  }

  /**
   * Фильтры для полученных параметров
   */

  /**
   * Метод выполняет ограничение интервала,
   * в рамках максимально допустимого для выборки данных из БД.
   * Для ограничения применяется константа self::MAX_PERIOD_INTERVAL_SEC
   */
  private function restriction_interval(){
    switch (true) {
      //Если указаны и начало и конец интервала или только начало интервала
      case ($this->timeStart > 0 && $this->timeEnd >= 0):
        $this->timeEnd =  $this->timeStart + self::MAX_PERIOD_INTERVAL_SEC;
        break;
      //Если указан конец интервала
      case ($this->timeStart == 0 && $this->timeEnd > 0):
        $this->timeStart =  $this->timeEnd - self::MAX_PERIOD_INTERVAL_SEC;
        break;
      default:
        break;
    }
  }

  /*
  * Геттеры и сеттеры
  */
  
  public function setter_time_start($timeStart){
    $this->timeStart = ((int)$timeStart > 0)?(int)$timeStart: 0;
    //Вызовем фильтр интервала
    $this->restriction_interval();
  }
  public function setter_time_end($timeEnd){
    $this->timeEnd = ((int)$timeEnd > 0)?(int)$timeEnd: 0;
    //Вызовем фильтр интервала
    $this->restriction_interval();
  }
  public function setter_uuid($UUID){
    $this->UUID = (string)$UUID;
  }
  public function setter_ip($IP){
    $this->IP = ip_conv_ip_to_binary_32((string)$IP);
  }
  
  public function setter_type_access_site($typeAccessSite){
    $this->typeAccessSite = (string)$typeAccessSite;
  }
  
  /**
   * Возвращает процентное значение наличия бота
   * @return float
   */
  public function getter_isbot_percent(){
    /**
     * Выполним необходимые методы, для получения процентной вероятности наличия бота.
     */
    /*$isBot = */$this->get_is_bot();
    /*$frequentFU = */$this->get_frequent_for_uuid();
    /*$frequentFI = */$this->get_frequent_for_ip();
    /*$AS = */$this->get_access_site();
    /*
    return [
        '0_isBot' => $isBot,
        '1_$frequentFU' => $frequentFU,
        '2_$frequentFI' => $frequentFI,
        '3_$AS' => $AS,
        '4_isBotPercent' => $this->isBotPercent
    ];
    */
    return $this->isBotPercent;
  }

  /**
   * Возвращает полученные параметры после фильтрации
   * @return array
   */
  public function get_params(){
    return [
      'timeStart' => $this->timeStart,
      'timeEnd' => $this->timeEnd,
      'timeUUID' => $this->UUID,
      'timeIP' => $this->IP,
      'typeAccessSite' => $this->typeAccessSite
    ];
  }
  
  /**
   * Возвращает массив названий таблиц для шардированной таблицы l_sequence_4_user,
   * при этом, если списокк уже был получен один раз, то он и используется в дальнейшем.
   * @return array
   */
  public function get_shard_l_sequencer_4_user(){
    //Если ещё не получали список таблиц
    if(empty($this->shard_list_l_sequence)){
      if(!($this->timeStart == 0 && $this->timeEnd == 0)){
        $this->shard_list_l_sequence = sharding_getShardTableListFromDb('l_sequence_4_user_', $this->timeStart, $this->timeEnd);
      /**
       * Если временной интервал не задан,
       * то выбираем последнюю таблицу из шардированной l_sequence_4_user,
       * т.к. нужны самые актуальные данные.
       */
      }else{
        $this->shard_list_l_sequence = sharding_getShardTableListFromDb('l_sequence_4_user_');
        $this->shard_list_l_sequence = array_pop($this->shard_list_l_sequence);
      }
    }
    return $this->shard_list_l_sequence;
  }

    /**
     * @param array $arrayUUIDS - массив UUID'ов, для которых требуетя узнать статус наличия бота, проверяется таблица user_property
     * @return array|null
     */
  public function get_probability_percent_bots($arrayUUIDS){
    if(!is_array($arrayUUIDS) || empty($arrayUUIDS)){return null;}
    
    $mysqliResult = simple_query("SELECT `uuid`, `percent_is_bot`, `is_bot` FROM `user_property` WHERE `uuid` IN ('" . implode("', '", $arrayUUIDS) . "')");
    $data = return_mysqli_results($mysqliResult);
    
    //Получим общее количество пользователей
    $countUsers = count($data);
    
    //Подсчитаем количество ботов
    $countBots = 0;

    //Данные по посетителям
    $arrDataBot = [];

    for($i=0; $i< $countUsers; $i++){
      $IsBot = (int)$data[$i]['is_bot'];
      $arrDataBot[$data[$i]['uuid']] = $IsBot;

      if($IsBot === 1){
        $countBots ++;
      }
    }
    
    return [
        //'dataUsers' => $arrDataBot,
        'countBots' => $countBots,
        'countUsers' => $countUsers,
        'percentBots' => round($countBots/$countUsers * 100, 2)
    ];
  }

    /**
     * @param array $arrayUUIDS - массив UUID'ов, для которых требуетя узнать статус наличия бота, проверяется таблица user_property
     * @return array|null
     */
    public function get_probability_percent_ads($arrayUUIDS){
        if(!is_array($arrayUUIDS) || empty($arrayUUIDS)){return null;}

        $mysqliResult = simple_query("SELECT `ad`, `uuid` FROM `user_property` WHERE `uuid` IN ('" . implode("', '", $arrayUUIDS) . "')");
        $data = return_mysqli_results($mysqliResult);

        //Получим общее количество пользователей
        $countUsers = count($data);

        //Подсчитаем количество ботов
        $countAds = 0;

        //Данные по посетителям
        $arrDataAds = [];

        for($i=0; $i< $countUsers; $i++){
            $IsAd = (int)$data[$i]['ad'];
            $arrDataAds[$data[$i]['uuid']] = $IsAd;

            if($IsAd === 1){
                $countAds ++;
            }
        }

        return [
            //'dataUsers' => $arrDataAds,
            'countAds' => $countAds,
            'countUsers' => $countUsers,
            'percentAds' => round($countAds/$countUsers * 100, 2)
        ];
    }

    /**
     * Метод выполняет проверку массива пользователей на наличие бота, по приницпам проверки метода
     * getter_isbot_percent, но с учётом переданного времени.
     * @param array $uuids - массив uuid'ов пользователей
     * @param $timeStart - начало интервала проверки
     * @param $timeEnd - конец интервала проверки
     * @return array
     */
  public function get_status_bot_manual($uuids = [], $timeStart, $timeEnd){
      if(!is_array($uuids) || empty($uuids)){return null;}

      $this->timeStart = $timeStart;
      $this->timeEnd = $timeEnd;

      $arrResult = [];
      $countUuids = count($uuids);

      foreach($uuids as $val){
          $this->isBotPercent = 0;//Обнулим показатель адблока
          $this->setter_uuid($val);
          $isBot = $this->getter_isbot_percent();
          //$arrResult[$val] = $this->getter_isbot_percent();
          $arrResult[$val] = ($isBot >= 90)?1:0;
      }
      $countBots = array_sum($arrResult);
      return [
          //'dataUuids' => $arrResult,
          'countBots' => array_sum($arrResult),
          'countUuids' => $countUuids,
          'percentBots' => round($countBots/$countUuids * 100, 2)
      ];
    return $arrResult;
  }

}