<?php

// подключаем основное API
include_once __DIR__ . '/../lib/autoload.php';
// подключаем основной конфиг
$GLOBALS['conf'] = common_conf();

$log = "start:".time().";";
$s = microtime(true);
common_inc('parser');

// основная работа скрипта
$data = parser_init();

$log .= "end:"  . number_format(microtime(true) - $s, 4) . "ms;";
$log .= "memory:" . number_format(memory_get_peak_usage() / 1048576, 2) . "mb;";
file_put_contents(__DIR__ . '/../../syslog/parser.log', $log . "\n", FILE_APPEND);