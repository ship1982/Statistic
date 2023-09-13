<?php

use Sequencer\Sequencer;

/**
 * Класс выполняет парсинг из json строки в SQL условия,
 * а также выполняет запросы к шардированным таблицам l_sequence_4_user_
 */
class parseConditions
{

  /**
   *
   * @var int Начальное значение временного диапазона,
   * в котором следует выполнять поиск и запись данных.
   * По умолчанию это три месяца назад.
   */
  public $time_start = 0;

  /**
   *
   * @var int Конечное значение временного диапазона,
   * в котором следует выполнять поиск и запись данных.
   * По умолчанию, это текущее время.
   */
  public $time_end = 0;

  /**
   *
   * @var array Общий список шардированных таблиц
   */
  private $arr_sharding = [];

  /**
   *
   * @var - Список названий полей в таблицах l_sequence_4_user_
   */
  private $list_fields_name = [];

  /**
   *
   * @var array Список полей, для которых будут подгружатся значения через ajax
   */
  private $list_fields_load_values = [];

  /**
   *
   * @var array Список полей, для которых будут подгружаться значения в виде статичного выпадающего списка
   */
  private $list_fields_static_values = [];

  /**
   *
   * @var array содержит список идентификаторов из таблицы uuids_conditions_bitmaps
   */
  private $list_uuids_conditions_bitmaps = [];

  public function __construct()
  {
    //Загружаем массив конфигурации, если его нет, то вернём ошибку.
    if (empty($GLOBALS['conf']))
    {
      echo json_encode([
              'status' => 'error',
              'description' => 'No config parameters'
          ], JSON_UNESCAPED_UNICODE) . "\r\n";
      die();
    }

    //Получим список полей, для которых будет производится установка условий
    if (is_file($GLOBALS['conf']['root'] . '/config/misk_sequence/user_property_conditions.php'))
    {
      $user_property_conditions = include($GLOBALS['conf']['root'] . '/config/misk_sequence/user_property_conditions.php');

      $this->list_fields_name = (!empty($user_property_conditions['list_fields_name'])) ? $user_property_conditions['list_fields_name'] : [];
    }


    //Получим список всех шардированных таблиц, которые будем фильтровать по времени
    if (is_file($GLOBALS['conf']['root'] . '/config/sharding/list.php'))
    {
      $arr_sharding = include($GLOBALS['conf']['root'] . '/config/sharding/list.php');

      $this->arr_sharding = (!empty($arr_sharding['l_sequence_4_user'])) ? array_keys($arr_sharding['l_sequence_4_user']) : [];
    }

    $this->time_start = time() - (3 * 2629743); //3 месяца 3 *(30.44 дней);
    $this->time_end = time(); //Текущее время
  }

  /**
   * Метод заполняет параметр list_uuids_conditions_bitmaps,
   * идентификаторами из таблицы uuids_conditions_bitmaps.
   * @return array
   */
  public function fill_list_uuids_conditions_bitmaps()
  {
    $this->list_uuids_conditions_bitmaps = []; //Обнулим массив
    common_inc('_database');
    $list_query = query_db(
        1,
        'uuids_conditions_bitmaps',
        "SELECT DISTINCT(uuid) WHERE 1=1"
    );
    if (!empty($list_query))
    {
      while ($row = $list_query->fetch_assoc())
      {
        $this->list_uuids_conditions_bitmaps[$row['uuid']] = $row['uuid'];
      }
    }
    return $this->list_uuids_conditions_bitmaps;
  }

