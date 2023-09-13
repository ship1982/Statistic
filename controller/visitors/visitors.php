<?php

use services\MainService;

$layout = 'statistic';

/**
 * Возвращает список параметров для фильтрации.
 *
 * @return array
 */
function visitors_addFilterVariables()
{
  $result = [];
  // подключаем класс для работы с данными формы
  common_inc('html/form', 'Form');

  $form = new Form();
  $form->setMethod('POST');

  // получаем каналы
  $result['filter_channels'] = [];
  $utm_medium = $form->getValue('filter_channels');
  if (!empty($utm_medium[0]))
  {
    $result['filter_channels'] = $utm_medium;
  }

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
  // домен
  $result['domain_text'] = $form->getValue('domain_text');
  // ссылка
  $result['link_text'] = $form->getValue('link_text');

  return $result;
}

/**
 * Метод для получения полей группировки для запроса данных от сервиса.
 *
 * @return array
 */
function visitors_addGroupParams()
{
  $result = [];
  // подключаем класс для работы с данными формы
  common_inc('html/form', 'Form');

  $form = new Form();
  $form->setMethod('POST');

  // получаем группировку
  $utm_medium = $form->getValue('group');
  if (!empty($utm_medium[0]))
  {
    $result = $utm_medium;
  }

  return $result;
}

/**
 * Получаем список количество записей на странице.
 *
 * @return string
 */
function visitors_addLimitsParams()
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
 * Контроллер главной страницы статистики посещений.
 *
 * @return bool
 */
function showVisitorsList()
{
  $result = [];
  $service = new MainService();

  // получаем список каналов
  $channels = [];
  if (file_exists(__DIR__ . '/../../config/visitors/channel.php'))
  {
    $channels = require __DIR__ . '/../../config/visitors/channel.php';
  }

  // получаем список событий для группировки
  $grouping = [];
  if (file_exists(__DIR__ . '/../../config/visitors/grouping.php'))
  {
    $grouping = require __DIR__ . '/../../config/visitors/grouping.php';
  }

  // получаем список событий для группировки
  $countOfPage = [];
  if (file_exists(__DIR__ . '/../../config/visitors/limits.php'))
  {
    $countOfPage = require __DIR__ . '/../../config/visitors/limits.php';
  }

  // получаем события в соответсвии с фильтром
  $filters = visitors_addFilterVariables();

  // получаем параметры для группировки
  $groupParams = visitors_addGroupParams();

  if (empty($filters['to']) || empty($filters['from']))
  {
    if (!empty($_POST['submit-form']))
    {
      $error = ['Некорректно указан диапазон дат'];
    }
    else
    {
      $error = "";
    }

    return common_setView(
        'visitors/startPage', [
            'error' => $error,
            '_channels' => $channels,
            'grouping' => $grouping,
            'count' => $countOfPage
        ]
    );
  }

  // получаем данные для limit части запроса
  $limits = visitors_addLimitsParams();

  $filter = array_merge([
      'filter' => $filters,
      'group' => $groupParams,
      'limits' => $limits
  ], [
          'action' => 'visitors_visitorsList',
          'method' => 'visitorsRun'
      ]
  );

  // запрос сервису событий
  $answer = $service->query(
      'visitors',
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
        'visitors/startPageCount',
        array_merge([
            '_channels' => $channels,
            'grouping' => $grouping,
            'count' => $countOfPage
        ], $result
        )
    );
  }
  else
  {
    return common_setView(
        'visitors/startPage',
        array_merge([
            '_channels' => $channels,
            'grouping' => $grouping,
            'count' => $countOfPage
        ], $result
        )
    );
  }
}