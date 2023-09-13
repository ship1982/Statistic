<?php
common_inc('api/repeate_actions', 'repeate_actions');

/**
 * Класс работающий с цепочками
 *
 * Что использует:
 * to_sql;
 * _database;
 * common;
 */
class Chains{

  /**
   * [ ] |
   * [
   *  'utm_conditions' => [...],
   *  'page_conditions' => [...],
   *  'event_for_pages' => [...],
   *  'param_for_pages' => [...],
   *  'events_on_all_pages' => [...]
   * ]
   * @var array Массив, содержит общие фильтры, накладываемые на выборку данных.
   */
  private $arrParams = [];
  /**
   * 1
   * @var int Содержит идентификатор партнёра
   */
  private $partnerNumber;
  /**
   * 1490965200
   * @var int Содержит начальный интервал времени в UNIX формате
   */
  private $dps;
  /**
   * 1490965201
   * @var int Содержит конечный интервал времени в UNIX формате
   */
  private $dpe;
  /**
   * 'visits_of_pages' | 'actions_of_the_user' | 'action_elements' | 'events'
   * | 'combination_of_services_simple' | 'combination_of_services_ext'
   * | 'change_of_parameters'
   * @var string Содержит название типа отчёта
   */
  private $reportType;
  /**
   * 'interval' | 'last_session'
   * @var string Содержит тип поиска по временному интервалу
   */
  private $intervalType;
  /**
   *
   * @var string Содержит SQL строку с основными фильтрами для получения отчёта
   */
  private $sqlWhere = '';


  public function get_user_uuids(){
    common_inc('_database');
    $data = simple_query('SELECT DISTINCT `uuid` FROM `event_list`;');
    $data = return_mysqli_results($data);
    return ['data' => $data];
  }

  /**
   * Возвращает SQL строку с условиями.
   * @param array $arr_conditions - Массив условий
   * @param string['utm_conditions'|'page_conditions'|'events_on_all_pages'|'event_for_pages'|'param_for_pages'] $type - в зависимости от фильтра нужно использовать разные поля из массива $arr_conditions
   * @param array $sql_old - SQL строка с уже существующими условиями
   * @return string
   */
  public function get_sql_where_cond($arr_conditions, $type, $sql_old = ''){
    common_inc('to_sql');
    $prep_arr = [];
    for($i = 0; $i < count($arr_conditions); $i++){
      $params = $this->__get_params($arr_conditions[$i], $type);
      if(is_array($params) && count($params) == 1){
        $prep_arr[] = $params[0];
      }elseif(is_array($params) && count($params) == 2){
        //$prep_arr[] = $params[0];
        //$prep_arr[] = $params[1];
        $prep_arr[] = [
            $params[0],
            $params[1]
            ];
      }
      continue;
    }
    //common_pre($prep_arr);
    //common_pre(to_sql_get_parentheses($sql_old, to_sql_arr_conditions_to_sql($prep_arr)));
    return to_sql_get_parentheses($sql_old, to_sql_arr_conditions_to_sql($prep_arr));
  }

  /**
   * Проверяет наличие необходимых ключей для массива,
   * в зависимости от типа условия и в случае успешной проверки, возвращает массив параметров.
   * @param array $array
   * @param string $type
   * @return boolean
   */
  private function __get_params($array, $type){
    switch ($type) {
      case 'utm_conditions':
      case 'page_conditions':
        if(array_key_exists('field_1_cond', $array)
          && array_key_exists('type_cond', $array)
          && array_key_exists('value_cond', $array)
          && array_key_exists('andor', $array)
          ){
          return [[
              'field' => $array['field_1_cond'],
              'type'  => $array['type_cond'],
              'value' => $array['value_cond'],
              'andor' => $array['andor']
              ]];
        }
        break;
      case 'events_on_all_pages':
        return [[
            'field' => 'event_label',
            'type'  => 1,
            'value' => $array,
            'andor' => 'OR'
            ]];
      case 'event_for_pages':
        if(array_key_exists('type_cond', $array)
          && array_key_exists('value_cond', $array)
          && array_key_exists('andor', $array)
          ){
          return [
              [
              'field' => 'link',
              'type'  => 2,//Содержит
              'value' => $array['field_1_cond'],
              'andor' => $array['andor']
              ],[
              'field' => 'event_label',
              'type'  => $array['type_cond'],
              'value' => $array['value_cond'],
              'andor' => 'AND'
              ]];
        }
        break;
      case 'param_for_pages':
        if(array_key_exists('field_1_cond', $array)
          && array_key_exists('field_2_cond', $array)
          && array_key_exists('type_cond', $array)
          && array_key_exists('value_cond', $array)
          && array_key_exists('andor', $array)
          ){
          return [
              [
              'field' => 'link',
              'type'  => 2,//Содержит
              'value' => $array['field_1_cond'],
              'andor' => $array['andor']
              ],[
              'field' => $array['field_2_cond'],
              'type'  => $array['type_cond'],
              'value' => $array['value_cond'],
              'andor' => 'AND'
              ]
          ];
        }
        break;

      default:
        return false;
    }
    return false;
  }

