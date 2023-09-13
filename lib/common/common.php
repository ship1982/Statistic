<?php

/**
 * Определяем переменную окружения
 */
$GLOBALS['serverType'] = __DIR__ . '/../../config/server.php';
if (!is_file($GLOBALS['serverType']))
{
  define('SERVER', 'dev');
}
else
{
  include_once $GLOBALS['serverType'];
}

// для дебага
if (!empty($argv[1]))
{
  define('DEBUG', true);
  define('DEBUG_FILE', __DIR__ . '/../../../syslog/' . $argv[1] . '.log');
}
else
{
  define('DEBUG', false);
}

/**
 * include module and template if it is.
 * the module must be locate in $_SERVER['DOCUMENT_ROOT'] . '/lib/' . $module folder,
 * where @property $model is a folder (and the name of module).
 * model of the model must be named by a module.
 * For example $_SERVER['DOCUMENT_ROOT'] . '/lib/parser/parser.php'
 *
 * @param bool $module
 * @param bool $template
 *
 * @return bool|int
 */
function common_module($module = false, $template = false)
{
  if (!$module)
  {
    return -1;
  }
  if (file_exists(__DIR__ . '/../../inc/' . $module . '/' . $module . '.php'))
  {
    require(__DIR__ . '/../../inc/' . $module . '/' . $module . '.php');
    if (!$template)
    {
      return true;
    }
    if (file_exists(__DIR__ . '/../../inc/' . $module . '/template/' . $template . '.php'))
    {
      require(__DIR__ . '/../../inc/' . $module . '/template/' . $template . '.php');
    }

    return true;
  }
  else
  {
    return -2;
  }
}

/**
 * include all config
 * the config files must be in $_SERVER['DOCUMENT_ROOT'] . '/config' directory.
 * folder with config must have a same name as a php file on it, for example:
 * $_SERVER['DOCUMENT_ROOT'] . '/config/main/main.php'
 * if config files have a same key, then key will be overwrite by below config file
 * @return array configuration
 */
function common_conf()
{
  $conf = [];
  $f = scandir(__DIR__ . '/../../config');
  for ($i = 0; $i < $ic = count($f); $i++)
  {
    if ($f[$i] == '.' || $f[$i] == '..')
    {
      continue;
    }
    if (file_exists(__DIR__ . '/../../config/' . $f[$i] . '/' . $f[$i] . '.php'))
    {
      $conf = array_merge($conf, require(__DIR__ . '/../../config/' . $f[$i] . '/' . $f[$i] . '.php'));
    }
  }
  return $conf;
}

/**
 * Возвращает процентное значение одногочисла от другого,
 * пример:всего 100 спичек, мы взяли 10 => взяли 10%
 * @param $count - Число, из которого, расчитывается процент
 * @param $value - Число, по которому расчитываем процент
 * @return float|int
 */
function common_percent_from_number($count, $value){
    if($count <= 0 ){return 0;}
    if($value <= 0 ){return 0;}
    return round((float)$value/(float)$count*100, 2);
}

/**
 * Возвращает текущее время в формате timestamp
 * @return float
 */
function common_microtime_float()
{
  list($usec, $sec) = explode(" ", microtime());
  return ((float)$usec + (float)$sec);
}

/**
 * Функция возвращает url адрес хоста в виде: http://hosturl.ru
 * @return string
 */
function common_get_url_host()
{
  $host = ((common_check_https()) ? 'https://' : 'http://');
  if (!empty($_SERVER['HTTP_HOST']))
  {
    $host .= $_SERVER['HTTP_HOST'];
  }

  return $host;
}

/**
 * Функция возвращает истину,
 * если используется защищённое соединение
 * @return boolean
 */
function common_check_https()
{
  /*if (SERVER !== 'dev')
  {
    return true;
  }*/
  if (!empty($_SERVER['HTTPS']))
  {
    if ($_SERVER['HTTPS'] !== 'off')
    {
      return true;
    } //https
    else
    {
      return false;
    } //http
  }
  else
  {
    if (!empty($_SERVER['SERVER_PORT'])
        && $_SERVER['SERVER_PORT'] == 443
    )
    {
      return true;
    } //https
    else if (!empty($_SERVER['HTTP_HTTPS']) && $_SERVER['HTTP_HTTPS'] === 'YES')
    {
      return true;
    }
    else
    {
      //http
      return false;
    }
  }
}

/**
 * pretty print array
 *
 * @param array $array
 */
function dump($array = array())
{
  echo '<pre>';
  var_dump($array);
  echo '</pre>';
}

