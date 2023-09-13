<?php

namespace sharding;

class ShardWork
{
  /**
   * Возвращает список постфиксов для диаппазона дат.
   *
   * @param int $timeFrom
   * @param int $timeTo
   *
   * @return array
   */
  static function sharding_getShards($timeFrom = 0, $timeTo = 0)
  {
    $result = [];
    $monthPeriod = 86400 * 32; // чтобы наверняка
    // определяем начало месяца отчета
    $startTime = strtotime(date("Y-m-01", (int)$timeFrom));
    // определеям конец периода
    $endTime = strtotime(date("Y-m-01", (int)$timeTo));
    // получаем между датами периоды
    if ($startTime == $endTime)
    {
      return [$startTime];
    }
    else
    {
      $compare = $startTime;
      $result[] = $compare;
      do
      {
        $compare += $monthPeriod;
        $compare = strtotime(date("Y-m-01", $compare));
        $result[] = $compare;
      } while ($compare < $endTime);
      if (!in_array($endTime, $result))
      {
        $result[] = $timeTo;
      }

      return $result;
    }
  }
}