<?php

namespace html\form;

class ModernForm
{
  /**
   * @var - данные, передеанные формой
   */
  static protected $formData;

  /**
   * @var - метод формы
   */
  static protected $method;

  /**
   * Не обрабатывать значения стандартно.
   *
   * @var array
   */
  static protected $skipValueTypes = [
      'file',
      'password',
      'checkbox',
      'radio'
  ];

  /**
   * Получение метода формы.
   *
   * @param array $options
   */
  static function setMethod($options = [])
  {
    if (!empty($options['method']))
    {
      switch ($options['method'])
      {
        case 'POST':
        case 'GET':
          self::$method = $options['method'];
          break;
        default:
          self::$method = 'POST';
      }
    }
  }

  /**
   * Возвращает метод формы.
   *
   * @return mixed
   */
  static function getMethod()
  {
    return self::$method;
  }

  /**
   * @param array $options
   *
   * @return string
   */
  static function open($options = [])
  {
    self::setMethod($options);

    ob_start();
    return '<form ' . self::setAttribute($options) . '>';
  }

  /**
   * Установка атрибутов формы.
   *
   * @param array $options
   *
   * @return string
   */
  static function setAttribute($options = [])
  {
    $html = '';
    if (!empty($options))
    {
      foreach ($options as $field => $value)
      {
        $html .= " $field=\"$value\"";
      }
    }

    return $html;
  }

  /**
   * Закрытие формы.
   *
   * @return string
   */
  static function close()
  {
    ob_get_flush();
    return '</form>';
  }

  /**
   * Получает массив данных, переданный формой.
   */
  static function getData()
  {
    if (!empty(self::$formData))
    {
      return;
    }

    $method = self::getMethod();
    switch ($method)
    {
      case 'POST':
        self::$formData = $_POST;
        break;

      case 'GET':
        self::$formData = $_GET;
        break;

      default:
        self::$formData = [];
        break;
    }

    return;
  }

  /**
   * Получает значение поля.
   *
   * @param string $name
   *
   * @return string
   */
  static function getValue($name = '')
  {
    if (!empty($name))
    {
      self::getData();
      return (empty(self::$formData[$name]) ? '' : self::$formData[$name]);
    }
    return '';
  }

  /**
   * Вывод input поля формы.
   *
   * @param string $type
   * @param string $name
   * @param array  $options
   * @param bool   $callback
   *
   * @return string
   */
  static function input($type = '', $name = '', $options = [], $callback = false)
  {
    if (!empty($name))
    {
      $value = self::getValues($type, $name, $callback);
      $merge = compact('type', 'value', 'name');
      $options = array_merge($merge, $options);
      return '<input ' . self::setAttribute($options) . '>';
    }
    return '';
  }

  /**
   * Установка значения checked для input.
   *
   * @param $type
   * @param $name
   * @param $value
   *
   * @return string
   */
  static function getCheckedState($type, $name, $value)
  {
    switch ($type)
    {
      case 'checkbox':
        if (!empty($name))
        {
          self::getData();
          return (empty(self::$formData[$name]) ? '' : 'checked');
        }
        return '';
      case 'radio':
        return $value;
      default:
        return $value;
    }
  }

  /**
   * Отображение чекбокса в html.
   *
   * @param       $name
   * @param int   $value
   * @param array $options
   * @param null  $checked
   *
   * @return string
   */
  static function checkbox($name, $value = 1, $options = [], $checked = null)
  {
    $options['type'] = 'checkbox';
    if ($checked)
    {
      $options['checked'] = 'checked';
    }
    $checked = self::getCheckedState($options['type'], $name, $value);
    if ($checked)
    {
      $options['checked'] = 'checked';
    }
    $options['value'] = $value;
    return self::input($options['type'], $name, $options);
  }

  /**
   * Вывод textarea поля формы.
   *
   * @param string $type
   * @param string $name
   * @param array  $options
   *
   * @return string
   */
  static function textarea($type = '', $name = '', $options = [])
  {
    if (!empty($name))
    {
      $value = self::getValues($type, $name, []);
      $merge = compact('name');
      $options = array_merge($options, $merge);

      return '<textarea ' . self::setAttribute($options) . '>' . htmlspecialchars($value) . '</textarea>';
    }
    return '';
  }

  /**
   * Возвращает значение из модели или массива POST.
   *
   * @param string $type
   * @param string $name
   *
   * @return array|mixed|string
   */
  static function getValues($type = '', $name = '', $callback)
  {
    if (!empty($type)
        && !empty($name)
    )
    {
      if (!in_array($type, self::$skipValueTypes))
      {
        $values = self::getValue($name);
        if (!empty($callback)
            && is_callable($callback))
        {
          $values = call_user_func_array($callback, [$values]);
        }

        return $values;
      }
    }

    return null;
  }

  /**
   * Формирует options для select.
   *
   * @param string $type
   * @param string $name
   * @param array  $values
   * @param array  $optionAttribute
   *
   * @return string
   */
  static function getSelectedOptions($type = '', $name = '', $values = [], $optionAttribute = [])
  {
    $html = '';
    $arSelected = self::getValues($type, $name, []);
    foreach ($values as $key => $value)
    {
      if (!empty($arSelected) && in_array($key, $arSelected))
      {
        $selected = ' selected="selected" ';
      }
      else
      {
        $selected = ' ';
      }
      $html .= '<option' . $selected . 'value="' . $key . '" ' . self::setAttribute($optionAttribute) . '>' . $value . '</option>';
    }

    return $html;
  }

  /**
   * Формирует options для select.
   *
   * @param string $type
   * @param string $name
   * @param array  $values
   * @param array  $optionAttribute
   *
   * @return string
   */
  static function getSelectedOptionsOne($type = '', $name = '', $values = [], $optionAttribute = [])
  {
    $html = '';
    $strSelected = self::getValues($type, $name, []);
    foreach ($values as $key => $value)
    {
      if (!empty($strSelected) && $strSelected == $key)
      {
        $selected = ' selected="selected" ';
      }
      else
      {
        $selected = ' ';
      }
      $html .= '<option' . $selected . 'value="' . $key . '" ' . self::setAttribute($optionAttribute) . '>' . $value . '</option>';
    }

    return $html;
  }

  /**
   * Вывод multiselect для формы.
   *
   * @param string $type
   * @param string $name
   * @param array  $values
   * @param array  $options
   * @param array  $optionsAttribute
   *
   * @return string
   */
  static function multiselect($type = '', $name = '', $values = [], $options = [], $optionsAttribute = [])
  {
    $optionsValues = self::getSelectedOptions(
        $type,
        $name,
        $values,
        $optionsAttribute
    );

    $options['name'] = $name . '[]';

    return '<select ' . self::setAttribute($options) . '>' . $optionsValues . '</select>';
  }

  /**
   * Вывод select для формы.
   *
   * @param string $type
   * @param string $name
   * @param array  $values
   * @param array  $options
   * @param array  $optionsAttribute
   *
   * @return string
   */
  static function select($type = '', $name = '', $values = [], $options = [], $optionsAttribute = [])
  {
    $optionsValues = self::getSelectedOptionsOne(
        $type,
        $name,
        $values,
        $optionsAttribute
    );

    $options['name'] = $name;

    return '<select ' . self::setAttribute($options) . '>' . $optionsValues . '</select>';
  }
}