  /**
   * Заполняем uuids_conditions_bitmaps всеми пользователями, которые ещё не были
   * добавлены, за указанный период.
   */
  public function fill_uuids_for_uuids_conditions_bitmaps()
  {
    //Подключим библиотеки
    common_inc('_database');
    common_inc('sharding');
    common_inc('system/cron', 'cron');
    //Подключим логгер
    common_inc('logger');


    $logger = new logger();
    $logger->start();

    //Чтобы клиент не отрубался попробуем вывод на экран
    //common_inc('timer');
    //$timer = new timerPrint();

    //Получим mysqli соединение
    $mysqli_connection = $this->get_connect_multiquery();


    $uuids = []; //Массив uuid'ов в таблицах l_sequence_4_user
    $uuids_queue = []; //Массив uuid'ов в таблице uuids_conditions_bitmaps_queue
    $group_conditions = []; //Массив условий в таблице list_sequence_conditions, которые либо добавлены, либо изменены

    /**
     * Здесь будут хранится данные для вставки в uuids_conditions_bitmaps,
     * разбитые по 1000 строк.
     */
    $data_for_insert = [];

    /**
     * Здесь будут хранится данные для вставки или обновления
     * в uuids_conditions_bitmaps.
     */
    $data_for_insert_update = [];

    $logger->add_event('get_shard');
    //$timer->start('get_shard', "\r\nПолучаем список шардов, для таблиц l_sequence_4_user.");
    //Получаем список шардов, для таблиц l_sequence_4_user
    $list_tables = sharding_getShardTableList('l_sequence_4_user', $this->time_start, $this->time_end);
    //$timer->stop('get_shard', "Время выполнения: %time% ms.\r\n");

    $logger->add_event('get_uuids_from_uuids_conditions_bitmaps');
    //$timer->start('get_uuids', 'Получаем все uuid\'ы из таблицы uuids_conditions_bitmaps.');
    //Получаем все uuid из таблицы uuids_conditions_bitmaps.
    $this->fill_list_uuids_conditions_bitmaps();
    //$timer->stop('get_uuids', "Время выполнения: %time% ms.\r\n");

    $logger->add_event('get_uuids_from_l_sequence_4_user');
    //$timer->start('get_uuids_from_l_sequence_4_user', "Получаем все uuid'ы из шардированных таблиц l_sequence_4_user_ за ближайшие три месяца.\r\n");
    /**
     * Проходим по всем шардированным таблицам,
     * и получаем uuid всех посетителей,
     * которые были в статистике за выбранный период.
     */
    $count_uuids = 0;//Обнулим счётчик uuid'ов в таблицах l_sequence_4_user
    for ($i = 0; $i < count($list_tables); $i++)
    {
      $table_name = 'l_sequence_4_user_' . $list_tables[$i];
      $sql_string = 'SELECT SQL_NO_CACHE DISTINCT `uuid` WHERE 1=1';// LIMIT 5';
      //print("Просматривается таблица: $table_name\r\n");
      //Выполняем запрос на выборку идентификаторов из таблиц l_sequence_4_user_
      $data_query = query_db(1, $table_name, $sql_string);
      if ($data_query)
      {
        //Для каждой строки результата
        while ($row = $data_query->fetch_assoc())
        {
          //Убираем пустые элементы, и те, у которых вместо ключа -
          if (!empty($row['uuid']) && $row['uuid'] != '-')
          {
            $count_uuids++;
            $uuids[$row['uuid']] = $row['uuid'];
          }
        }
      }
    }

    //$timer->stop('get_uuids_from_l_sequence_4_user', "Время выполнения: %time% ms.\r\n");

    $logger->add_event('get_uuids_from_conditions_bitmaps_queue');
    //$timer->start('get_uuids_conditions_bitmaps_queue', "Получаем все uuid'ы для которых появилась новая статистика.");
    /**
     * Добавляем все uuid'ы, которые есть в очереди, т.к. это значит, что появились новые данные по uuid'ам
     */
    $count_uuids = 0;
    //Обнулим счётчик строк uuid'ов в таблицах l_sequence_4_user
    $table_name = 'uuids_conditions_bitmaps_queue';
    //print("Просматривается таблица: $table_name\r\n");
    $sql_string = 'SELECT SQL_NO_CACHE DISTINCT `uuid` WHERE 1=1';
    //Выполняем запрос на выборку идентификаторов из таблиц l_sequence_4_user_
    $data_query = query_db(1, $table_name, $sql_string);
    if ($data_query)
    {
      //Для каждой строки результата
      while ($row = $data_query->fetch_assoc())
      {
        //Убираем пустые элементы, и те, у которых вместо ключа -
        if (!empty($row['uuid']) && $row['uuid'] != '-')
        {
          $count_uuids++;
          $uuids_queue[$row['uuid']] = $row['uuid'];
        }
      }
    }
    //$timer->stop('get_uuids_conditions_bitmaps_queue', "Время выполнения: %time% ms.\r\n");

    $logger->add_event('get_group_conditions');
    //$timer->start('get_group_conditions', "Получаем все группы условий.");
    /**
     * Получаем все группы условий, которые не помечены на удаление.
     */
    $sql_group_conditions = 'SELECT `id`, `position`, `state`, `invers`, `json_cond` WHERE `state` != 3';
    $group_conditions_result = query_db(1, 'list_sequence_conditions', $sql_group_conditions);
    if ($group_conditions_result)
    {
      $group_conditions = $group_conditions_result->fetch_all(MYSQLI_ASSOC);
      //print_r($group_conditions);
    }
    //$timer->stop('get_group_conditions', "Время выполнения: %time% ms.\r\n");

    /**
     * Убираем из таблицы uuids все дубликаты,
     * а также все строки, которые есть в очереди и list_uuids_conditions_bitmaps.
     */
    $logger->add_event('filter_uuids');
    //$timer->start('filter_uuids', "Фильтруем все uuid'ы, убирая все дубликаты и разделяя новые uuid'ы от тех, по которым просто добавилась статистика.");
    //Оставляем только новые uuid'ы
    $uuids = array_diff($uuids, $this->list_uuids_conditions_bitmaps);
    //Перенумеруем массив
    $uuids = array_values($uuids);
    //Перенумеруем массив
    $uuids_queue = array_values($uuids_queue);
    //$timer->stop('filter_uuids', "Время выполнения: %time% ms.\r\n");

    /**
     * Подготавливаем SQL шаблон для его применения в проверке групп условий.
     */

    $sql_where_template = <<<EOT
(SELECT !ISNULL((SELECT
    `uuid`
  FROM `<<<TABLE_ANME>>>`
	WHERE `uuid` = '<<<UUID>>>'
	AND (<<<WHERE>>>) LIMIT 1)))

EOT;

    $sql_group_where = ''; //Строка запроса
    $arr_bit_invers = []; //Массив содержит номер условия(ключ массива) и значение, отражающее необходимость инверсии группы условия.

    for ($i_gc = 0; $i_gc < count($group_conditions); $i_gc++)
    {
      //Получаем массив с признаками инверисонности группы условия
      $arr_bit_invers[$i_gc] = $group_conditions[$i_gc]['invers'];

      //Добавляем группу условий
      $sql_group_where .= str_replace('<<<WHERE>>>', $this->parse_conditions((string)$group_conditions[$i_gc]['json_cond']), $sql_where_template);

      if ($i_gc < (count($group_conditions) - 1))
      {
        $sql_group_where .= '
UNION ALL
';
      }
    }

    //print("Всего найдено ".count($uuids)." новых uuid'дов\r\n");
    //print("Всего найдено ".count($uuids_queue)." обновлённых uuid'дов\r\n");
    //print_r($arr_bit_invers);

    //Получаем битовые маски результатов для всех uuid'ов
    $logger->add_event('condition_uuids_new');
    //$timer->start('condition_uuids_new', "Выполняем проверку групп условий для новых uuid'ов.");
    $data_for_insert = $this->get_arr_bit_mask($uuids, $list_tables, $sql_group_where, $arr_bit_invers);
    //$timer->stop('condition_uuids_new', "Время выполнения: %time% ms.\r\n");
    $logger->add_event('condition_uuids_upd');
    //$timer->start('condition_uuids_upd', "Выполняем проверку групп условий для обновлённый uuid'ов.");
    $data_for_insert_update = $this->get_arr_bit_mask($uuids_queue, $list_tables, $sql_group_where, $arr_bit_invers);
    //$timer->stop('condition_uuids_upd', "Время выполнения: %time% ms.\r\n");

    //Разбиваем массив с полями на части по 10000
    $count_all_insert_rows = count($data_for_insert) + count($data_for_insert_update);
    $data_for_insert = array_chunk($data_for_insert, 10000);

    //print("Всего будет вставлено/заменено строк: ".$count_all_insert_rows."\r\n");
    /**
     * Проходим по пакетам данных, и выполняем вставку строк, для новых uuid'ов.
     */
    for ($i = 0; $i < count($data_for_insert); $i++)
    {
      //print("Выполняется запись $i пакета данных из ".count($data_for_insert)."\r\n");
      query_batchInsert(1, 'uuids_conditions_bitmaps', $data_for_insert[$i]);
    }

    /**
     * Выполняем вставку или обновление uuid'ов, которые появились
     * в результате обновления статистики.
     */
    $logger->add_event('insert_uuid\'s bitmap');
    for ($i = 0; $i < count($data_for_insert_update); $i++)
    {
      $sql_insert = 'INSERT INTO uuids_conditions_bitmaps (`uuid`, `cond_bitmap`) VALUES ';
      $sql_insert .= "('" . $data_for_insert_update[$i]['uuid'] . "', '" . $data_for_insert_update[$i]['cond_bitmap'] . "')  ON DUPLICATE KEY UPDATE `cond_bitmap` = '" . $data_for_insert_update[$i]['cond_bitmap'] . "'";

      multyQuery_db(1, 'uuids_conditions_bitmaps', $sql_insert);
    }

    /**
     * По завершению записи данных удаляем все строки из очереди
     */
    $logger->add_event('clear_uuids_conditions_bitmaps_queue');
    for ($i = 0; $i < count($data_for_insert_update); $i++)
    {
      if (!empty($data_for_insert_update[$i]) && !empty($data_for_insert_update[$i]['uuid']))
      {
        delete_db(1, 'uuids_conditions_bitmaps_queue', ['uuid' => $data_for_insert_update[$i]['uuid']]);
      }
    }

    $memory = number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
    writeLog(basename(__FILE__), $logger->stop($memory));

  }

