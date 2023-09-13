<?php

/**
 * Class AuthHandler
 * Клас для проверки авторизации пользователя
 */
class AuthHandler
{
  /**
   * Мастер функция
   */
  static function run()
  {
    common_inc('auth');
    if (!auth_is())
    {
      header('Location: /');
    }
  }
}