<?php
use model\Model;

//автозагрузка
include_once __DIR__ . '/../../../lib/autoload.php';

/**
 * @return void
 */
function work()
{
  global $eventList, $eventListNew, $lastNewIndex;

  $data = $eventList->_list(
      [],
      [['id', '>', $lastNewIndex]],
      ['id' => 'ASC'],
      '0,200'
  );

  if (!empty($data))
  {
    $dataLength = count($data);

    $sql = 'INSERT INTO `event_list_new` (';
    $keys = [];

    foreach ($data[0] as $key => $value)
    {
      if ($eventListNew->isField($key))
      {
        $keys[] = $key;
        $sql .= "`$key`,";
      }
    }

    $sql = substr($sql, 0, -1) . ") VALUES ";

    for ($i = 0; $i < $dataLength; $i++)
    {
      $sql .= "(";

      for ($j = 0; $j < count($keys); $j++)
      {
        $sql .= $eventListNew->prepare($keys[$j], $data[$i][$keys[$j]]) . ",";
      }

      $sql = substr($sql, 0, -1) . "),";
    }

    $sql = substr($sql, 0, -1);

    $success = $eventListNew->query($sql);

    if ($success)
    {
      $lastNewIndex = $data[$dataLength-1]['id'];
    }
    else
    {
      file_put_contents(__DIR__."/log/transfer_{$lastNewIndex}.error", $sql."\r\n\r\n".$eventListNew->error);
      exit('Ошибка переноса данных');
    }
  }
  else
  {
    exit('Работы по переносу данных в новую таблицу завершены');
  }
}

$eventList = new Model([1], 'event_list');
$eventListNew = new Model([1], 'event_list_new');

$data = $eventListNew->_list(
    ['id'],
    [],
    ['id' => 'DESC'],
    '0,1'
);

if (!empty($data))
{
  $lastNewIndex = $data[0]['id'];
}
else
{
  $lastNewIndex = 0;
}

for ($i = 0; $i < 500; $i++)
{
  work();
}
?>