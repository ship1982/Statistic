<?php

/**
 * Класс для построения html формы.
 *
 * @author Vitaly Khyakkinen <hva@zionec.ru>
 */
class Form
{
  /**
   * Свойство для метода передаваемых из формы данных.
   *
   * @param string $method - метод для отправки формы
   */
  private $method = '';

  /**
   * Метод инициализации формы.
   *
   * @param array $params - содержит массив параметров,
   *                      которые будут восприняты как key\value атрибуты для формы.
   *                      Например:
   *                      <form name="text-form" action="text">.
   *
   * @return string
   */
  public function run($params = [])
  {
    ob_start();
    $html = '<form';
    // так как метод у формы обязательное значение, то если его нет, то по умолчанию устанавливаем POST
    if (empty($params['method']))
    {
      $params['method'] = 'POST';
    }

    $html .= $this->params($params);
    $html .= '>';

    return $html;
  }

  /**
   * Метод вывода формы на экран.
   *
   * @return string
   */
  public function end()
  {
    ob_get_flush();
    return $html = '</form>';
  }

  public function setUniquey($options)
  {
    $html = '';
    $hidden = [];
    if (!empty($options['unique'])
        && is_array($options['unique'])
    )
    {
      foreach ($options['unique'] as $key => $value)
        $hidden[$key] = $value;

      $hidden['type'] = 'hidden';
      $html = $this->input($hidden);
    }

    return $html;
  }

  /**
   * Метод-конструктор для полей формы.
   *
   * @param string $type     - тип поля (input, select, text)
   * @param array  $options  - список атрибутов для элемента формы
   * @param string $template - шаблон для элемента формы.
   *                         Конструкция {field} определяет поле
   * @param array  $list     - список значений для select
   *
   * @return string
   */
  public function field($type = '', $options = [], $template = '', $list = [])
  {
    $html = '';
    $part = explode('{field}', $template);
    if (!empty($part[0]))
    {
      $html .= $part[0];
    }

    $html .= $this->setUniquey($options);
    unset($options['unique']);
    switch ($type)
    {
      case 'input':
      case 'text':
        $html .= $this->$type($options);
        break;
      case 'select':
        $html .= $this->$type($list, $options);
        break;
    }

    if (!empty($part[1]))
    {
      $html .= $part[1];
    }

    return $html;
  }

  /**
   * Вывод html для select элемента формы.
   *
   * @param array $list    - список значений для списка (key/value)
   * @param array $options - список атрибутов для select
   *
   * @return string
   */
  public function select($list = [], $options = [])
  {
    $html = '<select';
    $html .= $this->params($options);
    $html .= '>';
    // list
    if (!empty($list)
        && is_array($list)
    )
    {
      // смотрим значение по name атрибуту
      if (!empty($options['name']))
      {
        $valueValue = $this->getValue($options['name']);
      }
      else
      {
        $valueValue = [];
      }

      foreach ($list as $key => $value)
      {
        // если выбран мультиселект, то проверка через in_array идет
        if (is_array($valueValue))
        {
          if (in_array($key, $valueValue))
          {
            $selected = ' selected="selected" ';
          }
          else
          {
            $selected = ' ';
          }
        }
        else
        {
          // если значение из массива данных форм совпадает со значенем, то этот элемент выбран
          if ($key == $valueValue)
          {
            $selected = ' selected="selected" ';
          }
          else
          {
            $selected = ' ';
          }
        }

        $html .= '<option' . $selected . 'value="' . $key . '">' . $value . '</value>';
      }
    }
    $html .= '</select>';

    return $html;
  }

  /**
   * Вывод html параметров для checkbox элемента формы.
   *
   * @param array $params - список атрибутов
   *
   * @return string
   */
  public function checkbox($params = [])
  {
    $html = '';
    if (!empty($params)
        && is_array($params)
    )
    {
      // получаем списко передаваемых данных
      $data = $this->getData();
      foreach ($params as $key => $value)
      {
        // ставим checked если есть в массиве передаваемых данных
        if ('value' == $key
            && !empty($params['name'])
        )
        {
          if (isset($data[$params['name']]))
          {
            $html .= ' checked="checked" ';
          }
        }

        // устанавливаем метод формы
        $html .= " $key='$value'";
      }
    }

    return $html;
  }

  /**
   * Вывод html для input элемента формы.
   *
   * @param array $options - список атрибутов
   *
   * @return string
   */
  public function input($options = [])
  {
    $html = '<input';
    // для checkbox
    if (!empty($options['type'])
        && 'checkbox' == $options['type']
    )
    {
      $html .= $this->checkbox($options);
    }
    else
    {
      $html .= $this->params($options);
    }

    $html .= '>';
    return $html;
  }

  /**
   * Вывод html для textarea элемента формы.
   *
   * @param array $options - список атрибутов
   *
   * @return string
   */
  public function text($options = [])
  {
    $html = '<textarea';

    // получаем тест
    $text = '';
    if (isset($options['value']))
    {
      $text = $options['value'];
      unset($options['value']);
    }

    $html .= $this->params($options);
    $html .= '>' . $text . '</textarea>';

    return $html;
  }

  /**
   * Устанавливает атрибуты формы в зависимости от переданных значений.
   *
   * @param array $params - массив атрибутов
   *
   * @return string
   */
  public function params($params = [])
  {
    $html = '';
    if (!empty($params)
        && is_array($params)
    )
    {
      foreach ($params as $key => $value)
      {
        // устанавливаем метод формы
        if ('method' == $key)
        {
          $this->setMethod($value);
        }

        $html .= " $key='$value'";
      }
    }

    return $html;
  }

  /**
   * Установка метода для формы.
   *
   * @param string $method - тип метода (POST, GET)
   *
   * @return string
   */
  public function setMethod($method = '')
  {
    switch ($method)
    {
      case 'POST':
      case 'GET':
        $this->method = $method;
        break;
      default:
        $this->method = 'POST';
        break;
    }

    return $this->getMethod();
  }

  /**
   * Возвращает метод формы.
   *
   * @return string
   */
  public function getMethod()
  {
    return $this->method;
  }

  /**
   * Получает массив данных, переданный формой.
   *
   * @return array
   */
  public function getData()
  {
    $method = $this->getMethod();
    switch ($method)
    {
      case 'POST':
        return $_POST;
        break;

      case 'GET':
        return $_GET;
        break;

      default:
        return [];
        break;
    }
  }

  /**
   * Получает по ключу значение из массива. Если значение пустое, то может возвратить значение по умолчанию.
   *
   * @param string $key     - ключ из массива переданных формой данных
   * @param string $default - значение по умолчанию, если значение оказалось пустым
   *
   * @return string
   */
  public function getValue($key = '', $default = '')
  {
    $data = $this->getData();
    // если было передано множенственное значение в виде массива, то у ключа нужно убрать скобки
    if (strpos($key, '[]') !== false)
    {
      $key = str_replace('[]', '', $key);
    }

    // если значение массив, то вернуть нужно массив
    if (!empty($data[$key])
        && is_array($data[$key])
    )
    {
      return $data[$key];
    }

    if (empty($data[$key]))
    {
      if (empty($default))
      {
        return '';
      }
      else
      {
        return $default;
      }
    }
    else
    {
      return $data[$key];
    }
  }
}