<?php

namespace AdsList;

use model\Model;
use services\MainService;

class AdsList extends Model
{
  /**
   * @var string - путь для рекламных скриптов
   */
  public $script = 'http://stat.mgts.zionec.ru/ad/';

  /**
   * @var array - массив полей таблицы с их значениями для одной записи. Здесь храниться модель.
   */
  public $options = [];

  /**
   * @var array - изначальный ключ шардинга
   */
  protected $shard = [1];

  /**
   * @var string - таблица для модели
   */
  public $table = 'ads_list';

  /**
   * Правила валидации для модели.
   *
   * @return array
   */
  public function getValidationRule()
  {
    return [
        'name' => [
            'validation' => [
                'empty' => 'Поле Название не должно быть пустым.',
                'unique' => [
                    'message' => 'Такое название уже существует, придумайте другое.',
                    'target' => $this,
                    'getValue' => $this->options[0]
                ]
            ]
        ],
        'url' => [
            'validation' => [
                'empty' => 'Поле Ссылка не должно быть пустым.',
            ]
        ],
        'partner' => [
            'validation' => [
                'empty' => 'Поле Партнер не должно быть пустым.'
            ]
        ]
    ];
  }

  /**
   * Правила валидации для запроса на вставку данных.
   *
   * @return array
   */
  public function getValidationRule4Insert()
  {
    return [
        'name' => [
            'validation' => [
                'empty' => 'Поле Название не должно быть пустым.',
                'unique' => [
                    'message' => 'Такое название уже существует, придумайте другое.',
                    'target' => $this,
                    'getValue' => []
                ]
            ]
        ],
        'url' => [
            'validation' => [
                'empty' => 'Поле Ссылка не должно быть пустым.',
            ]
        ],
        'partner' => [
            'validation' => [
                'empty' => 'Поле Партнер не должно быть пустым.'
            ]
        ]
    ];
  }

  /**
   * Список дополнительных значений к полям.
   * Например рускоязыное название или какие-то доп параметры.
   *
   * @return array
   */
  public function attributes()
  {
    return [
        'id' => [
            'name' => 'ID',
            'in_form' => 1,
            'in_list' => 1
        ],
        'name' => [
            'name' => 'Название',
            'in_form' => 1,
            'in_list' => 1
        ],
        'url' => [
            'name' => 'Ссылка',
            'in_form' => 1,
            'in_list' => 1
        ],
        'partner' => [
            'name' => 'Партнер',
            'in_form' => 1,
            'in_list' => 1
        ],
        'script' => [
            'name' => 'Код для рекламы',
            'in_form' => 1
        ],
        'content' => [
            'name' => 'Содержимое рекламы',
            'in_form' => 1
        ],
        'content_type' => [
            'name' => 'Тип рекламы',
            'in_form' => 1
        ]
    ];
  }

  /**
   * Получаем список возможных партнеров.
   *
   * @return mixed
   */
  public function getPartners()
  {
    // подключаем API для работы с сервисами
    $service = new MainService();
    // получаем партнеров
    $arPartner['items'] = [];
    $jsonPartner = $service->query(
        'partners', [
        'state' => 1,
        'action' => 'partnersList',
        'method' => 'partnerRun'
    ]);

    if (is_string($jsonPartner))
    {
      $arPartner = json_decode($jsonPartner, JSON_UNESCAPED_UNICODE);
    }

    // формируем список партнеров из массива
    common_inc('native/array', 'array');
    $arPartner = array_mapping(
        $arPartner['items'],
        'id',
        'name'
    );

    return $arPartner;
  }

  /**
   * Собираем массив из заголовков для списка.
   *
   * @return array
   */
  public function header4View()
  {
    // преобразуем хэдер
    $tableHeader = [];
    if (!empty($this->header))
    {
      foreach ($this->header as $key => $param)
      {
        if (!empty($param['in_form']))
        {
          $tableHeader[$key] = [
              'name' => $param['name']
          ];
        }
      }
    }

    return $tableHeader;
  }

