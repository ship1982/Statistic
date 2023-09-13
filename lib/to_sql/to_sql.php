<?php

/*
 * Требуется подключение библиотечки ip_conv
 */

/**
 * Возвращает SQL сртоку для одного условия.
 * @param string $field
 * @param int $type
 * @param string $value
 * @return string
 */
function to_sql_parse_condition($field, $type, $value){
  common_inc('ip_conv');

  $sql_query = '';
  $field = '`'.$field.'`';
  switch ($type) {
    case 1://Точно соответствует
      $sql_query .= $field . ' = \'' . $value . '\'';
      break;
    case 2://Содержит
      $sql_query .= $field . ' LIKE \'%' . $value . '%\'';
      break;
    case 3://Начинается с
      $sql_query .= $field . ' LIKE \'' . $value . '%\'';
      break;
    case 4://Заканчивается на
      $sql_query .= $field . ' LIKE \'%' . $value . '\'';
      break;
    case 5://Соответствует регулярному выражению
      $sql_query .= $field . ' REGEXP \'' . $value . '\'';
      break;
    case 6://Является одним из
      $sql_query .= $field . ' IN \'' . $value . '\'';
      break;
    case 7://Не является точным соответствием
      $sql_query .= $field . ' != \'' . $value . '\'';
      break;
    case 8://Не содержит
      $sql_query .= $field . ' NOT LIKE \'%' . $value . '%\'';
      break;
    case 9://Интервал
      $value = explode('-', $value);
      $sql_query .= '('.$field . ' BETWEEN \'' . $value[0] . '\' AND \'' . ((array_key_exists(1, $value)) ? $value[1] : '') . '\')';
      break;
    case 10://Больше
      $sql_query .= $field . ' > \'' . $value . '\'';
      break;
    case 11://Больше (день)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') > \'' . ((int) $value * 86400) . '\'';
      break;
    case 12://Больше (неделя)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') > \'' . ((int) $value * 604800) . '\'';
      break;
    case 13://Больше (месяц)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') > \'' . ((int) $value * 2629743) . '\'';
      break;
    case 14://Равно
      $sql_query .= $field . ' = \'' . $value . '\'';
      break;
    case 15://Равно (день)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') = \'' . ((int) $value * 86400) . '\'';
      break;
    case 16://Равно (неделя)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') = \'' . ((int) $value * 604800) . '\'';
      break;
    case 17://Равно (месяц)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') = \'' . ((int) $value * 2629743) . '\'';
      break;
    case 18://Меньше
      $sql_query .= $field . ' < \'' . $value . '\'';
      break;
    case 19://Меньше (день)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') < \'' . ((int) $value * 86400) . '\'';
      break;
    case 20://Меньше (неделя)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') < \'' . ((int) $value * 604800) . '\'';
      break;
    case 21://Меньше (месяц)
      $sql_query .= '(UNIX_TIMESTAMP(NOW()) - '.$field . ') < \'' . ((int) $value * 2629743) . '\'';
      break;
    case 22://Маска сети
      $range = ip_conv_cidr_to_range($value);
      $value[0] = ip_conv_ip_to_binary_32($range[0]);
      $value[1] = ip_conv_ip_to_binary_32($range[1]);
      $sql_query .= '('.$field . ' BETWEEN \'' . $value[0] . '\' AND \'' . $value[1] . '\')';
      break;
    case 23://Соответствует IP
      $sql_query .= $field . ' = \'' . ip_conv_ip_to_binary_32($value) . '\'';
      break;
    default:
      break;
  }
  return $sql_query;
}

/**
 * Возвращает SQL строку для нескольких условий, с учётом логического И/ИЛИ, между ними.
 * @param array $arr_conditions массив условий
 * @return string
 */
function to_sql_arr_conditions_to_sql($arr_conditions){
  $count_query = 0; //Счетчик условий в группе
	$sql_query = ''; //Строка запросов

  if(is_array($arr_conditions)){
    for($i=0;$i< count($arr_conditions); $i++){
      //$arr_conditions - Количество условий для каждой группы
      $count_cond = count($arr_conditions[$i]);
      if($count_cond == 4 && test_fields_from_query($arr_conditions[$i]) === true){
        $sql_query .= ($count_query > 0) ? ' ' . $arr_conditions[$i]['andor'] . ' ' : '';
        $sql_query .= to_sql_parse_condition($arr_conditions[$i]['field'], $arr_conditions[$i]['type'], $arr_conditions[$i]['value']);

        $count_query++;
      }elseif($count_cond == 2){
        $is_ok = true;
        for($j = 0; $j < $count_cond; $j++){
          if(test_fields_from_query($arr_conditions[$i][$j]) !== true){
            $is_ok = false;
          }
        }
        if($is_ok === true){
          
          for($j = 0; $j < $count_cond; $j++){
            $sql_query .= ($j > 0)?' ' . $arr_conditions[$i][$j]['andor'] . ' ':'';
            //$sql_query .= ($j == 0)?'(':'';
            if($count_query > 0 && $j == 0){
              $sql_query .= ' ' . $arr_conditions[$i][$j]['andor'] . ' (';
            }elseif($count_query == 0 && $j == 0){
              $sql_query .= '(';
            }
            $sql_query .= to_sql_parse_condition($arr_conditions[$i][$j]['field'], $arr_conditions[$i][$j]['type'], $arr_conditions[$i][$j]['value']);
          }
          $sql_query .= ')';
          $count_query++;
          //common_pre($sql_query);
        }
      }
    }
  }
  //common_pre($sql_query);
  return $sql_query;
}

/**
 * Вернёт истину, если в массиве условия присутствуют поля: field; type; value; andor;
 * @param array $arr_condition
 * @return boolean
 */
function test_fields_from_query($arr_condition){
  if(array_key_exists('field', $arr_condition)
    && array_key_exists('type', $arr_condition)
    && array_key_exists('value', $arr_condition)
    && array_key_exists('andor', $arr_condition)
  ){
    return true;
  }
  return false;
}

/**
 * Добавляет скобочки для SQL запроса(входной переменной $query), и AND если это не первый запрос
 * @param string $query - Первоначальная строка запроса, которая изменяется
 * @param string $new_qeury - добавленный SQL запрос.
 * @return string
 */
function to_sql_get_parentheses($query, $new_qeury){
  if(!empty($query) && (!empty($new_qeury))){
    $query .= ' AND ('.$new_qeury.')';

  }elseif(empty($query) && (!empty($new_qeury))){
    $query = '('.$new_qeury.')';
  }
  return $query;
}