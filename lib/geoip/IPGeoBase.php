<?php

namespace geoip;

class IPGeoBase
{
  private $fhandleCIDR, $fhandleCities, $fSizeCIDR, $fsizeCities;

  /*
   * @brief Конструктор
   *
   * @param CIDRFile файл базы диапазонов IP (cidr_optim.txt)
   * @param CitiesFile файл базы городов (cities.txt)
   */
  function __construct($CIDRFile = false, $CitiesFile = false)
  {
    if (!$CIDRFile)
    {
      $CIDRFile = __DIR__ . '/../../config/geoip/cidr_optim.txt';
    }

    if (!$CitiesFile)
    {
      $CitiesFile = __DIR__ . '/../../config/geoip/cities.txt';
    }

    $this->fhandleCIDR = fopen($CIDRFile, 'r') or die("Cannot open $CIDRFile");
    $this->fhandleCities = fopen($CitiesFile, 'r') or die("Cannot open $CitiesFile");
    $this->fSizeCIDR = filesize($CIDRFile);
    $this->fsizeCities = filesize($CitiesFile);
  }

  /*
   * @brief Получение информации о городе по индексу
   * @param idx индекс города
   * @return массив или false, если не найдено
   */
  private function getCityByIdx($idx)
  {
    rewind($this->fhandleCities);
    while (!feof($this->fhandleCities))
    {
      $str = fgets($this->fhandleCities);
      $arRecord = explode("\t", trim($str));
      if ($arRecord[0] == $idx)
      {
        return array(
            'city' => $arRecord[1],
            'region' => $arRecord[2],
            'district' => $arRecord[3],
            'lat' => $arRecord[4],
            'lng' => $arRecord[5]
        );
      }
    }
    return false;
  }

  /*
   * @brief Получение гео-информации по IP
   * @param ip IPv4-адрес
   * @return массив или false, если не найдено
   */
  function getRecord($ip)
  {
    rewind($this->fhandleCIDR);
    $rad = floor($this->fSizeCIDR / 2);
    $pos = $rad;
    while (fseek($this->fhandleCIDR, $pos, SEEK_SET) != -1)
    {
      if ($rad)
      {
        $str = fgets($this->fhandleCIDR);
      }
      else
      {
        rewind($this->fhandleCIDR);
      }

      $str = fgets($this->fhandleCIDR);

      if (!$str)
      {
        return false;
      }

      $arRecord = explode("\t", trim($str));

      $rad = floor($rad / 2);
      if (!$rad && ($ip < $arRecord[0] || $ip > $arRecord[1]))
      {
        return false;
      }

      if ($ip < $arRecord[0])
      {
        $pos -= $rad;
      }
      elseif ($ip > $arRecord[1])
      {
        $pos += $rad;
      }
      else
      {
        $result = array(
            'range' => $arRecord[2],
            'cc' => $arRecord[3]
        );

        if ($arRecord[4] != '-' && $cityResult = $this->getCityByIdx($arRecord[4]))
        {
          $result += $cityResult;
        }

        /** get isp */
        $result['isp'] = $this->getProvider($ip);

        return $result;
      }
    }

    return false;
  }

  /**
   * Get ISP from RIPE.
   *
   * @param string $ip - ip address
   *
   * @return string
   */
  function getProvider($ip)
  {
    $handle = fopen(__DIR__ . '/../../config/geoip/isp.txt', 'r');
    $sizeFile = filesize(__DIR__ . '/../../config/geoip/isp.txt');
    rewind($handle);
    $rad = floor($sizeFile / 2);
    $pos = $rad;
    while (fseek($handle, $pos, SEEK_SET) != -1)
    {
      if ($rad)
      {
        $str = fgets($handle);
      }
      else
      {
        rewind($handle);
      }

      $str = fgets($handle);

      if (!$str)
      {
        return false;
      }

      $arRecord = explode("\t", trim($str));

      $rad = floor($rad / 2);
      if (!$rad && ($ip < $arRecord[0] || $ip > $arRecord[1]))
      {
        return false;
      }

      if ($ip < $arRecord[0])
      {
        $pos -= $rad;
      }
      elseif ($ip > $arRecord[1])
      {
        $pos += $rad;
      }
      else
      {
        /*$result = [
            'sip' => $arRecord[0],
            'eip' => $arRecord[1],
            'netnum' => $arRecord[2],
            'descr' => $arRecord[3],
            'country' => $arRecord[4],
        ];*/

        $result = $arRecord[3];

        return $result;
      }
    }

    return false;
  }
}