/**
 * set a header of html page
 *
 * @param $title - title of a page
 * @param $css   array - array of absolute css style
 *               for example: ['web/css/1.css','web/css/2.css']
 * @param $js    array - array of absolute css script
 *               for example: ['web/js/1.js','web/js/2.js']
 *
 * @return string
 */
function common_setHeader($title = 'No named', $css = [], $js = [])
{
  $cssString = '';
  $jsString = '';
  if (!empty($css))
  {
    for ($i = 0; $i < $ic = count($css); $i++)
    {
      if (strpos($css[$i], '<') !== false)
      {
        $cssString .= $css[$i];
      }
      else
      {
        $cssString .= '<link rel="stylesheet" href="' . $css[$i] . '">';
      }
    }
  }
  if (!empty($js))
  {
    for ($i = 0; $i < $ic = count($js); $i++)
    {
      if (strpos($js[$i], '<') !== false)
      {
        $jsString .= $js[$i];
      }
      else
      {
        $jsString .= '<script type="text/javascript" src="' . $js[$i] . '"></script>';
      }
    }
  }
  return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"><title>' . $title . '</title>' . $cssString . $jsString . '</head><body>';
}

/**
 * set a footer of html page
 *
 * @param $js array - array of absolute css script
 *            for example: ['web/js/1.js','web/js/2.js']
 *
 * @return string
 */
function common_setFooter($js = [])
{
  $jsString = '';
  if (!empty($js))
  {
    for ($i = 0; $i < $ic = count($js); $i++)
    {
      if (strpos($js[$i], '<') !== false)
      {
        $jsString .= $js[$i];
      }
      else
      {
        $jsString .= '<script type="text/javascript" src="' . $js[$i] . '"></script>';
      }
    }
  }
  return $jsString . '</body></html>';
}

/**
 * Wrapper for php include_once for 'lib' folder.
 *
 * @param string $folder - folder name for a libraries.
 *                       If $file is empty, then $folder will be used as a file name also
 * @param string $file   - name of file (libraries) for a including
 *
 * @return bool
 */
function common_inc($folder = '', $file = '')
{
  if (!$folder)
  {
    return false;
  }
  if (empty($file))
  {
    if (file_exists(__DIR__ . '/../' . $folder . '/' . $folder . '.php'))
    {
      include_once(__DIR__ . '/../' . $folder . '/' . $folder . '.php');
      return true;
    }
  }
  else
  {
    if (file_exists(__DIR__ . '/../' . $folder . '/' . $file . '.php'))
    {
      include_once(__DIR__ . '/../' . $folder . '/' . $file . '.php');
      return true;
    }
  }

  return false;
}

/**
 * prepare POST or GET data by functions
 *
 * @param string $type   - type of data
 * @param array  $filter is a array of function for processing value
 *                       example:
 *                       [
 *                       id => number
 *                       name => strip
 *                       ]
 *                       Value of the array is a part of functions, which is @property $prefix . value
 * @param string $prefix - is a prefix for the @property $filter array value
 *
 * @return array|bool
 */
function common_prepareRequest($type = 'POST', $filter = [], $prefix = 'commonFilter')
{
  $array = [];
  switch ($type)
  {
    case 'POST':
      $__type = $_POST;
      break;
    case 'GET':
      $__type = $_GET;
      break;
    default:
      return false;
  }

  foreach ($__type as $key => $value)
  {
    if (isset($filter[$key]))
    {
      $array[$key] = call_user_func($prefix . $filter[$key], $value);
    }
    else
    {
      $array[$key] = common_filterDefault($value);
    }
  }

  return $array;
}

/**
 * prepare value
 *
 * @param $value - value for preparing
 *
 * @return string
 */
function common_filterDefault($value)
{
  return strip_tags(trim($value));
}

/**
 * return error for html forms
 *
 * @param array $error - array form error texts
 *
 * @return string
 */
function common_showError($error = [])
{
  $str = '';
  if (empty($error))
  {
    return $str;
  }
  for ($i = 0; $i < $count = count($error); $i++)
    $str .= $error[$i] . '<br />';

  return $str;
}

/**
 * write a syslog for application
 *
 * @param bool   $file     - file, where will be call this function
 * @param bool   $line     - line, where will be call this function
 * @param bool   $function - function, what will call a log
 * @param string $text     - message for log
 *
 * @return bool|int
 */
function common_appLog($file = false, $line = false, $function = false, $text = '')
{
  if (empty($file) || empty($line) || empty($function))
  {
    return false;
  }
  if (empty($GLOBALS['log_timer']))
  {
    $GLOBALS['log_timer'] = date('d-m-Y H:i', time());
  }

  $str = $file . ';' . $line . ';' . $function . ';' . $text . ";\n";
  $logName = $GLOBALS['conf']['syslog'] . '/' . $GLOBALS['log_timer'] . '.log';
  return file_put_contents($logName, $str, FILE_APPEND);
}

