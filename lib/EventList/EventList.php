<?php

namespace EventList;

use geoip\IPGeoBase;
use GoogleChannels\GoogleChannels;
use ListConditionISP\ListConditionISP;
use model\Model;
use native\Strings\StringHelper;
use queue\queues\QueueEvents;
use QueueEnter\QueueEnterEvents;
use services\MainService;
use Ticket64\Ticket64;
use UserlistOnline\UserlistOnline;

class EventList extends Model
{
  /**
   * @var array - массив полей таблицы с их значениями для одной записи. Здесь храниться модель.
   */
  public $options = [];

  /**
   * @var GoogleChannels
   */
  public $googleChannel;

  /**
   * @var MainService
   */
  public $services;

  /**
   * @var Ticket64
   */
  public $ticket;

  /**
   * @var array - изначальный ключ шардинга
   */
  protected $shard = [1];

  /**
   * @var string - таблица для модели
   */
  public $table = 'event_list';

  /**
   * Правила валидации для модели.
   *
   * @return array
   */
  public function getValidationRule()
  {
    return [];
  }

  /**
   * Список дополнительных значений к полям.
   * Например рускоязыное название или какие-то доп параметры.
   *
   * @return array
   */
  public function attributes()
  {
    return [];
  }

  /**
   * Возвращает одну запись по значениею и полю.
   *
   * @param string $value
   * @param string $key
   *
   * @return array
   */
  public function one($value = '', $key = 'id')
  {
    $this->select();
    $this->from();
    $this->where([$key => $value]);
    $this->limit(1);
    $this->execute();
    $data = $this->fetch();
    if (!empty($data[0]))
    {
      return $data[0];
    }
    else
    {
      return [];
    }
  }

  /**
   * Получение массива со временем.
   *
   * @param array $data
   *
   * @return array
   */
  function timePrepare($data = [])
  {
    $res = [];
    if (empty($data['time']))
    {
      return $res;
    }
    $res['time'] = $data['time'];
    $formattedDateTime = date('YmdH', $data['time']);
    if (!empty($formattedDateTime))
    {
      $res['datetime'] = $formattedDateTime;
      $res['date'] = substr($formattedDateTime, 0, 8);
      $res['hour'] = substr($formattedDateTime, 8, 2);
    }

    return $res;
  }

  /**
   * Обработка значения события для сайта МГТС.
   *
   * @param array $data
   *
   * @return array
   */
  function prepareMGTSQuery($data = [])
  {
    $partner = common_getVariable($data, ['partner'], 0);
    $stat_data = [];
    switch ($partner)
    {
      case 1:
        //Статистика для МГТС
        $stat_data = (array_key_exists('event_value', $data)) ? $this->event_parseParamMGTS($data['event_value']) : $stat_data;
        break;
    }

    // Если получили данные по статистике
    if (!empty($stat_data))
    {
      $data = array_replace($data, $stat_data);
      unset($data['event_value']);
    }

    return $data;
  }

  /**
   * Функция возвращает массив с данными для статистики МГСТ
   *
   * @param array $stat_data
   *
   * @return array|null
   */
  function event_parseParamMGTS($stat_data)
  {
    if (empty($stat_data) || !is_array($stat_data))
    {
      return null;
    }

    return [
        'internet' => common_getVariable($stat_data, ['internet'], 0),
        'internet_price' => common_getVariable($stat_data, [
            'internet_data',
            'price'
        ], 0),
        'internet_rate_num' => common_getVariable($stat_data, [
            'internet_data',
            'rate_num'
        ], ''),
        'internet_rate_name' => common_getVariable($stat_data, [
            'internet_data',
            'rate_name'
        ], ''),
        'telephone' => common_getVariable($stat_data, ['telephone'], 0),
        'tv' => common_getVariable($stat_data, ['tv'], 0),
        'tv_price' => common_getVariable($stat_data, [
            'tv_data',
            'price'
        ], 0),
        'tv_rate_num' => common_getVariable($stat_data, [
            'tv_data',
            'rate_num'
        ], ''),
        'tv_rate_name' => common_getVariable($stat_data, [
            'tv_data',
            'rate_name'
        ], ''),
        'mobile' => common_getVariable($stat_data, ['mobile'], 0),
        'mobile_price' => common_getVariable($stat_data, [
            'mobile_data',
            'price'
        ], 0),
        'mobile_rate_num' => common_getVariable($stat_data, [
            'mobile_data',
            'rate_num'
        ], ''),
        'mobile_rate_name' => common_getVariable($stat_data, [
            'mobile_data',
            'rate_name'
        ], ''),
        'serv_v' => common_getVariable($stat_data, ['serv_v'], 0),
        'serv_s' => common_getVariable($stat_data, ['serv_s'], 0),
        'summ' => common_getVariable($stat_data, ['summ'], 0),
        'discount' => common_getVariable($stat_data, ['discount'], 0)
    ];
  }

  /**
   * Определение канала по классификации гугл.
   *
   * @param array $data
   *
   * @return array
   */
  function setGoogleChannel($data = [])
  {
    /**
     * определяем канал.
     * 1) получаем все каналы
     * 2) ведем поиск, пока не найдем первый удовлетворяющий
     */
    $data['channel'] = NULL;
    if (!empty($this->googleChannel->config))
    {
      foreach ($this->googleChannel->config as $channel => $channelName)
      {
        if ($this->googleChannel->checkPhpCondition(
            $this->googleChannel->startCondition('php', $this->googleChannel->channel[$channel]),
            $data
        ))
        {
          $data['channel'] = $channel;
          break;
        }
      }
    }

    return $data;
  }

