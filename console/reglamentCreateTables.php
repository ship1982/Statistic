<?php

/**
 * Скрипт по созданию шардированных таблиц на месяц.
 * 
 * Раз в месяц для шардированых таблиц необходимо создавать новую таблицу.
 * Данный класс реализует все методы для создания таких таблиц.
 * Алгоритм работы следующий:
 * 1) получаем конфигурационный файл со списком шардов
 * 2) получаем список всех таблиц
 * 3) выбираем таблицы, которые нужны нам (передаются в конструктор) и timestamp начала месяца. Если не передать время, то будет взят следующий месяц
 * 4) получаем запросы на создание таблиц последних что есть (самые свежие)
 * 5) добавляем новые таблицы в конфиг файл
 * 6) сохраняем конфигурационный файл с таблицами
 */

use Reglament\ReglamentWork;

include_once __DIR__ . '/../lib/autoload.php';

// получаем список шардированых таблиц
$shardingTables = common_getConfig('main/main', 'listShardTable');
if(!empty($shardingTables))
{
  $reglamnet = new ReglamentWork($shardingTables);
  $reglamnet->run();
}