<?php

use model\Model;
use services\MainService;

$layout = 'statistic';

/**
 * Возвращает список параметров для фильтрации.
 *
 * @return array
 */
function topReferers_addFilterVariables()
{
  $result = [];

  // подключаем класс для работы с данными формы
  common_inc('html/form', 'Form');

  $form = new Form();
  $form->setMethod('POST');

  // время От
  $result['from'] = $form->getValue('from');

  // время До
  $result['to'] = $form->getValue('to');

  // тип отчета
  $report_type = $form->getValue('report_type');
  $result['report_type'] = !empty($report_type) ? (int) $report_type : 1;

  // партнерские домены
  $result['partner_domains'] = $form->getValue('partner_domains');

  //отчет по пересечению доменов
  $result['is_cross'] = (int) $form->getValue('is_cross');

  //число строк при пагинации
  $result['count'] = (int) $form->getValue('count');

  //статус отправки формы
  $result['run'] = (int) $form->getValue('run');

  //ошибки
  $result['error'] = [];
  if (!empty($result['run'])){
    if (empty($result['to']) || empty($result['from'])){
      $result['error'][] = 'Некорректно указан диапазон дат';
    }
    if(empty($result['partner_domains'][0]))
      $result['error'][] = 'Не выбраны партнерские домены';

    if(count($result['partner_domains']) == 1
          && !empty($result['is_cross']))
    {
      $result['error'][] = 'Для расчета рейтинга по пересечениям нужно выбрать хотя бы 2 партнерских домена';
    }
  }

  return $result;
}

/**
 * Контроллер главной страницы TOP Referer
 *
 * @return void
 */
function showTopReferers()
{
  $service = new MainService();

  // подключаем API Model
  $tableDomain = new Model([1], 'domain');
  $data = $tableDomain->_list(['id', 'name'], ['show' => 1], [], '');
  $partnerDomains = [];
  if(is_array($data) && $dataLength = count($data))
  {
    for ($i = 0; $i < $dataLength; $i++)
     {
       $filterDomainName = str_replace('www.', '', $data[$i]['name']);
       $partnerDomains[$filterDomainName] = $data[$i]['name'];
     }
  }

  //тип отчета
  $reportTypes = [];
  if(file_exists(__DIR__ . '/../../config/top_referers/type.php'))
    $reportTypes = require __DIR__ . '/../../config/top_referers/type.php';

  //кол-во страниц для пагинации
  $countOfPage = [];
  if(file_exists(__DIR__ . '/../../config/top_referers/limits.php'))
    $countOfPage = require __DIR__ . '/../../config/top_referers/limits.php';

  // получаем условия отображения отчета в соответсвии с фильтром
  $filter = topReferers_addFilterVariables();

  $selectedPartnerDomains = [];

  $result = [];

  if ($filter['run'] && empty($filter['error']))
  {
    $action = !$filter['is_cross'] ? 'topReferers_count' : 'topReferers_cross';
    // запрос сервису
    $result = $service->query(
        'top_referers',[
            'action' => $action,
            'method' => 'topReferersRun',
            'filter' => $filter
        ]
    );
  }

  common_setView(
      'top_referers/startPage',[
        'error' => $filter['error'],
        'reportTypes' => $reportTypes,
        'partnerDomains' => $partnerDomains,
        'selectedPartnerDomains' => $selectedPartnerDomains,
        'countOfPage' => $countOfPage,
        'result' => $result,
        'filter' => $filter
      ]
  );
}