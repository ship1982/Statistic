<?php

namespace TableSize;

use model\Model;

class TableSize extends Model
{
  /**
   * @var array - массив полей таблицы с их значениями для одной записи. Здесь храниться модель.
   */
  public $options = [];

  /**
   * @var array - изначальный ключ шардинга
   */
  protected $shard = [1];

  /**
   * @var string - таблица для модели
   */
  public $table = 'system_table_size';

  /**
   * Правила валидации для модели.
   *
   * @return array
   */
  public function getValidationRule()
  {
    return [];
  }

  /**
   * Список дополнительных значений к полям.
   * Например рускоязыное название или какие-то доп параметры.
   *
   * @return array
   */
  public function attributes()
  {
    return [
        'table' => [
            'name' => 'Таблица',
            'in_list' => 1
        ],
        'size' => [
            'name' => 'Размер (Мб)',
            'in_list' => 1,
        ],
        'rows' => [
            'name' => 'Кол-во строк',
            'in_list' => 1
        ]
    ];
  }

  /**
   * Получение списка данных из БД с четом времени и оператора.
   *
   * @param int    $time
   * @param string $operator
   *
   * @return array
   */
  public function getData($time = 0, $operator = '=')
  {
    $data = [];
    if (!empty($time))
    {
      $this->select();
      $this->from();
      $this->where();
      $this->addWhere($operator, ['time' => $time]);
      $this->execute();
      $data = $this->fetch();
    }

    return $data;
  }

  /**
   * Получение всех данных и вычисление разницы.
   *
   * @param int $from
   * @param int $to
   *
   * @return array
   */
  public function getAll($from = 0, $to = 0)
  {
    $data = [];
    if (!empty($from)
        && !empty($to)
    )
    {
      $first = $this->formatAnswer($this->getData($from));
      $second = $this->formatAnswer($this->getData($to));
      $data = $this->subData($first, $second);
    }

    return $data;
  }

  /**
   * Вычитание разницы между двумя массива с таблицами.
   *
   * @param array $first
   * @param array $second
   *
   * @return array
   */
  public function subData($first = [], $second = [])
  {
    $result = [];
    if (!empty($first)
        && !empty($second)
    )
    {
      foreach ($second as $table => $info)
      {
        if (!empty($first[$table]))
        {
          if (isset($second[$table]['size'])
              && isset($first[$table]['size'])
          )
          {
            $result[$table]['size'] = $second[$table]['size'] - $first[$table]['size'];
          }

          if (isset($second[$table]['rows'])
              && isset($first[$table]['rows'])
          )
          {
            $result[$table]['rows'] = $second[$table]['rows'] - $first[$table]['rows'];
          }
        }
      }
    }

    return $result;
  }

  /**
   * Переформатирование вывода на ассоциативный массив.
   *
   * @param array $data
   *
   * @return array
   */
  public function formatAnswer($data = [])
  {
    $result = [];
    $result['all']['size'] = 0;
    $result['all']['rows'] = 0;
    if (!empty($data))
    {
      for ($i = 0; $i < $ic = count($data); $i++)
      {
        if (!empty($data[$i]['table'])
            && isset($data[$i]['rows'])
            && isset($data[$i]['size'])
        )
        {
          // строчки
          if (empty($result[$data[$i]['table']]['rows']))
          {
            $result[$data[$i]['table']]['rows'] = 0;
          }

          $result[$data[$i]['table']]['rows'] += $data[$i]['rows'];

          // размер
          if (empty($result[$data[$i]['table']]['size']))
          {
            $result[$data[$i]['table']]['size'] = 0;
          }

          $result[$data[$i]['table']]['size'] += $data[$i]['size'];

          // общее кол-во
          $result['all']['size'] += $data[$i]['size'];
          $result['all']['rows'] += $data[$i]['rows'];
        }
      }
    }

    return $result;
  }

  /**
   * TableSize constructor.
   *
   * @param string $callbackShard
   */
  function __construct($callbackShard = '')
  {
    parent::__construct(
        $this->shard,
        $this->table,
        $callbackShard
    );

    if (is_callable([
        $this,
        'attributes'
    ]))
    {
      $params = $this->attributes();
      $this->addParam($params);
    }
  }
}