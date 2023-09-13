<?php

include_once(__DIR__ . '/../../../lib/common/common.php');
common_inc('_database');
common_inc('system/cron', 'cron');
include_once(__DIR__ . '/../models/common.php');
set_time_limit(0);

/**
 * Local config function.
 *
 * @return array
 */
function getParams()
{
    return [
        'key' => 'tableUserDeactivateSequencer',
        'lastId' => 'sequencerUserDeactivate',
        'monthPeriod' => 2690743,
        'limit' => 10000
    ];
}

/**
 * @constructor
 */
function work()
{
    $params = getParams();
    $rsList = getUserPropertyData($params);
    $userTypes = getUserTypes();

    $howMuchRow = $lid = 0;
    if(!empty($rsList)
        || !is_bool($rsList)
    )
    {
        $sql = '';
        while($arList = mysqli_fetch_assoc($rsList))
        {
            $utype = getUserTypeWithoutCount($arList['last_visit'], $userTypes);
            $sql .= prepareSQL4UpdateUserTypeDeactivate($arList['uuid'], $utype);
            $lid = $arList['uuid'];
            $howMuchRow++;
        }
        if(!empty($sql))
            executeSQL($sql, 'user_property');
    }

    /** set table key if need and set last id */
    set_pid($lid, $params['lastId'], 'sequencer');
    if($howMuchRow < $limit)
        set_pid(0, $params['lastId'], 'sequencer');
}

//for ($i = 0; $i < 20; $i++)
    work();