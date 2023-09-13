<?php

/**
 * Класс основной валидации данных.
 */
class Validation
{
  /**
   * @var array - правила валидации
   * Пример правила:
   * [
   *   'name' => [
   *     'validation' => [
   *       'empty' => "Поле Название не должно быть пустым.",
   *       'unique' => [
   *        'message' => "Такое название уже существует.",
   *        'target' => $reportDomains,
   *        'getValue' => $modelData
   *       ],
   *       'less' => [
   *          'value' => 1,
   *          'message' => "Поле должно быть не менее 1 символа"
   *        ],
   *        'more' => [
   *          'value' => 10,
   *           'message' => 'Поле должно быть не длиннее 10 симолов'
   *        ]
   *     ]
   *   ],
   *   'domains' => [
   *     'validation' => [
   *       'empty' => "Поле Домены не должно быть пустым.",
   *     ]
   *   ]
   * ];
   *
   * Ключами являются названия полей, а значениями правила валидации.
   * Поддерживаются следующие правила:
   * empty - проверка на обязательность
   * unique - проверка на уникальность значения в БД.
   *  message - сообщение в случае ошибки
   *  target - класс модели
   *  getValue - значения текущей модели из БД
   *  callback - функиция, которая будет вызвана для обработки значения на уникальность
   * less - проверка, что значение имеет не менее n символов
   * more - проверка, что значение имеет не более n символов
   */
  protected $rule = [];

  /**
   * @var array - массив ошибок
   */
  protected $error = [];

  /**
   * @var string - таблица для запров на уникальность
   */
  protected $table = '';

  function __construct($rule = [], $table = '')
  {
    $this->rule = $rule;
    $this->table = $table;
  }

  /**
   * Метод для проверки на заполненость значения.
   *
   * @param array $data - список всех полей, отправленных формой
   *
   * @return void
   */
  public function required($data = [])
  {
    if (!empty($this->rule)
        && !empty($data)
    )
    {
      foreach ($this->rule as $field => $__data)
      {
        if (empty($data[$field]))
        {
          $this->error[] = $__data['validation']['empty'];
        }
      }
    }
  }

  /**
   * Проверка на уникальность значения в БД.
   * @see $this->rule
   *
   * @param array $data - список всех полей, отправленных формой
   *
   * @return void
   */
  public function isUnique($data = [])
  {
    // передаем два класса, 1 - как получить старые записи, 2 - как проверить дубли в БД
    if (!empty($this->rule)
        && !empty($data)
    )
    {
      foreach ($this->rule as $field => $__data)
      {
        if (!empty($__data['validation']['unique'])
            && !empty($__data['validation']['unique']['target'])
            && isset($data[$field])
        )
        {
          // получаем данные из модели
          $modelsData = (empty($__data['validation']['unique']['getValue']) ? [] : $__data['validation']['unique']['getValue']);

          $targetClass = $__data['validation']['unique']['target'];

          // если переан колбэк, то выполняем его
          if (!empty($__data['validation']['unique']['callback'])
              && !empty($__data['validation']['unique']['callback'][0])
              && !empty($__data['validation']['unique']['callback'][1])
          )
          {
            $answer = call_user_func_array(
                $__data['validation']['unique']['callback'][0],
                $__data['validation']['unique']['callback'][1]
            );
            if (!empty($answer))
            {
              $this->error[] = $__data['validation']['unique']['message'];
            }
          }
          else
          {
            // строим запрос на проверку по уникальности
            $targetClass->select([$field]);
            $targetClass->from();
            $targetClass->where([$field => $data[$field]]);
            if (!empty($modelsData[$field]))
            {
              $targetClass->addAnd();
              $targetClass->addWhere('<>', [$field => $modelsData[$field]]);
            }
            $targetClass->limit(1);
            $targetClass->execute();
            $data = $targetClass->fetch();
            if (!empty($data[0]))
            {
              $this->error[] = $__data['validation']['unique']['message'];
            }
          }
        }
      }
    }
  }

  /**
   * Проверка, что значение не менее заданного.
   *
   * @param array $data - список всех полей, отправленных формой
   *
   * @return void
   */
  public function isLess($data = [])
  {
    if (!empty($this->rule)
        && !empty($data)
    )
    {
      foreach ($this->rule as $field => $__data)
      {
        if (empty($__data['validation']['less'])
            && !empty($__data['validation']['less']['value'])
            && !empty($__data['validation']['less']['message'])
            && isset($data[$field])
            && $__data['validation']['less']['value'] < strlen($data[$field])
        )
        {
          $this->error[] = $__data['validation']['less']['message'];
        }
      }
    }
  }

  /**
   * Проверка, что значение не более заданного.
   *
   * @param array $data - список всех полей, отправленных формой
   *
   * @return void
   */
  public function isMore($data = [])
  {
    if (!empty($this->rule)
        && !empty($data)
    )
    {
      foreach ($this->rule as $field => $__data)
      {
        if (empty($__data['validation']['more'])
            && !empty($__data['validation']['more']['value'])
            && !empty($__data['validation']['more']['message'])
            && isset($data[$field])
            && $__data['validation']['more']['value'] > strlen($data[$field])
        )
        {
          $this->error[] = $__data['validation']['more']['message'];
        }
      }
    }
  }

  /**
   * Метод для проверки успешна ли валидация.
   *
   * @return bool
   */
  public function isValid()
  {
    return (empty($this->error) ? true : false);
  }

  /**
   * Метод выводит массив ошибок валидации.
   *
   * @return array
   */
  public function showError()
  {
    return $this->error;
  }

  /**
   * Пользовательская валидация данных.
   *
   * @param array $data - массив данных для валидации
   *
   * @return void
   */
  public function customValidation($data = [])
  {
    if (!empty($this->rule))
    {
      foreach ($this->rule as $key => $param)
      {
        if (!empty($param['callback'])
            && !empty($param['callback']['function'])
        )
        {
          $additional = (empty($param['callback']['params']) ? [] : $param['callback']['params']);
          $error = call_user_func_array(
              $param['callback']['function'],
              [
                  $key,
                  $data,
                  $additional
              ]
          );
          if (!empty($error))
          {
            $this->error = array_merge(
                $error,
                $this->error
            );
          }
        }
      }
    }
  }

  /**
   * Валидация данных.
   *
   * @param array $data - список всех полей, отправленных формой
   *
   * @return bool
   */
  public function validate($data = [])
  {
    $this->customValidation($data);
    $this->required($data);
    $this->isLess($data);
    $this->isMore($data);
    $this->isUnique($data);
    return $this->isValid();
  }
}