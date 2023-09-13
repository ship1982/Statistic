<?php

// загрузка классов из lib
spl_autoload_register('Autoload7a6ddc05a5f40c203fc95b0725b4e378');

/**
 * Функция автозагрузки.
 *
 * @param $class
 */
function Autoload7a6ddc05a5f40c203fc95b0725b4e378($class)
{
  $folder = [
      __DIR__ . '/',
      __DIR__ . '../handlers/'
  ];

  for ($i = 0; $i < $ic = count($folder); $i++)
  {
    loadFromLibs($class, $folder[$i]);
  }
}

/**
 * Функция определения пути к классу.
 *
 * @param $class
 * @param $base_dir
 */
function loadFromLibs($class, $base_dir)
{
  $relative_class = $class;
  $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

  if (file_exists($file))
  {
    include_once $file;
  }
}

// загрузка классов из vendor
include_once __DIR__ . '/../vendor/autoload.php';

// подключение API
include_once __DIR__ . '/../lib/common/common.php';