  /**
   * Новая функция для @see fill_uuids_for_uuids_conditions_bitmaps
   */
  public function fufucb()
  {
    //Подключим библиотеки
    common_inc('_database');
    common_inc('sharding');
    common_inc('system/cron', 'cron');
    common_inc('logger');

    $logger = new logger();
    $logger->start();

    // даннеы берем за три месяца
    $start = time() - (3 * 2629743); //3 месяца 3 *(30.44 дней);
    $end = time();
    $rowsByTable = 500; // мы не считаем кол-во записей в таблице, чтобы не тратить на это время. Чаще всего записей не бывает больше 4 000 000.
    $mucb = new \misk_sequence\MiskUCB();

    // получение данных из uuids_conditions_bitmaps_queue
    $uuid_queue = [];
    $mucbq = new \misk_sequence\MiskUCBQ();
    $uuidData = $mucbq->_list(['uuid'], [], [], 50000);
    for ($i = 0; $i < $ic = count($uuidData); $i++)
    {
      $uuid_queue[$uuidData[$i]['uuid']] = $uuidData[$i]['uuid'];
    }
    $uuids_queue = array_values($uuid_queue);

    // получаем диапазон таблиц
    $range = \sharding\ShardWork::sharding_getShards($start, $end);

    // берем пачками по 10 000 из таблиц секвенсора
    if (!empty($range))
    {
      // формируем список таблиц
      for ($i = 1; $i < $ic = count($range); $i++)
      {
        $list_tables[] = 'l_sequence_4_user_' . $range[$i];
      }

      $uuids = [];
      for ($i = 1; $i < $ic = count($range); $i++)
      {
        // экземпляр для секвенсора
        $sequencer = new Sequencer([$range[$i]]);
        $lastId = 0; // id последней выбранной записи
        // записи берем по 10 000, но с учетом индексов и на протяжении всей таблицы
        for ($j = 0; $j < $rowsByTable; $j++)
        {
          $partList = $sequencer->_list([
              'uuid',
              'id'
          ], [
              [
                  'id',
                  '>',
                  $lastId
              ]
          ], ['id' => 'ASC'], 10000);

          // если получилось выбрать записи, то смотрим их наличие в uuids_conditions_bitmaps
          $uuid = []; // массив uuid
          if (!empty($partList))
          {
            for ($m = 0; $m < $mc = count($partList); $m++)
            {
              if (!empty($partList[$m]['id']))
              {
                $lastId = $partList[$m]['id'];
              }
              if (!empty($partList[$m]['uuid']) && $partList[$m]['uuid'] != '-')
              {
                $uuid[$partList[$m]['uuid']] = $partList[$m]['uuid'];
              }
            }

            // смотрим наличие данных в таблице uuids_conditions_bitmaps
            $arListUuid = $mucb->_list(['uuid'], [
                [
                    'uuid',
                    'in',
                    $uuid
                ]
            ]);
            if (!empty($arListUuid))
            {
              for ($m = 0; $m < $mc = count($arListUuid); $m++)
              {
                if (!empty($partList[$m]['uuid']) && $partList[$m]['uuid'] != '-')
                {
                  // если значения нет, то добавляем его в массив
                  if (empty($uuid[$partList[$m]['uuid']]))
                  {
                    $uuids[$partList[$m]['uuid']] = $partList[$m]['uuid'];
                  }
                }
              }
            }
          }
        }
      }

      $uuids = array_values($uuids);

      $listSequencerCondition = new \ListSequencerCondition\ListSequencerCondition();
      $group_conditions = $listSequencerCondition->_list([
          'id',
          'position',
          'state',
          'invers',
          'json_cond'
      ], [
          [
              'state',
              '<>',
              3
          ]
      ]);

      /**
       * Подготавливаем SQL шаблон для его применения в проверке групп условий.
       */

      $sql_where_template = <<<EOT
(SELECT !ISNULL((SELECT
    `uuid`
  FROM `<<<TABLE_ANME>>>`
	WHERE `uuid` = '<<<UUID>>>'
	AND (<<<WHERE>>>) LIMIT 1)))

EOT;

      $sql_group_where = ''; //Строка запроса
      $arr_bit_invers = []; //Массив содержит номер условия(ключ массива) и значение, отражающее необходимость инверсии группы условия.

      for ($i_gc = 0; $i_gc < count($group_conditions); $i_gc++)
      {
        //Получаем массив с признаками инверисонности группы условия
        $arr_bit_invers[$i_gc] = $group_conditions[$i_gc]['invers'];

        //Добавляем группу условий
        $sql_group_where .= str_replace('<<<WHERE>>>', $this->parse_conditions((string)$group_conditions[$i_gc]['json_cond']), $sql_where_template);

        if ($i_gc < (count($group_conditions) - 1))
        {
          $sql_group_where .= '
UNION ALL
';
        }
      }

      //print("Всего найдено ".count($uuids)." новых uuid'дов\r\n");
      //print("Всего найдено ".count($uuids_queue)." обновлённых uuid'дов\r\n");
      //print_r($arr_bit_invers);

      //Получаем битовые маски результатов для всех uuid'ов
      $logger->add_event('condition_uuids_new');
      //$timer->start('condition_uuids_new', "Выполняем проверку групп условий для новых uuid'ов.");
      $data_for_insert = $this->get_arr_bit_mask($uuids, $list_tables, $sql_group_where, $arr_bit_invers);
      //$timer->stop('condition_uuids_new', "Время выполнения: %time% ms.\r\n");
      $logger->add_event('condition_uuids_upd');
      //$timer->start('condition_uuids_upd', "Выполняем проверку групп условий для обновлённый uuid'ов.");
      $data_for_insert_update = $this->get_arr_bit_mask($uuids_queue, $list_tables, $sql_group_where, $arr_bit_invers);
      //$timer->stop('condition_uuids_upd', "Время выполнения: %time% ms.\r\n");

      //Разбиваем массив с полями на части по 10000
      $count_all_insert_rows = count($data_for_insert) + count($data_for_insert_update);
      $data_for_insert = array_chunk($data_for_insert, 10000);

      //print("Всего будет вставлено/заменено строк: ".$count_all_insert_rows."\r\n");
      /**
       * Проходим по пакетам данных, и выполняем вставку строк, для новых uuid'ов.
       */
      for ($i = 0; $i < count($data_for_insert); $i++)
      {
        //print("Выполняется запись $i пакета данных из ".count($data_for_insert)."\r\n");
        query_batchInsert(1, 'uuids_conditions_bitmaps', $data_for_insert[$i]);
      }

      /**
       * Выполняем вставку или обновление uuid'ов, которые появились
       * в результате обновления статистики.
       */
      $logger->add_event('insert_uuid\'s bitmap');
      for ($i = 0; $i < count($data_for_insert_update); $i++)
      {
        $sql_insert = 'INSERT INTO uuids_conditions_bitmaps (`uuid`, `cond_bitmap`) VALUES ';
        $sql_insert .= "('" . $data_for_insert_update[$i]['uuid'] . "', '" . $data_for_insert_update[$i]['cond_bitmap'] . "')  ON DUPLICATE KEY UPDATE `cond_bitmap` = '" . $data_for_insert_update[$i]['cond_bitmap'] . "'";

        multyQuery_db(1, 'uuids_conditions_bitmaps', $sql_insert);
      }

      /**
       * По завершению записи данных удаляем все строки из очереди
       */
      $logger->add_event('clear_uuids_conditions_bitmaps_queue');
      for ($i = 0; $i < count($data_for_insert_update); $i++)
      {
        if (!empty($data_for_insert_update[$i]) && !empty($data_for_insert_update[$i]['uuid']))
        {
          delete_db(1, 'uuids_conditions_bitmaps_queue', ['uuid' => $data_for_insert_update[$i]['uuid']]);
        }
      }

      $memory = number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
      writeLog(basename(__FILE__), $logger->stop($memory));
    }
  }

