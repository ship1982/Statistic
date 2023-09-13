<?php

// подключаем основную библиотеку для API
use TableSize\TableSize;

include_once __DIR__ . '/../lib/autoload.php';

/**
 * Class TableSizeJob
 *
 * Класс для получения информации по таблицам раз в сутки.
 * Алгоритм работы:
 * 1 - запускаемв  кроне раз в сутки в 23-59
 * 2 - получаем по всем таблицам все параметры (SHOW TABLES STATUS)
 * 3 - формруем массив с названием таблицы, кол-вом строчек, размером таблицы (в мегабайтах и в месте с индексом)
 * 4 - сохраняем значение в таблицу system_table_size
 */
class TableSizeJob
{
  /**
   * @var array - список таблиц
   */
  public $tables = [];

  /**
   * Формируем массив с кол-вом строчек в таблице.
   *
   * @param $data
   * @param $a
   */
  function getRows(&$data, $a)
  {
    if (!empty($a['Rows']))
    {
      $data['rows'] = (int)$a['Rows'];
    }
    else
    {
      $data['rows'] = 0;
    }
  }

  /**
   * Формируем массив с размером таблицы.
   *
   * @param $data
   * @param $a
   */
  function getSize(&$data, $a)
  {
    if (!empty($a['Data_length'])
        || !empty($a['Index_length'])
    )
    {
      $data['size'] = round((($a['Data_length'] + $a['Index_length']) / 1024 / 1024), 2);
    }
    else
    {
      $data['size'] = 0;
    }
  }

  /**
   * Получаем параметры по всем таблицам.
   */
  function getTableStatus()
  {
    // подключаем библиотеку для работы с БД
    common_inc('_database');
    $o = simple_query("SHOW TABLE STATUS");
    if (!empty($o))
    {
      while ($a = mysqli_fetch_assoc($o))
      {
        $data = [];
        $this->getRows($data, $a);
        $this->getSize($data, $a);
        $this->tables[$a['Name']] = $data;
      }
    }
  }

  /**
   * Сохраняем данные в таблицу.
   */
  function save()
  {
    if (!empty($this->tables))
    {
      // подключаем модель для сохранения данных
      $tableSize = new TableSize();
      // сохраняем данные модели построчно
      $time = date('Ymd'); // получаем текущую дату
      foreach ($this->tables as $table => $param)
      {
        $info = array_merge(
            [
                'time' => $time,
                'table' => $table
            ],
            $param
        );
        $tableSize->save($info, []);
      }
    }
  }

  /**
   * Мастре функция для запуска механизма
   */
  function run()
  {
    $this->getTableStatus();
    $this->save();
  }
}

$tableSize = new TableSizeJob();
$tableSize->run();