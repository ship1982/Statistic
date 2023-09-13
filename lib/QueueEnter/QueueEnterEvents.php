<?php

namespace QueueEnter;

class QueueEnterEvents extends QueueEnter
{
  /**
   * @var string - таблица для модели
   */
  public $table = 'queue_cron_enterEventlist';

  /**
   * Изменение данных перед вставкой данных.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeSave($data = [])
  {
    if (!empty($data))
    {
      $newData = [
          'seance' => $data['seance']
      ];
      $text = json_encode($newData);
      $data['param'] = $text;
      $data['id'] = md5(time() . uniqid());
      $data['state'] = '1';
      $data['time'] = time();

      return $data;
    }

    return [];
  }

  /**
   * QueueEnterEvents constructor.
   *
   * @param string $callbackShard
   */
  function __construct($callbackShard = '')
  {
    parent::__construct($callbackShard);
  }
}