  /**
   * Возвращает массив uuid'ов с битовыми масками, отражающими результат
   * выполнения групп условия для uuid'а.
   *
   * @param array  $arr_uuids       Массив uuid'ов пользователей
   * @param array  $list_tables     Массив шардированных таблиц
   * @param string $sql_group_where Суммарная строка всех сгруппированных запросов
   * @param array  $arr_bit_invers  Массив инверсий, для каждой группы условия.
   *
   * @return array
   */
  private function get_arr_bit_mask($arr_uuids = [], $list_tables = [], string $sql_group_where, $arr_bit_invers = [])
  {
    $data_for_insert = [];

    $tmp_percent_counter = 0;
    $percent_counter = 0;
    $sql_where = '';
    $cntr = 0;
    //common_inc('timer');
    //$timer = new timerPrint();

    print("\n");
    for ($i = 0; $i < count($arr_uuids); $i++)
    {
      $sql_multi_query = '';
      $check_query = [];

      $percent_counter = round(($i / count($arr_uuids) * 100), 0);

      if ($cntr == 0)
      {
        //$timer->start('condition_group', "\r\nВыполнение групп условий... ");
        $cntr++;
      }

      /**
       * Подготовим маску c результатами запросов для каждого uuid'а
       */
      $bit_mask = '';
      for ($j = 0; $j < count($list_tables); $j++)
      {
        $table_name = 'l_sequence_4_user_' . $list_tables[$j];
        $sql_where = str_replace('<<<TABLE_ANME>>>', $table_name, $sql_group_where);
        $sql_where = str_replace('<<<UUID>>>', $arr_uuids[$i], $sql_where);

        $sql_multi_query .= $sql_where . ';';
      }
      //print("\nSQL запрос группы условий:\n".$sql_multi_query."\n");
      //Выполнение группы подзапросов
      $res_mq = $this->get_data_multi_query($sql_multi_query);

      //Преобразование результатов проверки в битовые маски
      for ($k = 0; $k < count($res_mq); $k++)
      {
        $check_query[] = implode('', $res_mq[$k]);
      }

      $bit_mask = $this->merge_bit_mask($check_query);

      //Для каждого бита, для которого требуется инверсия - выполним её.
      /**
       * Как работает: берем бит,
       * представим что он равен 0, тогда abs(0-1) = 1
       * представим что он равен 1, тогда abs(1-1) = 0
       */
      $arr_bit_mask = str_split($bit_mask);
      //print("\r\nПервоначальный массив битов результатов. Начальная битовая маска: ".$bit_mask."\r\n");
      //print_r($arr_bit_mask);
      for ($i_b = 0; $i_b < count($arr_bit_invers); $i_b++)
      {
        if (array_key_exists($i_b, $arr_bit_mask)
            && $arr_bit_invers[$i_b] == 1
        )
        {
          $arr_bit_mask[$i_b] = abs($arr_bit_mask[$i_b] - 1);
        }
      }
      $bit_mask = implode('', $arr_bit_mask);
      //print("\r\nИтоговый массив битов результатов. Итоговая битовая маска: ".$bit_mask."\r\n");
      //print_r($arr_bit_mask);

      /**
       * Подготавливаем массив с полями, для вставки в uuids_conditions_bitmaps.
       * После заполнения этого массива он будет разбит на части по 10000
       * элементов.
       */

      $data_for_insert[] = [
          'uuid' => $arr_uuids[$i],
          'cond_bitmap' => $bit_mask
      ];


      if ($tmp_percent_counter != $percent_counter)
      {
        //$timer->stop('condition_group', "Время выполнения: %time% ms.");
        $tmp_percent_counter = $percent_counter;
        $cntr = 0;
        print("\r\nВыполнено $percent_counter%\r\n");
      }

    }

    return $data_for_insert;
  }