  /**
   * Заполняет переменную $this->sqlWhere SQL условиями,
   * для наложения основных фильтров на отчёты
   */
  private function __fill_sql_where(){
    common_inc('_database');
    $period_where = '';
    
    if($this->intervalType == 'interval'){
      $this->sqlWhere = "(`time` >= '{$this->dps}' AND `time` <= '{$this->dpe}')";
    }elseif($this->intervalType == 'last_session'){
      $sql = 'SELECT MAX(el.seance) AS seance, el.uuid AS uuid FROM event_list el GROUP BY el.uuid ORDER BY el.uuid DESC LIMIT 0, 10000;';
      $last_seance_db = simple_query($sql);
      $last_seance = return_mysqli_results($last_seance_db);
      if($last_seance_db && property_exists($last_seance_db, 'num_rows')){
        for($i=0; $i < $last_seance_db->num_rows; $i++){
          $period_where .= (($i>0)?',':'').'\''.$last_seance[$i]['uuid'].'\'';
        }
        if(!empty($period_where)){
          $this->sqlWhere = '(`uuid` IN ('.$period_where.'))';
        }else{
          $this->sqlWhere = '';
        }
      }
    }else{
      $this->sqlWhere = '';
    }
    
    //Если идентификатор партнёра указан, и фильтр уже не пустой
    if(!empty($this->sqlWhere) && !empty($this->partnerNumber)){
      $this->sqlWhere .= " AND (`partner` = '{$this->partnerNumber}')";
    //Если идентификатор партнёра указан, но фильтр ещё пустой
    }elseif(empty($this->sqlWhere) && !empty($this->partnerNumber)){
      $this->sqlWhere = "(`partner` = '{$this->partnerNumber}')";
    }
    
    /**
     * Добавляем, при необходимости дополнительные фильтры
     */
    //UTM метки
    $this->sqlWhere = $this->get_sql_where_cond($this->arrParams['utm_conditions'], 'utm_conditions', $this->sqlWhere);
    
    //Страницы
    $this->sqlWhere = $this->get_sql_where_cond($this->arrParams['page_conditions'], 'page_conditions', $this->sqlWhere);
    
    //Общие события для всех страниц
    $this->sqlWhere = $this->get_sql_where_cond($this->arrParams['events_on_all_pages'], 'events_on_all_pages', $this->sqlWhere);
    
    //События для страниц
    $this->sqlWhere = $this->get_sql_where_cond($this->arrParams['event_for_pages'], 'event_for_pages', $this->sqlWhere);
    
    //Изменение параметров на странице
    $this->sqlWhere = $this->get_sql_where_cond($this->arrParams['param_for_pages'], 'param_for_pages', $this->sqlWhere);
  }

  /**
   * Возвращает массив параметров из конфиг файла
   * @param string $type - название конфиг параметра
   * @return array
   */
  private function __get_config_array($type = ''){
    switch ($type) {
      case 'fields_for_services':
        return common_getVariable($GLOBALS, [
          'conf',
          'chains',
          'fields_for_services'
         ], []);
        break;
      case 'fields_for_services_ext':
        return common_getVariable($GLOBALS, [
          'conf',
          'chains',
          'fields_for_services_ext'
         ], []);
        break;
      case 'events_on_certain_pages':
        return common_getVariable($GLOBALS, [
          'conf',
          'chains',
          'events_on_certain_pages'
         ], []);
        break;

      default:
        return [];
        break;
    }
  }
  
