<?php

namespace Ripe;

use model\Model;
use IpConv\IpConv;

class RipeRoute extends Model
{
  /**
   * @var string - таблица для модели
   */
  public $table = 'test_route';

  /**
   * @var array - массив полей таблицы с их значениями для одной записи. Здесь храниться модель.
   */
  public $options = [];

  /**
   * @var array - изначальный ключ шардинга
   */
  protected $shard = [1];

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
   * @param array $data
   *
   * @return array
   */
  function onBeforeInsert($data = [])
  {
    if (!empty($data['route']))
    {
      $ip = new IpConv();
      $interval = $ip->ip_conv_ipv4_cidr_to_range($data['route']);
      if (!empty($interval)
          && !empty($interval[0])
          && !empty($interval[1])
      )
      {
        $data['sip'] = ip2long($interval[0]);
        $data['eip'] = ip2long($interval[1]);
      }
    }
    return $data;
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
   * PTV constructor.
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