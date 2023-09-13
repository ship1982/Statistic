<?php

namespace Ticket64;

use model\Model;

class Ticket64 extends Model
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
  public $table = 'Tickets64';

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
    return [];
  }

  /**
   * Возвращает одну запись по значениею и полю.
   *
   * @param string $value
   * @param string $key
   *
   * @return array
   */
  public function one($value = '', $key = 'id')
  {
    $this->select();
    $this->from();
    $this->where([$key => $value]);
    $this->limit(1);
    $this->execute();
    $data = $this->fetch();
    if (!empty($data[0]))
    {
      return $data[0];
    }
    else
    {
      return [];
    }
  }

  /**
   * Получение сквозного id.
   *
   * @param string $stub
   *
   * @return int
   */
  function getId($stub = 'a')
  {
    $this->query("REPLACE INTO $this->table (stub) VALUES ('$stub')");
    $this->query('SELECT LAST_INSERT_ID() as id');
    $data = $this->fetch();
    return (empty($data[0]['id']) ? 0 : $data[0]['id']);
  }

  /**
   * Ticket64 constructor.
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