  /**
   * Возвращает массив страниц, для которых проводится отслеживание событий
   * @return array
   */
  private function __get_page_for_track(){
    $events_on_certain_pages = $this->__get_config_array('events_on_certain_pages');
    return array_keys($events_on_certain_pages);
  }
  
  /**
   * Возвращает вложенный массив, в котором ключи - это отслеживаемая страница,
   * а вложенный массив для каждого такого ключа - это списо событий,
   * которые отслеживаются на этой странице.
   * @return array
   */
  private function __get_page_for_track_params(){
    $events_on_certain_pages = $this->__get_config_array('events_on_certain_pages');
    $page_for_track_params = [];
    $page_for_track = $this->__get_page_for_track();

    for($i=0; $i<count($page_for_track); $i++){
      $page_for_track_params[$page_for_track[$i]] = array_keys($events_on_certain_pages[$page_for_track[$i]]);
    }
    return $page_for_track_params;
  }
  
  /**
   * Возвращает результаты для отчёта Цепочки посещений страниц.
   * @return array
   */
  public function get_report_visits_of_pages(){
    //Заполним основные фильтры
    $this->__fill_sql_where();
    
    common_inc('_database');
    $c = [];
    $bots = [];
    $ads = [];

    $sql = 'SELECT `time`, `link`, `seance`, `uuid`, `is_bot`, `ad` '.((!empty($this->sqlWhere))?'WHERE ':'WHERE 1=1').$this->sqlWhere.' ORDER BY `time`';
    $data_db = query_db(1, 'event_list', $sql);
    $data = return_mysqli_results($data_db);

    //Подготавливаем результаты для сбора цепочек
    for($i=0; $i < count($data); $i++){
      $c[$data[$i]['seance']][] = $data[$i]['link'];
      $bots[$data[$i]['seance']] = (int)$data[$i]['is_bot'];
      $ads[$data[$i]['seance']] = (int)$data[$i]['ad'];
    }
    
    $chains = [];
    $count_bot = 0;
    $count_ad = 0;
    $data_summ = 0;
    //Собираем цепочки
    foreach($c as $key => $arr_val){
      $c_chain = implode(' <b style="color: #4169E1;">►</b>   ',$arr_val);
      if (empty($chains[$c_chain]))
      {
        $chains[$c_chain] = [
          1,           //количество цепочек одного типа
          $bots[$key], //количество ботов среди цепочек одного типа
          $ads[$key]   //количество адблоков среди цепочек одного типа
        ];
      }
      else
      {
        $chains[$c_chain][0]++;
        $chains[$c_chain][1] += $bots[$key];
        $chains[$c_chain][2] += $ads[$key];
      }

      $data_summ++;
      $count_bot += $bots[$key];
      $count_ad += $ads[$key];
    }
    
    asort($chains);

    return[
        $chains,
        $data_summ,
        $count_bot,
        $count_ad
    ];
  }

