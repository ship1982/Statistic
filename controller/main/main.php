<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 11:17
 */

$layout = 'statistic';

/**
 * Auth function.
 * Check if user is auth - continue, else go to main page.
 */
function MainAuthUser()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');
}

/**
 * Common function.
 * Must do in each controller.
 *
 * @return array with keys
 * [
 *      'strError' => [],
 *      'where' => [],
 *      'in' => [],
 *      'type' => (!empty($_POST['sum']) ? 'sum' : 'count'),
 *      'arResult' => []
 * ];
 */
function MainSupportFunction()
{
    $dataArray = [
        'strError' => [],
        'where' => [],
        'in' => [],
        'type' => (!empty($_POST['sum']) ? 'sum' : 'count'),
        'arResult' => []
    ];
    /** common logic */
    if(!empty($_POST['form']))
    {
        /** time */
        if($_POST['form'] != 'form-3' && $_POST['form'] != 'form-4')
        {
            if(!empty($_POST['from']) && !empty($_POST['to']))
            {
                $dataArray['where']['from'] = common_dateFromDatePicketToTimestamp($_POST['from']);
                $dataArray['where']['to'] = common_dateFromDatePicketToTimestamp($_POST['to']);
            }
            else
                $dataArray['strError'][] = 'Укажите диапазон дат для фильтрации.';
        }
    }

    return $dataArray;
}

/**
 * Do action by domain (/maindomain url).
 *
 * @return bool
 */
function MainByDomainController()
{
    MainAuthUser();
    $dataArray = MainSupportFunction();

    $strError = $dataArray['strError'];
    $where = $dataArray['where'];
    $in = $dataArray['in'];
    $type = $dataArray['type'];
    $arResult = $dataArray['arResult'];

    /** logic by domain */
    if(!empty($_POST['form']) && $_POST['form'] == 'form-1')
    {
        $arGroup = [];
        switch ($_POST['tab_opened'])
        {
            case 'one':
                if(!empty($_POST['domain']))
                {
                    for($i = 0; $i < $ic = count($_POST['domain']); $i++)
                    {
                        if(!empty($_POST['domain'][$i]))
                        {
                            $in['domain'][] = $_POST['domain'][$i];
                        }
                    }
                }
                else
                    $strError[] = 'Выберите домен или список доменов для фильтрации.';

                break;
            case 'multiple':
                if(!empty($_POST['group']))
                {
                    common_inc('groupFilter');
                    $rsGroup = gf_get([], ['id' => $_POST['group']]);
                    while ($__arGroup = mysqli_fetch_assoc($rsGroup))
                    {
                        $listDomain = json_decode($__arGroup['value']);
                        if(!empty($listDomain))
                        {
                            for ($i = 0; $i < $ic = count($listDomain); $i++)
                            {
                                $in['domain'][] = $listDomain[$i];
                                $arGroup[$listDomain[$i]] = $__arGroup['name'];
                            }
                        }
                    }
                }
                else
                    $strError[] = 'Выберите группу доменов для фильтрации.';

                break;
        }

        /** do query if not errors */
        if(empty($strError))
        {
            common_inc('_fetcher');

            if(!empty($_POST['diff']))
            {
                switch ($_POST['tab_opened'])
                {
                    case 'one': $arResult['diff'] = fetcher_compareByDomain($in['domain'], $where); break;
                    case 'multiple': $arResult['diff'] = fetcher_compareGroupDomain($arGroup, $where); break;
                }
            }
            else
                $arResult = fetcher_getByDomain($type, $where, $in);
        }
    }

    return common_setView('statistic/domain', [
        'error' => $strError,
        'result' => $arResult,
        'group' => (!empty($arGroup) ? $arGroup : [])
    ]);
}

/**
 * Do action by domain (/mainreferrer url).
 *
 * @return bool
 */
function MainByReferrerController()
{
    MainAuthUser();
    $dataArray = MainSupportFunction();

    $strError = $dataArray['strError'];
    $where = $dataArray['where'];
    $in = $dataArray['in'];
    $type = $dataArray['type'];
    $arResult = $dataArray['arResult'];

    /** logic by referrer */
    if(!empty($_POST['form']) && $_POST['form'] == 'form-2')
    {
        $arGroup = [];
        switch ($_POST['tab_opened'])
        {
            case 'one':
                if(!empty($_POST['referrer']))
                {
                    for($i = 0; $i < $ic = count($_POST['referrer']); $i++)
                    {
                        if(!empty($_POST['referrer'][$i]))
                            $in['referrer'][] = $_POST['referrer'][$i];
                    }
                }
                else
                    $strError[] = 'Выберите домен или список доменов для фильтрации.';

                break;
            case 'multiple':
                if(!empty($_POST['group']))
                {
                    common_inc('groupFilter');
                    $rsGroup = gf_get([], ['id' => $_POST['group']]);
                    while ($__arGroup = mysqli_fetch_assoc($rsGroup))
                    {
                        $listDomain = json_decode($__arGroup['value']);
                        if(!empty($listDomain))
                        {
                            for ($i = 0; $i < $ic = count($listDomain); $i++)
                            {
                                $in['referrer'][] = $listDomain[$i];
                                $arGroup[$listDomain[$i]] = $__arGroup['name'];
                            }
                        }
                    }
                }
                else
                    $strError[] = 'Выберите группу доменов для фильтрации.';

                break;
        }

        /** do query if not errors */
        if(empty($strError))
        {
            common_inc('_fetcher');
            if(!empty($_POST['diff']))
            {
                switch ($_POST['tab_opened'])
                {
                    case 'one': $arResult['diff'] = fetcher_compareByReferrer($in['referrer'], $where); break;
                    case 'multiple': $arResult['diff'] = fetcher_compareGroupReferrer($arGroup, $where); break;
                }
            }
            else
                $arResult = fetcher_getStartReferrer($type, $where, $in);
        }
    }

    return common_setView('statistic/referrer', [
        'error' => $strError,
        'result' => $arResult,
        'group' => (!empty($arGroup) ? $arGroup : [])
    ]);
}