/**
 * return timestamp from datepicker date format
 *
 * @param string $date datepicker date format
 *
 * @return int
 */
function common_dateFromDatePicketToTimestamp($date = '')
{
  if (empty($date))
  {
    return time();
  }
  return strtotime($date);
}

/**
 * include template (php file)
 *
 * @param bool  $module   - model, where template should be search
 * @param bool  $template - name of template
 * @param array $data     - input data
 *
 * @return array|bool|int
 */
function common_incTemplate($module = false, $template = false, $data = array())
{
  if (!$module)
  {
    return -1;
  }
  if (file_exists(__DIR__ . '/../../inc/' . $module . '/' . $module . '.php'))
  {
    if (!$template)
    {
      return true;
    }
    if (file_exists(__DIR__ . '/../../inc/' . $module . '/template/' . $template . '.php'))
    {
      require(__DIR__ . '/../../inc/' . $module . '/template/' . $template . '.php');
      return $data;
    }

    return $data;
  }
  else
  {
    return -2;
  }
}

/**
 * Инициализация хэндлера.
 *
 * @param array $handler
 *
 * @return array|bool
 */
function common_handlerInit($handler = [])
{
  if (empty($handler))
  {
    return true;
  }
  $handlersList = setConfig('handlers/handlers');
  if (!empty($handlersList)
      && is_array($handlersList)
  )
  {
    foreach ($handlersList as $name => $path)
    {
      if (in_array($name, $handler))
      {
        include_once $path;
        $className = pathinfo($path, PATHINFO_FILENAME);
        $className::run();
      }
    }
  }

  return true;
}

/**
 * Set controller and set layout in controller
 *
 * @return bool|mixed
 */
function common_setController()
{
  // покдючение handler'ов
  $controller = common_getControllerFunction();
  if (strpos($controller, ':') === false)
  {
    // получаем название класса
    $arClasses = explode('.', $controller);
    if (!empty($arClasses[0]))
    {
      $layout = '';
      // загрузка класса
      $file = __DIR__ . '/../../controller/' . str_replace('\\', '/', $arClasses[0]) . '.php';
      if (file_exists($file))
      {
        include_once $file;
      }
      $GLOBALS['layout'] = $layout;
      return call_user_func($arClasses);
    }
    else
    {
      include_once(__DIR__ . '/../error/error.php');
      error_show(4, 'common', [
          'line' => __LINE__,
          'file' => __FILE__,
          'function' => __FUNCTION__
      ]);
    }
  }
  else
  {
    // загрузка функции
    $arConfig = explode(':', $controller);
    if (file_exists(__DIR__ . '/../../controller/' . $arConfig[0] . '/' . $arConfig[0] . '.php'))
    {
      $layout = '';
      include_once(__DIR__ . '/../../controller/' . $arConfig[0] . '/' . $arConfig[0] . '.php');
      if (function_exists($arConfig[1]))
      {
        $GLOBALS['layout'] = $layout;
        return call_user_func($arConfig[1]);
      }
    }
    else
    {
      include_once(__DIR__ . '/../error/error.php');
      error_show(4, 'common', [
          'line' => __LINE__,
          'file' => __FILE__,
          'function' => __FUNCTION__
      ]);
    }
  }

  return false;
}

/**
 * Непосредственная обработка контроллера и сопоставление с адресом.
 *
 * @param string $curLink
 * @param string $use
 *
 * @return bool
 */
function common_getUse($curLink = '', $use = '')
{
  if (!empty($curLink)
      && !empty($use)
  )
  {
    $re = "/(^" . str_replace('/', '\/', $use) . "$)/";
    preg_match($re, $curLink, $matches);
    if (!empty($matches[1]))
    {
      return true;
    }
  }
  return false;
}

/**
 * Return name of controller and function for execute
 *
 * @return bool|mixed
 */
function common_getControllerFunction()
{
  $links = [];
  if (!empty($GLOBALS['conf']['route']))
  {
    $links = $GLOBALS['conf']['route'];
  }

  if (!empty($links))
  {
    $curLink = trim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
    $curLink = ($curLink === '') ? '/' : $curLink;
    foreach ($links as $link => $arParams)
    {
      // если arParams массив, то смотрим дополнительные параметры
      if (is_array($arParams))
      {
        // проверяем, подключен ли handler
        if (!empty($arParams['handler']))
        {
          // исполняем хэндлер
          if (common_getUse($curLink, $link))
          {
            common_handlerInit($arParams['handler']);
            return @$arParams['use'];
          }
        }
      }
      else
      {
        if (common_getUse($curLink, $link))
        {
          return $arParams;
        }
      }
    }
    common_404();
  }
  common_404();
}

