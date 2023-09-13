<?php

namespace queue\queues;

use queue\Queue;

class QueueEvents extends Queue
{
  /**
   * @var string - таблица для очереди
   */
  public $table = 'queue_events';

  /**
   * QueueEvents constructor.
   *
   * @param string $callbackShard
   */
  function __construct($callbackShard = '')
  {
    parent::__construct($callbackShard);
  }

  /**
   * Определение, что строка - это число и возвращает ее.
   *
   * @param string $value
   *
   * @return string
   */
  function isPhone($value = '')
  {
    if (empty($value))
    {
      return false;
    }
    $number = preg_replace("/[^0-9]/", '', $value);
    if (!empty($number)
        && 10 == strlen($number)
    )
    {
      return $number;
    }

    return '';
  }

  /**
   * Изменение данных перед вставкой.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeSave($data = [])
  {
    if (!empty($data))
    {
      $text = json_encode($data);
      $data['param'] = $text;
      $data['id'] = md5(time() . uniqid());
      $data['state'] = '4';
      $data['time'] = time();

      return $data;
    }

    return [];
  }
}