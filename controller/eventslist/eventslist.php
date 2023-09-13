<?php

/**
 * @var string $layout - шаблон для контроллера.
 */
$layout = 'statistic';

/**
 * Проверяем авторизацию пользователя.
 */
function MainAuthUser()
{
  common_inc('auth');
  if (!auth_is())
  {
    header('Location: /');
  }
}

/**
 * Преобразования списка партнеров к виду, где ключом массива будет id партнера,
 * а его значением - название партнера.
 *
 * @param array $partners - список партнеров (массив)
 *
 * @return array
 */
function eventlist_partnerMap($partners = [])
{
  $partnerList = [];
  if (empty($partners))
  {
    return $partnerList;
  }
  if (is_array($partners))
  {
    foreach ($partners as $key => $partner)
      $partnerList[$partner['id']] = $partner['name'];
  }

  return $partnerList;
}

/**
 * Возвращает список параметров для фильтрации.
 *
 * @return array
 */
function eventlist_addFilterVariables()
{
  $result = [];
  // подключаем класс для работы с данными формы
  common_inc('html/form', 'Form');

  $form = new Form();
  $form->setMethod('POST');

  // получаем партнера
  $result['partner'] = $form->getValue('partner');

  // получаем каналы
  $result['filter_channels'] = [];
  $utm_medium = $form->getValue('filter_channels');
  if (!empty($utm_medium[0]))
  {
    $result['filter_channels'] = $utm_medium;
  }

  // meta
  $result['title'] = $form->getValue('title');
  $result['keywords'] = $form->getValue('keywords');
  $result['description'] = $form->getValue('description');

  // event_type
  $result['event_type'] = $form->getValue('event_type');
  // event_category
  $result['event_category'] = $form->getValue('event_category');
  // event_label
  $result['event_label'] = $form->getValue('event_lable');
  // event_value
  $result['event_value'] = $form->getValue('event_value');
  // from
  $result['from'] = $form->getValue('from');
  // to
  $result['to'] = $form->getValue('to');
  // utm_campaign
  $result['utm_campaign'] = $form->getValue('utm_campaign');
  // utm_content
  $result['utm_content'] = $form->getValue('utm_content');
  // utm_term
  $result['utm_term'] = $form->getValue('utm_term');
  // utm_source
  $result['utm_source'] = $form->getValue('utm_source');
  // utm_medium
  $result['utm_medium'] = $form->getValue('utm_medium');
  // ad
  $ad = $form->getValue('ad');
  switch ($ad)
  {
    case 0:
      $result['ad'] = -1;
      break;
    case 1:
      $result['ad'] = 0;
      break;
    case 2:
      $result['ad'] = 1;
      break;
  }
  // is_bot
  $is_bot = $form->getValue('is_bot');
  switch ($is_bot)
  {
    case 0:
      $result['is_bot'] = -1;
      break;
    case 1:
      $result['is_bot'] = 0;
      break;
    case 2:
      $result['is_bot'] = 1;
      break;
  }

  // домен
  $result['domain'] = $form->getValue('domain');

  // ссылка
  $result['link'] = $form->getValue('link');

  return $result;
}

/**
 * Метод для получения полей группировки для запроса данных от сервиса.
 *
 * @return array
 */
function eventlist_addGroupParams()
{
  $group = [];
  // подключаем класс для работы с данными формы
  common_inc('html/form', 'Form');

  $form = new Form();
  $form->setMethod('POST');

  // получаем группировку
  $groupArray = $form->getValue('group');
  if (!empty($groupArray))
  {
    $group = $groupArray;
  }

  // получаем группировку для пересечений
  $group['domaingroup'] = $form->getValue('domaingroup');

  return $group;
}

/**
 * Получаем список количество записей на странице.
 *
 * @return array
 */
function eventlist_addLimitsParams()
{
  // подключаем класс для работы с данными формы
  common_inc('html/form', 'Form');

  $form = new Form();
  $form->setMethod('POST');

  // получаем limit
  $result = $form->getValue('count');

  return $result;
}

/**
 * Получаем список групп из БД.
 *
 * @return array
 */
