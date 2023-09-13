<?php

namespace api;

use HistoryReferrerByOrder\HistoryReferrerByOrder;

class APIv2
{
  /**
   * Поиск заявок и первоначальных данных по ним.
   * То есть меток и рефереров,
   * которые были у пользователя при первом захлде на страницу в данном сеансе.
   */
  function historyOrder()
  {
    $send= new HistoryReferrerByOrder();
    echo $send->toJSON($send->execute());
  }
}