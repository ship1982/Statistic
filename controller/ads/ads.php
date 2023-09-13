<?php

use AdsList\AdsList;
use native\request\Request;

$layout = 'common';

/**
 * Функция авториации.
 */
function ads_auth()
{
  common_inc('auth');
  if (!auth_is())
  {
    header('Location: /');
  }
}

/**
 * Просмотр списка добавленныхх событий.
 *
 * @return bool
 */
function adsStartPage()
{
  ads_auth();

  common_inc('native/url', 'url');
  $return_url = url_getUrl4Index(1);
  $ads = new AdsList();

  $data = $ads->_list();

  return common_setView(
      'ads/list', [
          'items' => $data,
          'header' => $ads->header,
          'url' => $return_url
      ]
  );
}

/**
 * Форма добавления нового банера рекламного.
 *
 * @return bool
 */
function adsAddAd()
{
  ads_auth();
  $error = []; // массив ошибок

  common_inc('native/url', 'url');
  $return_url = url_getUrl4Index(1);

  $ads = new AdsList();

  $arPartner = $ads->getPartners();

  $request = new Request();
  $post = $request->getPost();
  // если форма отправляется

  /**
   * сначала валидируем поля формы и если все хорошо, то загружаем файл
   */
  if (!empty($post))
  {
    // пишем данные из формы в модель
    $ads->saveModelFromRequest();

    // пробуем сохранить модель
    $adsId = $ads->save($ads->options, $ads->getValidationRule4Insert());
    if (is_numeric($adsId))
    {
      // сохраняем картинку
      if (!empty($post['content_type'])
          && $post['content_type'] == 1
          && !empty($_FILES)
          && !empty($_FILES['content'])
      )
      {
        $pic = $ads->saveBanner($adsId);
        $ads->saveScript($adsId, $ads->options['url'], $pic);
        $ads->saveScript4Partner($ads->options['partner'], $adsId);
      }
      // сохраняем html
      elseif (!empty($post['content_type'])
          && 2 == $post['content_type']
      )
      {
        $ads->savePixelHTML(
            $adsId,
            $ads->options['name'],
            $ads->options['url'],
            $ads->options['content']
        );
        $ads->saveScript4Partner($ads->options['partner'], $adsId);
      }

      // после успешного добавления редирект на список
      header("Location: $return_url");
      exit;
    }
    else
    {
      $error = array_merge(
          (array)$error,
          (array)$adsId
      );
    }
  }


  // преобразуем хэдер
  $tableHeader = $ads->header4View();

  return common_setView(
      'ads/item', [
          'header' => $tableHeader,
          'return_url' => $return_url,
          'partners' => $arPartner,
          'error' => $error
      ]
  );
}

/**
 * Форма обновления рекламы.
 *
 * @return bool
 */
function showUpdateForm()
{
  // авторизация
  ads_auth();

  // подключаем API для работы со ссылками
  common_inc('native/url', 'url');
  $id = url_getPartOfURL(2);
  $return_url = url_getUrl4Index(1);
  $error = [];

  // получаем запись
  common_inc('AdsList');
  $ads = new AdsList();
  $ads->_list([], ['id' => $id]);

  // пытаемся сохранить запись
  common_inc('native/request', 'Request');
  $request = new Request();
  $post = $request->getPost();
  if (!empty($post))
  {
    //common_dd($post);
    $updateResult = $ads->edit($post, ['id' => $id], $ads->getValidationRule());

    // сохраняем картинку
    if (empty($updateResult))
    {
      if (!empty($post['content_type'])
          && $post['content_type'] == 1
          && !empty($_FILES)
          && !empty($_FILES['content']['name'])
      )
      {
        $pic = $ads->saveBanner($id);
        $ads->saveScript($id, $ads->options[0]['url'], $pic);
        $ads->saveScript4Partner($ads->options[0]['partner'], $id);
      }
      // сохраняем html
      elseif (!empty($post['content_type'])
          && 2 == $post['content_type']
      )
      {
        $ads->savePixelHTML(
            $id,
            $ads->options[0]['name'],
            $ads->options[0]['url'],
            $ads->options[0]['content']
        );
        $ads->saveScript4Partner($ads->options[0]['partner'], $id);
      }

      // после успешного добавления редирект на список
      header("Location: $return_url");
      exit;
    }
    else
    {
      $error = array_merge(
          (array)$updateResult,
          (array)$error
      );

      // сохраняем request в модель
      $ads->saveModelFromRequest();
    }
  }

  $arPartner = $ads->getPartners();

  // преобразуем хэдер
  $tableHeader = $ads->header4View();

  // сохраняем модель в request
  $ads->sendModel2Request($ads->options[0], $_POST);

  return common_setView(
      'ads/item', [
          'header' => $tableHeader,
          'return_url' => $return_url,
          'partners' => $arPartner,
          'error' => $error
      ]
  );
}

/**
 * Форма удаления
 *
 * При удалении также нужно удалять и рекламный баннер, если он был загружен.
 */
function showDeleteForm()
{
  // авторизация
  ads_auth();

  // подключаем API для работы со ссылками
  common_inc('native/url', 'url');
  $id = url_getPartOfURL(2);
  $return_url = url_getUrl4Index(1);

  // проверяем наличие элемента в БД
  common_inc('AdsList');
  $ads = new AdsList();
  $ads->_list([], ['id' => $id], [], 1);

  // запись есть и ее можно удалять
  if (!empty($ads->options))
  {
    // удалеяем картинку
    $ads->deleteBannerFromDisk();
    $ads->remove(['id' => $id]);
  }

  header("Location: $return_url");
  exit;
}