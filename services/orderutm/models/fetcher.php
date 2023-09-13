<?php

use services\MainService;

/**
 * Convert json string to array.
 *
 * @param string $info - json string
 *
 * @return array
 */
function orderutm_json2array($info = '')
{
  if (!empty($info)
      && is_string($info)
  )
  {
    $data = json_decode($info, true);
    if (!empty($data))
    {
      return $data;
    }
  }

  return [];
}

/**
 * Get configuration variables located in __DIR__ . '/../config/variables.php'.
 *
 * @return array
 */
function orderutm_getVariables()
{
  if (file_exists(__DIR__ . '/../config/variables.php'))
  {
    return require __DIR__ . '/../config/variables.php';
  }
  else
  {
    return [];
  }
}

/**
 * Get form array in queue from, to, retrotime parameters for query in bitrix.
 *
 * @param array $info - array form queue.
 *
 * @return array
 */
function orderutm_prepareData4Bitrix($info = [])
{
  if (!empty($info)
      && !empty($info['param'])
  )
  {
    $queryData = json_decode($info['param'], true);
    if (isset($queryData['from'])
        && isset($queryData['to'])
        && isset($queryData['retrotime'])
    )
    {
      return $queryData;
    }
  }
  return [];
}

/**
 * Get list of table for Lacanich query for report.
 *
 * @param string $from - time form
 * @param string $to   - time to
 *
 * @return array with list of tables time prefix
 */
function orderutm_getListofTable($from = '', $to = '')
{
  $data = [];
  if (!empty($from)
      && !empty($to)
  )
  {
    $moreThenMonth = 2764800;
    while ($from <= $to)
    {
      $s = strtotime(date("Y-m-01", $from));
      $data[] = $s;
      $from = $s + $moreThenMonth;
    }
  }

  return $data;
}

/**
 * Get main data in Lacanich for report.
 *
 * @param string $from         - time form
 * @param string $to           - time to
 * @param array  $listOfTables - list of tables @see orderutm_getListofTable()
 *
 * @return array
 */
function orderutm_getMainData($from = '', $to = '', $listOfTables = [])
{
  $data = [];
  if (!empty($from)
      && !empty($to)
  )
  {
    common_inc('_database');
    if (!empty($listOfTables)
        && is_array($listOfTables)
    )
    {
      for ($i = 0; $i < $ic = count($listOfTables); $i++)
      {
        $sql = "SELECT 
					`time`,
					CONCAT(referer_domain, '/', referer_link) as referrer,
					CONCAT(domain_text, '/', link_text) as link,
					link_text,
					utm_term,
					utm_content,
					utm_source,
					utm_medium,
					utm_campaign,
					uuid
				WHERE `time` BETWEEN '$from' AND '$to'
					AND `form` <> 0";
        $o = query_db(
            $listOfTables[$i],
            'l_sequence_4_user',
            $sql
        );

        if (!empty($o))
        {
          while ($a = mysqli_fetch_assoc($o))
            $data[] = $a;
        }
      }
    }
  }

  return $data;
}

/**
 * Convert headers from configuration array @see orderutm_getVariables()
 *
 * @param array $config - configuration array
 *
 * @return string
 */
function orderutm_convertHeader($config = [])
{
  $header = '';
  if (!empty($config)
      && is_array($config)
  )
  {
    foreach ($config as $key => $value)
      $header .= $key . ':' . $value . "\r\n";
  }

  return $header;
}

/**
 * Get data from bitrix.
 *
 * @param array $data - data from @see orderutm_getMainData()
 *
 * @return array
 */
function orderutm_getOrdersFromBitrix($data = [])
{
  if (!empty($data))
  {
    $variables = orderutm_getVariables();
    if (!empty($variables['link']))
    {
      for ($i = 0; $i < $ic = count($data); $i++)
      {
        $link = $variables['link'] . '?time=' . common_setValue($data[$i], 'time') . '&link=/' . strtok(common_setValue($data[$i], 'link_text'), '?');

        if (!empty($variables['header']))
        {
          $header = orderutm_convertHeader($variables['header']);
          $context = stream_context_create([
              'http' => [
                  'header' => $header
              ]
          ]);
          $answer = file_get_contents($link, false, $context);
        }
        else
        {
          $answer = file_get_contents($link);
        }

        $arrayAnswer = [];
        if (!empty($answer))
        {
          $arrayAnswer = json_decode($answer, true);
        }

        if (!empty($arrayAnswer['ID']))
        {
          $data[$i]['newid'] = $arrayAnswer['ID'];
        }
        if (!empty($arrayAnswer['NAME']))
        {
          $data[$i]['name'] = $arrayAnswer['NAME'];
        }
      }
    }
  }

  return $data;
}

