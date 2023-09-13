<?php

class apiSequencer {

    /**
     * Массив содержит основные параметры для работы класса.
     * Подключается из конфиг файла.
     * @var array
     */
    private $conf = [];

    /**
     * Массив содержит список таблиц,
     * которые будут использованы при выборке основного запроса.
     * @var array
     */
    private $list_tables = [];

    /**
     * Интервал времени для выборки данных.
     * Может задаваться как timestamp число, либо как строка в виде
     * 124543655-123456324,
     * Если передано как число, либо второе значение не указано,
     * то будет применятся расширение интервала времени,
     * с помощью параметра $time_diff.
     * Если время не передано, то будет использовано текущее время.
     * Передаётся GET или POST запросом.
     * @var string
     */
    private $time;

    /**
     * Поля, которые должны выводится в результате.
     * Если не переданы, то будут выводится поля по умолчанию,
     * с помощью параметра $fields_def_l_sequence_4_user.
     * Передаётся GET или POST запросом.
     * @var array
     */
    private $fields;

    /**
     * Объект с условиями выборки для полей.
     * Передаются в виде
     * Поле : {
     *  type: тип условия, соответствует значениям параметра $condition_types
     *  value: значение, по которому выполняется сравнение.
     * }
     * Передаётся GET или POST запросом.
     * @var array
     */
    private $conditions;

    /**
     * Массив содержит типы сравнений полей,
     * к примеру: "=", "!=", "like", "between", "regexp".
     * Берётся из конфиг файла.
     * @var array
     */
    private $condition_types;

    /**
     * Массив содержит поля, которые можно выбирать из шардинговых таблиц l_sequence_4_user...
     * Берётся из конфиг файла.
     * @var array
     */
    private $fields_l_sequence_4_user;

    /**
     * Массив содержит поля, которые будут применяться в выборке,
     * если параметр fields не будет передан,
     * или ни одно переданное поле не соответствует
     * параметру $fields_l_sequence_4_user.
     * Берётся из конфиг файла.
     * @var array
     */
    private $fields_def_l_sequence_4_user;

    /**
     * Максимальное количество строк, допустимое при выборке в основном запросе.
     * В дополнительных запросах установлен лимит на одну строку.
     * Берётся из конфиг файла.
     * @var int
     */
    private $limit_row;

    /**
     * Количество секунд, которое будет расширять интервал временного поиска,
     * в случае, если параметр $time передан как число а не интервал.
     * Пример: $time = 1475269200, тогда:
     * time_start = 1475269200 - 10;
     * time_end = 1475269200 + 10;
     * Берётся из конфиг файла.
     * @var int
     */
    private $time_diff;

    /**
     * Начало временного интервала для выборки данных,
     * применяется для определения, из каких шардинговых таблиц извлекать данные,
     * а также установки интервала для поля time при выборке данных
     * из шардинговых таблиц l_sequence_4_user.
     * Расчитывается автоматически.
     * @var int
     */
    private $time_start = 0;

    /**
     * Конец временного интервала для выборки данных,
     * применяется для определения, из каких шардинговых таблиц извлекать данные,
     * а также установки интервала для поля time при выборке данных
     * из шардинговых таблиц l_sequence_4_user.
     * Расчитывается автоматически.
     * @var int
     */
    private $time_end = 0;

    /**
     * Строка будет содержать условия запроса первого уровня.
     * @var string
     */
    private $string_ware = '';

    /**
     * @var string - Строка, отвечающая за группировку в запросе (блок GROUP BY в MySQL)
     */
    private $groupBy = '';