  /**
   * Получает массив битовых масок и сливает их
   * битовым оператором |
   *
   * @param array $arr_masks
   *
   * @return string
   */
  public function merge_bit_mask($arr_masks)
  {
    $bitmask_result = '';
    if (is_array($arr_masks) && !empty($arr_masks))
    {
      for ($i = 0; $i < count($arr_masks); $i++)
      {
        $bitmask_result = ($bitmask_result | $arr_masks[$i]);
      }
    }

    return $bitmask_result;
  }


  /**
   * Возврадает SQL строку, содержащую условия из JSON
   *
   * @param string $json_conditions
   */
  public function parse_conditions(string $json_conditions)
  {

    common_inc('ip_conv');

    $count_query = 0; //Счетчик условий в группе
    $sql_query = ''; //Строка запросов

    $json_cond = json_decode($json_conditions, JSON_UNESCAPED_UNICODE);

    //Проходим по каждому условию в группе
    for ($i = 0; $i < count($json_cond); $i++)
    {

      //Если в условии есть все необходимые данные
      if (array_key_exists('field', $json_cond[$i]) && array_key_exists('type_cond', $json_cond[$i]) && array_key_exists('value_cond', $json_cond[$i]) && array_key_exists('andor', $json_cond[$i]) && array_key_exists($json_cond[$i]['field'], $this->list_fields_name)
      )
      {
        $sql_query .= ($count_query > 0) ? ' ' . $json_cond[$i]['andor'] . ' ' : ' ';

        //$sql_query .= '`' . $this->list_fields_name[$json_cond[$i]['field']] . '`';
        $sql_field = '`' . $this->list_fields_name[$json_cond[$i]['field']] . '`';

        $value = $json_cond[$i]['value_cond'];

        switch ($json_cond[$i]['type_cond'])
        {
          case 1://Точно соответствует
            $sql_query .= $sql_field . ' = \'' . $value . '\'';

            break;
          case 2://Содержит
            $sql_query .= $sql_field . ' LIKE \'%' . $value . '%\'';
            break;
          case 3://Начинается с
            $sql_query .= $sql_field . ' LIKE \'' . $value . '%\'';
            break;
          case 4://Заканчивается на
            $sql_query .= $sql_field . ' LIKE \'%' . $value . '\'';
            break;
          case 5://Соответствует регулярному выражению
            $sql_query .= $sql_field . ' REGEXP \'' . $value . '\'';
            break;
          case 6://Является одним из
            $sql_query .= $sql_field . ' IN \'' . $value . '\'';
            break;
          case 7://Не является точным соответствием
            $sql_query .= $sql_field . ' != \'' . $value . '\'';
            break;
          case 8://Не содержит
            $sql_query .= $sql_field . ' NOT LIKE \'%' . $value . '%\'';
            break;
          case 9://Интервал
            $value = explode('-', $value);
            $sql_query .= '(' . $sql_field . ' BETWEEN \'' . $value[0] . '\' AND \'' . ((array_key_exists(1, $value)) ? $value[1] : '') . '\')';
            break;
          case 10://Больше
            $sql_query .= $sql_field . ' > \'' . $value . '\'';
            break;
          case 11://Больше (день)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') > \'' . ((int)$value * 86400) . '\'';
            break;
          case 12://Больше (неделя)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') > \'' . ((int)$value * 604800) . '\'';
            break;
          case 13://Больше (месяц)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') > \'' . ((int)$value * 2629743) . '\'';
            break;
          case 14://Равно
            $sql_query .= $sql_field . ' = \'' . $value . '\'';
            break;
          case 15://Равно (день)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') = \'' . ((int)$value * 86400) . '\'';
            break;
          case 16://Равно (неделя)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') = \'' . ((int)$value * 604800) . '\'';
            break;
          case 17://Равно (месяц)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') = \'' . ((int)$value * 2629743) . '\'';
            break;
          case 18://Меньше
            $sql_query .= $sql_field . ' < \'' . $value . '\'';
            break;
          case 19://Меньше (день)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') < \'' . ((int)$value * 86400) . '\'';
            break;
          case 20://Меньше (неделя)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') < \'' . ((int)$value * 604800) . '\'';
            break;
          case 21://Меньше (месяц)
            $sql_query .= '(UNIX_TIMESTAMP(NOW()) - ' . $sql_field . ') < \'' . ((int)$value * 2629743) . '\'';
            break;
          case 22://Маска сети
            $value = $this->conv_cidr_to_interval((int)$value);
            $sql_query .= '(' . $sql_field . ' BETWEEN \'' . $value[0] . '\' AND \'' . ((array_key_exists(1, $value)) ? $value[1] : '') . '\')';
            break;
          case 23://Соответствует IP
            $sql_query .= $sql_field . ' = \'' . ip_conv_ip_to_binary_32($value) . '\'';
            break;
          default:
            break;
        }
        $count_query++;
      }
    }
    return $sql_query;
  }

