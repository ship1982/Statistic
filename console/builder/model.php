<?php

function builder_default()
{
  return '<?php
  
namespace {{class}};

use model\Model;

class {{class}} extends Model
{
  /**
   * @var array - массив полей таблицы с их значениями для одной записи. Здесь храниться модель.
   */
  public $options = [];

  /**
   * @var array - изначальный ключ шардинга
   */
  protected $shard = [1];

  /**
   * @var string - таблица для модели
   */
  public $table = \'{{table}}\';

  /**
   * Правила валидации для модели.
   *
   * @return array
   */
  public function getValidationRule()
  {
    return [];
  }

  /**
   * Список дополнительных значений к полям.
   * Например рускоязыное название или какие-то доп параметры.
   *
   * @return array
   */
  public function attributes()
  {
    return [];
  }

  /**
   * Возвращает одну запись по значениею и полю.
   * 
   * @param string $value
   * @param string $key
   *
   * @return array
   */
  public function one($value = \'\', $key = \'id\')
  {
    $this->select();
    $this->from();
    $this->where([$key => $value]);
    $this->limit(1);
    $this->execute();
    $data = $this->fetch();
    return (!empty($data[0]) ? $data[0] : []);
  }
  
  /**
   * {{class}} constructor.
   *
   * @param string $callbackShard
   */
  function __construct($callbackShard = \'{{callback}}\')
  {
    parent::__construct(
        $this->shard,
        $this->table,
        $callbackShard
    );

    if (is_callable([
        $this,
        \'attributes\'
    ]))
    {
      $params = $this->attributes();
      $this->addParam($params);
    }
  }
}';
}

function builder_replace($model = '', $table = '', $sharding = '')
{
  $content = builder_default();
  // производим замены
  $content = str_replace('{{class}}', $model, $content);
  $content = str_replace('{{table}}', $table, $content);
  $content = str_replace('{{callback}}', $sharding, $content);

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