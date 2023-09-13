<?php

namespace queue\queues;

use queue\Queue;

class QueueBotChecker extends Queue
{
  /**
   * @var string - таблица для очереди
   */
  public $table = 'queue_cron_botChecker';

  /**
   * QueueEvents constructor.
   *
   * @param string $callbackShard
   */
  function __construct($callbackShard = '')
  {
    parent::__construct($callbackShard);
  }
}