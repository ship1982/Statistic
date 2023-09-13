<?php

/**
 * Получаем список служебных переменных из файла __DIR__ . '/../config/variables.php'
 * 
 * @return type
 */
if(!function_exists('getVariables'))
{
  function getVariables()
  {
    if(file_exists(__DIR__ . '/../config/variables.php'))
      return require(__DIR__ . '/../config/variables.php');
    else
      return [];
  }
}


/**
 * Получаем список полей и их наименований из файла __DIR__ . '/../config/fields.php'
 * 
 * @return type
 */
if(!function_exists('getFields'))
{
  function getFields()
  {
    if(file_exists(__DIR__ . '/../config/fields.php'))
      return require(__DIR__ . '/../config/fields.php');
    else
      return [];
  }
}

/**
 * trim(strip_tags()).
 * 
 * @param type|string $value - строка для обработки
 * @return type
 */
if(!function_exists('striptrim'))
{
  function striptrim($value = '')
  {
    return trim(strip_tags($value));
  }
}

/**
 * Основные правила валидации для партнеров.
 * 
 * @return type
 */
if(!function_exists('getValidationRule'))
{
  function getValidationRule()
  {
    return [
      'name' => [
        'validation' => [
          'empty' => "Поле Партнер не должно быть пустым.",
          'unique' => "Такой партнер уже существует."
        ]
      ],
      'domains' => [
        'validation' => [
          'empty' => "Поле Домены не должно быть пустым.",
        ]
      ]
    ];
  }
}

/**
 * Проверка на обяхательные поля.
 * 
 * @param type|array $data - массив полей для проверки
 * @return type
 */
if(!function_exists('validateRequired'))
{
  function validateRequired($data = [])
  {
    $error = [];
    $validationRule = getValidationRule();
    if(!empty($validationRule)
      && !empty($data)
    )
    {
      foreach ($validationRule as $field => $__data)
      {
        if(empty($data[$field]))
          $error[] = $__data['validation']['empty'];
      }
    }

    return $error;
  }
}

/**
 * Убираем у old полей префикс old_.
 * 
 * @param type|array $old - массив старых значений в полях
 * @return type
 */
if(!function_exists('getOldDataWithNewDataKey'))
{
  function getOldDataWithNewDataKey($old = [])
  {
    $data = [];
    if(empty($old)) return $data;
    if(is_array($old))
    {
      foreach ($old as $key => $value)
      {
        $newKey = str_replace('old_', '', $key);
        if(!empty($newKey))
          $data[$newKey] = $value;
      }
    }

    return $data;
  }
}

/**
 * Делает запрос в БД, чтобы убедиться, что значение поля уникально.
 * 
 * @param type|array $variables @see getVariables()
 * @param type|string $field - поле для проверки
 * @param type|string $value - значение поля для проверки
 * @param type|array $old - массив полей с уникальными значениями (требуют проверки на уникальность)
 * @return type
 */
if(!function_exists('checkUniqueQuery'))
{
  function checkUniqueQuery($variables = [], $field = '', $value = '', $old = [])
  {
    $a = [];
    include_once(__DIR__ . '/database.php');
    if(!empty($field)
      || !empty($value)
      || !empty($variables['partnersTable'])
    )
    {
      $additionalWhere = "";
      if(!empty($old[$field]))
        $additionalWhere = " AND `$field`!='$old[$field]' ";

      $sql = "SELECT
          `$field`
        WHERE
          `$field`='$value'
          $additionalWhere";
      
      $o = query_db(
        1,
        $variables['partnersTable'],
        $sql
      );

      if(!empty($o))
      {
        if(!empty($o))
          $a = mysqli_fetch_assoc($o);
      }
    }

    return $a;
  }
}

/**
 * Проверка на уникальность значения поля в БД.
 * 
 * @param type|string $field - поле ля проверки 
 * @param type|string $value - значение для проверки
 * @param type|array $old - массив полей, которые уникальны
 * @return type
 */
