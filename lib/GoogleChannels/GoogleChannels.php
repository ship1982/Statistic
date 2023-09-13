<?php

namespace GoogleChannels;

use SearchEngines\SearchEngines;
use SocialNetwork\SocialNetwork;
use native\arrays\ArrayHelper;

class GoogleChannels
{
  /**
   * @var array - параметры из конфиг-файла
   */
  public $config = [];

  /**
   * Список условий для канала в виде массива.
   *
   * @var array
   */
  public $channel = [];

  /**
   * Получение настроек РК
   *
   * @return array
   */
  private static function loadConfig()
  {
    if (file_exists(__DIR__ . '/../../config/visitors/channel.php'))
    {
      return include(__DIR__ . '/../../config/visitors/channel.php');
    }
    return [];
  }

  /**
   * Метод строит часть запроса для фильтрации в соответствии с типом тарифка.
   *
   * @param array $channels - список каналов по которым нужно строить запрос
   *
   * @return string
   */
  function getFilterByGroup($channels)
  {
    $sql = [];
    if (!empty($channels))
    {
      for ($i = 0; $i < $ic = count($channels); $i++)
      {
        switch ($channels[$i])
        {
          case 'display':
          case 'paid_search':
          case 'other_advertising':
          case 'organic_search':
          case 'social_network':
          case 'referral':
          case 'email':
          case 'direct':
            $sql[] = $this->startCondition('mysql', $this->channel[$channels[$i]]);
            break;
        }
      }
    }

    return "(" . implode(" OR ", $sql) . ")";
  }

  /**
   * Построение условия mysql.
   *
   * @param array  $channels
   * @param string $condition
   */
  function mysqlCondition($channels = [], &$condition = '')
  {
    if (!empty($channels))
    {
      foreach ($channels as $item)
      {
        // тип
        if (is_array($item)
            && !empty($item['type']))
        {
          $condition .= " " . $item['type'] . " ";
        } // если это блок условий
        else if (is_array($item)
            && !empty($item['field'])
            && !empty($item['operator'])
            && isset($item['value'])
        )
        {
          if ('in' == strtolower($item['operator'])
              || 'not in' == strtolower($item['operator'])
          )
          {
            $condition .= $item['field'] . " " . $item['operator'] . " (" . $item['value'] . ")";
          }
          else
          {
            $condition .= $item['field'] . " " . $item['operator'] . " '" . $item['value'] . "'";
          }
        }
        else
        {
          // условия со скобками
          $condition .= '(';
          self::mysqlCondition($item, $condition);
          $condition .= ')';
        }
      }
    }
  }

  /**
   * Построение условия для php.
   *
   * @param array  $channels
   * @param string $condition
   * @param        $name
   */
  function phpCondition($channels = [], &$condition = '', $name)
  {
    if (!empty($channels))
    {
      foreach ($channels as $item)
      {
        // тип
        if (is_array($item)
            && !empty($item['type']))
        {
          // dump('тип');
          if ('and' == strtolower($item['type']))
          {
            $condition .= " && ";
          }
          elseif ('or' == strtolower($item['type']))
          {
            $condition .= " || ";
          }
          else
          {
            $condition .= " " . $item['type'] . " ";
          }
        } // если это блок условий
        else if (is_array($item)
            && !empty($item['field'])
            && !empty($item['operator'])
            && isset($item['value'])
        )
        {
          if ('in' == strtolower($item['operator']))
          {
            $condition .= '@in_array(' . $name . "['" . $item['field'] . "'], [" . $item['value'] . "])";
          }
          else if ('not in' == strtolower($item['operator']))
          {
            $condition .= '@!in_array(' . $name . "['" . $item['field'] . "'], [" . $item['value'] . "])";
          }
          else if ('=' == $item['operator'])
          {
            if (empty($item['value']))
            {
              $condition .= 'empty(' . $name . '["' . $item['field'] . '"])';
            }
            else
            {
              $condition .= '@' . $name . "['" . $item['field'] . "'] == '" . $item['value'] . "'";
            }
          }
          else
          {
            $condition .= '@' . $name . "['" . $item['field'] . "'] " . $item['operator'] . " '" . $item['value'] . "'";
          }
        }
        else
        {
          // условия со скобками
          $condition .= '(';
          self::phpCondition($item, $condition, $name);
          $condition .= ')';
        }
      }
    }
  }

  /**
   * Исполнение условия на php.
   *
   * @param string $condition
   * @param        $data
   *
   * @return bool
   */
  function checkPhpCondition($condition = '', $data)
  {
    $execute = 'if (' . $condition . '){return "1";}else{return "0";}';
    $answer = eval($execute);
    return ('1' === $answer);
  }