  /**
   * Получение рекламного скрипта для пользователя.
   *
   * @return bool|string
   */
  public function getPixelTemplate()
  {
    $file = __DIR__ . '/../../config/ads/adsPixel';
    $pixelContent = '';
    if (is_file($file))
    {
      $pixelContent = file_get_contents($file);
    }

    return $pixelContent;
  }

  /**
   * Замена параметров в рекламном скрипте для пользователей.
   *
   * @param int    $id
   * @param string $url
   * @param string $pic
   *
   * @return bool|mixed|string
   */
  public function replacePixelWarData($id = 0, $url = '', $pic = '')
  {
    $contentPixel = $this->getPixelTemplate();
    if (!empty($contentPixel))
    {
      // заменяем id
      if (!empty($id))
      {
        $contentPixel = str_replace('{{id}}', $id, $contentPixel);
      }
      // заменяем картинку
      if (!empty($pic))
      {
        $contentPixel = str_replace('{{pic}}', $this->script . $pic, $contentPixel);
      }
      // заменяем ссылку
      if (!empty($url))
      {
        $contentPixel = str_replace('{{url}}', $url, $contentPixel);
      }
    }

    return $contentPixel;
  }

  /**
   * Создание рекламного скрипта для пользователя.
   *
   * @param int    $id
   * @param string $url
   * @param string $pic
   */
  public function createScript($id = 0, $url = '', $pic = '')
  {
    if (!empty($id))
    {
      $file = __DIR__ . '/../../web/plugin/' . $id . '.js';
      // подключаем шаблон
      $pixelContent = $this->replacePixelWarData($id, $url, $pic);
      file_put_contents($file, $pixelContent);
    }
  }

  /**
   * Сохранение рекламного скрипта для пользователя.
   *
   * @param int    $id
   * @param string $url
   * @param string $pic
   */
  public function saveScript($id = 0, $url = '', $pic = '')
  {
    $this->createScript($id, $url, $pic);
  }

  /**
   * Сохранение баннера в рекламе.
   *
   * @param int $id - id рекламы
   *
   * @return string
   */
  public function saveBanner($id = 0)
  {
    if (empty($id))
    {
      return '';
    }
    // подключаем библиотеку для работы с файлами
    common_inc('native/upload', 'Upload');
    $upload = new Upload('ad', 'content');
    $uploadResult = $upload->save();
    // если при загрузке нет ошибок, то записываем картинку к объявлению
    if ($upload->isValid()
        && !empty($uploadResult[0]['path'])
    )
    {
      // обновляем запись и вставляем в контент картинку
      $this->edit(['content' => $uploadResult[0]['path']], ['id' => $id], []);
      return $uploadResult[0]['path'];
    }

    return '';
  }

  /**
   * Получение пикселя для пратнера.
   *
   * @return bool|string
   */
  public function getPixel4Partner()
  {
    $file = __DIR__ . '/../../config/ads/script4partner';
    $pixelContent = '';
    if (is_file($file))
    {
      $pixelContent = file_get_contents($file);
    }

    return $pixelContent;
  }

  /**
   * Формирование пикселя для партнера.
   *
   * @param int $partner
   * @param int $adId
   *
   * @return bool|mixed|string
   */
  public function replacePixel4Partner($partner = 0, $adId = 0)
  {
    $contentPixel = $this->getPixel4Partner();
    if (!empty($contentPixel))
    {
      // заменяем id
      $id = 'mstat_' . md5(time() . uniqid());
      if (!empty($id))
      {
        $contentPixel = str_replace('{{scriptId}}', $id, $contentPixel);
      }
      // заменяем партнеру
      if (!empty($partner))
      {
        $contentPixel = str_replace('{{partnerId}}', $partner, $contentPixel);
      }
      // заменяем рекламный id
      if (!empty($adId))
      {
        $contentPixel = str_replace('{{adId}}', $adId, $contentPixel);
      }
    }

    return $contentPixel;
  }

  /**
   * Сохранение скрипта в интерфейсе для пратнера.
   *
   * @param int $partner
   * @param int $adId
   */
  public function saveScript4Partner($partner = 0, $adId = 0)
  {
    $script = $this->replacePixel4Partner($partner, $adId);
    $this->edit(['script' => $script], ['id' => $adId], []);
  }