  /**
   * Определение провайдера и гео.
   *
   * @param array $data
   *
   * @return array
   */
  function getGeoISP($data = [])
  {
    if (!empty($data['ip']))
    {
      $gb = new IPGeoBase();
      $listISP = new ListConditionISP();
      $arData = $gb->getRecord($data['ip']);
      // получение города
      if (!empty($arData['city'])
          && !empty($arData['region'])
          && !empty($arData['district'])
      )
      {
        $arRes = $listISP->_list(['id'], [
            'city' => $arData['city'],
            'region' => $arData['region'],
            'district' => $arData['district']
        ], [], 1);
        if (!empty($arRes[0]['id']))
        {
          $data['geo'] = $arRes[0]['id'];
        }
      }
      // получение провайдера
      if (!empty($arData['isp']))
      {
        $arRes = $listISP->_list(['id'], [
            'ips' => $arData['isp'],
        ], [], 1);
        if (!empty($arRes[0]['id']))
        {
          $data['isp'] = $arRes[0]['id'];
        }
      }
    }

    return $data;
  }

  /**
   * Событие на обработку данных перед вставкой.
   *
   * @param array $data
   *
   * @return array
   */
  function onBeforeSave($data = [])
  {
    /** обработка времени */
    $data = array_merge(
        $data,
        $this->timePrepare($data)
    );

    $data['domain'] = common_setValue($data, 'domain', '');
    $data['link'] = common_setValue($data, 'link', '');
    $data['_c'] = common_setValue($data, '_c', '');
    $data['partner'] = common_setValue($data, 'pin', 1);
    $data['partner'] = common_setValue($data, 'partner', 1);
    $data['seance'] = common_setValue($data, '_mstats', '');
    $data['event_type'] = common_setValue($data, 'event_type', '');
    $data['event_category'] = common_setValue($data, 'event_category', '');
    $data['event_label'] = common_setValue($data, 'event_label', '');
    $data['event_value'] = common_setValue($data, 'event_value', '');
    $data['description'] = common_setValue($data, 'description', '');
    $data['title'] = common_setValue($data, 'title', '');
    $data['keywords'] = common_setValue($data, 'keywords', '');
    $data['uuid'] = common_setValue($data, 'uuid', '');
    $data['device'] = common_setValue($data, 'device', '');
    $data['cookies'] = (int)common_setValue($data, 'cookies', 0);
    $data['javascript'] = (int)common_setValue($data, 'javascript', 0);
    $data['ip'] = common_setValue($data, 'ip', '');
    $data['ad'] = common_setValue($data, 'ad', 0);
    $data['ip_long'] = common_setValue($data, 'ip_long', '');
    $data['id'] = $this->ticket->getId();

    // utm метки
    if (!empty($data['link']))
    {
      $data['utm_campaign'] = StringHelper::getGETFromLink('utm_campaign', $data['link']);
      $data['utm_content'] = StringHelper::getGETFromLink('utm_content', $data['link']);
      $data['utm_term'] = StringHelper::getGETFromLink('utm_term', $data['link']);
      $data['utm_medium'] = StringHelper::getGETFromLink('utm_medium', $data['link']);
      $data['utm_source'] = StringHelper::getGETFromLink('utm_source', $data['link']);
    }
    else
    {
      $data['utm_campaign'] = $data['utm_source'] = $data['utm_term'] = $data['utm_medium'] = $data['utm_content'] = '';
    }

    if (!empty($data['referrer']))
    {
      $linkReferrer = $data['referrer'];
      $data['referer_domain'] = parse_url($linkReferrer, PHP_URL_HOST);
      $data['referer_link'] = trim(parse_url($linkReferrer, PHP_URL_PATH));
    }
    else
    {
      $data['referer_domain'] = '';
      $data['referer_link'] = '';
    }

    // определение канала гугл
    $data = $this->setGoogleChannel($data);

    $data = $this->prepareMGTSQuery($data);

    // geo и ISP
    if (!empty($data['ip']))
    {
      $data = $this->getGeoISP($data);
    }

    $data['isp'] = common_setValue($data,'isp', NULL);
    $data['geo'] = common_setValue($data,'geo', NULL);

    return $data;
  }

  /**
   * Действия, после сохранения.
   *
   * @param array $data
   * @param array $additional
   *
   * @return array
   */
  function onAfterSave($data = [], $additional = [])
  {
    if (!empty($data))
    {
      $queue = new QueueEnterEvents();
      $queue->batchSave($data, true);
      $queue->batchEnd();
    }

    // вставляем данные в очередь обработки событий
    $queueEvents = new QueueEvents();
    $queueEvents->batchSave($data, true);
    $queueEvents->batchEnd();

    // вставляем данные в таблицу по списку пользователей
    $userList = new UserlistOnline();
    $userList->batchSave($data, true);
    $userList->batchEnd();

    return $data;
  }

  /**
   * EventList constructor.
   *
   * @param string $callbackShard
   */
  function __construct($callbackShard = '')
  {
    parent::__construct(
        $this->shard,
        $this->table,
        $callbackShard
    );

    /**
     * гугл каналы
     */
    $this->googleChannel = new GoogleChannels();

    /** ticket */
    $this->ticket = new Ticket64();

    // сервисы
    $this->services = new MainService();
    if (is_callable([
        $this,
        'attributes'
    ]))
    {
      $params = $this->attributes();
      $this->addParam($params);
    }
  }
}