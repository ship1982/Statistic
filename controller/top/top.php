<?php

/**
 * @var $layout - layout for a controller
 */

use services\MainService;

$layout = 'top_detalizer';

/**
 * @constructor
 */
function TopController()
{
  $validate = TopValidateAndExecute();

  if ($validate)
  {
    return common_setView('top/show', [
        'error' => common_setValue($validate, 'error'),
        'result' => common_setValue($validate, 'result'),
        'filter' => common_setValue($validate, 'filter'),
    ]);
  }
}

/**
 * Validate form for a single domain.
 *
 * @param $validate - @see TopValidateAndExecute
 *
 * @return array
 */
function TopValidateOne(&$validate)
{
  if (empty($_POST['domain']))
  {
    $validate['error'][] = 'Укажите домен!';
  }

  return $validate;
}

/**
 * Validate form for a group domain.
 *
 * @param $validate - @see TopValidateAndExecute
 *
 * @return array
 */
function TopValidateMultiple(&$validate)
{
  if (empty($_POST['group']))
  {
    $validate['error'][] = 'Выберите группу!';
  }

  return $validate;
}

/**
 * Do a common validation rule.
 *
 * @param $validate - @see TopValidateAndExecute
 *
 * @return array
 */
function TopCommonValidate(&$validate)
{
  /** date */
  if (empty($_POST['from']) || empty($_POST['to']))
  {
    $validate['error'][] = 'Укажите диапазон дат!';
  }

  return $validate;
}

/**
 * Do main validation.
 *
 * @param $validate - @see TopValidateAndExecute
 *
 * @return array
 */
function TopMainValidate(&$validate)
{
  TopCommonValidate($validate);

  /** domain */
  switch ($_POST['tab_opened'])
  {
    /** for single domain */
    case 'one':
      TopValidateOne($validate);
      break;

    /** for group of domain */
    case 'multiple':
      TopValidateMultiple($validate);
      break;
  }

  return $validate;
}

/**
 * Get filter type for a @see top_get.
 *
 * @param $filter - type of filter.
 *                1 - is for single domain
 *                2 - is for group domain
 *
 * @return string
 */
function TopSwitchTop($filter = 1)
{
  $type = 'link';
  if ($filter == 1)
  {
    if (!empty($_POST['top_detail']))
    {
      switch ($_POST['top_detail'])
      {
        case 'top1':
          $type = 'link';
          break;
        case 'top2':
          $type = 'region';
          break;
        case 'top3':
          $type = 'provider';
          break;
        default:
          $type = 'link';
          break;
      }
    }
  }
  else if ($filter == 2)
  {
    if (!empty($_POST['top_detail']))
    {
      switch ($_POST['top_detail'])
      {
        case 'top1':
          $type = 'cross';
          break;
        case 'top2':
          $type = 'crossCity';
          break;
        case 'top3':
          $type = 'crossProvider';
          break;
        default:
          $type = 'cross';
          break;
      }
    }
    else
    {
      $type = 'cross';
    }
  }

  return $type;
}

/**
 * Get value for a single domain without diff.
 *
 * @return string
 */
function TopGetValueNotDiffOne()
{
  return common_setValue($_POST, 'domain');
}

/**
 * Get value for a single domain with diff.
 *
 * @return array
 */
function TopGetValueDiffOne()
{
  common_inc('_fetcher');
  return fetcher_compareByDomain($_POST['domain'], [
      'from' => strtotime($_POST['from']),
      'to' => strtotime($_POST['to']),
  ], 1, true);
}

/**
 * Get value for a group domain without diff.
 *
 * @param int $filter
 *
 * @return array
 */
function TopGetValueNotDiffMultiple($filter = 1)
{
  common_inc('groupFilter');
  $rsGroup = gf_get([], [
      'id' => $_POST['group']
  ]);
  $arDomain = [];
  while ($__arGroup = mysqli_fetch_assoc($rsGroup))
  {
    $listDomain = json_decode($__arGroup['value']);
    if (!empty($listDomain))
    {
      for ($i = 0; $i < $ic = count($listDomain); $i++)
      {
        if ($filter == 1)
        {
          $arDomain[$listDomain[$i]] = $listDomain[$i];
        }
        else if ($filter == 2)
        {
          $arDomain[$listDomain[$i]] = $__arGroup['name'];
        }
      }
    }
  }

  return $arDomain;
}

/**
 * Get value for a group domain with diff.
 *
 * @return array
 */
function TopGetValueDiffMultiple()
{
  /** get domains into the group */
  $arGroup = TopGetValueNotDiffMultiple(2);
  /** get uuids */
  common_inc('_fetcher');
  return fetcher_compareGroupDomain($arGroup, [
      'from' => strtotime($_POST['from']),
      'to' => strtotime($_POST['to']),
  ], 1, true);
}

/**
 * Main execution and validate function
 */
