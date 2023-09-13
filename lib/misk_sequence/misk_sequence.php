<?php

/**
 * Функция возвращает список доменов из таблицы domain
 * @param string $ids
 * @return string
 */
function getDomain(string $ids) {

  $ids = filter_var($ids, FILTER_SANITIZE_STRING);

  //Ожидается json данные, поэтому пробуем декодировать в массив
  $ids = json_decode($ids, true);

  //Если декодировать не удалось
  if (!$ids) {
    return json_encode([
      'status' => 'error',
      'description' => 'It was not succeeded to decode JSON line'
      ], JSON_UNESCAPED_UNICODE);
  }

  common_inc('_database');
  $domain_data = select_db(1, 'domain', ['show', 'id', 'name'], [], [], '', ['id' => $ids]);

  if ($domain_data->num_rows <= 0) {
    return json_encode([
      'status' => 'error',
      'description' => 'It was not succeeded to find data on the domain'
      ], JSON_UNESCAPED_UNICODE);
  } else {
    $res = [];
    $res['status'] = 'Ok';
    while ($row = $domain_data->fetch_assoc()) {
      $res['domains'][] = $row;
    }
    return json_encode($res, JSON_UNESCAPED_UNICODE);
  }
}

/**
 * Функция возвращает данные о городе по его идентификатору
 * @param int $id
 * @return array|null
 */
function getCityFromId(int $id) {
  //Фильтруем
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	if(empty($id)){
		return null;
	}
  common_inc('_database');
  $sql_string = 'SELECT `id`, `city`, `region`, `district`' .
    ' WHERE `id` = \'' . $id . '\''
    . ' LIMIT 0, 1';
  //common_pre($sql_string);
  //Выполняем основной запрос
  $stat_data = query_db(1, 'list_condition_geo', $sql_string);
  if ($stat_data) {
    return $stat_data->fetch_assoc();
  } else {
    return null;
  }
}

/**
 * Функция возвращает данные о провайдере по его идентификатору
 * @param int $id
 * @return array|null
 */
function getIpsFromId(int $id) {
  //Фильтруем
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	if(empty($id)){
		return null;
	}
  common_inc('_database');
  $sql_string = 'SELECT `id`, `org_name` AS ips' .
    ' WHERE `id` = \'' . $id . '\''
    . ' LIMIT 0, 1';
  //common_pre($sql_string);
  //Выполняем основной запрос
  $stat_data = query_db(1, 'ripe_isp', $sql_string);
  if ($stat_data) {
    return $stat_data->fetch_assoc();
  } else {
    return null;
  }
}


/**
 * Функция возвращает данные о ip диапазоне по его идентификатору
 * @param int $id
 * @return array|null
 */
function getIpDiapFromId(int $id) {
  //Фильтруем
  $id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
	if(empty($id)){
		return null;
	}
  common_inc('_database');
  $sql_string = 'SELECT `id`, `prefix`' .
    ' WHERE `id` = \'' . $id . '\''
    . ' LIMIT 0, 1';
  //common_pre($sql_string);
  //Выполняем основной запрос
  $stat_data = query_db(1, 'list_condition_ip_interval', $sql_string);
  if ($stat_data) {
    return $stat_data->fetch_assoc();
  } else {
    return null;
  }
}

/**
 * Функция возвращает список городов из таблицы list_condition_geo
 * @param string $string_like
 * @return string json
 */
function getListCity(string $string_like = '') {
  $limit = 10;
  //Фильтруем
  $string_like = filter_var($string_like, FILTER_SANITIZE_STRING);

  if (mb_strlen($string_like) < 3) {
    return json_encode(['status' => 'ok', 'cities' => []], JSON_UNESCAPED_UNICODE);
  }

  common_inc('_database');
  $sql_string = 'SELECT `id`, `city`, `region`, `district`' .
    ' WHERE `city` LIKE \'%' . $string_like . '%\''
    . ' LIMIT 0,' . $limit;
  //common_pre($sql_string);
  //Выполняем основной запрос
  $stat_data = query_db(1, 'list_condition_geo', $sql_string);

  $data = [];

  if ($stat_data) {//Если данные вообще получены
    //Для каждой строки результата
    while ($row = $stat_data->fetch_assoc()) {
      $data[] = $row;
    }
  }

  return json_encode(['status' => 'ok', 'cities' => $data], JSON_UNESCAPED_UNICODE);
}

/**
 * Функция возвращает список провайдеров из таблицы ripe_ips
 * @param string $string_like
 * @return string json
 */