  /**
   * Метод возвращает первый и последний элементы диапозона ip адресов в
   * целочисленном формате.
   *
   * @param int $cidr_num Номер записи в list_condition_ip_interval
   *
   * @return array
   */
  private function conv_cidr_to_interval(int $cidr_num)
  {
    common_inc('_database');
    $cidr_sel = select_db(1, 'list_condition_ip_interval', ['prefix'], ['id' => $cidr_num]);
    if (!empty($cidr_sel) && $cidr_sel->num_rows > 0)
    {
      common_inc('ip_conv');
      $cidr = $cidr_sel->fetch_assoc()['prefix'];
      $re = ip_conv_cidr_to_range($cidr);
      //return $re;
      return [
          ip_conv_ip_to_binary_32(ip_conv_cidr_to_range($cidr)[0]),
          ip_conv_ip_to_binary_32(ip_conv_cidr_to_range($cidr)[1])
      ];
    }
    else
    {
      return [
          0,
          0
      ];
    }
  }

  /**
   * Обновляем данные по маскам.
   *
   * @param array  $condition
   * @param int    $type
   * @param string $uuid
   */
  function updateBitmask($condition = [], $uuid = '', $conditionCount, $resverse = false)
  {
    $bit = ($condition['invers'] == 1) ? 0 : 1;
    if ($resverse)
    {
      $bit = ($condition['invers'] == 1) ? 1 : 0;
    }
    $bitmask = '';
    $operation = (0 === $bit) ? ' & ~ ' : ' | ';
    // определяем операцию
    for ($i = 0; $i < $conditionCount; $i++)
    {
      if ($condition['position'] == $i)
      {
        $bitmask .= '1';
      }
      else
      {
        $bitmask .= '0';
      }
    }
    $sql = "INSERT INTO uuids_conditions_bitmaps (uuid,cond_bitmap) VALUES ('$uuid'," . bindec($bitmask) . ") ON DUPLICATE KEY UPDATE cond_bitmap = cond_bitmap$operation" . bindec($bitmask);
    simple_query($sql);
  }

  /**
   * @param string $sql_where
   * @param array  $condition
   * @param int    $type
   */
  public function send_query_sequencer(string $sql_where, $condition = [], $type = 0, $conditionCount = 0)
  {
    // Получаем таблицы l_sequence_4_user_, в которых будем выполнять sql запрос.
    common_inc('sharding');
    $list_tables = array_reverse(sharding_getShardTableList(
        'l_sequence_4_user',
        $this->time_start,
        $this->time_end
    ));
    common_inc('_database'); //Подключаем библиотечку работы с БД
    // Для каждой таблицы, которая подпадает в интервал, делаем выборку
    for ($i = 0; $i < $ic = count($list_tables); $i++)
    {
      // выборка на записи, что входят в выборку
      $sql_string = "SELECT DISTINCT(uuid) WHERE $sql_where";
      $data = query_db(
          $list_tables[$i],
          'l_sequence_4_user',
          $sql_string
      );
      /**
       * получаем данные, что есть в таблице
       */
      $inTable = [];
      if (!empty($data))
      {
        echo "Обработка в таблице " . $list_tables[$i] . "\n";
        while ($row = mysqli_fetch_assoc($data))
        {
          $inTable[] = $row['uuid'];
          $this->updateBitmask(
              $condition,
              $row['uuid'],
              $conditionCount
          );
        }
      }

      // получаем данные, что не входят в выборку
      $sql_string = "SELECT DISTINCT(uuid) WHERE uuid NOT IN ('".implode("','", $inTable)."')";
      $data = query_db(
          $list_tables[$i],
          'l_sequence_4_user',
          $sql_string
      );
      if (!empty($data))
      {
        echo "Обработка не подходящих условий " . $list_tables[$i] . "\n";
        while ($row = mysqli_fetch_assoc($data))
        {
          $this->updateBitmask(
              $condition,
              $row['uuid'],
              $conditionCount,
              true
          );
        }
      }
    }
  }

