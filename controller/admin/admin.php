<?php

use native\request\Request;

$layout = 'common';

/**
 * Страница со списком таблиц и их размерностью.
 *
 * @return bool
 */
function showTablesInfo()
{
  $items = [];
  $request = new Request();
  $post = $request->getPost();

  common_inc('TableSize');
  $tableSize = new TableSize();

  // получаем фильтр
  if (!empty($post))
  {
    $from = date('Ymd', strtotime($post['from']));
    $to = date('Ymd', strtotime($post['to']));
    $items = $tableSize->getAll($from, $to);
  }

  return common_setView('admin/tables', [
      'items' => $items,
      'header' => $tableSize->header
  ]);
}