function TopValidateAndExecute()
{
  $validate = [
      'error' => [],
      'result' => []
  ];

  $field = 'domain';
  $inDomains = [];
  $valueResult = [];
  $type = 'link';

  if (empty($_POST['from']) || empty($_POST['to']))
  {
    $from = $to = '';
  }
  /** if form is submit */
  if (!empty($_POST))
  {
    TopMainValidate($validate);

    /** if error, then stop execution and return error */
    if (!empty($validate['error']))
    {
      return $validate;
    }

    /** if not choose diff for domain or group */
    if (empty($_POST['diff']))
    {
      $field = 'domain';
      /** filter type by top */
      $type = TopSwitchTop(1);

      /** choose tab (single domain or group) */
      switch ($_POST['tab_opened'])
      {
        /** for single domain */
        case 'one':
          $valueResult = TopGetValueNotDiffOne();
          break;

        /** for group of domain */
        case 'multiple':
          $valueResult = TopGetValueNotDiffMultiple();
          break;
      }

      /** top_detalizer validate */
      $datelizerFilter = TopGetTopDetalizerFilter();
      if (!empty($datelizerFilter))
      {
        $filter = prepareData4TopDetalizer($datelizerFilter, $valueResult);

        $service = new MainService();
        $answer = $service->query('top_detalizer', array_merge($filter, ['type' => $type]));

        if ($answer)
        {
          $arAnswer = json_decode($answer, true);
          $filterType = TopSwitchTop(1);
          return common_setView('top/show_topDetalizer', [
              'result' => $arAnswer,
              'filter' => $filterType
          ]);
        }
      }
    }
    else
    {
      /** if choose diff for domain or group */
      $field = 'uuid';
      $type = TopSwitchTop(2);

      /** choose tab (single domain or group) */
      switch ($_POST['tab_opened'])
      {
        /** for a single domains */
        case 'one':
          $inDomains = TopGetValueNotDiffOne();
          $valueResult = TopGetValueDiffOne();
          if (!empty($valueResult))
          {
            $valueResult = end($valueResult);
          }
          break;

        /** for a doamin groups */
        case 'multiple':
          $inDomains = TopGetValueNotDiffMultiple();
          $valueResult = TopGetValueDiffMultiple();
          break;
      }
    }
  }

  common_inc('filter/top', 'top');

  /** if filter is active */
  if (!empty($_POST))
  {
    if (!empty($inDomains))
    {
      $inCondition = [
          $field => $valueResult,
          'domain' => $inDomains
      ];
    }
    else
    {
      $inCondition = [
          $field => $valueResult
      ];
    }

    $validate['result'] = top_get($type, [
        'from' => strtotime($_POST['from']),
        'to' => strtotime($_POST['to']),
        'IN' => $inCondition
    ]);
  }
  /** if filter is not active */
  else
  {
    $validate = [
        'result' => [],
        'error' => []
    ];
  }

  $validate['filter'] = $type;

  return $validate;
}

/**
 * Collect data for top_detalizer service.
 */
function TopGetTopDetalizerFilter()
{
  $week = [
      1,
      2,
      3,
      4,
      5,
      6,
      7
  ];
  $result['calendar'] = [];
  $isActive = false;
  if (!empty($_POST))
  {
    for ($i = 1; $i <= $iw = count($week); $i++)
    {
      if ($_POST['day_' . $i . '_s'] != 'all'
          || $_POST['day_' . $i . '_e'] != 'all'
      )
      {
        $isActive = true;
      }

      if (!empty($_POST['day_' . $i . '_s'])
          && $_POST['day_' . $i . '_s'] != 'all'
      )
      {
        $result['calendar'][(string)($i - 1)] = $_POST['day_' . $i . '_s'];
      }
      if (!empty($_POST['day_' . $i . '_e'])
          && $_POST['day_' . $i . '_e'] != 'all'
      )
      {
        $result['calendar'][(string)($i - 1)] .= '-' . $_POST['day_' . $i . '_e'];
      }
      if (empty($result['calendar'][(string)($i - 1)]))
      {
        $result['calendar'][(string)($i - 1)] = 'all';
      }
    }
  }

  if ($isActive)
  {
    return $result;
  }
  else
  {
    return [];
  }
}

function prepareData4TopDetalizer($calendar = [], $valueResult = [])
{
  $arResult = [];
  $domain = '';

  if (!empty($valueResult))
  {
    foreach ($valueResult as $key => $value)
      $domain .= $value . ',';

    $domain = substr($domain, 0, -1);
  }

  if (!empty($calendar))
  {
    $cal = json_encode($calendar['calendar']);
  }

  if (!empty($domain) && !empty($cal))
  {
    $arResult = [
        'method' => 'execute',
        'domain' => $domain,
        'to' => strtotime(common_setValue($_POST, 'to')),
        'from' => strtotime(common_setValue($_POST, 'from')),
        'calendar' => $cal
    ];

    $arrayFilterAdditional = prepareData4TopDetalizerOne();
    if (!empty($arrayFilterAdditional))
    {
      $arResult = array_merge($arResult, $arrayFilterAdditional);
    }
  }


  return $arResult;
}

function prepareData4TopDetalizerOne()
{
  $table = 'top_detalizer';
  $field = '';
  if (!empty($_POST['top_detail']))
  {
    switch ($_POST['top_detail'])
    {
      case 'top1':
        $table = 'top_detalizer';
        $field = '';
        break;

      case 'top2':
        $table = 'top_detalizer_city';
        $field = ' ,`city` ';
        break;

      case 'top3':
        $table = 'top_detalizer_provider';
        $field = ' ,`provider` ';
        break;

      default:
        $table = 'top_detalizer';
        $field = '';
        break;
    }
  }

  return [
      'table' => $table,
      'field' => $field
  ];
}