  /**
   * Метод возвращает mysqli объект соединения
   * @return \mysqli
   */
  private function get_connect_multiquery()
  {
    common_inc('sharding');
    $link = sharding_getConnection();
    if (!$link['connect'])
    {
      if (function_exists('common_appLog'))
      {
        $file = __FILE__;
        $line = __LINE__;
        $function = __FUNCTION__;
        common_appLog($file, $line, $function, 'connect_db() вернула false');
      }
      else
      {
        return false;
      }
    }
    else
    {
      return $link['connect'];
    }
  }

  /**
   * Метод возвращает результат запроса.
   *
   * @param string $sql_multi_query
   *
   * @return array
   */
  private function get_data_query(string $sql_query)
  {

    //Получим mysqli соединение
    $mysqli_connection = $this->get_connect_multiquery();

    $data_query = $mysqli_connection->query($sql_query);
    if ($data_query)
    {
      return $data_query->fetch_all(MYSQLI_ASSOC);
    }
    return [];
  }

  /**
   * Метод возвращает результат мультизапроса,
   * внимание формат возврата следующщий:
   * [Порядковый номер запроса в мультизапросе] => [[0] => 'Знач1', [1] => 'Знач2']
   *
   * @param string $sql_multi_query строка запроса
   *
   * @return array
   */
  private function get_data_multi_query(string $sql_multi_query)
  {
    $return_arr_res = [];
    $cnt_res = 0;

    //Получим mysqli соединение
    $mysqli_connection = $this->get_connect_multiquery();

    if ($mysqli_connection && $mysqli_connection->multi_query($sql_multi_query))
    {
      do
      {
        if ($result = $mysqli_connection->store_result())
        {

          while ($row = $result->fetch_row())
          {
            $return_arr_res[$cnt_res][] = $row[0];
          }
          $cnt_res++;
          //$result->free_result();
          //$result->free();
        }
      } while ($mysqli_connection->more_results() && $mysqli_connection->next_result());
    }
    return $return_arr_res;
  }

  /**
   * Метод выполняет добавление бита в битовую маску
   * таблицы uuids_conditions_bitmaps для новой группы условий,
   * замену бита для изменённой группы условий,
   * и удаление бита, для удаляемогой группы условий.
   *
   * @param array $arr_list_sequence_conditions - массив групп условий
   * @param int   $type                         - Тип изменения:
   *                                            1 - добавление группы условий,
   *                                            2 - изменение группы условий,
   *                                            3 - удаление группы условий
   *
   * @return boolean
   */
  public function group_query_change($arr_list_sequence_conditions, int $type = 0, $countAllCondition)
  {
    common_inc('_database');
    if (!empty($arr_list_sequence_conditions)
        && is_array($arr_list_sequence_conditions)
    )
    {
      //Проходим по всем добавляемым условиям
      for ($i = 0; $i < $ic = count($arr_list_sequence_conditions); $i++)
      {
        /**
         * Если выполняется удаление условия, сразу очищаем ненужный бит
         * и выходим из метода.
         */
        if ((int)$type == 3)
        {
          if (array_key_exists('id', $arr_list_sequence_conditions[$i])
              && array_key_exists('position', $arr_list_sequence_conditions[$i])
          )
          {
            /**
             * Т.к. бит удаляется под одним номеромдля всех записей в
             * таблице uuids_conditions_bitmaps, без выполнения отдельных запросов
             * - запрос удаления выполняется единожды для конкретной группы.
             */
            $this->bit_query_del($arr_list_sequence_conditions[$i]['position']);

            //Удаляем группу условий
            if (!empty($arr_list_sequence_conditions[$i])
                && !empty($arr_list_sequence_conditions[$i]['id'])
            )
            {
              delete_db(
                  1,
                  'list_sequence_conditions', [
                      'id' => $arr_list_sequence_conditions[$i]['id']
                  ]
              );
              //Если удаление прошло успешно, то вызовем хранимую процедуру репозирования записей.
              multyQuery_db(1, '', 'CALL repos_list_sequence_conditions;');
            }
            else
            {
              return false;
            }
          }
        }
        else
        {
          /**
           * Проверим наличие строки условия и позиции, если их нет,
           * то не будем выполнять проверку условия.
           */
          if (array_key_exists('json_cond', $arr_list_sequence_conditions[$i])
              && array_key_exists('invers', $arr_list_sequence_conditions[$i])
              && array_key_exists('position', $arr_list_sequence_conditions[$i])
              && array_key_exists('id', $arr_list_sequence_conditions[$i])
              && array_key_exists('state', $arr_list_sequence_conditions[$i])
          )
          {
            // И для каждого uuid в таблице uuids_conditions_bitmaps, выполняем запрос.
            $parse_condition = $this->parse_conditions($arr_list_sequence_conditions[$i]['json_cond']);
            $this->send_query_sequencer(
                $parse_condition,
                $arr_list_sequence_conditions[$i],
                $type,
                $countAllCondition
            );

            //Обнуляем статус для добавленной/изменённой группы условий
            if (!empty($arr_list_sequence_conditions[$i])
                && !empty($arr_list_sequence_conditions[$i]['id'])
            )
            {
              update_db(1, 'list_sequence_conditions', ['state' => 0], ['id' => $arr_list_sequence_conditions[$i]['id']]);
            }
            else
            {
              return false;
            }
          }
        }
      }
    }
    else
    {
      return false;
    }
  }

