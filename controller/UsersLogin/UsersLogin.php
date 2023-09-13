<?php

namespace UsersLogin;

use native\request\Request;
use UserLogin\UserLogin;

$layout = 'common';

class UsersLogin
{
  /**
   * Страница, где можно по логину пользоваетля узнать его id
   * А также по id узнать все логины пользователя
   *
   * @return bool
   */
  function showPair()
  {
    $request = new Request();
    $post = $request->getPost();
    $arData = [];
    $fieldName = 'Id пользователя';
    if (!empty($post)
        && !empty($post['activeTab'])
    )
    {
      // удаляем поле, по которому не ищем
      unset($post[$post['activeTab']]);
      $model = new UserLogin();
      $arData = $model->_list([$post['activeTab']], $post);
      $fieldName = (!empty($post['activeTab']) && $post['activeTab'] === 'uuid') ? 'Id пользователя' : 'Логин пользоваетля';
    }

    return common_setView('usersLogin/getPair', [
        'data' => $arData,
        'field' => common_setValue($post, 'activeTab'),
        'name' => $fieldName
    ]);
  }
}