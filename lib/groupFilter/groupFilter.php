<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 31.08.16
 * Time: 16:49
 */

/**
 * Get all groups for filter.
 *
 * @param array $where - where clause
 * @param array $in - in clause
 * @return bool|mysqli_result
 */
function gf_get($where = [], $in = [])
{
    common_inc('_database');
    $res = select_db(false, 'group_by_filter', ['*'], $where, [], '', $in);

    return $res;
}

/**
 * Prepare data for use with db.
 *
 * @param $type - type of opertaion
 * @return array - arrya of data
 */
function gf_prepare($type)
{
    common_inc('_database');
    $preparedData = [];
    $arrayError = [];
    switch($type)
    {
        case 'add':
            $domain = [];
            if(!empty($_POST))
            {
                if(!empty($_POST['name']))
                    $preparedData['name'] = prepare_db(
                        $_POST['name'],
                        true
                    );
                else
                    $arrayError[] = -2;

                /** check unique name */
                if(!empty($preparedData['name']))
                {
                    $rsRow = select_db(
                        1,
                        'group_by_filter',
                        ['id'],
                        ['name' => $preparedData['name']]
                    );

                    if($arRow = mysqli_fetch_assoc($rsRow))
                        $arrayError[] = -1;
                }
                

                if(!empty($_POST['domain'])
                    && is_array($_POST['domain'])
                )
                {
                    for($i = 0; $i < $ic = count($_POST['domain']); $i++)
                    {
                        $domain[] = prepare_db(
                            $_POST['domain'][$i],
                            true
                        );
                    }
                }
                else
                    $arrayError[] = -3;

                $preparedData['value'] = json_encode($domain);

            }
            break;
        case 'update':
            $domain = [];
            if(!empty($_POST))
            {
                if(!empty($_POST['name']))
                    $preparedData['name'] = prepare_db(
                        $_POST['name'],
                        true
                    );
                else
                    $arrayError[] = -2;

                $preparedData['oldname'] = prepare_db(
                    $_POST['oldname'],
                    true
                );

                /** check unique name */
                $rsRow = query_db(
                    1,
                    'group_by_filter',
                    'SELECT `id` WHERE `name` = \'' . $preparedData['name'] . '\' AND `name` <> \'' . $preparedData['oldname'] . '\''
                );

                unset($preparedData['oldname']);

                if($arRow = mysqli_fetch_assoc($rsRow))
                    $arrayError[] = -1;

                if(!empty($_POST['domain'])
                    && is_array($_POST['domain'])
                )
                {
                    for($i = 0; $i < $ic = count($_POST['domain']); $i++)
                    {
                        $domain[] = prepare_db(
                            $_POST['domain'][$i],
                            true
                        );
                    }
                }
                else
                    $arrayError[] = -3;

                $preparedData['value'] = json_encode($domain);

            }
            break;
    }

    return [
        'error' => $arrayError,
        'data' => $preparedData
    ];
}

/**
 * Add new record in group_by_filter.
 *
 * @return array
 */
function gf_add()
{
    $preparedData = gf_prepare('add');
    $lastInserted = 0;
    if(empty($preparedData['error']))
    {
        /** add row */
        common_inc('_database');
        $lastInserted = insert_db(
            1,
            'group_by_filter',
            $preparedData['data']
        );

        return [
            'error' => $preparedData['error'],
            'data' => $lastInserted
        ];
    }
    else
    {
        return [
            'error' => $preparedData['error'],
            'data' => $lastInserted
        ];
    }
}

/**
 * Delete record in group_by_filter.
 * ID of record get form url.
 *
 * @return bool
 */
function gf_delete()
{
    $uri = common_getURI();
    common_inc('_database');
    return delete_db(
        1,
        'group_by_filter',
        ['id' => $uri[3]]
    );
}

/**
 * Update record in group_by_filter.
 * ID of record get form url.
 *
 * @return array
 */
function gf_update()
{
    $preparedData = gf_prepare('update');
    $lastInserted = 0;
    if(empty($preparedData['error']))
    {
        /** add row */
        common_inc('_database');
        $uri = common_getURI();
        $lastInserted = update_db(
            1,
            'group_by_filter',
            $preparedData['data'],
            ['id' => $uri[3]]
        );

        return [
            'error' => $preparedData['error'],
            'data' => $lastInserted
        ];
    }
    else
    {
        return [
            'error' => $preparedData['error'],
            'data' => $lastInserted
        ];
    }
}

/**
 * Get html for list of domain's id.
 *
 * @param array $link - list of domain's id
 * @return string
 */
function gf_compareDomain($link = [])
{
    common_inc('_fetcher');
    $str = '';
    if(empty($GLOBALS['gf']['arDomain']))
    {
        $resDomain = fetcher_getDomain();
        $arDomain = [];
        while($__arDomain = mysqli_fetch_assoc($resDomain))
            $arDomain[$__arDomain['id']] = $__arDomain['name'];

        $GLOBALS['gf']['arDomain'] = $arDomain;
    }
    else
        $arDomain = $GLOBALS['gf']['arDomain'];

    for ($i = 0; $i < $ic = count($link); $i++)
    {
        if(!empty($arDomain[$link[$i]]))
            $str .= $arDomain[$link[$i]] . '<br>';
    }

    return $str;
}