/**
 * Do action by domain (/fastdomain url).
 *
 * @return bool
 */
function MainByFastDomainController()
{
    MainAuthUser();
    $dataArray = MainSupportFunction();

    $strError = $dataArray['strError'];
    $in = $dataArray['in'];
    $type = $dataArray['type'];
    $arResult = $dataArray['arResult'];

    /** logic by fast domain */
    if(!empty($_POST['form']) && $_POST['form'] == 'form-3')
    {
        $where = [
            'from' => time() - 3600,
            'to' => time()
        ];

        $arGroup = [];
        switch ($_POST['tab_opened'])
        {
            case 'one':
                if(!empty($_POST['domain']))
                {
                    for($i = 0; $i < $ic = count($_POST['domain']); $i++)
                    {
                        if(!empty($_POST['domain'][$i]))
                        {
                            $in['domain'][] = $_POST['domain'][$i];
                        }
                    }
                }
                else
                    $strError[] = 'Выберите домен или список доменов для фильтрации.';

                break;
            case 'multiple':
                if(!empty($_POST['group']))
                {
                    common_inc('groupFilter');
                    $rsGroup = gf_get([], ['id' => $_POST['group']]);
                    while ($__arGroup = mysqli_fetch_assoc($rsGroup))
                    {
                        $listDomain = json_decode($__arGroup['value']);
                        if(!empty($listDomain))
                        {
                            for ($i = 0; $i < $ic = count($listDomain); $i++)
                            {
                                $in['domain'][] = $listDomain[$i];
                                $arGroup[$listDomain[$i]] = $__arGroup['name'];
                            }
                        }
                    }
                }
                else
                    $strError[] = 'Выберите группу доменов для фильтрации.';

                break;
        }

        /** do query if not errors */
        if(empty($strError))
        {
            common_inc('_fast');
            if(!empty($_POST['diff']))
                $arResult['diff'] = fast_compareByDomain($in['domain'], $where);
            else
                $arResult = fast_getByDomain($type, $where, $in);
        }
    }

    return common_setView('statistic/fastDomain', [
        'error' => $strError,
        'result' => $arResult,
        'group' => (!empty($arGroup) ? $arGroup : [])
    ]);
}

/**
 * Do action by domain (/fastreferrer url).
 *
 * @return bool
 */
function MainByFastReferrerController()
{
    MainAuthUser();
    $dataArray = MainSupportFunction();

    $strError = $dataArray['strError'];
    $in = $dataArray['in'];
    $type = $dataArray['type'];
    $arResult = $dataArray['arResult'];
    $arGroup = [];

    /** logic by fast referrer */
    if(!empty($_POST['form']) && $_POST['form'] == 'form-4')
    {
        $where = [
            'from' => time() - 3600,
            'to' => time()
        ];

        switch ($_POST['tab_opened'])
        {
            case 'one':
                if(!empty($_POST['referrer']))
                {
                    for($i = 0; $i < $ic = count($_POST['referrer']); $i++)
                    {
                        if(!empty($_POST['referrer'][$i]))
                        {
                            $in['referrer'][] = $_POST['referrer'][$i];
                        }
                    }
                }
                else
                    $strError[] = 'Выберите домен или список доменов для фильтрации.';

                break;
            case 'multiple':
                if(!empty($_POST['group']))
                {
                    common_inc('groupFilter');
                    $rsGroup = gf_get([], ['id' => $_POST['group']]);
                    while ($__arGroup = mysqli_fetch_assoc($rsGroup))
                    {
                        $listDomain = json_decode($__arGroup['value']);
                        if(!empty($listDomain))
                        {
                            for ($i = 0; $i < $ic = count($listDomain); $i++)
                            {
                                $in['referrer'][] = $listDomain[$i];
                                $arGroup[$listDomain[$i]] = $__arGroup['name'];
                            }
                        }
                    }
                }
                else
                    $strError[] = 'Выберите группу доменов для фильтрации.';

                break;
        }

        /** do query if not errors */
        if(empty($strError))
        {
            common_inc('_fast');
            if(!empty($_POST['diff']))
                $arResult['diff'] = fast_compareByReferrer($in['referrer'], $where);
            else
                $arResult = fast_getStartReferrer($type, $where, $in);
        }
    }

    return common_setView('statistic/fastReferrer', [
        'error' => $strError,
        'result' => $arResult,
        'group' => $arGroup
    ]);
}