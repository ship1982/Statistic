<?php

use services\MainService;

$layout = 'statistic';

/**
 * Получение id партнера из url.
 *
 * @return int
 */
function partners_getPartnerId()
{
  // получаем id партнера
  $partnerId = '';
  $arUrl = explode('/', strtok($_SERVER['REQUEST_URI'], '?'));
  if (!empty($arUrl))
  {
    $partnerId = end($arUrl);
  }

  return $partnerId;
}

/**
 * Выводит список партнеров.
 *
 * @return bool
 */
function partners_showList()
{
  $result = [];
  $service = new MainService();
  $answer = $service->query(
      'partners', [
      'state' => 1,
      'action' => 'partnersList',
      'method' => 'partnerRun'
  ]);

  if (is_string($answer))
  {
    $result = json_decode($answer, JSON_UNESCAPED_UNICODE);
  }

  return common_setView(
      'partners/startPage',
      $result
  );
}

/**
 * Получения списка полей c old_ префиксом. (уникальные поля).
 *
 * @return array
 */
function partners_getOldFiled()
{
  $data = [];
  if (empty($_POST))
  {
    return $data;
  }
  foreach ($_POST as $key => $value)
  {
    if (strpos($key, 'old_') !== false)
    {
      $data[$key] = $value;
    }
  }

  return $data;
}

/**
 * Добавление нвого партнера.
 *
 * @return bool
 */
function showAddPartnersForm()
{
  $result = [];
  if (!empty($_POST))
  {
    $service = new MainService();
    $answer = $service->query('partners', [
        'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
        'domains' => filter_input(INPUT_POST, 'domains', FILTER_SANITIZE_STRING),
        'pixel' => filter_input(INPUT_POST, 'pixel', FILTER_SANITIZE_STRING),
        'old' => partners_getOldFiled(),
        'action' => 'partnerAdd',
        'method' => 'partnerRun'
    ]);

    if (is_string($answer))
    {
      $resultAdd = json_decode($answer, JSON_UNESCAPED_UNICODE);
    }

    // при успешном добавлении редирект на страницу обнволения
    if (!empty($resultAdd['success'])
        && is_numeric($resultAdd['success'])
    )
    {
      header("Location: /partners/update/$resultAdd[success]");
      exit;
    }
  }

  return common_setView(
      'partners/element',
      $result
  );
}

/**
 * Обновление существующего партнера.
 *
 * @return bool
 */
function showUpdatePartnersForm()
{
  $result = $resultUpdate = [];
  $service = new MainService();

  // получаем id партнера
  $partnerId = partners_getPartnerId();

  if (!empty($_POST))
  {
    $answer = $service->query('partners', [
        'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
        'domains' => filter_input(INPUT_POST, 'domains', FILTER_SANITIZE_STRING),
        'pixel' => filter_input(INPUT_POST, 'pixel', FILTER_SANITIZE_STRING),
        'old' => partners_getOldFiled(),
        'id' => $partnerId,
        'action' => 'partnerAdd',
        'method' => 'partnerRun',
        'update' => true
    ]);

    if (is_string($answer))
    {
      $resultUpdate = json_decode($answer, JSON_UNESCAPED_UNICODE);
    }
  }

  // проверяем наличие записи в БД
  $answer = $service->query(
      'partners', [
      'id' => $partnerId,
      'state' => 1,
      'action' => 'partnersList',
      'method' => 'partnerRun'
  ]);

  if (is_string($answer))
  {
    $result = json_decode($answer, JSON_UNESCAPED_UNICODE);
  }

  // если нет такой записи
  if (empty($result['items']))
  {
    header('Location: /partners/');
    exit;
  }

  if (empty($resultUpdate['error']))
  {
    $_POST = (!empty($result['items'][0]) ? $result['items'][0] : $result['list']);
  }

  return common_setView(
      'partners/element',
      array_merge(
          $result,
          ['update' => 'Y'],
          $resultUpdate
      )
  );
}

/**
 * Удаление партнера.
 * Не используется физическое удаление партнера, а просто меняется статус на 0 (state)
 */
function showDeletePartnersForm()
{
  $result = [];
  $partnerId = partners_getPartnerId();
  if (!empty($partnerId)
      && is_numeric($partnerId)
  )
  {
    $service = new MainService();
    $answer = $service->query('partners', [
        'id' => $partnerId,
        'action' => 'partnerDelete',
        'method' => 'partnerRun'
    ]);

    if (is_string($answer))
    {
      $result = json_decode($answer, JSON_UNESCAPED_UNICODE);
    }
  }

  if ($result)
  {
    header('Location: /partners/');
    exit;
  }
}