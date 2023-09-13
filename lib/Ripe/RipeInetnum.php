<?php

namespace Ripe;

use model\Model;

class RipeInetnum extends Model
{
  /**
   * @var string - таблица для модели
   */
  public $table = 'test_inetnum';

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
   * Перед сохранением изменение параметров.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeInsert($data = [])
  {
    if (!empty($data['inetnum'])
        && strpos($data['inetnum'], ' - ') !== false)
    {
      $arIps = explode(" - ", $data['inetnum']);
      if (!empty($arIps)
          && !empty($arIps[0])
          && !empty($arIps[1])
      )
      {
        $data['sip'] = trim(ip2long($arIps[0]));
        $data['eip'] = trim(ip2long($arIps[1]));
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
   * RipeInetnum constructor.
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