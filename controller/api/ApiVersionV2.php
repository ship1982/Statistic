<?php

use EventsAPI\EventsAPI;

class ApiVersionV2
{
  /**
   * Обработка запросов API на event_list
   */
  function eventsProcess()
  {
    $events = new EventsAPI();
    echo $events->toJSON($events->execute());
  }

  /**
   * Обработка запросов API на event_list
   */
  function sequenceProcess()
  {
    common_inc('SequencerAPI');
    $events = new SequencerAPI();
    echo $events->toJSON($events->execute());
  }
}