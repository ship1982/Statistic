<?php

use services\MainService;


/**
 * Класс для работы с файловым кэшем в рамках набора функций import.
 * Служит для запоминания доменов в зависиомости от pin (параметра, отвечающего за id партнера)
 */
class ImportCache
{
  /**
   * @var array - основная переменная для хранения данных кэша
   */
  public $cache = [];

  /**
   * Получение значения из кэша по ключу.
   *
   * @param string $key - ключ, по которому ищем значение.
   *
   * @return mixed
   */
  public function get($key = '')
  {
    return (!empty($this->cache[$key]) ? $this->cache[$key] : '');
  }

  /**
   * Устанавливаем значение в кэш по ключу.
   *
   * @param string $key
   * @param string $value
   *
   * @return mixed
   */
  public function set($key = '', $value = '')
  {
    if (empty($key))
    {
      return '';
    }
    $this->cache[$key] = $value;

    return $this->get($key);
  }

  /**
   * Очищает весь кэш.
   *
   * @return bool
   */
  public function clear()
  {
    unset($this->cache);

    return true;
  }

  /**
   * Удаляет значение по ключу.
   *
   * @param string $key - ключ, по которому будет удалено значение.
   *
   * @return bool
   */
  public function rm($key = '')
  {
    if (!empty($key))
    {
      if (!empty($this->cache[$key]))
      {
        unset($this->cache[$key]);
        return true;
      }
    }

    return false;
  }
}


/**
 * Return a file for parsing
 * get all access log files (pixel_access)
 * if file is locked, that find next file
 * if file is not locked, then lock it and return array with locked and original file
 *
 * @return array|bool|int
 */
function parser_get()
{
  if (is_dir($GLOBALS['conf']['log_dir']))
  {
    if ($handle = opendir($GLOBALS['conf']['log_dir']))
    {
      while (false !== ($entry = readdir($handle)))
      {
        if ($entry == '.' || $entry == '..')
        {
          continue;
        }

        if (strpos($entry, 'access') === false || strpos($entry, '.pixel_') !== false)
        {
          continue;
        }

        //TODO: для тестирования
        /*if (strpos($entry, 'pixel_access.log') === false)
        {
          continue;
        }*/

        if (parser_isLock($entry) === false)
        {
          $file = parser_lock($entry);
          if (is_array($file))
          {
            return $file;
          }
          else
          {
            return -1;
          }
        }
      }
    }
    else
    {
      return false;
    }
  }
  else
  {
    return false;
  }

  return false;
}

/**
 * Check file for a lock
 * if file is locked return true.
 *
 * @param bool $file - file for checking
 *
 * @return bool|int
 */
function parser_isLock($file = false)
{
  if ($file == false)
  {
    return -1;
  }
  if (file_exists($GLOBALS['conf']['log_dir'] . '/.' . $file))
  {
    return true;
  }
  else
  {
    return false;
  }
}

/**
 * Lock the file
 * Return array with locked and original file.
 *
 * @param bool $file - file for locking
 *
 * @return array|int
 */
function parser_lock($file = false)
{
  if (file_exists($GLOBALS['conf']['log_dir'] . '/' . $file))
  {
    file_put_contents($GLOBALS['conf']['log_dir'] . '/.' . $file, '');
  }
  else
  {
    return -1;
  }

  return [
      'lock' => $GLOBALS['conf']['log_dir'] . '/.' . $file,
      'original' => $GLOBALS['conf']['log_dir'] . '/' . $file
  ];
}

/**
 * Parse a file.
 *
 * @param array $array @link parse_get()
 *
 * @return int
 */
function parser_parse($array = array())
{
  if (empty($array['lock']) || empty($array['original']))
  {
    return -1;
  }
  return parser_parseFile($array);
}

/**
 * Open file.
 *
 * @param bool $file - file for opening
 *
 * @return int|resource
 */
function parser_open($file = false)
{
  if (!file_exists($file))
  {
    return -1;
  }
  if (($handle = fopen($file, 'r')) !== false)
  {
    if (flock($handle, LOCK_EX))
    {
      return $handle;
    }
    else
    {
      return -3;
    }
  }
  else
  {
    return -2;
  }
}

/**
 * Parse log file.
 *
 * @param bool $file file for opening
 *
 * @return bool|int|resource
 */