    public function __construct() {
        //Загружаем массив конфигурации, если его нет, то вернём ошибку.
        if (empty($GLOBALS['conf'])) {
            echo json_encode([
                'status' => 'error',
                'description' => 'No config parameters'
                ], JSON_UNESCAPED_UNICODE);
            die();
        }

        if (is_file($GLOBALS['conf']['root'] . '/config/api/api_sequencer.php')) {
            $this->conf = include_once($GLOBALS['conf']['root'] . '/config/api/api_sequencer.php');
        }

        //Если не удалось определить параметры, то выведем ошибку
        if (empty($this->conf)) {
            echo json_encode([
                'status' => 'error',
                'description' => 'No config parameters'
                ], JSON_UNESCAPED_UNICODE);
            die();
        }

        /**
         * Подготавливаем первоначальные настройки
         */
        $this->condition_types = !empty($this->conf['condition_types']) ? $this->conf['condition_types'] : [];
        $this->fields_l_sequence_4_user = !empty($this->conf['fields']) ? $this->conf['fields'] : [];
        $this->fields_def_l_sequence_4_user = !empty($this->conf['fields_def']) ? $this->conf['fields_def'] : [];
        $this->limit_row = !empty($this->conf['limit_row']) ? $this->conf['limit_row'] : 100;
        $this->time_diff = !empty($this->conf['time_diff']) ? $this->conf['time_diff'] : 30;

        //Установим временной диапазон по умолчанию, его можно изменить с помощью функции set_time()
        $this->set_time('', true);
    }

