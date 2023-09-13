<?php

include_once __DIR__ . '/../../lib/common/common.php';

/**
 * Class CompareDirtyAndSequencer
 * Проверка за сутки записей в таблице dirty и в таблице sequencer.
 * Если кол-во записей отличается, то это плохо.
 */
class CompareDirtyAndSequencer
{
  /**
   * @var false|int - время начала месяца
   */
  protected $tablePrefix;

  /**
   * @var false|int - время на сутки назад
   */
  protected $start;

  /**
   * Получение времени для таблицы.
   *
   * @return false|int
   */
  function getFirstMonth()
  {
    $res = strtotime(date("Y-m-01"));
    return $res;
  }

  /**
   * Получение записей за период.
   *
   * @param string $tablePostfix
   *
   * @return int
   */
  function getCount($tablePostfix = '')
  {
    if (!empty($tablePostfix))
    {
      $sql = "SELECT COUNT(`id`) as `cnt` FROM `" . $tablePostfix . "_" . $this->tablePrefix . "` WHERE `time` BETWEEN '$this->start' AND '$this->time'";
      $rs = simple_query($sql);
      if (!empty($rs))
      {
        $answer = mysqli_fetch_assoc($rs);
        if (!empty($answer['cnt']))
        {
          return $answer['cnt'];
        }
      }
    }
    return 0;
  }

  /**
   * Сравнение и отправка на почту данных.
   */
  function compare()
  {
    $dirty = $this->getCount('dirty');
    $sequencer = $this->getCount('l_sequence_4_user');
    $date = date('Y-m-d H:i:s', $this->time);
    $strMessage = "Date: $date\nDirty: $dirty\nSequencer: $sequencer\n";
    mail('hva@zionec.ru', "Compare between dirty and sequencer $date", $strMessage);
  }

  /**
   * CompareDirtyAndSequencer constructor.
   */
  function __construct()
  {
    common_inc('_database');
    $this->tablePrefix = $this->getFirstMonth();
    $this->time = strtotime("-1 day") - 3600; // чтобы убрать разницу в отработке крон скриптов
    $this->start = $this->time - 86400;
  }
}

$workClass = new CompareDirtyAndSequencer();
$workClass->compare();