<?php

/**
 * Функция возвращает постфиксы таблиц, для которых будут строиться запросы.
 *
 * @param string $table
 * @param array  $shard
 *
 * @return array
 */
function shardChooser($table = '', $shard = [])
{
  $result = [];
  common_inc('sharding');
  // для времени в виде массива
  if (isset($shard['from'])
      && isset($shard['to'])
  )
  {
    return sharding_getShards($shard['from'], $shard['to']);
  }

  // для просто времени
  if (!empty($shard[0])
      && $shard[0] != 1)
  {
    if ($answeredShard = sharding_getFirstMonthDay($shard[0]))
    {
      $result[] = $answeredShard;
      return $result;
    }
  }

  return $shard;
}