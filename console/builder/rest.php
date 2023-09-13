<?php

function builder_default()
{
  return '<?php
  
namespace {{class}};

use model\Model;
use Rest\Rest;

include_once __DIR__ . \'/../autoload.php\';

class {{class}} extends Rest
{
  /**
   * @var array|Model
   */
  public $model = [];

  /**
   * SendAPI constructor.
   *
   * @param array $params
   */
  function __construct(array $params = [])
  {
    parent::__construct($params);
    $this->model = new Model([1], \'{{table}}\', false);
  }

  /**
   * Получение данных сущности
   */
  function get()
  {
    // TODO: Implement get() method.
  }

  /**
   * Добавление данных сущности
   */
  function post()
  {
    // TODO: Implement post() method.
  }

  /**
   * Удаление данных сущности
   */
  function del()
  {
    // TODO: Implement del() method.
  }

  /**
   * Обновление данных сущности
   */
  function put()
  {
    // TODO: Implement put() method.
  }
}';
}

function builder_replace($model = '', $table = '', $sharding = '')
{
  $content = builder_default();
  // производим замены
  $content = str_replace('{{class}}', $model, $content);
  $content = str_replace('{{table}}', $table, $content);
  $content = str_replace('{{callback}}', (empty($sharding) ? 'false' : "'" . $sharding . "'"), $content);

  return $content;
}

function builder_create($model = '', $table = '', $sharding = '')
{
  $content = builder_replace($model, $table, $sharding);
  if (!is_dir(__DIR__ . '/../../lib/' . $model . '/'))
  {
    mkdir(__DIR__ . '/../../lib/' . $model . '/', 0775, true);
  }

  $file = __DIR__ . '/../../lib/' . $model . '/' . $model . '.php';
  if (!file_exists($file))
  {
    file_put_contents($file, $content);
    echo "\nМодель успешно создана и расположена в " . realpath($file) . "\n\n";
  }
}

function builder_help()
{
  return "\n\nСправка\n\n
- первым параметром укажите название модели.\n
- вторым параметром указываем название таблицы.\n
- третим параметром указываем тип функции для шардинга\n
";
}

// вызов справки
if (!empty($argv[1])
    && $argv[1] == 'help'
)
{
  echo builder_help();
  exit;
}

if (empty($argv[1])
    || empty($argv[2])
)
{
  echo builder_help();
  exit;
}

builder_create(@$argv[1], @$argv[2], @$argv[3]);