    public function get_statistics() {
                
        //Если поля не переданы
        if (empty($this->fields)) {
            $this->fields = $this->fields_def_l_sequence_4_user;
        }

        //Определяем таблицы, из которых будут выбираться данные запроса первого уровня
				common_inc('sharding');

				$this->list_tables = sharding_getShardTableList('l_sequence_4_user', $this->time_start, $this->time_end);
				
        //Подготавливаем строки условий для запросов первого и второго уровней
        $this->define_string_where();

				common_inc('timer');
				$timer = new timerPrint();
				$timer->start('start');

        $limit = $this->limit_row;
        $result = [];
        common_inc('_database'); //Подключаем библиотечку работы с БД

        //Для каждой таблицы, которая подпадает в интервал, делаем выборку
        for ($i = 0; $i < count($this->list_tables) && $limit > 0; $i++) {
            $table_name = 'l_sequence_4_user_' . $this->list_tables[$i];
            // process 
            $strFields = '';
            for ($j=0; $j < $jc = count($this->fields); $j++)
            { 
                if(strpos($this->fields[$j], '#') !== false)
                    $strFields .= trim($this->fields[$j], '#') . ',';
                else
                    $strFields .= '`'.$this->fields[$j].'`,';
            }
            $strFields = substr($strFields, 0, -1);


            $select = (!empty($this->fields)) ? 'SELECT '.$strFields : 'SELECT *'; //SQL_NO_CACHE

            $sql_string = $select .
                    ' WHERE ' . $this->string_ware .
                    $this->groupBy . 
                    ' LIMIT 0,' . $limit;

            // var_dump($sql_string);exit;
            //common_pre($sql_string);
            //Выполняем основной запрос
            $stat_data = query_db(1, 'l_sequence_4_user_' . $this->list_tables[$i], $sql_string);

            $result['count'] = 0;
            //Для каждой строки результата
            while ($row = $stat_data->fetch_assoc()) {
                if ($stat_data->num_rows > 0) {

                    $limit -= $stat_data->num_rows;
                    //Если лимит получился меньше нуля, то обнулим его и прекратим выборку
                    $limit = ($limit < 0) ? 0 : $limit;
                    
                    $result['count'] ++ ;
                    $result['statistics'][] = $row;
                }
            }
        }

        $result['status'] = 'Ok';
        $result['time'] = $timer->stop('start');

        //Если данные получены, то вернём их, иначе дадим сообщение
        if (empty($result['statistics'])) {
            $result['statistics'] = '';
            $result['description'] = 'It was not succeeded to find data on the statistics';
        } else {
            $result['description'] = '';
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
    
		public function get_user_property(string $uuid){
			//uuid - в БД это VARCHAR 40, приведём его именно к этому типу.
			$uuid = mb_strimwidth($uuid, 0, 40);
			$mask = '';
			$res_return = [];
			
			common_inc('_database');
			
			common_inc('timer');
			$timer = new timerPrint();
			$timer->start('start');
			
			// Получим все группы условий
			$query_conditions = select_db(1, 'list_sequence_conditions', ['id','position'], [], ['position' => 'ASC']);
			
			//Получим битовую маску
			$query_uuids = select_db(1, 'uuids_conditions_bitmaps', ['cond_bitmap'], ['uuid' => $uuid], [], '1');

			if($query_uuids && $query_uuids->num_rows > 0){
				$mask = $query_uuids->fetch_assoc()['cond_bitmap'];
			}

			/**
			 * Идея такая:
			 * Если маска не пуста, то проходим по всем группам условий,
			 * ключами устанавливаем id группы условия,
			 * а значение берём из маски, по позиции бита в группе условия.
			 */
			//Если битовая маска не пуста
			if(!empty($mask)){
				if($query_conditions && $query_conditions->num_rows > 0){
					//common_pre($query_conditions->fetch_all(MYSQLI_ASSOC));
					while($row = $query_conditions->fetch_assoc()){
						$res_return[$row['id']] = (mb_substr($mask, $row['position'], 1, "UTF-8"))?true:false;
					}
				}
			}

			echo json_encode([
				'status' => 'ok',
				'description' => '',
				'time' => $timer->stop('start'),
				'result' => $res_return
				]);
     }
		 
    /**
     * Метод генерирует и устанавлливает строки условий для запроса первого уровня,
     * а также для подзапросов второго.
     * Помимо это, если в параметрах выводимых полей нет поля,
     * на которое устанавливается ограничение, то оно будет добавлено.
     */
    private function define_string_where() {
        //Устанавливаем условие по временному интервалу
        $this->string_ware = "`time` BETWEEN {$this->time_start} AND {$this->time_end}";
        if (!empty($this->conditions)) {

            //Устанавливаем счётчики условия для 
            //Проходим по каждому условию
            foreach ($this->conditions as $cond_field => $cond_params) {
                //Если в параметрах выводимого поля нет, то добавим его

                if (!in_array($cond_field, $this->fields)) {
                    $this->fields[] = $cond_field;
                    $this->fields = array_values($this->fields);
                }

                switch (mb_convert_case($cond_params['type'], MB_CASE_LOWER, "UTF-8")) {
                    case 'regexp':
                        $this->string_ware .= (!empty($this->string_ware) ? ' AND ' : ' ') . "`$cond_field` REGEXP '{$cond_params['value']}'";
                        break;
                    case 'between':
                        list($time_start_val, $time_end_val) = $this->format_interval($cond_params['value']);

                        $this->string_ware .= (!empty($this->string_ware) ? ' AND ' : ' ') . "`$cond_field` BETWEEN $time_start_val AND $time_end_val";
                        break;
                    case 'between_unix_time':
                        list($time_start_val, $time_end_val) = $this->format_interval($cond_params['value']);

                        $this->string_ware .= (!empty($this->string_ware) ? ' AND ' : ' ') . "`$cond_field` BETWEEN FROM_UNIXTIME($time_start_val) AND FROM_UNIXTIME($time_end_val)";
                        break;
                    default:
                        $this->string_ware .= (!empty($this->string_ware) ? ' AND ' : ' ') . "`$cond_field` " . mb_convert_case($cond_params['type'], MB_CASE_UPPER, "UTF-8") . " '{$cond_params['value']}'";
                        break;
                }
            }
        }
    }

    /**
     * Фильтрует массив условий $conditions,
     * и сотавляет в нём только те условия,
     * которые имеют ключи type и value
     * и разрешены в параметре $condition_types.
     */
    private function filter_conditions() {
        $condition_checked = []; //В этом массиве будут хранится только проверенные условия

				if($this->conditions){
					foreach ($this->conditions as $cond_field => $cond_params) {
							//Если указанное поле для условия существует
							if (in_array($cond_field, $this->fields_l_sequence_4_user)) {

									//Если тип условия указан и соответствует возможному
									if (!empty($cond_params['type']) && in_array($cond_params['type'], $this->condition_types)) {

											//Если параметр value также указан
											if (array_key_exists('value', $cond_params)) {
													//Сохраняем условие выборки, оставляя только поля type и value
													$condition_checked[$cond_field]['type'] = $cond_params['type'];
													$condition_checked[$cond_field]['value'] = $cond_params['value'];
											} else {
													continue;
											}
									} else {//Пропустим обработку условия
											continue;
									}
							} else {//Пропустим обработку условия
									continue;
							}
					}
				}

        //Переопределяем условия
        $this->conditions = $condition_checked;
    }

    /**
     * Метод возвращает массив,
     * содержащий два значения: начало и конец интервала.
     * 
     * @param string $interval строка в формате '1477947600-1480539600'.
     * @param array $is_timestamp - если истина, то значения приводятся к целочисленному значению.
     */
    private function format_interval($interval, $is_timestamp = true) {
        //Если время не передано, то испоьзуем текущее
        if (empty($interval)) {
            $interval = time();
        }

        //Возможна передача интервала времени через дефис
        $time = explode('-', $interval);

        $time_start = ($is_timestamp) ? (int) $time[0] : $time[0];
        if (!empty($time[1])) {
            $time_end = ($is_timestamp) ? (int) $time[1] : $time[1];
        } else {
            $time_end = ($is_timestamp) ? (int) $time[0] : $time[0];
        }

        //Если конечный интервал не задан, то расширим интервалс помощью параметра $this->time_diff
        if ($time_start == $time_end) {
            $time_start -= $this->time_diff;
            $time_end += $this->time_diff;
        }

        //На случай, если начальное значение больше конечного, меняем их местами
        if ($time_start > $time_end) {
            list($time_start, $time_end) = [$time_end, $time_start];
        }

        return [
            $time_start,
            $time_end
        ];
    }

    /**
     * Метод вернёт истину, если параметр установлен.
     * @param $time int|string
     * @return boolean
     */
    public function set_time($time) {
        $this->time = filter_var($time);
        list($this->time_start, $this->time_end) = $this->format_interval($time, true);
        return (!empty($this->time));
    }

    /**
     * Метод вернёт истину, если параметр установлен.
     * @param $fields JSON string 
     * @return boolean
     */
    public function set_fields($fields) {
        $this->fields = filter_var($fields);
        //Ожидаются json данные, поэтому пробуем декодировать в ассоциативный массив
        $this->fields = json_decode($this->fields, true);

        /**
         * Если список полей передан, то оставляем в нём только те поля,
         * которые указаны в параметре $fields_l_sequence_4_user.
         */
        if (!empty($this->fields)) {
            $this->fields = array_intersect($this->fields_l_sequence_4_user, $this->fields);
        }

        /**
         * Если список полей не указан, либо после фильтрации остался пуст,
         * то установим поля по умолчанию.
         */
        if (empty($this->fields)) {
            $this->fields = $this->fields_def_l_sequence_4_user;
        }

        //Перегруппируем массив полей
        $this->fields = array_values($this->fields);

        return (!empty($this->fields));
    }

    /**
     * Метод вернёт истину, если параметр установлен.
     * @param $conditions JSON string
     * @return boolean
     */
    public function set_conditions($conditions) {
        $this->conditions = filter_var($conditions);
        //Ожидаются json данные, поэтому пробуем декодировать в ассоциативный массив
        $this->conditions = json_decode($this->conditions, true);
        //Фильтруем условия
        $this->filter_conditions();
        return (!empty($this->conditions));
    }

    /**
     * Устанавливает группировку для запроса.
     * 
     * @param type|string $fileds - JSON поля для группировки
     * @return type string
     */
    public function setGroupBy($fields = '')
    {
        if(empty($fields)
            && !is_string($fields)
        )
            return $this->groupBy;

        $arFields = json_decode($fields, true);
        if(!empty($arFields)
            && is_array($arFields)
        )
        {
            $this->groupBy = ' GROUP BY ';
            for ($i=0; $i < $ic = count($arFields); $i++)
                $this->groupBy .= '`' . $arFields[$i] . '`,';
            $this->groupBy = substr($this->groupBy, 0, -1);
        }
        
        return $this->groupBy;
    }
}