/**
 * Handler for 404 page.
 *
 * @param string $page - custom 404 page
 */
function common_404($page = '')
{
  if (!empty($page)
      && file_exists(__DIR__ . '/../../web/' . $page . '.php')
  )
  {
    header("HTTP/1.0 404 Not Found");
    header('Location: /' . $page . '.php');
    exit;
  }
  else
  {
    header("HTTP/1.0 404 Not Found");
    header('Location: /404.php');
    exit;
  }
}

/**
 * Set view with layout
 *
 * @param bool  $name   - name of view
 * @param array $params - needed parameters
 *
 * @return bool
 */
function common_setView($name = false, $params = [])
{
  if (empty($name))
  {
    include_once(__DIR__ . '/../error/error.php');
    error_show(1, 'common', [
        'line' => __LINE__,
        'file' => __FILE__,
        'function' => __FUNCTION__
    ]);
  }

  if (!empty($GLOBALS['layout']))
  {
    if (file_exists(__DIR__ . '/../../layout/' . $GLOBALS['layout'] . '/header.php')
        && file_exists(__DIR__ . '/../../layout/' . $GLOBALS['layout'] . '/footer.php')
    )
    {
      include_once(__DIR__ . '/../../layout/' . $GLOBALS['layout'] . '/header.php');

      if (file_exists(__DIR__ . '/../../views/' . $name . '.php'))
      {
        include_once(__DIR__ . '/../../views/' . $name . '.php');
      }
      else
      {
        include_once(__DIR__ . '/../error/error.php');
        error_show(2, 'common', [
            'line' => __LINE__,
            'file' => __FILE__,
            'function' => __FUNCTION__
        ]);
      }

      include_once(__DIR__ . '/../../layout/' . $GLOBALS['layout'] . '/footer.php');
    }
  }
  else
  {
    include_once(__DIR__ . '/../error/error.php');
    error_show(3, 'common', [
        'line' => __LINE__,
        'file' => __FILE__,
        'function' => __FUNCTION__
    ]);
  }

  return false;
}

/**
 * Set template without layout.
 *
 * @param bool  $name   - name of template.
 * @param array $params - params for template.
 *
 * @return void
 */
function common_setAloneView($name = false, $params = [])
{
  if (empty($name))
  {
    include_once(__DIR__ . '/../error/error.php');
    error_show(1, 'common', [
        'line' => __LINE__,
        'file' => __FILE__,
        'function' => __FUNCTION__
    ]);
  }

  if (file_exists(__DIR__ . '/../../views/' . $name . '.php'))
  {
    require(__DIR__ . '/../../views/' . $name . '.php');
  }
  else
  {
    include_once(__DIR__ . '/../error/error.php');
    error_show(2, 'common', [
        'line' => __LINE__,
        'file' => __FILE__,
        'function' => __FUNCTION__
    ]);
  }
}

/**
 * Do !empty($array[$key]) ? $array[$key] : '' for arrays key.
 *
 * @param array  $array - array, where need to find value by key.
 * @param string $key   - key, which value must be a find.
 * @param string $default
 *
 * @return string
 */
function common_setValue($array = [], $key = '', $default = '')
{
  return (!empty($array[$key]) ? $array[$key] : $default);
}

/**
 * Рекурсивная функция, работает так:
 * 1. Если передана скалярная переменная и она не пустая, то вернётся её значение;
 * 2. Если передана скалярная переменная и она пустая, то вернётся значение по умолчанию;
 * 3. Если передан массив или объект и не заполнен массив ключей, то вернётся массив или объект соответственно;
 * 4. Если передан массив или объект и в нём нет указанного ключа/параметра, то вернёт значение по умолчанию;
 * 5. Если передан массив или объект и в нём удалось найти всю цепочку ключей/параметров,
 *    то вернётся значение соответствующее этой цепочке.
 * Цепочка ключей/параметров - это последовательность вложенных ключей/параметров в массив/объект.
 * Например у массива $data['ke1']['key2'], цепочка ключей это 'key1','key2' при вызове функции
 * указывается так: common_getVariable($data, ['key1','key2'], 'Значение по умолчанию').
 * Для объекта $data->key1->key2: вызов функции выглядит так же, как и для массива.
 *
 * @param array  $variable  - Переменная
 * @param array  $keys      - Массив ключей в переменной
 * @param string $def_value - Значение по умолчанию
 *
 * @return variable|string
 */