function parser_parseFile($file = false)
{
  if (is_int($file))
  {
    return $file;
  }

  $_file = parser_open($file['original']);

  if (is_int($_file))
  {
    return $_file;
  }

  common_inc('_import');
  $service = new MainService();
  // инициализируем объект кэша
  $importCache = new ImportCache();
  $events = new \EventList\EventList();

  while (($data = fgetcsv($_file, 0, ';')) !== false)
  {
    $string = parse_parseString($data);

    /**
     * если данные являются событиями
     */
    if (!empty($string['_t'])
        && $string['_t'] == 'send'
    )
    {
      // пакетная вставка
      $events->batchSave($string);
    }
    else
    {
      /**
       * общая статистики
       */

      /**
       * ставим данные в очередь по событиям
       */
      if (is_array($string) && !empty($string))
      {
        //Добавим в очередь botChecker
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_set',
            'queue' => 'cron_botChecker',
            'state' => 1,
            'param' => prepare_data_to_queue($string, [
                'os',
                'browser',
                'ip',
                'botname',
                'uuid',
                'time',
                'domain',
                'link',
                'referrer',
                'start_referrer'
            ])
        ]);

        //Добавим в очередь form2link
        $service->query('mysqlqueue', [
            'method' => 'mysqliqueue_set',
            'queue' => 'cron_form2link',
            'state' => 1,
            'param' => prepare_data_to_queue($string, [
                'id',
                'link',
                'time'
            ])
        ]);

        //Добавим в очередь topReferers
        //для учета рассматриваем только визиты
        if (empty($string['_t']))
        {
          $service->query('mysqlqueue', [
              'method' => 'mysqliqueue_set',
              'queue' => 'topReferers',
              'state' => 1,
              'param' => json_encode($string)
          ]);
        }
      }

      /**
       * определение уникального посещения в момент времени
       */
      $state = json_encode([
          'time' => $string['time'],
          'uuid' => $string['uuid'],
          'domain' => common_setValue($string, 'domain_text'),
          'link' => common_setValue($string, 'link_text'),
          'referrer' => common_setValue($string, 'referrer')
      ]);

      if (!$importCache->get($state))
      {
        /** standard import */
        $result = import_set($string, $importCache);
        if (true !== $result)
        {
          common_appLog(__FILE__, __LINE__, 'import_set', 'function return ' . $result);
        }
        else
        {
          // кэш ставим в том случае, если нет ошибок
          $importCache->set($state, 1);
        }
      }
    }
  }

  // записываем пакет, если он меньше лимита
  $events->batchEnd();
  parser_close($_file);
  parse_delLock($file['lock']);
  parser_mvLog($file['original']);

  // удаляем кэш, что объявлен в импорте
  if ($importCache)
  {
    $importCache->clear();
  }

  return true;
}

function prepare_data_to_queue($arr_data, $arr_fields)
{
  if (!is_array($arr_data) || empty($arr_data)
      || !is_array($arr_fields) || empty($arr_fields)
  )
  {
    return '';
  }


  $data_res = [];

  foreach ($arr_fields as $val_arr_fields)
  {
    $data_res[$val_arr_fields] = common_setValue($arr_data, $val_arr_fields);
  }
  return json_encode($data_res);
}

/**
 * Move file to $GLOBALS['conf']['log_done'].
 *
 * @param bool $file
 *
 * @return bool
 */
function parser_mvLog($file = false)
{
  if (!$file)
  {
    return false;
  }
  return rename($file, $GLOBALS['conf']['log_done'] . '/' . basename($file));
}

/**
 * Parse string from log file
 * it is structure
 * array(11) {
 *       [0]=> string(25) "2016-04-19T00:15:08+03:00"
 *       [1]=> string(3) "304"
 *       [2]=> string(26) "http://dev.mgts.zionec.ru/"
 *       [3]=> string(19) "stat.mgts.zionec.ru"
 *       [4]=> string(10) "/pixel.gif"
 *       [5]=> string(113)
 *       "_c1=1&_t=require&data=dWlkPTE0NjA5OTAwODQ2ODE5Mzc4NzMyODY5NjI1NjY3MDtzZXNzPThiMTA2ZTY1MjNlMWRjMDkxY2E4NTAyYmE3OWNkMWU0O3NpdGU9czE7"
 *       [6]=> string(1) "-"
 *       [7]=> string(104) "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87
 *       Safari/537.36"
 *       [8]=> string(11) "10.12.52.11"
 *       [9]=> string(13) "94.25.229.119"
 *       [10]=> string(0) ""
 *       }
 *
 * @param array $string
 *
 * @return array|bool
 */