  /**
   * Возвращает результаты для отчёта Цепочки действий пользователя.
   * @return array
   */
  public function get_report_actions_of_the_user(){
    //Заполним основные фильтры
    $this->__fill_sql_where();
    
    common_inc('_database');
    $c = [];
    $bots = [];
    $ads = [];
    
    $fields_for_services = $this->__get_config_array('fields_for_services');
        
    $sql_services_fields = (!empty($fields_for_services))?(', `'.implode('`, `', $fields_for_services).'` '):'';
    
    $page_for_track = $this->__get_page_for_track();
    $page_for_track_params = $this->__get_page_for_track_params();
    
    $sql_where_for_events_param = ' AND (';

    for($i=0; $i<count($page_for_track); $i++){
      $sql_where_for_events_param .= (($i > 0)?' OR ':'').'(`link` = \''.$page_for_track[$i].'\' ';
      $sql_where_for_events_param .= ' AND (`event_label` = \''.implode('\' OR `event_label` = \'',$page_for_track_params[$page_for_track[$i]]).'\')';
      $sql_where_for_events_param .= ')';
    }
    $sql_where_for_events_param .= ')';

    if($i == 0){
      $sql_where_for_events_param = '';
    }
    
    $sql = 'SELECT `time`, `event_label`, `seance`, `link`, `uuid`, `is_bot`, `ad`'.$sql_services_fields.((!empty($this->sqlWhere))?'WHERE ':'WHERE 1=1').$this->sqlWhere.((!empty($sql_where_for_events_param)?$sql_where_for_events_param:'')).' ORDER BY `time`';
    $data_db = query_db(1, 'event_list', $sql);
    $data = return_mysqli_results($data_db);

    $chains = [];
    $count_bot = 0;
    $count_ad = 0;
    $data_summ = 0;

    $page_for_track = $this->__get_page_for_track();
    $page_for_track_params = $this->__get_page_for_track_params();
    
    for($i=0; $i < count($data); $i++){
      $fields_param = [];
      //Собираем значения параметров
      for($j=0; $j < count($fields_for_services); $j++){
        $fields_param[] = '['.$fields_for_services[$j].'='.$data[$i][$fields_for_services[$j]].']';
      }
      $is_track = 0;
      for($k=0; $k < count($page_for_track); $k++){
        if($data[$i]['link'] == $page_for_track[$k]){
          if(in_array($data[$i]['event_label'], $page_for_track_params[$data[$i]['link']])){
            $is_track = 1;
          }
        }
      }
      if($is_track){          
        $c[$data[$i]['seance']][] = $data[$i]['event_label'].' {'.implode(',', $fields_param).'}';
      }else{
        $c[$data[$i]['seance']][] = $data[$i]['event_label'];
      }

      $bots[$data[$i]['seance']] = (int)$data[$i]['is_bot'];
      $ads[$data[$i]['seance']] = (int)$data[$i]['ad'];
    }
    //Собираем цепочки
    foreach($c as $key => $arr_val){
      $c_chain = implode(' <b style="color: #4169E1;">►</b>   ',$arr_val);
      if (empty($chains[$c_chain]))
      {
        $chains[$c_chain] = [
            1,           //количество цепочек одного типа
            $bots[$key], //количество ботов среди цепочек одного типа
            $ads[$key]   //количество адблоков среди цепочек одного типа
        ];
      }
      else
      {
        $chains[$c_chain][0]++;
        $chains[$c_chain][1] += $bots[$key];
        $chains[$c_chain][2] += $ads[$key];
      }

      $data_summ++;
      $count_bot += $bots[$key];
      $count_ad += $ads[$key];
    }
    asort($chains);
    
    return[
        $chains,
        $data_summ,
        $count_bot,
        $count_ad
    ];
  }

  /**
   * Возвращает результаты для отчёта События.
   * @return array
   */
  public function get_report_events()
  {
      //Заполним основные фильтры
      $this->__fill_sql_where();

      common_inc('_database');
      $c = [];
      $count_bot = 0;
      $count_ad = 0;
      $data_summ = 0;

      $sql = 'SELECT `time`, `event_label`, `uuid`, `is_bot`, `ad` ' . ((!empty($this->sqlWhere)) ? 'WHERE ' : 'WHERE 1=1') . $this->sqlWhere . ' ORDER BY `time`';
      $data_db = query_db(1, 'event_list', $sql);
      $data = return_mysqli_results($data_db);

      for($i=0; $i < count($data); $i++){
        $c_key = $data[$i]['event_label'];
        if (empty($c[$c_key]))
        {
          $c[$c_key] = [
              1,                        //количество событий одного типа
              (int)$data[$i]['is_bot'], //количество ботов среди событий одного типа
              (int)$data[$i]['ad']      //количество адблоков среди событий одного типа
          ];
        }
        else
        {
          $c[$c_key][0]++;
          $c[$c_key][1] += (int)$data[$i]['is_bot'];
          $c[$c_key][2] += (int)$data[$i]['ad'];
        }

        $data_summ++;
        $count_bot += (int)$data[$i]['is_bot'];
        $count_ad += (int)$data[$i]['ad'];
      }

      asort($c);

      return[
          $c,
          $data_summ,
          $count_bot,
          $count_ad
      ];
  }

