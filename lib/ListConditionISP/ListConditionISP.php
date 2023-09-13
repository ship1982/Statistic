<?php
  
namespace ListConditionISP;

use model\Model;

class ListConditionISP extends Model
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
  public $table = 'list_condition_ips';

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
    return (!empty($data[0]) ? $data[0] : []);
  }
  
  /**
   * ListConditionISP constructor.
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