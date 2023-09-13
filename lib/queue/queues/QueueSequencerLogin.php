<?php

namespace queue\queues;

use queue\Queue;

class QueueSequencerLogin extends Queue
{
  /**
   * @var string - таблица для очереди
   */
  public $table = 'queue_cron_login4sequencer';

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