  /**
   * Возвращает результаты для отчёта События параметров.
   * @return array
   */
  public function get_report_action_elements(){
    //Заполним основные фильтры
    $this->__fill_sql_where();
    
    common_inc('_database');
    $c = [];
    $count_bot = 0;
    $count_ad = 0;
    $data_summ = 0;
    
    $page_for_track = $this->__get_page_for_track();
    $page_for_track_params = $this->__get_page_for_track_params();
    
    $sql_where_for_events_param = 'AND (';

    for($i=0; $i<count($page_for_track); $i++){
      $sql_where_for_events_param .= (($i > 0)?' OR ':'').'(`link` = \''.$page_for_track[$i].'\' ';
      $sql_where_for_events_param .= ' AND (`event_label` = \''.implode('\' OR `event_label` = \'',$page_for_track_params[$page_for_track[$i]]).'\')';
      $sql_where_for_events_param .= ')';
    }
    $sql_where_for_events_param .= ')';

    if($i == 0){
      $sql_where_for_events_param = '';
    }
    
    $sql = 'SELECT `time`, `event_label`, `uuid`, `is_bot`, `ad` '.((!empty($this->sqlWhere))?'WHERE ':'WHERE 1=1').$this->sqlWhere.((!empty($sql_where_for_events_param)?$sql_where_for_events_param:'')).' ORDER BY `time`';
    $data_db = query_db(1, 'event_list', $sql);
    $data = return_mysqli_results($data_db);

    for($i=0; $i < count($data); $i++){
      $c_key = $data[$i]['event_label'];
      if (empty($c[$c_key]))
      {
        $c[$c_key] = [
            1,                        //количество событий одного типа
            (int)$data[$i]['is_bot'], //количество ботов среди событий одного типа
            (int)$data[$i]['ad']      //количество адблоков среди событий одного типа
        ];
      }
      else
      {
        $c[$c_key][0]++;
        $c[$c_key][1] += (int)$data[$i]['is_bot'];
        $c[$c_key][2] += (int)$data[$i]['ad'];
      }

      $data_summ++;
      $count_bot += (int)$data[$i]['is_bot'];
      $count_ad += (int)$data[$i]['ad'];
    }

    asort($c);

    return[
        $c,
        $data_summ,
        $count_bot,
        $count_ad
    ];
  }

  /**
   * Возвращает результаты для отчёта Комбинация услуг упрощённая.
   * @return array
   */
  public function get_report_combination_of_services_simple(){
    //Заполним основные фильтры
    $this->__fill_sql_where();
    
    common_inc('_database');
    $c = [];
    $count_bot = 0;
    $count_ad = 0;
    $data_summ = 0;
    
    $fields_for_services = $this->__get_config_array('fields_for_services');
    $sql_services_fields = (!empty($fields_for_services))?(', `'.implode('`, `', $fields_for_services).'` '):'';
    
    $sql = 'SELECT `time`, `event_label`, `uuid`, `is_bot`, `ad` '.$sql_services_fields.((!empty($this->sqlWhere))?'WHERE ':'WHERE 1=1').$this->sqlWhere.' AND `event_label` = \'send_order\' ORDER BY `time`';
    $data_db = query_db(1, 'event_list', $sql);
    $data = return_mysqli_results($data_db);

    for($i=0; $i < count($data); $i++){
      if(!empty($fields_for_services)){
        $fields_param = [];
        //Собираем значения параметров
        for($j=0; $j < count($fields_for_services); $j++){
          $fields_param[] = '['.$fields_for_services[$j].'='.$data[$i][$fields_for_services[$j]].']';
        }
        $c_key = $data[$i]['event_label'].' {'.implode(',', $fields_param).'}';
      }else{
        $c_key = $data[$i]['event_label'];
      }

      if (empty($c[$c_key]))
      {
        $c[$c_key] = [
            1,                        //количество событий одного типа
            (int)$data[$i]['is_bot'], //количество ботов среди событий одного типа
            (int)$data[$i]['ad']      //количество адблоков среди событий одного типа
        ];
      }
      else
      {
        $c[$c_key][0]++;
        $c[$c_key][1] += (int)$data[$i]['is_bot'];
        $c[$c_key][2] += (int)$data[$i]['ad'];
      }

      $data_summ++;
      $count_bot += (int)$data[$i]['is_bot'];
      $count_ad += (int)$data[$i]['ad'];
    }

    asort($c);

    return[
        $c,
        $data_summ,
        $count_bot,
        $count_ad
    ];
  }
  