  /*	 * **
   * Методы работы с битами в таблице uuids_conditions_bitmaps
   * *** */

  /**
   * Метод выполняет добавление бита в таблице uuids_conditions_bitmaps,
   * в поле cond_bitmap
   *
   * @param int        $bit  - значение бита
   * @param int|string $uuid - идентификатор группы условия
   *
   * @return boolean
   */
  public function bit_query_add(int $bit, $uuid)
  {

    $uuid = filter_var($uuid, FILTER_SANITIZE_STRING);
    if (empty($uuid))
    {
      return false;
    }


    common_inc('_database');

    $sql_string = "UPDATE uuids_conditions_bitmaps SET `cond_bitmap` = CONCAT(`cond_bitmap`, $bit) WHERE `uuid` = '$uuid';";
    //common_pre($sql_string);
    return $this->get_data_multi_query($sql_string);
    //return multyQuery_db(1, 'uuids_conditions_bitmaps', $sql_string);
  }

  /**
   * Метод выполняет изменение бита в таблице uuids_conditions_bitmaps,
   * в поле cond_bitmap
   *
   * @param int        $pos_bit - позиция бита в маске
   * @param int        $bit     -значение бита
   * @param int|string $uuid    - идентификатор группы условия
   *
   * @return boolean
   */
  public function bit_query_upd(int $pos_bit, int $bit, $uuid)
  {

    $uuid = filter_var($uuid, FILTER_SANITIZE_STRING);
    if (empty($uuid))
    {
      return false;
    }
    common_inc('_database');

    /**
     * Обновляем маску, удаляя, элемент, по указанной позиции.
     * Нужно учесть, что в MySQL позиция строки начинается с 1
     */
    $pos_bit++;
    $sql_string = "UPDATE uuids_conditions_bitmaps SET `cond_bitmap` = INSERT(`cond_bitmap`, $pos_bit, 1, $bit) WHERE `uuid` = '$uuid';";

    return $this->get_data_multi_query($sql_string);
    //return multyQuery_db(1, 'uuids_conditions_bitmaps', $sql_string);
  }

  /**
   * Метод удаляет бит в маске, в таблице uuids_conditions_bitmaps,
   * по указанном его номеру, тем самым сдвигая все последующие биты.
   *
   * @param int $pos_bit - позиция бита в маске
   *
   * @return boolean
   */
  public function bit_query_del(int $pos_bit)
  {

    common_inc('_database');

    /**
     * Обновляем маску, удаляя, элемент, по указанной позиции.
     * Нужно учесть, что в MySQL позиция строки начинается с 1
     */
    $pos_bit++;
    $sql_string = "UPDATE uuids_conditions_bitmaps SET `cond_bitmap` = INSERT(`cond_bitmap`, $pos_bit, 1, '');";

    //common_pre($sql_string);
    return $this->get_data_multi_query($sql_string);
    //return multyQuery_db(1, 'uuids_conditions_bitmaps', $sql_string);
  }

  /*	 * ****
   * Сеттеры и Геттеры
   * *** */

  function get_time_start()
  {
    return $this->time_start;
  }

  function get_time_end()
  {
    return $this->time_end;
  }

  function set_time_start($time_start)
  {
    $this->time_start = (int)$time_start;
  }

  function set_time_end($time_end)
  {
    $this->time_end = (int)$time_end;
  }

  /**
   * Получение условий из БД.
   *
   * @return array
   */
  function getAllCondition()
  {
    common_inc('_database');
    $group_conditions_result = select_db(1, 'list_sequence_conditions', ['*'], [], ['position' => 'ASC']);
    if ($group_conditions_result)
    {
      while ($row = $group_conditions_result->fetch_assoc())
      {
        $group_conditions[] = $row;
      }
    }

    return $group_conditions;
  }

  /**
   * Очищаем данные по пользователям.
   */
  function clearListUuids()
  {
    $this->list_uuids_conditions_bitmaps = [];
  }

  /**
   * Получаем условия по пользователям частями.
   *
   * @param int $start
   *
   * @return array
   */
  function getDataWithListCondition($start = 0)
  {
    $this->clearListUuids();
    common_inc('_database');
    $res = query_db(
        1,
        'uuids_conditions_bitmaps',
        "SELECT DISTINCT(uuid) WHERE 1=1
        LIMIT $start,100000"
    );
    if (!empty($res))
    {
      while ($row = $res->fetch_assoc())
      {
        $this->list_uuids_conditions_bitmaps[] = $row['uuid'];
      }
    }

    return $this->list_uuids_conditions_bitmaps;
  }

  /**
   * Выбираем пачками пользователей и считаем для них условия.
   *
   * @param array  $params
   * @param logger $logger
   */
  public function bitmapsJob($params = [], $countAllCondition = 0)
  {
    if (!empty($params))
    {
      for ($j = 0; $j < $jc = count($params); $j++)
      {
        if (!empty($params[$j]['condition'])
            && !empty($params[$j]['type'])
        )
        {
          $this->group_query_change(
              $params[$j]['condition'],
              $params[$j]['type'],
              $countAllCondition
          );
        }
      }
    }
  }
}