function getListIPS(string $string_like = '') {
  $limit = 10;
  //Фильтруем
  $string_like = filter_var($string_like, FILTER_SANITIZE_STRING);

  if (mb_strlen($string_like) < 3) {
    return json_encode(['status' => 'ok', 'ips' => []], JSON_UNESCAPED_UNICODE);
  }

  common_inc('_database');
  $sql_string = 'SELECT `id`, `org_name` AS ips' .
    ' WHERE `org_name` LIKE \'%' . $string_like . '%\''
    . ' LIMIT 0,' . $limit;
  //common_pre($sql_string);
  //Выполняем основной запрос
  $stat_data = query_db(1, 'ripe_isp', $sql_string);

  $data = [];

  if ($stat_data) {//Если данные вообще получены
    //Для каждой строки результата
    while ($row = $stat_data->fetch_assoc()) {
      $data[] = $row;
    }
  }

  return json_encode(['status' => 'ok', 'ips' => $data], JSON_UNESCAPED_UNICODE);
}

/**
 * Функция возвращает список диапазонов адресов из таблицы list_condition_ip_interval
 * @param string $string_like
 * @return string json
 */
function getListIpDiap(string $string_like = '') {
  $limit = 10;
  //Фильтруем
  $string_like = filter_var($string_like, FILTER_SANITIZE_STRING);

  if (mb_strlen($string_like) < 2) {
    return json_encode(['status' => 'ok', 'ip_diap' => []], JSON_UNESCAPED_UNICODE);
  }

  common_inc('_database');
  $sql_string = 'SELECT `id`, `prefix`' .
    ' WHERE `prefix` LIKE \'%' . $string_like . '%\''
    . ' LIMIT 0,' . $limit;
  //common_pre($sql_string);
  //Выполняем основной запрос
  $stat_data = query_db(1, 'list_condition_ip_interval', $sql_string);

  $data = [];

  if ($stat_data) {//Если данные вообще получены
    //Для каждой строки результата
    while ($row = $stat_data->fetch_assoc()) {
      $data[] = $row;
    }
  }

  return json_encode(['status' => 'ok', 'ip_diap' => $data], JSON_UNESCAPED_UNICODE);
}

/**
 * Функция возвращает данные по группам условий
 * @return mysqli result
 */
function getUserPropertyCond() {
  common_inc('_database');
			$select = 'SELECT *';

			$sql_string = $select .
							' WHERE ' . 'state != 3';
	return query_db(1, 'list_sequence_conditions', $sql_string);
  return select_db(false, 'list_sequence_conditions', ['*'], [], [], '', []);
}

/**
 * Функция выполняет добавление или обновление группы условий.
 * @param bool $update - если истина, то будет выполнятся обновление группы условий
 * В случае успеха вернёт на страницу со списком условий, иначе будет выведено сообщение на странице.
 */