function common_getVariable($variable, $keys = [], $def_value = '')
{
  //Для пустой переменной вернём значение по умолчанию
  if (!empty($variable))
  {
    if (!empty($keys)
        && is_array($keys)
        && (is_array($variable) || is_object($variable))
    )
    {
      //Если переменная объект, то конвертируем в массив
      $variable = (is_object($variable)) ? (array)$variable : $variable;
      //Извлечём первый элемент массива ключей
      $key = array_shift($keys);

      //Если ключ в массиве найден, то предполагаеться более глубокая вложенность массива
      if (array_key_exists($key, $variable))
      {
        return common_getVariable($variable[$key], $keys, $def_value);
      }
      else
      {
        return $def_value;
      }
    }
    else
    {
      return $variable;
    }
  }
  else
  {
    return $def_value;
  }
}

/**
 * If value is in array, then print checked or selected attributes.
 *
 * @param array  $array - array, where need to find value by key.
 * @param string $key   - key, which value must be a find.
 * @param int    $type  - attribute
 *                      1 - checked
 *                      2 - selected
 *
 * @return string
 */
function common_setCheckedSelected($array = [], $key = '', $val = '', $type = 1)
{
  $text = '';
  switch ($type)
  {
    case 1:
      $text = 'checked="checked"';
      break;
    case 2:
      $text = 'selected="selected"';
      break;
  }

  if (!empty($val))
  {
    return (!empty($array[$key]) && $array[$key] == $val ? $text : '');
  }
  else
  {
    return (!empty($array[$key]) ? $text : '');
  }
}

/**
 * Include message file for lib.
 *
 * @param string $model - lib name.
 *
 * @return array|mixed
 */
function common_setMessage($model = '')
{
  if (empty($model))
  {
    return [];
  }
  if (file_exists(__DIR__ . '/../../errors/' . $model . '/Message.php'))
  {
    return require(__DIR__ . '/../../errors/' . $model . '/Message.php');
  }
  else
  {
    return [];
  }
}

/**
 * Show message string from array for models by using codes.
 *
 * @param string $model   - model name.
 * @param array  $arCodes - array with codes.
 *
 * @return string
 */
function common_prepareMessageString($model = '', $arCodes = [])
{
  $message = common_setMessage($model);
  $str = '';
  if (empty($message))
  {
    return '';
  }
  if (empty($arCodes))
  {
    return '';
  }
  for ($i = 0; $i < $ic = count($arCodes); $i++)
  {
    if (!empty($arCodes[$i]))
    {
      $str .= '<p>' . $message[$arCodes[$i]] . '</p>';
    }
  }

  return $str;
}

/**
 * Get uri by array.
 *
 * @return array
 */
function common_getURI()
{
  $uri = explode('/', $_SERVER['REQUEST_URI']);
  unset($uri[0]);
  return $uri;
}

/**
 * Include config file.
 *
 * @param string $name - name of config file (can include a path from /var/www/stat/config)
 *
 * @return array
 */
function setConfig($name = '')
{
  if (!empty($name))
  {
    if (file_exists(__DIR__ . '/../../config/' . $name . '.php'))
    {
      return require(__DIR__ . '/../../config/' . $name . '.php');
    }
  }
  return [];
}

/**
 * Вывод массива на печать и выход.
 *
 * @param array $array - массив для печати
 */
function dd($array = [])
{
  dump($array);
  exit;
}

/**
 * Получает переменную с конфигурационного файла, зависящую от костанты сервера.
 *
 * @param string $key
 *
 * @return string
 */
function common_getServerVar($key = '')
{
  if (!empty(SERVER)
      && !empty($key)
  )
  {
    $file = __DIR__ . '/../../config/main/main.php';
    if (is_file($file))
    {
      $configArray = include $file;
      if (!isset($configArray[SERVER][$key]))
      {
        return '';
      }
      else
      {
        return $configArray[SERVER][$key];
      }
    }
  }

  return '';
}

/**
 * Подключаем конфигурационный файл и получаем по ключу значение конфига.
 *
 * @param string $name
 * @param string $key
 * @param string $server
 *
 * @return array
 */
function common_getConfig($name = '', $key = '', $server = '')
{
  if (!empty($name)
      && !empty($key)
  )
  {
    $file = __DIR__ . '/../../config/' . $name . '.php';
    if (is_file($file))
    {
      $config = require $file;
      if (empty($server)
          && !empty($config[$key])
      )
      {
        return $config[$key];
      }
      elseif (!empty($server)
          && !empty($config[$server][$key])
      )
      {
        return $config[$server][$key];
      }
    }
  }

  return [];
}