function eventlist_getGroup4Filter()
{
  $result = [];
  common_inc('groupFilter');
  $rsGroup = gf_get();
  while ($arGroup = mysqli_fetch_assoc($rsGroup))
    $result[$arGroup['id']] = $arGroup['name'];

  return $result;
}

/**
 * Контроллер главной страницы событий.
 */
function showEventsList()
{
  $service = new \services\MainService();
  $result = [];

  // получаем список партнеров
  $partnerAnswer = $service->query(
      'partners', [
      'state' => 1,
      'action' => 'partnersList',
      'method' => 'partnerRun'
  ]);

  $arPartner = [];
  if (is_string($partnerAnswer))
  {
    $arPartner = json_decode($partnerAnswer, JSON_UNESCAPED_UNICODE);
  }

  // преобразуем партнеров в вид, необходимый для вывода
  if (!empty($arPartner))
  {
    $arPartner = eventlist_partnerMap($arPartner['items']);
  }

  // получаем список каналов
  $channels = [];
  if (file_exists(__DIR__ . '/../../config/eventlist/channel.php'))
  {
    $channels = require __DIR__ . '/../../config/eventlist/channel.php';
  }

  // получаем список событий для группировки
  $grouping = [];
  if (file_exists(__DIR__ . '/../../config/eventlist/grouping.php'))
  {
    $grouping = require __DIR__ . '/../../config/eventlist/grouping.php';
  }

  // получаем список событий для группировки
  $countOfPage = [];
  if (file_exists(__DIR__ . '/../../config/eventlist/limits.php'))
  {
    $countOfPage = require __DIR__ . '/../../config/eventlist/limits.php';
  }

  // получаем список групп
  $groupFromDB = eventlist_getGroup4Filter();

  // получаем события в соответсвии с фильтром
  $filters = eventlist_addFilterVariables();

  // получаем параметры для группировки
  $groupParams = eventlist_addGroupParams();
  // если есть domaingroup то работаем с пересечениями
  if (!empty($groupParams['domaingroup']))
  {
    // запрос сервису событий
    $answer = $service->query(
        'eventlist', [
            'groupdomain' => $groupParams['domaingroup'],
            'from' => common_setValue($filters, 'from'),
            'to' => common_setValue($filters, 'to'),
            'action' => 'eventlist_eventlistCross',
            'method' => 'eventlistRun'
        ]
    );
  }
  else
    // иначе сервис не правильно строит запрос
  {
    unset($groupParams['domaingroup']);
  }

  // выводим шаблон для пересечений
  if (!empty($answer))
  {
    if (!empty($answer))
    {
      $result = json_decode($answer, true);
    }

    return common_setView(
        'eventlist/startPageCross',
        array_merge([
            'partners' => $arPartner,
            '_channels' => $channels,
            'grouping' => $grouping,
            'groupFromDB' => $groupFromDB,
            'count' => $countOfPage
        ], $result
        )
    );
  }

  // получаем данные для limit части запроса
  $limits = eventlist_addLimitsParams();

  $group = $groupParams;
  if (!empty($_POST['md5login']))
  {
    array_unshift($group, 'md5login');
  }

  $filter = array_merge([
      'filter' => $filters,
      'group' => $group,
      'limits' => $limits
  ], [
          'action' => 'eventlist_eventlistList',
          'method' => 'eventlistRun'
      ]
  );

  // запрос сервису событий
  $answer = $service->query(
      'eventlist',
      $filter
  );

  if (is_string($answer))
  {
    $result = json_decode($answer, JSON_UNESCAPED_UNICODE);
  }

  // если выбран параметр группировки, то нужно выводить шаблон с учетом количества
  if (!empty($filter['group']))
  {
    return common_setView(
        'eventlist/startPageCount',
        array_merge([
            'partners' => $arPartner,
            '_channels' => $channels,
            'grouping' => $grouping,
            'groupFromDB' => $groupFromDB,
            'count' => $countOfPage
        ], $result
        )
    );
  }
  else
  {
    return common_setView(
        'eventlist/startPage',
        array_merge([
            'partners' => $arPartner,
            '_channels' => $channels,
            'grouping' => $grouping,
            'groupFromDB' => $groupFromDB,
            'count' => $countOfPage
        ], $result
        )
    );
  }
}