<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 25.08.16
 * Time: 0:46
 */

$layout = 'statistic';

/**
 * Show list of groups.
 *
 * @return bool
 */
function SettingSetFilterGroupController()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');

    $strError = [];
    $arResult = [];
    return common_setView('setting/addGroup', [
        'error' => $strError,
        'result' => $arResult
    ]);
}

/**
 * Add group.
 */
function SettingSetFilterGroupAddController()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');

    common_inc('groupFilter');
    $result = [];
    if(!empty($_POST))
        $result = gf_add();

    if(!empty($result['data']) && $result['data'] > 0)
    {
        header('Location: /groupfilter');
        exit;
    }


    $arError = common_setValue($result, 'error');
    $message = [];
    if(!empty($arError))
        $message = common_prepareMessageString('groupFilter', $arError);

    return common_setView('setting/group/add', [
        'error' => $message,
        'result' => common_setValue($result, 'data')
    ]);
}

/**
 * Update group.
 */
function SettingSetFilterGroupUpdateController()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');

    common_inc('groupFilter');
    $uri = common_getURI();
    $message = '';
    $result = gf_get(['id' => $uri[3]]);
    $arData = mysqli_fetch_assoc($result);
    if(!empty($_POST))
    {
        $result = gf_update();
        if(!empty($result['data']) && $result['data'] > 0)
        {
            header('Location: /groupfilter');
            exit;
        }

        $arError = common_setValue($result, 'error');
        if(!empty($arError))
            $message = common_prepareMessageString('groupFilter', $arError);
    }

    return common_setView('setting/group/add', [
        'error' => $message,
        'result' => $arData,
        'update' => true
    ]);
}

/**
 * Delete group.
 */
function SettingSetFilterGroupDeleteController()
{
    common_inc('auth');
    if(!auth_is())
        header('Location: /');

    common_inc('groupFilter');
    gf_delete();
    header('Location: /groupfilter');
    exit;
}