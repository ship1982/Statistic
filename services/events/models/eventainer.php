<?php

function getVariables()
{
  if(file_exists(__DIR__ . '/../config/variables.php'))
    return require(__DIR__ . '/../config/variables.php');
  else
    return [];
}

function getFields()
{
  if(file_exists(__DIR__ . '/../config/fields.php'))
    return require(__DIR__ . '/../config/fields.php');
  else
    return [];
}

function striptrim($value = '')
{
  return trim(strip_tags($value));
}

function getValidationRule()
{
  return [
    'event' => [
      'validation' => [
        'empty' => "Поле Событие не должно быть пустым."
      ]
    ],
    'label' => [
      'validation' => [
        'empty' => "Поле Ярлык не должно быть пустым.",
        'unique' => "Поле с таким ярлыком уже существует."
      ]
    ]
  ];
}

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

function checkUniqueQuery($variables = [], $field = '', $value = '', $old = [])
{
  $a = [];
  include_once(__DIR__ . '/database.php');
  if(!empty($field)
    || !empty($value)
    || !empty($variables['eventainerTable'])
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
      $variables['eventainerTable'],
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

function validateUnique($field = [], $value = '', $old = [])
{
  $error = [];
  $validationRule = getValidationRule();
  $variables = getVariables();
  if(empty($variables['eventainerTable']))
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

function extractPosition($id = '')
{
  if(empty($id)) return [];
  $left = '';
  $right = '';
  $level = 1;
  list($left, $right, $level) = explode('-', $id);

  return [
    'left' => $left,
    'right' => $right,
    'level' => $level
  ];
}

function validateAddEvent($data = [])
{
  $error = [];
  if(!empty($data))
  {
    $name = striptrim($data['name']);
    $event = striptrim($data['event']);
    $category = striptrim($data['category']);
    $label = striptrim($data['label']);
    $value = striptrim($data['value']);
    $old = common_setValue($data, 'old');

    $position = extractPosition($data['id']);

    // required validate
    $error1 = validateRequired(array_merge([
      'name' => $name,
      'event' => $event,
      'category' => $category,
      'label' => $label,
      'value' => $value,
    ]), $position);

    // unique field
    $error2 = validateUnique('label', $label, $old);
    $error = array_merge(
      (array) $error1,
      (array) $error2
    );
  }

  return [
    'list' => array_merge([
      'name' => $name,
      'event' => $event,
      'category' => $category,
      'label' => $label,
      'value' => $value,
    ], $position),
    'error' => $error
  ];
}

function eventAddAction($data = [])
{
  // validate
  $res = validateAddEvent($data);
  if(empty($res['error']))
  {
    // add
    $variables = getVariables();
    if(empty($variables['eventainerTable']))
      $res['error'] = array_merge(
        $res['error'],
        ["Непредвиденная ошибка на строке " . __LINE__ . "."]
      );
    else
    {
      if(!empty($res['list']))
      {
        if(empty($data['update']))
        {
          $key = addNode(
            common_setValue($res['list'], 'right'),
            $res['list']
          );
        }
        else
        {
          update_db(
            1,
            $variables['eventainerTable'],
            array_merge(
              ['id' => 1],
              $res['list']
            ), [
              'label' => $res['list']['label']
            ]
          );
          $key = true;
        }
        
        if(!empty($key))
          $res['success'] = true;
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

/**
 * Prepare keys for tree search in MySQL.
 * 
 * @param type|array $data - array with data, sending form controller.
 * @return type
 */
function eventSetPosition($data = [])
{
  if(!empty($data)
    && !empty($data['left'])
    && !empty($data['right'])
    && !empty($data['level'])
    || (!empty($data['label']))
  )
    return $data;
  else
    return array_merge(['level' => 1], $data);
}

/**
 * Prepare where cluase string by using tree.
 * 
 * @param type|array $data - array with keys for tree's search
 * possible keys:
 * level, right, left
 * @return type
 */
function eventSetWhereStr($data = [])
{
  $strWhere = '';
  $field4Position = [
    'right' => 1,
    'left' => 1,
    'level' => 1
  ];

  if(!empty($data))
  {
    foreach ($data as $key => $value)
    {
      if(!empty($field4Position[$key]))
      {
        switch ($key)
        {
          case 'right': $strWhere .= " `right` <= '" . ($value - 1) . "' AND "; break;
          case 'left': $strWhere .= " `left` >= '" . $value . "' AND "; break;
          case 'level': $strWhere .= " `level` = '" . $value . "' AND "; break;
        }
      }
    }
  }

  if(!empty($data['label']))
    $strWhere .= " `label`='$data[label]' AND ";

  if(!empty($strWhere))
    $strWhere = substr($strWhere, 0, -5);
  else
    $strWhere = "1=1";

  return $strWhere;
}

function eventShowAction($data = [])
{
  include_once(__DIR__ . '/database.php');
  $variables = getVariables();
  $fields = getFields();

  if(!empty($data['action']))
    unset($data['action']);

  $queryParams = eventSetPosition($data);

  $o = query_db(
    1,
    $variables['eventainerTable'],
    "SELECT `" . implode("`,`", array_keys($fields)) . "`
    WHERE " . eventSetWhereStr(array_merge($queryParams, $data)) . "
    LIMIT 1000"
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

function getRightMargin($right = 0, $table = '')
{
  if(!empty($right)) return $right;
  if(!empty($table))
  {
    $o = query_db(
      1,
      $table,
      "SELECT MAX(`right`) as `right` WHERE 1=1"
    );

    if(!empty($o))
    {
      $a = mysqli_fetch_assoc($o);
      if(!empty($a['right']))
        return $a['right'] + 1;
      else
        return 2;
    }
  }

  return 2;
}

function updatePositionInInsert($right = '', $table = '')
{
  if(!empty($right)
    && !empty($table)
  )
  {
    $o = updateQuery_db(
      1,
      $table,
      "UPDATE {from} SET
        `left` = `left` + 2,
        `right` = `right` + 2
      WHERE `left` > '$right'"
    );
  }
  return true;
}

function updateParentTree($right = '', $table = '')
{
  if(!empty($right)
    && !empty($table)
  )
  {
    $o = updateQuery_db(
      1,
      $table,
      "UPDATE {from} SET
        `right` = `right` + 2
      WHERE `right` >= $right
        AND `left` < $right"
    );
    
    return true;
  }
  return false;
}

function insertNewNode($table = '', $param = [])
{
  if(!empty($param)
    && !empty($table)
  )
  {
    $o = insert_db(
      1,
      $table, 
      $param
    );
    if(!empty($o))
      return true;
    else
      return false;
  }
  return false;
}

function addNode($right = '', $data = [])
{
  $variables = getVariables();
  if(empty($variables['eventainerTable']))
    $error[] = "Непредвиденная ошибка на строке " . __LINE__ . ".";
  else
  {
    $right = getRightMargin(
      $right,
      $variables['eventainerTable']
    );

    if(!empty($right)
      && $right != 2
    )
    {
      $res = updatePositionInInsert(
        $right,
        $variables['eventainerTable']
      );
    }

    if(!empty($right))
    {
      updateParentTree(
        $right,
        $variables['eventainerTable']
      );

      $param = [
       'left' => $right,
       'right' => $right + 1,
       'level' => (empty($data['level']) ? 1 : $data['level']),
       'id' => 1
      ];

      $newParam = array_merge(
        $data,
        $param
      );

      $data = insertNewNode(
        $variables['eventainerTable'],
        $newParam
      );

      return $data;
    }
  }

  return false;
}

function deleteNodePart1($left = '', $right = '', $table = '')
{
  if(!empty($left)
    && !empty($right)
    && !empty($table)
  )
  {
    include_once(__DIR__ . '/database.php');
    $sql = "DELETE WHERE `left` >= $left AND `right` <= $right";

    return query_db(
      1,
      $table,
      $sql
    );
  }
  return false;
}

function deleteNodePart2($left = '', $right = '', $table = '')
{
  if(!empty($left)
    && !empty($right)
    && !empty($table)
  )
  {
    include_once(__DIR__ . '/database.php');
    $sql = "UPDATE {from} SET `right` = `right` – ($right - $left + 1)
      WHERE `right` > $right
        AND `left` < $left";

    return updateQuery_db(
      1,
      $table,
      $sql
    );
  }
  return false;
}

function deleteNodePart3($left = '', $right = '', $table = '')
{
  if(!empty($left)
    && !empty($right)
    && !empty($table)
  )
  {
    include_once(__DIR__ . '/database.php');
    $sql = "UPDATE {from} SET `left` = `left` – ($right - $left + 1), `right` = `right` – ($right - $left + 1)
      WHERE `left` > $right";

    return updateQuery_db(
      1,
      $table,
      $sql
    );
  }
  return false;
}

function deleteNode($position)
{
  if(!empty($position))
  {
    $variables = getVariables();
    if(!empty($variables['eventainerTable'])
      && !empty($position['left'])
      && !empty($position['right'])
    )
    {
      // var_dump('expression');exit;
      $res = deleteNodePart1(
        $position['left'],
        $position['right'],
        $variables['eventainerTable']
      );

      if($res)
      {
        deleteNodePart2(
          $position['left'],
          $position['right'],
          $variables['eventainerTable']
        );

        deleteNodePart3(
          $position['left'],
          $position['right'],
          $variables['eventainerTable']
        );

        return 1;
      }
    }
  }

  return 0;
}

function eventDeleteAction($data = [])
{
  $result = 0;
  if(!empty($data['id']))
  {
    $position = extractPosition($data['id']);
    if(!empty($position['left'])
      && !empty($position['right'])
    )
      $result = deleteNode($position);
  }
  return json_encode($data);
}