if(!function_exists('validateUnique'))
{
  function validateUnique($field = '', $value = '', $old = [])
  {
    $error = [];
    $validationRule = getValidationRule();
    $variables = getVariables();
    if(empty($variables['partnersTable']))
      $error[] = "Непредвиденная ошибка на строке " . __LINE__ . ".";
    else
    {
      if(empty($field)
        || empty($value)
      )
      {
        if(empty($validationRule[$field]))
          $error[] = "Непредвиденная ошибка на строке " . __LINE__ . ".";

        $error[] = $validationRule[$field]['validation']['empty'];
      }

      // get old data
      $oldData = getOldDataWithNewDataKey($old);
      $a = checkUniqueQuery(
        $variables,
        $field,
        $value,
        $oldData
      );

      if(!empty($a))
        $error[] = $validationRule[$field]['validation']['unique'];
    }
    
    return $error;
  }
}

/**
 * Валидация данных до вставки в БД.
 * 
 * @param type|array $data - список полей для валидации
 * @return type
 */
if(!function_exists('validateAddEvent'))
{
  function validateAddEvent($data = [])
  {
    $error = [];
    if(!empty($data))
    {
      $name = striptrim($data['name']);
      $domains = striptrim($data['domains']);
      $pixel = striptrim($data['pixel']);
      $id = common_setValue($data, 'id');
      $state = 1;
      $old = common_setValue($data, 'old');

      // required validate
      $error1 = validateRequired([
        'name' => $name,
        'domains' => $domains,
        'pixel' => $pixel
      ]);

      // unique field
      $error2 = validateUnique('name', $name, $old);
      $error = array_merge(
        (array) $error1,
        (array) $error2
      );
    }

    return [
      'list' => [
        'name' => $name,
        'domains' => $domains,
        'pixel' => $pixel,
        'state' => $state,
        'id' => $id
      ],
      'error' => $error
    ];
  }
}

/**
 * Обновление пикселя события у партнера.
 * 
 * @param type|string $id 
 * @param type|string $pixel 
 * @return type
 */
if(!function_exists('partnresUpdatePartners2Pixel'))
{
  function partnresUpdatePartners2Pixel($id = '', $pixel = '')
  {
    $variables = getVariables();
    if(!empty($id)
      || !empty($pixel)
    )
    {
      if(!empty($variables['partnersTable']))
      {
        include_once(__DIR__ . '/database.php');
        $res = update_db(
          1,
          $variables['partnersTable'],
          ['pixel' => $pixel],
          ['id' => $id]
        );

        return $res;
      }
    }

    return false;
  }
}

/**
 * Если нет кода пикселя, то генерирует его.
 * 
 * @param type|array $data - список полей для добавления от сервиса
 * @param type|string $id - id партнера
 * @return type
 */
if(!function_exists('partnersAddPixel'))
{
  function partnersAddPixel($data = [], $id = '')
  {
    if(empty($data['pixel']))
    {
      // забираем содержимое пикселя
      if(file_exists(__DIR__ . '/../config/pixel.php'))
      {
        $pixel = require __DIR__ . '/../config/pixel.php';
        if(!empty($pixel)
          && !empty($id)
        )
        {
          $pixel = str_replace('{pin}', $id, $pixel);
          return $pixel;
        }
      }
    }

    return $data['pixel'];
  }
}


/**
 * Добавление нового партнера.
 * 
 * @param type|array $data - список полей для добавления.
 * @return type
 */
if(!function_exists('partnersAddAction'))
{
  function partnersAddAction($data = [])
  {
    // validate
    $res = validateAddEvent($data);
    if(empty($res['error']))
    {
      // add
      $variables = getVariables();
      if(empty($variables['partnersTable']))
        $res['error'] = array_merge(
          $res['error'],
          ["Непредвиденная ошибка на строке " . __LINE__ . "."]
        );
      else
      {
        // если нет ошибок
        if(!empty($res['list']))
        {
          // добавление нового партнера
          if(empty($data['update']))
          {
            $key = addNode($res['list']);
          }
          else
          {
            // обновление нового партнера
            // удаляем пиксель (его не обновляем)
            if(!empty($res['list']['pixel']))
              unset($res['list']['pixel']);

            update_db(
              1,
              $variables['partnersTable'],
              $res['list'], [
                'id' => $res['list']['id']
              ]
            );

            $key = true;
          }
          
          if(!empty($key))
            $res['success'] = $key;
        }
      }
    }

    if(!empty($res))
    {
      $res['items']['0'] = $res['list'];
      unset($res['list']);
    }
    
    return json_encode($res);
  }
}