function parse_parseString($string = [])
{
  common_inc('ip_conv');
  if (empty($string))
  {
    return false;
  }
  $array = [];
  $num = count($string);
  for ($c = 0; $c < $num; $c++)
  {
    switch ($c)
    {
      case 0:
        $array['time'] = parse_getTimestamp($string[$c]);
        break;
      case 1:
        $array['pixel_status'] = parser_pixelStatus(trim($string[$c]));
        break;
      case 2:
        if (!empty($string[$c]))
        {
          $array = array_merge(
              (array)$array,
              (array)parse_link($string[$c])
          );
        }
        break;
      case 3:
        $array['stat_domain'] = parser_statDomain(trim($string[$c]));
        break;
      case 5:
        if (!empty($string[$c]))
        {
          $array = array_merge(
              (array)$array,
              (array)parse_dataString($string[$c])
          );
        }
        break;
      case 7:
        if (!empty($string[$c]))
        {
          $array = array_merge(
              (array)$array,
              (array)parse_parseUserAgent($string[$c])
          );
        }
        break;
      case 9:
        $array['ip'] = trim(ip2long($string[$c]));
        $array['ip_long'] = ip_conv_ip_to_binary_32(trim($string[$c]));
        break;
      case 10:
        if (!empty($string[$c])
            && $string[$c] != '-'
        )
        {
          $needUUID = explode('=', $string[$c]);
          $array['uuid'] = end($needUUID);
        }
        break;
      case 11:
        if (!empty($string[$c])
            && $string[$c] != '-'
        )
        {
          $needUUID = explode('=', $string[$c]);
          $array['uuid'] = end($needUUID);
        }
        break;
    }
  }

  return $array;
}

/**
 * Parse url and return array with:
 * domain
 * url
 *
 * @param bool $link - url for parsing
 *
 * @return array|bool
 */
function parse_link($link = false)
{
  if (empty($link))
  {
    return false;
  }
  $domain = parse_getDomain($link);
  return [
      'domain' => $domain,
      'link' => $link
  ];
}

/**
 * Return a domain by link.
 *
 * @param bool $link - url for parsing
 *
 * @return string
 */
function parse_getDomain($link = false)
{
  if (empty($link))
  {
    return false;
  }
  $name = explode('/', $link);
  return (!empty($name[2]) ? $name[2] : false);
}

/**
 * Return a name of OS.
 *
 * @param string $string - user agent string
 *
 * @return string
 */
function parse_getOS($string = '')
{
  if (empty($string))
  {
    return '';
  }
  $os_platform = 'unknown';
  $os_array = [
      '/windows nt 10/i' => 'Windows 10',
      '/windows nt 6.3/i' => 'Windows 8.1',
      '/windows nt 6.2/i' => 'Windows 8',
      '/windows nt 6.1/i' => 'Windows 7',
      '/windows nt 6.0/i' => 'Windows Vista',
      '/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
      '/windows nt 5.1/i' => 'Windows XP',
      '/windows xp/i' => 'Windows XP',
      '/windows nt 5.0/i' => 'Windows 2000',
      '/windows me/i' => 'Windows ME',
      '/win98/i' => 'Windows 98',
      '/win95/i' => 'Windows 95',
      '/win16/i' => 'Windows 3.11',
      '/macintosh|mac os x/i' => 'Mac OS X',
      '/mac_powerpc/i' => 'Mac OS 9',
      '/linux/i' => 'Linux',
      '/ubuntu/i' => 'Ubuntu',
      '/iphone/i' => 'iPhone',
      '/ipod/i' => 'iPod',
      '/ipad/i' => 'iPad',
      '/android/i' => 'Android',
      '/blackberry/i' => 'BlackBerry',
      '/webos/i' => 'Mobile'
  ];

  foreach ($os_array as $regex => $value)
  {
    if (preg_match($regex, $string))
    {
      $os_platform = $value;
    }
  }
  return $os_platform;
}

