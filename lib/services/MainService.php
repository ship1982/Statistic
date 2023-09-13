<?php

namespace services;

/**
 * Main class for manage service.
 * Contain method for make query to service and get answer from it.
 *
 * @author vitalykhy
 */
class MainService
{
  /**
   * Get services configuration file.
   * This file must be on each servers.
   *
   * @param string - service name.
   *
   * @return array
   * Must be url key.
   */
  function config($serviceName = '')
  {
    if (!empty($serviceName))
    {
      if (file_exists(__DIR__ . '/../../config/services/route.php'))
      {
        $servicesRoute = require(__DIR__ . '/../../config/services/route.php');
        if (!empty($servicesRoute[$serviceName]))
        {
          return $servicesRoute[$serviceName];
        }
        else
        {
          return [];
        }
      }
      else
      {
        return [];
      }
    }
    else
    {
      return [];
    }
  }

  /**
   * Input file for make query to the service
   *
   * @param string $serviceName - name of service
   * @param array  $params      - array of parameters
   *
   * @return mixed service answer
   */
  function query($serviceName = '', $params = [])
  {
    $serviceConfig = $this->config($serviceName);
    if (!empty($serviceConfig['url']))
    {
      $answer = $this->urlProcessing($serviceConfig['url'], $params);
      return $answer;
    }
    else
    {
      return '';
    }
  }

  /**
   * Prepare parameters for transfer on remote machine ($_GET)
   *
   * @param array $params - array of params.
   *
   * @return string
   */
  function stringParams($params = [])
  {
    if (empty($params['method']))
    {
      return '';
    }
    $query = '?method=' . $params['method'] . '&';
    unset($params['method']);
    foreach ($params as $key => $value)
      $query .= $key . '=' . $value . '&';

    $query = substr($query, 0, -1);
    return $query;
  }

  /**
   * Prepare parameters for transfer on local machine (array)
   *
   * @param array $params - array of params.
   *
   * @return array
   */
  function arrayParams($params = [])
  {
    return $params;
  }

  /**
   * Send query to service
   *
   * @param string $url - url for service.
   * @param array|string - parameters for query
   *
   * @return mixed - data form service
   */
  function urlProcessing($url = '', $params)
  {
    if ($this->isRemoteService($url))
    {
      /** link */

    }
    else
    {
      /** local service */
      if (file_exists($url))
      {
        $params = $this->arrayParams($params);
        require $url;

        return (empty($answer) ? '' : $answer);
      }
    }

    return '';
  }

  /**
   * Call needed class (if exist) and method with data.
   *
   * @param array $params
   *
   * @return bool|mixed
   */
  function execute($params = [])
  {
    $config = $this->parse($params);
    if (empty($config))
    {
      return false;
    }
    if (!empty($config['class']))
    {
      $class = new $config['class'];
      $data = call_user_func_array([
          $class,
          $config['method']
      ], [$config['data']]
      );
    }
    else
    {
      $data = call_user_func_array($config['method'], [$config['data']]);
    }

    return $data;
  }

  /**
   * Parse receive data for service
   *
   * @param array|string $data - data, transferred to the service
   *                           On index.php on the service side, you need to understand what data is coming. ($_GET -
   *                           for remote or array - for local)
   *
   * @return array
   * Contains:
   * class (if exist). If method, which will give to service contain dot.
   * method - name of method
   * data - data receiving to the service
   */
  function parse($data = [])
  {
    if (!empty($data) && !empty($data['method']))
    {
      $className = '';
      if (strpos($data['method'], '.'))
      {
        list($className, $method) = explode('.', $data['method']);
      }
      else
      {
        $method = $data['method'];
      }

      unset($data['method']);
      $query = [];
      foreach ($data as $key => $value)
        $query[$key] = $value;

      return [
          'class' => $className,
          'method' => $method,
          'data' => $query
      ];
    }
    else
    {
      return [];
    }
  }

  /**
   * Check remote service or local
   *
   * @param string $url - url of service
   *
   * @return bool
   */
  function isRemoteService($url = '')
  {
    if (($url[0] == '/' && $url[1] == '/')
        || strpos($url, 'http://')
        || strrpos($url, 'https://')
    )
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}