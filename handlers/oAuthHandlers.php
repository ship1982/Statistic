<?php

/**
 * Class oAuthHandlers
 * oAuth авторизация
 */
class oAuthHandlers
{
  /**
   * @var array - данные для oAuth авторизации
   */
  static public $oAuth = [];

  /**
   * Мастер метод
   */
  static function run()
  {
    common_inc('oauth');
    self::$oAuth = init_oauth();
    self::$oAuth->access_api();
  }
}