  /**
   * Запуск условия.
   *
   * @param string $type
   * @param array  $channels
   * @param string $name
   *
   * @return string
   */
  function startCondition($type = 'php', $channels = [], $name = '$data')
  {
    $condition = '(';
    switch ($type)
    {
      case 'php':
        self::phpCondition($channels, $condition, $name);
        break;
      case 'mysql':
        self::mysqlCondition($channels, $condition);
        break;
    }

    $condition .= ')';
    return $condition;
  }

  /**
   * GoogleChannels constructor.
   */
  function __construct()
  {
    $this->config = self::loadConfig();
    $search = new SearchEngines();
    $oSearch = $search->_list(['link']);
    $aSearch = ArrayHelper::map($oSearch, '', 'link');

    $socialNetwork = new SocialNetwork();
    $arSocialNetwork = $socialNetwork->_list(['href']);
    $social_network = ArrayHelper::map($arSocialNetwork, '', 'href');

    /**
     * заполнение массива
     */
    $this->channel = [
        'direct' => [
            [
                'field' => 'utm_campaign',
                'operator' => '=',
                'value' => ""
            ],
            [
                'type' => 'AND'
            ],
            [
                'field' => 'utm_source',
                'operator' => '=',
                'value' => ""
            ],
            [
                'type' => 'AND'
            ],
            [
                'field' => 'utm_medium',
                'operator' => '=',
                'value' => ""
            ],
            [
                'type' => 'AND'
            ],
            [
                'field' => 'utm_content',
                'operator' => '=',
                'value' => ""
            ],
            [
                'type' => 'AND'
            ],
            [
                'field' => 'utm_term',
                'operator' => '=',
                'value' => ""
            ],
            [
                'type' => 'AND'
            ],
            [
                'field' => 'referer_link',
                'operator' => '=',
                'value' => ""
            ]
        ],
        'display' => [
            [
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'display'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpm'
                ]
            ],
            [
                'type' => 'AND'
            ],
            [
                'field' => 'utm_source',
                'operator' => '=',
                'value' => 'content'
            ]
        ],
        'email' => [
            [
                'field' => 'utm_medium',
                'operator' => '=',
                'value' => "email"
            ]
        ],
        'paid_search' => [
            [
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'ppc'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpc'
                ],
            ],
            [
                'type' => 'AND'
            ],
            [
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'adfox'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'admitad'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'adnews'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'adnous'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'adriver'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'advmaker'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'aport'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'AvitoPromo'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'AvitoContext'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'begun'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'bing'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'B2BContext'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'criteo'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'directadvert'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'drivenetwork'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'facebook'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'giraffio'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'google'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'instagram'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'kavanga'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'ladycenter'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'link'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'magna'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'marketgid'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'medialand'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'merchant'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'moimir'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'nnn'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'odnoklassniki'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'price'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'prre'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'mytarget'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'taboola'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'torg.mail.ru'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'trorer'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'Ttarget'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'ubn.ua'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'videonow'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'vkontakte'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'whisla'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '=',
                    'value' => 'youtube'
                ],
            ]
        ],
        'other_advertising' => [
            [
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpc'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'ppc'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpm'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpv'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpa'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'cpp'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'affiliate'
                ],
            ],
            [
                'type' => 'AND'
            ],
            [
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'adfox'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'admitad'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'adnews'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'adnous'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'adriver'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'advmaker'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'aport'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'AvitoPromo'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'AvitoContext'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'begun'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'bing'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'B2BContext'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'criteo'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'directadvert'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'drivenetwork'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'facebook'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'giraffio'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'google'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'instagram'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'kavanga'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'ladycenter'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'link'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'magna'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'marketgid'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'medialand'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'merchant'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'moimir'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'nnn'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'odnoklassniki'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'price'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'prre'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'mytarget'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'taboola'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'torg.mail.ru'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'trorer'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'Ttarget'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'ubn.ua'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'videonow'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'vkontakte'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'whisla'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'utm_source',
                    'operator' => '!=',
                    'value' => 'youtube'
                ],
            ]
        ],
        'organic_search' => [
            [
                [
                    'field' => 'utm_medium',
                    'operator' => '=',
                    'value' => 'organic'
                ],
                [
                    'type' => 'OR'
                ],
                [
                    'field' => 'referer_domain',
                    'operator' => 'IN',
                    'value' => "'" . implode("','", $aSearch) . "'"
                ]
            ]
        ],
        'social_network' => [
            [
                [
                    'field' => 'referer_domain',
                    'operator' => 'IN',
                    'value' => "'" . implode("','", $social_network) . "'"
                ]
            ]
        ],
        'referral' => [
            [
                [
                    'field' => 'referer_domain',
                    'operator' => 'NOT IN',
                    'value' => "'" . implode("','", $social_network) . "'"
                ]
            ]
        ],
    ];
  }
}