  /**
   * Возвращает результаты для отчёта Комбинация услуг расширенная.
   * @return array
   */
  public function get_report_combination_of_services_ext(){
    //Заполним основные фильтры
    $this->__fill_sql_where();
    
    common_inc('_database');
    $c = [];
    $count_bot = 0;
    $count_ad = 0;
    $data_summ = 0;
    
    $fields_for_services_ext = $this->__get_config_array('fields_for_services_ext');
    $sql_services_fields_ext = (!empty($fields_for_services_ext))?(', `'.implode('`, `', $fields_for_services_ext).'` '):'';
    
    $sql = 'SELECT `time`, `event_label`, `uuid`, `is_bot`, `ad` '.$sql_services_fields_ext.((!empty($this->sqlWhere))?'WHERE ':'WHERE 1=1').$this->sqlWhere.' AND `event_label` = \'send_order\' ORDER BY `time`';
    $data_db = query_db(1, 'event_list', $sql);
    $data = return_mysqli_results($data_db);

    for($i=0; $i < count($data); $i++){
      if(!empty($fields_for_services_ext)){
        $fields_param = [];
        //Собираем значения параметров
        for($j=0; $j < count($fields_for_services_ext); $j++){
          $fields_param[] = '['.$fields_for_services_ext[$j].'='.$data[$i][$fields_for_services_ext[$j]].']';
        }
        $c_key = $data[$i]['event_label'].' {'.implode(',', $fields_param).'}';
      }else{
        $c_key = $data[$i]['event_label'];
      }

      if (empty($c[$c_key]))
      {
        $c[$c_key] = [
            1,                        //количество событий одного типа
            (int)$data[$i]['is_bot'], //количество ботов среди событий одного типа
            (int)$data[$i]['ad']      //количество адблоков среди событий одного типа
        ];
      }
      else
      {
        $c[$c_key][0]++;
        $c[$c_key][1] += (int)$data[$i]['is_bot'];
        $c[$c_key][2] += (int)$data[$i]['ad'];
      }

      $data_summ++;
      $count_bot += (int)$data[$i]['is_bot'];
      $count_ad += (int)$data[$i]['ad'];
    }

    asort($c);

    return[
        $c,
        $data_summ,
        $count_bot,
        $count_ad
    ];
  }
  
  /**
   * Возвращает результат отчёта
   * @param string $report_type
   * @return array
   */
  public function get_report($report_type = ''){
    $report_type = (!empty($report_type))?$report_type:$this->reportType;
    
    switch ($report_type) {
      case 'visits_of_pages':
        //Поле link
        return $this->get_report_visits_of_pages();
        break;
      case 'actions_of_the_user':
        //Поле event_label, где отслеживались действия пользователей
        return $this->get_report_actions_of_the_user();
        break;
      case 'events':
        //Поле event_label
        return $this->get_report_events();
        break;
      case 'action_elements':
        //Поле event_label, где отслеживались действия пользователей
        return $this->get_report_action_elements();
        break;
      case 'combination_of_services_simple':
        //Поле event_label, где отслеживались действия пользователей
        //Выводятся услуги
        return $this->get_report_combination_of_services_simple();
        break;
      case 'combination_of_services_ext':
        //Поле event_label, где отслеживались действия пользователей
        //Выводятся услуги всместе с их значениями
        return $this->get_report_combination_of_services_ext();
        break;
      case 'change_of_parameters':
      default:
        return [[],[]];
        break;
    }
  }
  
/**
 * Геттеры и сеттеры
 */
  public function getter_arr_params() {
    return $this->arrParams;
  }
  public function setter_arr_params($param) {
    $this->arrParams = (is_array($param) && !empty($param))?$param:[];
  }
  public function getter_partner_number() {
    return $this->partnerNumber;
  }
  public function setter_partner_number($param) {
    $this->partnerNumber = (int)$param;
  }
  public function getter_dps() {
    return $this->dps;
  }
  public function setter_dps($param) {
    $this->dps = (int)$param;
  }
  public function getter_dpe() {
    return $this->dpe;
  }
  public function setter_dpe($param) {
    $this->dpe = (int)$param;
  }
  public function getter_report_type() {
    return $this->reportType;
  }
  public function setter_report_type($param) {
    $this->reportType = (string)$param;
  }
  public function getter_interval_type() {
    return $this->intervalType;
  }
  public function setter_interval_type($param) {
    $this->intervalType = (string)$param;
  }
}