/**
 * Return a name of browser.
 *
 * @param $string - user agent string
 *
 * @return array
 */
function parse_getBrowser($string = '')
{
  if (empty($string))
  {
    return [];
  }
  require_once(__DIR__ . '/../../vendor/autoload.php');
  $bc = new \BrowscapPHP\Browscap();
  $browserArray = $bc->getBrowser($string);

  return [
      'browser' => $browserArray->browser,
      'version' => $browserArray->version,
      'cookies' => $browserArray->cookies,
      'javascript' => $browserArray->javascript
  ];
}

/**
 * Set device type for a User-Agent from nginx log.
 * Must be;
 * 0 - unknown.
 * 1 - mobile phone.
 * 2 - tablet pc.
 * 3 - computer.
 *
 * @param string $string - User-Agent string
 *
 * @return int
 */
function parse_getDeviceType($string = '')
{
  $device = 0;
  if (empty($string))
  {
    return $device;
  }
  require_once(__DIR__ . '/../../vendor/autoload.php');
  $detect = new Mobile_Detect([], $string);
  if ($detect->isTablet())
  {
    return 2;
  }

  if ($detect->isMobile())
  {
    return 1;
  }

  return 3;
}

/**
 * Parse a user agent for os and browser data.
 *
 * @param string $string - user agent string
 *
 * @return array
 */
function parse_parseUserAgent($string = '')
{
  $array['os'] = parse_getOS($string);
  $b = parse_getBrowser($string);
  $device = parse_getDeviceType($string);

  /** check bots */
  common_inc('filter/bot', 'bot');
  $array['botname'] = bot_setName($string);

  $c = array_merge(
      (array)$array,
      (array)$b,
      ['device' => $device]
  );

  return $c;
}

/**
 * Parse a json data from nginx log.
 *
 * @param bool $string
 *
 * @return bool|mixed
 */
function parse_dataString($string = false)
{
  if (empty($string))
  {
    return false;
  }
  $getArray = explode('&', $string);
  $a = [];
  for ($z = 0; $z < $iz = count($getArray); $z++)
  {
    /**
     * так как строка с данными кодирована в base64, а в ней могут быть знаки равно,
     * то мы эту строку получаем через substr
     * в противном случае - через explode
     */
    if (strpos($getArray[$z], 'data=') === 0)
    {
      $data[0] = 'data';
      $data[1] = substr($getArray[$z], 5);
    }
    else
    {
      $data = explode('=', $getArray[$z]);
    }

    if (!empty($data[0]))
    {
      switch ($data[0])
      {
        case '_c':
          $a['_c'] = $data[1];
          break;
        case '_t':
          $a['_t'] = $data[1];
          break;
        case '_mstats':
          $a['_mstats'] = $data[1];
          break;
        case 'data':
          if (empty($data[1]))
          {
            return false;
          }
          $decodedData = base64_decode($data[1]);
          $explodeData = explode(';', $decodedData);
          for ($i = 0; $i < $ic = count($explodeData); $i++)
          {
            $__a = explode('=', $explodeData[$i]);
            // ключ тоже может быть закодирован, например meta[description]
            if (!empty($__a[0]))
            {
              $__a[0] = urldecode($__a[0]);
            }
            // декодируем значение
            if (!empty($__a[1]))
            {
              $__a[1] = urldecode($__a[1]);
            }
            if (!empty($__a[0])
                && isset($__a[1])
            )
            {
              switch ($__a[0])
              {
                case 'uid':
                  $a['ouid'] = $__a[1];
                  break;
                case 'utm_source':
                  $a['utm_source'] = $__a[1];
                  break;
                case 'utm_medium':
                  $a['utm_medium'] = $__a[1];
                  break;
                case 'utm_campaign':
                  $a['utm_campaign'] = $__a[1];
                  break;
                case 'utm_content':
                  $a['utm_content'] = $__a[1];
                  break;
                case 'utm_term':
                  $a['utm_term'] = $__a[1];
                  break;
                case 'referrer':
                  $a['referrer'] = urldecode($__a[1]);
                  break;
                case 'start_referrer':
                  $a['start_referrer'] = parse_getDomain(urldecode($__a[1]));
                  break;
                case 'link':
                  $arLink = parse_url($__a[1]);
                  $a['link'] = ltrim(common_setValue($arLink, 'path'), '/');
                  if (!empty($arLink['query']))
                  {
                    $a['link'] .= '?' . $arLink['query'];
                  }
                  // обрабатываем сразу и домен
                  if (!empty($arLink['host']))
                  {
                    $a['domain'] = $arLink['host'];
                  }
                  break;
                case 'ad':
                  $a['ad'] = $__a[1];
                  break;
                case 'sr':
                  $a['sr'] = $__a[1];
                  break;
                // events variables
                case '1':
                  $a['event_type'] = $__a[1];
                  break;
                case '2':
                  $a['event_category'] = $__a[1];
                  break;
                case '3':
                  $a['event_label'] = $__a[1];
                  break;
                case '4':
                  $a['event_value'] = $__a[1];
                  break;
                case 'pin':
                  $a['partner'] = $__a[1];
                  break;
                case '_st':
                  $a['step'] = (int) $__a[1];
                  break;
                case 'meta[description]':
                  $a['description'] = $__a[1];
                  break;
                case 'meta[title]':
                  $a['title'] = $__a[1];
                  break;
                case 'meta[keywords]':
                  $a['keywords'] = $__a[1];
                  break;
              }
            }
          }
      }
    }
  }

  return $a;
}