/**
 * Get td cell with value if key exist in array.
 *
 * @param array  $array - array for searching
 * @param string $key   - key for searching
 *
 * @return string
 */
function orderutm_writePartCell($array = [], $key = '')
{
  if (!empty($array[$key]))
  {
    $str = '<td>' . $array[$key] . '</td>';
  }
  else
  {
    $str = '<td></td>';
  }

  return $str;
}

/**
 * Write header of report.
 *
 * @param string $name - name of report.
 *
 * @return void
 */
function orderutm_writeHeader($name = '')
{
  if (!empty($name))
  {
    $text = '<html><head><title></title><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><style>td {mso-number-format:\@;}.number0{mso-number-format:\'0\';}.number2{mso-number-format:Fixed;}</style></head><body><table border="1">';
    file_put_contents(__DIR__ . '/../../../web/orders/' . $name . '.xls', $text);
  }
}

/**
 * Write footer of report.
 *
 * @param string $name - name of report.
 */
function orderutm_writeFooter($name = '')
{
  if (!empty($name))
  {
    $text = '</table></body></html>';
    file_put_contents(
        __DIR__ . '/../../../web/orders/' . $name . '.xls',
        $text,
        FILE_APPEND
    );
  }
}

/**
 * Convert timestamp to 'Y-m-d H:i:s' format.
 *
 * @param int $time - timestamp for converting
 *
 * @return string
 */
function orderutm_timeConvert($time = 0)
{
  if (is_numeric($time))
  {
    return date('Y-m-d H:i:s', $time);
  }

  return $time;
}

/**
 * Set header of tables report's.
 *
 * @param array  $keys - array with names of header
 * @param string $name - name of report.
 *
 * @return void
 */
function orderutm_getNamedHeaderTable($keys = [], $name = '')
{
  if (!empty($keys)
      && is_array($keys)
  )
  {
    $str = '<tr>';
    for ($i = 0; $i < $ic = count($keys); $i++)
      $str .= '<td>' . $keys[$i] . '</td>';

    $str .= '</tr>';

    file_put_contents(
        __DIR__ . '/../../../web/orders/' . $name . '.xls',
        $str,
        FILE_APPEND
    );
  }
}

/**
 * Write data for report.
 *
 * @param string $name - name of report.
 * @param array  $data - data for writing
 *
 * @return void
 */
function orderutm_write($name = '', $data = [])
{
  if (!empty($data)
      && !empty($name)
  )
  {
    $data['time'] = orderutm_timeConvert(common_setValue($data, 'time'));
    $str = '<tr>';
    $str .= orderutm_writePartCell($data, 'newid');
    $str .= orderutm_writePartCell($data, 'name');
    $str .= orderutm_writePartCell($data, 'time');
    $str .= orderutm_writePartCell($data, 'referrer');
    $str .= orderutm_writePartCell($data, 'link');
    $str .= orderutm_writePartCell($data, 'utm_campaign');
    $str .= orderutm_writePartCell($data, 'utm_source');
    $str .= orderutm_writePartCell($data, 'utm_medium');
    $str .= orderutm_writePartCell($data, 'utm_term');
    $str .= orderutm_writePartCell($data, 'utm_content');
    $str .= '</tr>';

    file_put_contents(
        __DIR__ . '/../../../web/orders/' . $name . '.xls',
        $str,
        FILE_APPEND
    );
  }
}

/**
 * Get table prefix for table.
 *
 * @param array  $listOfTables - array with table prefix
 * @param string $key
 *
 * @return string
 */
function orderutm_getTableKey($listOfTables = [], $key = '')
{
  if (!empty($listOfTables)
      && !empty($key)
  )
  {
    for ($i = 0; $i < $ic = count($listOfTables); $i++)
    {
      if ($listOfTables[$i] < $key)
      {
        return $listOfTables[$i];
      }
    }
  }

  return '';
}