  /**
   * Удаление баннера из файлов.
   */
  public function deleteBannerFromDisk()
  {
    // пытаемся найти картинку банера, если она есть и удалить ее
    if (1 == $this->options['content_type']
        && !empty($this->options['content'])
    )
    {
      @unlink(__DIR__ . '/../../web/ad/' . $this->options['content']);
    }
  }

  /**
   * Получение кода рекламного объявления в HTML.
   *
   * @return bool|string
   */
  public function getHTMLTemplate()
  {
    $file = __DIR__ . '/../../config/ads/HTMLText';
    $pixelContent = '';
    if (is_file($file))
    {
      $pixelContent = file_get_contents($file);
    }

    return $pixelContent;
  }

  /**
   * Замена кода рекламного объявления на переменные.
   *
   * @param string $name
   * @param string $url
   * @param string $content
   *
   * @return array
   */
  public function replaceVarInHTMLTemplate($name = '', $url = '', $content = '')
  {
    $pixelContent = $this->getHTMLTemplate();
    $id = 'html_' . md5(time() . uniqid('mstat'));
    // заменяем id
    if (!empty($id))
    {
      $pixelContent = str_replace('{{id}}', $id, $pixelContent);
    }
    // заменяем название
    if (!empty($name))
    {
      $pixelContent = str_replace('{{name}}', $name, $pixelContent);
    }
    // заменяем url
    if (!empty($url))
    {
      $pixelContent = str_replace('{{url}}', $url, $pixelContent);
    }
    // заменяем контент
    if (!empty($content))
    {
      $pixelContent = str_replace('{{content}}', $content, $pixelContent);
    }

    return [
        'id' => $id,
        'pixel' => $pixelContent
    ];
  }

  /**
   * Получение шаблона кода партнера для рекламного объявления.
   *
   * @return bool|string
   */
  public function getHTMLPixel()
  {
    $file = __DIR__ . '/../../config/ads/adsPixelHTML';
    $pixelContent = '';
    if (is_file($file))
    {
      $pixelContent = file_get_contents($file);
    }

    return $pixelContent;
  }

  /**
   * Замена кода партнера в рекламном объявлении HTML.
   *
   * @param int    $id
   * @param string $name
   * @param string $url
   * @param string $content
   *
   * @return bool|mixed|string
   */
  public function replaceHTMLWarPixel($id = 0, $name = '', $url = '', $content = '')
  {
    $arContentPixel = $this->replaceVarInHTMLTemplate($name, $url, $content);
    $warPixel = $this->getHTMLPixel();
    if (!empty($id))
    {
      $warPixel = str_replace('{{id}}', $id, $warPixel);
    }
    if (!empty($arContentPixel['id']))
    {
      $warPixel = str_replace('{{bannerId}}', $arContentPixel['id'], $warPixel);
    }
    if (!empty($arContentPixel['pixel']))
    {
      $warPixel = str_replace('{{html}}', $arContentPixel['pixel'], $warPixel);
    }
    if (!empty($url))
    {
      $warPixel = str_replace('{{url}}', $url, $warPixel);
    }

    return $warPixel;
  }

  /**
   * Сохранение кода партнера рекламного объявления HTML.
   *
   * @param int    $id
   * @param string $name
   * @param string $url
   * @param string $content
   */
  public function savePixelHTML($id = 0, $name = '', $url = '', $content = '')
  {
    if (!empty($id))
    {
      $file = __DIR__ . '/../../web/plugin/' . $id . '.js';
      // подключаем шаблон
      $content = $this->replaceHTMLWarPixel($id, $name, $url, $content);
      file_put_contents($file, $content);
    }
  }

  /**
   * AdsList constructor.
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

    if (is_callable([
        $this,
        'attributes'
    ]))
    {
      $params = $this->attributes();
      $this->addParam($params);
    }

    // загрузка серверно зависимых переменных
    $this->script = common_getServerVar('ad_path');
  }
}