function changePropertyCond(bool $update = true) {
  common_inc('_database'); //Подключаем библиотечку работы с БД

  /**
   * Просматривается конфиг файл: misk_sequence/user_property_conditions:
   * И получаем наобходимые параметры, они будут переданы на страницу.
   */
  $configs = [];

  if (is_file($GLOBALS['conf']['root'] . '/config/misk_sequence/user_property_conditions.php')) {
    $configs = include_once($GLOBALS['conf']['root'] . '/config/misk_sequence/user_property_conditions.php');
  }

  $params = [
    'list_fields' => (array_key_exists('list_fields', $configs)) ? $configs['list_fields'] : [],
    'list_conditions_type' => (array_key_exists('list_conditions_type', $configs)) ? $configs['list_conditions_type'] : [],
    'list_fields_selective_conditions' => (array_key_exists('list_fields_selective_conditions', $configs)) ? $configs['list_fields_selective_conditions'] : [],
		'list_dp' => (array_key_exists('list_dp', $configs)) ? $configs['list_dp'] : [],
    'list_fields_load_values' => (array_key_exists('list_fields_load_values', $configs)) ? $configs['list_fields_load_values'] : [],
    'list_fields_static_values' => (array_key_exists('list_fields_static_values', $configs)) ? $configs['list_fields_static_values'] : []
  ];

  //Статус обновления
  $params['update'] = $update;

  //Идентификатор записи
  $uri = common_getURI();
  
  //Подготавливаем обработку POST данных
  $filters = [
    'field' => ['flags' => FILTER_REQUIRE_ARRAY],
    'type_cond' => ['flags' => FILTER_REQUIRE_ARRAY],
    'value_cond' => ['flags' => FILTER_REQUIRE_ARRAY],
    'value_select_cond' => ['flags' => FILTER_REQUIRE_ARRAY],
    'andor' => ['flags' => FILTER_REQUIRE_ARRAY]
  ];

  //Фильтруем массив параметров
  $arr_post = filter_input_array(INPUT_POST, $filters);

  //Фильтруем остальные POST параметры
  $name_cond = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
  $invers_cond = filter_input(INPUT_POST, 'invers', FILTER_SANITIZE_STRING);
  $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);


  $cond_data = []; //Массив полей с условиями
  //Если все переданные параметры это массивы
  if (is_array($arr_post['field'])
	  && is_array($arr_post['type_cond'])
	  && is_array($arr_post['value_cond'])
	  && is_array($arr_post['value_select_cond'])
	  && is_array($arr_post['andor'])
	){
    $j = 0;
    foreach ($arr_post['field'] as $post_key => $post_value) {
      //Если индекс есть и в других параметрах
      if (array_key_exists($post_key, $arr_post['type_cond'])
		  && array_key_exists($post_key, $arr_post['value_cond'])
		  && array_key_exists($post_key, $arr_post['value_select_cond'])
		  && array_key_exists($post_key, $arr_post['andor'])
		){
        $cond_data[$j]['field'] = $arr_post['field'][$post_key];
        $cond_data[$j]['type_cond'] = $arr_post['type_cond'][$post_key];
        //Если в выпадающем списке есть значение, то используем его.
        $cond_data[$j]['value_cond'] = (
					!empty($arr_post['value_select_cond'][$post_key]) 
					|| $arr_post['value_select_cond'][$post_key] === 0
					|| $arr_post['value_select_cond'][$post_key] === "0") ? $arr_post['value_select_cond'][$post_key] : $arr_post['value_cond'][$post_key];
				$cond_data[$j]['andor'] = $arr_post['andor'][$post_key];
        $j ++;
      }
    }
  }
   
  //Подготовим JSON строку для передачи в API
  $params['json_data'] = json_encode($cond_data, JSON_UNESCAPED_UNICODE  | JSON_HEX_APOS);

	//Статус неудачного сохранения
	$status_error_save = false;
	
  //Если POST массив с условиями не пуст
  if ($cond_data) {
		
		//Подключаем файл класса
		common_inc('misk_sequence','parse_conditions_to_query');
		$parse_conditions = new parseConditions();		
		
    
		//Если обновлениие
    if ($update == true) {
      $data_query = [
				'state' => 2,//Статус изменения условия
        'name' => $name_cond,
        'invers' => $invers_cond,
        'json_cond' => $params['json_data'],
				'query_where' => $parse_conditions->parse_conditions($params['json_data'])
      ];
			if(!empty($id)){
				$result_query = update_db(1, 'list_sequence_conditions', $data_query, ['id' => $id]);
			}else{
				$result_query = false;
			}
    } else {//Если добавление
      $data_query = [
		'state' => 1,//Статус добавления условия
        'name' => $name_cond,
        'invers' => $invers_cond,
        'json_cond' => $params['json_data'],
		'query_where' => $parse_conditions->parse_conditions($params['json_data'])
      ];

      $result_query = insert_db(1, 'list_sequence_conditions', $data_query);
    }
		
    //Если условия успешно сохранены, то переадресуем на основную страницу
    if ($result_query !== false) {
      header('Location: /condition_user_property/');
    } else {
			$status_error_save = true;
      $params['result_change'] = 'Не удалось '.(($update == true)?'обновить':'добавить').' условия, возможно такая запись уже существует.';
    }
  }

  /**
   * Строки ниже выполнятся только в том случае,
   * если открывается страница добавления/редактирования записи групп условий.
   */
  //Если выполняется обновление
  if($update && !empty($uri[3])){
    $params['conditions'] = select_db(1, 'list_sequence_conditions', ['*'], ['id' => $uri[3]], [], '', [])->fetch_assoc();
  }
  
  //Если группы условий получены из БД
  if (!empty($params['conditions']) && !$status_error_save) {
    $params['conditions']['json_cond'] = json_decode($params['conditions']['json_cond'], JSON_UNESCAPED_UNICODE);
  
	}elseif($status_error_save){
		$params['conditions']['json_cond'] = $cond_data;
	} else {
    $params['conditions']['json_cond'] = [];
  }
  
  //common_pre($params['conditions']['json_cond']);

  //Отобразим страницу
  $page = 'misk_sequence/cond_user_property/' . (($update == true) ? 'upd' : 'add');
  common_setView('misk_sequence/cond_user_property/change', $params);
}

/**
 * Функция удаляет группу условий
 * @return boolean
 */
function delUserPropertyCond() {
  $uri = common_getURI();
  common_inc('_database');
	if(!empty($uri[3])){
		$res = update_db(1, 'list_sequence_conditions', ['state' => 3], ['id' => $uri[3]]);
	}

/*
  if(!empty($uri[3])){
  $res = delete_db(
    1, 'list_sequence_conditions', ['id' => $uri[3]]
  );
	}
	*/
  header('Location: /condition_user_property/');

}