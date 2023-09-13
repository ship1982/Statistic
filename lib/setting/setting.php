<?php

/**
 * Получает конфигурационный файл для скрипта.
 *
 * @param array $args - argv parameters.
 *
 * @return array of configuration
 */
function setting_getConfig($args = [])
{
  $configName = $args[1];
  if (empty($args[1]))
  {
    exit("\nНет такого файла конфигурации.");
  }

  $arConfig = require(__DIR__ . '/../../config/setting/setting.php');
  if (empty($arConfig[$configName]))
  {
    exit("\nНет такого файла конфигурации.");
  }

  return $arConfig[$configName];
}

/**
 * Генерация скрипта.
 *
 * @param $args.
 *
 * @return int
 */
function setting_buildJS($args)
{
  if (!file_exists(__DIR__ . '/../../web/' . $args[2] . '.js'))
  {
    exit("\nНет такого скрипта\n");
  }
  $fileContent = file_get_contents(__DIR__ . '/../../web/' . $args[2] . '.js');
  $content = $fileContent;
  $arConfig = setting_getConfig($args);
  foreach ($arConfig as $code => $value)
  {
    if ($code == 'clear' && $value == true)
    {
      $content = preg_replace("/(debug\\(\\'.*?\\'\\);)/i", '', $content);
    }
    else
    {
      $content = preg_replace("/(<!--" . $code . "-->.*?<!--" . $code . "-->)/i", $value, $content);
    }
  }

  return file_put_contents(__DIR__ . '/../../web/' . $args[2] . '.min.js', $content);
}

/**
 * @constructor
 *
 * @param array $args - argv parameters.
 */
function setting_init($args = [])
{
  setting_showHelp($args);
  $res = setting_buildJS($args);
  if ($res)
  {
    echo "\nГенерация скрипта прошла усшпешно. Новый код лежит в файле /stat/web/$args[2].min.js.\n Для успешного продолжения работы, вам необходимо сжать данный код, используя сервис https://closure-compiler.appspot.com/home\n После чего поместите сжатый код в файл и указать этот файл как рабочий\n\n";
  }
  else
  {
    echo "\nПроизошла непредваиденная ошибка.\n";
  }
}

/**
 * Print name of table for current month and year,
 *
 * @param array $table
 * @param int   $numberOfMonth - number of month
 * @param int   $numberOfYear  - year
 *
 * @return string
 */
function setting_shardingTableName($table = [], $numberOfMonth = 0, $numberOfYear = 0)
{
  if (!empty($table)
      && !empty($numberOfMonth)
      && !empty($numberOfYear)
  )
  {
    $time = strtotime(date('c', mktime(0, 0, 0, $numberOfMonth, 1, $numberOfYear)));
    return $table . '_' . $time;
  }

  return '';
}

/**
 * Генерация справки.
 */
function setting_showHelp($data)
{
  if(empty($data[1])
      && empty($data[2])
  )
  {
    echo "\n\nСПРАВКА\n\n";
    echo "\n- первым параметром нужно передать название конфигурационного файла (zionec|mgts)";
    echo "\n- вторым параметром необходимо передать название скрипта относительно папки web без расщинения js. Например pixel";
    echo "\n\n";
    exit;
  }
}