/**
 * Return a time stamp for user friendly data.
 *
 * @param bool $value time
 *
 * @return bool|int
 */
function parse_getTimestamp($value = false)
{
  if (!$value)
  {
    return false;
  }
  return strtotime($value);
}

/**
 * Wrap for fclose.
 *
 * @param bool $file
 *
 * @return bool
 */
function parser_close($file)
{
  if (!is_resource($file))
  {
    return -1;
  }
  flock($file, LOCK_UN);
  if (@fclose($file))
  {
    return true;
  }
  else
  {
    return -1;
  }
}

/**
 * Delete a lock file.
 *
 * @param bool $file - file for deleting
 *
 * @return bool|int
 */
function parse_delLock($file = false)
{
  if (!file_exists($file))
  {
    return -1;
  }
  return unlink($file);
}

/**
 * Init function for parsing
 *
 * @constructor
 * @return array|bool|int
 */
function parser_init()
{
  $file = parser_get();
  if (is_array($file))
  {
    $fileForParse = parser_parse($file);
    if (is_int($fileForParse))
    {
      return common_appLog(__FILE__, __LINE__, 'parser_parse', 'function return ' . $fileForParse);
    }
    else if ($fileForParse === true)
    {
      return true;
    }
  }
  return true;
}

/**
 * Return a key of existing os.
 *
 * @param bool $name
 *
 * @return bool|int
 */
function parser_osFromArray($name = false)
{
  if (!$name)
  {
    return -1;
  }
  $os_array = [
      'Windows 10' => 1,
      'Windows 8.1' => 2,
      'Windows 8' => 3,
      'Windows 7' => 4,
      'Windows Vista' => 5,
      'Windows Server 2003/XP x64' => 6,
      'Windows XP' => 7,
      'Windows 2000' => 9,
      'Windows ME' => 10,
      'Windows 98' => 11,
      'Windows 95' => 12,
      'Windows 3.11' => 13,
      'Mac OS X' => 14,
      'Mac OS 9' => 15,
      'Linux' => 16,
      'Ubuntu' => 17,
      'iPhone' => 18,
      'iPod' => 19,
      'iPad' => 20,
      'Android' => 21,
      'BlackBerry' => 22,
      'Mobile => 23'
  ];

  return (empty($os_array[$name]) ? false : $os_array[$name]);
}

/**
 * Return a pixel status.
 *
 * @param bool $status
 *
 * @return bool
 */
function parser_pixelStatus($status = false)
{
  if (!$status)
  {
    return false;
  }
  $s = [
      '200' => 1,
      '304' => 2,
      '404' => 3,
      '403' => 4,
      '500' => 5
  ];

  return (!empty($s[$status]) ? $s[$status] : false);
}

/**
 * Return existing stat_domain.
 *
 * @param bool $status
 *
 * @return bool
 */
function parser_statDomain($status = false)
{
  if (!$status)
  {
    return false;
  }
  $s = [
      'stat.mgts.zionec.ru' => 1,
      'count.mgts.ru' => 2
  ];

  return (!empty($s[$status]) ? $s[$status] : false);
}