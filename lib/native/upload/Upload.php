<?php

// подключаем родительский класс
common_inc('native/request', 'Request');

class Upload extends Request
{
  /**
   * @var string - папка для загрузки файлов
   */
  public $uploadFolder = '';

  /**
   * @var int - максимальный размер файла для загрузки
   */
  protected $uploadMaxFileSize = 4000000;

  /**
   * @var array - массив с расширениями файлов для загрузки
   */
  protected $uploadFileExtension = [
      'jpg',
      'jpeg',
      'png',
      'gif'
  ];

  /**
   * @var bool|string - сохранять ли имя файла при загрузке
   */
  protected $saveName = false;

  /**
   * @var string - поле из формы, в котором искать массив FILES
   */
  protected $field = 'file';

  /**
   * @var array - массив ошибок
   */
  public $error = [];

  /**
   * Получение массива $_FILES.
   *
   * @return array
   */
  public function getFiles()
  {
    $this->actual = $_FILES;
    return $this->actual;
  }

  function __construct($folder = '', $field = '', $saveName = false, $uploadMaxFileSize = 4000000, $uploadFileExtension = [])
  {
    if (empty($folder))
    {
      exit("Не задана папка для сохранения файла.");
    }
    else
    {
      $uploadDir = __DIR__ . '/../../../web/' . trim($folder, '/');
      if (!is_dir($uploadDir))
      {
        mkdir($uploadDir, 0777, true);
      }

      $this->uploadFolder = $uploadDir;
    }

    if (!empty($uploadMaxFileSize))
    {
      $this->uploadMaxFileSize = $uploadMaxFileSize;
    }

    if (!empty($uploadFileExtension))
    {
      $this->uploadFileExtension = $uploadFileExtension;
    }

    if (!empty($field))
    {
      $this->field = $field;
    }

    if (!empty($saveName))
    {
      $this->saveName = $saveName;
    }
  }

  /**
   * Генерирует новое имя фала, если при загрузке выбрана опция @see $saveName.
   *
   * @param string $string - имя файла
   *
   * @return string
   */
  public function generateName($string = '')
  {
    if (!empty($string))
    {
      $salt = time() . uniqid();
      $name = $string . $salt;
      return sha1($name);
    }

    return '';
  }

  /**
   * Устанавливает новое имя файла.
   */
  public function setName()
  {
    // если не сохраняем имя
    if (false === $this->saveName)
    {
      if (is_array($this->actual[$this->field]))
      {
        if (is_array($this->actual[$this->field]['name']))
        {
          for ($i = 0; $i < $ic = count($this->actual[$this->field]['name']); $i++)
            $this->actual[$this->field]['name'][$i] = $this->generateName($this->actual[$this->field]['name'][$i]);
        }
        else
        {
          $this->actual[$this->field]['name'] = $this->generateName($this->actual[$this->field]['name']);
        }
      }
    }
  }

  /**
   * Проверяет допустимые расширения файла.
   */
  public function validateExtension()
  {
    if (!empty($this->actual[$this->field]))
    {
      // если передан массив
      if (is_array($this->actual[$this->field]['type']))
      {
        for ($i = 0; $i < $ic = count($this->actual[$this->field]['type']); $i++)
        {
          $ext = pathinfo($this->actual[$this->field]['name'][$i], PATHINFO_EXTENSION);
          if (!in_array($ext, $this->uploadFileExtension))
          {
            $this->error[] = "Данный вид файлов не поддерживается.";
          }
          else
          {
            $this->actual[$this->field]['ext'][$i] = $ext;
          }
        }
      }
      else
      {
        $ext = pathinfo($this->actual[$this->field]['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, $this->uploadFileExtension))
        {
          $this->error[] = "Данный вид файлов не поддерживается.";
        }
        else
        {
          $this->actual[$this->field]['ext'] = $ext;
        }
      }
    }
  }

  /**
   * Проеряет размер файла.
   */
  public function validateSize()
  {
    if (!empty($this->actual[$this->field]))
    {
      // если передан массив
      if (is_array($this->actual[$this->field]['size']))
      {
        for ($i = 0; $i < $ic = count($this->actual[$this->field]['size']); $i++)
        {
          if ($this->actual[$this->field]['size'][$i] > $this->uploadMaxFileSize)
          {
            $this->error[] = "Файл больше допустимого размера";
          }
        }
      }
      else
      {
        if ($this->actual[$this->field]['size'] > $this->uploadMaxFileSize)
        {
          $this->error[] = "Файл больше допустимого размера";
        }
      }
    }
  }

  /**
   * Возвращает прищнак того, валиден ли файл или нет.
   *
   * @return bool
   */
  public function isValid()
  {
    return (empty($this->error) ? true : false);
  }

  /**
   * Валидация файла перед сохранением.
   *
   * @return bool
   */
  public function validate()
  {
    if (!empty($this->actual[$this->field]))
    {
      $this->validateSize();
      $this->validateExtension();
      return $this->isValid();
    }

    return true;
  }

  /**
   * Сохранение файла.
   *
   * @return array
   */
  public function save()
  {
    $state = false;
    $result = []; // массив с путями для файлов
    $this->getFiles();
    if ($this->validate())
    {
      if (false === $this->saveName)
      {
        $this->setName();
      }

      if (is_array($this->actual[$this->field])
          && is_array($this->actual[$this->field]['name'])
      )
      {
        for ($i = 0; $i < $ic = count($this->actual[$this->field]['name']); $i++)
        {
          if (false !== $this->saveName)
          {
            $destination = $this->uploadFolder . "/" . $this->actual[$this->field]['name'][$i];
          }
          else
          {
            $destination = $this->uploadFolder . "/" . $this->actual[$this->field]['name'][$i] . "." . $this->actual[$this->field]['ext'][$i];
          }

          $statusByPicture = move_uploaded_file(
              $this->actual[$this->field]['tmp_name'][$i],
              $destination
          );

          $result[$i] = [
              'path' => str_replace($this->uploadFolder . "/", '', $destination),
              'state' => $statusByPicture
          ];

          if (!$statusByPicture)
          {
            break;
          }
        }
      }
      else
      {
        if (false !== $this->saveName)
        {
          $destination = $this->uploadFolder . "/" . $this->actual[$this->field]['name'];
        }
        else
        {
          $destination = $this->uploadFolder . "/" . $this->actual[$this->field]['name'] . "." . $this->actual[$this->field]['ext'];
        }
        $state = move_uploaded_file(
            $this->actual[$this->field]['tmp_name'],
            $destination
        );
        $result[0] = [
            'path' => str_replace($this->uploadFolder . "/", '', $destination),
            'state' => $state
        ];
      }
    }

    if (false === $state)
    {
      $this->error[] = 'Непредвиденная ошибка.';
    }

    return $result;
  }

  /**
   * Выводит список ошибок валидации при загрузке файла.
   *
   * @return array
   */
  public function showError()
  {
    return $this->error;
  }
}