/**
 * Метод для вывода списка партнеров.
 * 
 * @param type|array $data - передаваемые данные из сервиса.
 * @return type
 */
if(!function_exists('partnersShowAction'))
{
  function partnersShowAction($data = [])
  {
    include_once(__DIR__ . '/database.php');
    $variables = getVariables();
    $fields = getFields();

    if(!empty($data['action']))
      unset($data['action']);

    // провереям наличие параметров и строим из них запрос
    $sqlWhere = '';
    if(!empty($data))
    {
      foreach ($data as $key => $value)
      {
        if(isset($fields[$key]))
        {
          // если значение массив, то применяем IN оператор
          if(is_array($value)
            && !empty($value)
          )
            $sqlWhere .= "`$key`='" . implode("','", $value) . "' AND ";
          else
            $sqlWhere .= "`$key`='".prepare_db($value)."' AND ";
        }
      }

      $sqlWhere = ' WHERE ' . substr($sqlWhere, 0, -5);
    }

    $sql = "SELECT `" . implode("`,`", array_keys($fields)) . "`
      $sqlWhere
      LIMIT 1000";

    $o = query_db(
      1,
      $variables['partnersTable'],
      $sql
    );

    $data['items'] = [];
    $data['header'] = $fields;
    if(!empty($o))
    {
      while($a = mysqli_fetch_assoc($o))
        $data['items'][] = $a;
    }

    return json_encode($data);
  }
}

/**
 * Запрос на добавление нового партнера.
 * 
 * @param type|string $table - таблица
 * @param type|array $param - список полей для добавления
 * @return type
 */
if(!function_exists('insertNewNode'))
{
  function insertNewNode($table = '', $param = [])
  {
    if(!empty($param)
      && !empty($table)
    )
    {
      // добавление записи в таблицу
      // удаляем id
      if(isset($param['id']))
        unset($param['id']);

      $o = insert_db(
        1,
        $table, 
        $param
      );

      // добавить пиксель для партнера
      if(!empty($o))
      {
        $pixel = partnersAddPixel($param, $o);
        partnresUpdatePartners2Pixel($o, $pixel);
      }

      if(!empty($o))
        return $o;
      else
        return false;
    }
    return false;
  }
}

/**
 * Добавление нового партнера.
 *  
 * @param type|array $data - список полей
 * @return type
 */
if(!function_exists('addNode'))
{
  function addNode($data = [])
  {
    $variables = getVariables();
    if(empty($variables['partnersTable']))
      $error[] = "Непредвиденная ошибка на строке " . __LINE__ . ".";
    else
    {
      $data = insertNewNode(
        $variables['partnersTable'],
        $data
      );

      return $data;
    }

    return false;
  }
}

/**
 * Непосредственное удаление партнера.
 * 
 * @param type $id - id партнера 
 * @return type
 */
if(!function_exists('deleteNode'))
{
  function deleteNode($id = 0)
  {
    if(!empty($id))
    {
      include_once(__DIR__ . '/database.php');
      $variables = getVariables();
      if(!empty($variables['partnersTable']))
      {
        update_db(
          1,
          $variables['partnersTable'],
          ['state' => 0],
          ['id' => $id]
        );
        
        return 1;
      }
    }

    return 0;
  } 
}

/**
 * Удаление партнера.
 * 
 * @param type|array $data - список параметров, переданных сервисом
 * @return type
 */
if(!function_exists('partnerDeleteAction'))
{
  function partnerDeleteAction($data = [])
  {
    $result = 0;
    if(!empty($data['id']))
      $result = deleteNode($data['id']);

    return json_encode($data);
  }
}