/**
 * Main function for processing.
 *
 * @param array  $data         - data from lacanich
 * @param array  $listOfTables - list of tables for query
 * @param string $retrotime    - retrotime for query
 * @param array  $queue        - data from queue
 *
 * @return void
 */
function orderutm_getRetroData($data = [], $listOfTables = [], $retrotime = '', $queue = [])
{
  if (empty($data)
      || empty($queue['id'])
  )
  {
    return;
  }

  if (is_array($data))
  {
    // write a header of file
    orderutm_writeHeader($queue['id']);

    // write header of table
    orderutm_getNamedHeaderTable([
        'ID Заявки',
        'Заявка',
        'Дата получения UTM метки',
        'Referer',
        'URL_Посещения полностью',
        'UTM_Campaign',
        'UTM_Source',
        'UTM_medium',
        'UTM_Term',
        'UTM_Content'
    ], $queue['id']
    );

    for ($i = 0; $i < $ic = count($data); $i++)
    {
      if (empty($retrotime))
      {
        $time = common_setValue($data[$i], 'time');
      }
      else
      {
        $time = $retrotime;
      }

      // write order to file
      orderutm_write(
          $queue['id'],
          $data[$i]
      );

      if (!empty($time))
      {
        common_inc('_database');
        $sql = "SELECT 
					FROM_UNIXTIME(`time`) as `time`,
					CONCAT(referer_domain, '/', referer_link) as referrer,
					CONCAT(domain_text, '/', link_text) as link,
					utm_term,
					utm_content,
					utm_source,
					utm_medium,
					utm_campaign
					WHERE `time` < '$time'
						AND `uuid` = '" . $data[$i]['uuid'] . "'
						AND (
							`utm_term` <> ''
							OR `utm_content` <> ''
							OR `utm_source` <> ''
							OR `utm_medium` <> ''
							OR `utm_campaig` <> ''
						)
					ORDER BY `time` DESC";
        $o = query_db(
            orderutm_getTableKey($listOfTables, $data[$i]['time']),
            'l_sequence_4_user',
            $sql
        );

        if (!empty($o))
        {
          while ($a = mysqli_fetch_assoc($o))
          {
            // write retro data
            orderutm_write(
                $queue['id'],
                $a
            );
          }
        }
      }
    }

    // footer of file
    orderutm_writeFooter($queue['id']);
  }
}

/**
 * Delete data form queue.
 *
 * @param string $id    - id form queue
 * @param string $state - state in queue
 *
 * @return void
 */
function orderutm_setQueue($id = '', $state = '')
{
  if (!empty($id)
      && !empty($state)
  )
  {
    $service = new MainService();
    $service->query('mysqlqueue', [
        'method' => 'mysqliqueue_delete',
        'queue' => '4620',
        'id' => $id
    ]);
  }
}

/**
 * Runner for one queue.
 *
 * @param array $array - data form queue.
 *
 * @return void
 */
function orderutm_mainProcess($array = [])
{
  if (empty($array))
  {
    return;
  }
  if (is_array($array))
  {
    for ($i = 0; $i < $ic = count($array); $i++)
    {
      $paramQuery = orderutm_prepareData4Bitrix($array[$i]);
      if (!empty($paramQuery['from'])
          && !empty($paramQuery['to'])
      )
      {
        // get list of table
        $listOfTables = orderutm_getListofTable(
            $paramQuery['from'],
            $paramQuery['to']
        );

        // get all orders form lacanich
        $data = orderutm_getMainData(
            $paramQuery['from'],
            $paramQuery['to'],
            $listOfTables
        );

        // get data from bitrix
        $result = orderutm_getOrdersFromBitrix($data);

        if (!empty($result))
        {
          // get retro data and write a file
          orderutm_getRetroData(
              $result,
              $listOfTables,
              $paramQuery['retrotime'],
              $array[$i]
          );
        }
      }

      orderutm_setQueue(
          $array[$i]['id'],
          1
      );
    }
  }
}

/**
 * Constructor.
 *
 * @param string $info
 *
 * @return void
 */
function orderutm_run($info = '')
{
  $array = orderutm_json2array($info);
  if (!empty($array))
  {
    orderutm_mainProcess($array);
  }
}