<?php

namespace UserProperty;

use model\Model;

class UserProperty extends Model
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
  public $table = 'user_property';

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
   * Преобразование времени из парсинга в last_visit.
   *
   * @param array $data
   *
   * @return int|mixed
   */
  function time2LastVisitConvert($data = [])
  {
    return (!empty($data['time']) ? $data['time'] : time());
  }

  /**
   * Проверяет, бот или нет пользователь.
   *
   * @param array $data
   *
   * @return bool
   */
  function checkBot($data = [])
  {
    if (!empty($data['botname']))
    {
      if ($data['botname'] == 'unknown')
      {
        return false;
      }

      return true;
    }

    return false;
  }

  /**
   * Если бот, то вернет 1.
   *
   * @param array $data
   *
   * @return int
   */
  function addIsBot($data = [])
  {
    return ($this->checkBot($data) ? 1 : 0);
  }

  /**
   * Вернет 100% если бот.
   *
   * @param array $data
   *
   * @return int
   */
  function addBotPercent($data = [])
  {
    return ($this->checkBot($data) ? 100 : 0);
  }

  /**
   * Изменение данных перед вставкой.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeInsert($data = [])
  {
    $data['last_visit'] = $this->time2LastVisitConvert($data);
    $data['bot'] = $this->addIsBot($data);
    $data['is_bot'] = $this->addIsBot($data);
    $data['percent_is_bot'] = $this->addBotPercent($data);
    $data['useful'] = $this->addIsBot($data);

    return $data;
  }

  /**
   * UserProperty constructor.
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