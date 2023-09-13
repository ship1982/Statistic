<?php

namespace Ripe;


class Ripe
{
  /**
   * @var string - папка для сохранения файлов от RIPE
   */
  public $folder;

  /**
   * @var int - время, больше которого нельзя обновлять
   */
  public $time = 86400;

  /**
   * Ripe constructor.
   *
   * @param string $folder
   */
  function __construct($folder = '')
  {
    if (empty($folder))
    {
      $folder = __DIR__ . '/../../config/ripe';
    }

    if (!is_dir(__DIR__ . '/../../config/ripe'))
    {
      mkdir(__DIR__ . '/../../config/ripe', 0777, true);
    }
    $this->folder = $folder;
  }

  /**
   * Нужно ли обновлять файл.
   *
   * @param string $file
   *
   * @return bool
   */
  function needUpdate($file = '')
  {
    $current = time();
    if ($current - filectime($this->folder . '/' . $file) > $this->time)
    {
      return true;
    }
    return false;
  }

  /**
   * Загрузка файла.
   *
   * @param string $url
   */
  function download($url = '')
  {
    if (!empty($url)
        && true === $this->needUpdate($url)
    )
    {
      system("cd " . realpath($this->folder) . " && wget ftp://ftp.ripe.net/ripe/dbase/split/" . $url . " && gunzip $url");
    }
  }

  /**
   * Чтение файла по блокам.
   *
   * @param string $file
   * @param        $callback
   */
  function read($file = '', $callback)
  {
    if (is_file($this->folder . '/' . $file))
    {
      $f = fopen($this->folder . '/' . $file, 'r');
      if (!empty($f))
      {
        $string = [];
        while (($buffer = fgets($f)) !== false)
        {
          // запоминаем блок
          if ("\n" != $buffer)
          {
            $string[] = trim($buffer);
          }
          else
          {
            $blockArray = [];
            // дошли до конца блока
            for ($i = 0; $i < $ic = count($string); $i++)
            {
              if (strpos($string[$i], ': ') === false)
              {
                break;
              }
              else
              {
                $arBlockData = explode(": ", $string[$i]);
                if (!empty($arBlockData))
                {
                  $key = trim($arBlockData[0]);
                  $value = trim($arBlockData[1]);
                  if (!empty($blockArray[$key]))
                  {
                    $blockArray[$key] .= $value;
                  }
                  else
                  {
                    $blockArray[$key] = $value;
                  }
                }
              }
            }
            // callback
            if (!empty($callback)
                && is_callable($callback)
                && !empty($blockArray)
            )
            {
              call_user_func_array($callback, [
                  $blockArray,
                  $file
              ]);
            }
            $string = '';
          }
        }
      }
    }
  }
}