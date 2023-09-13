<?php

// подключаем автозагрузку
include_once __DIR__ . '/../lib/autoload.php';

// загрузка конфигурации
$GLOBALS['conf'] = common_conf();

// опредление контроллера
common_setController();