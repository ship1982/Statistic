<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 18.07.16
 * Time: 16:15
 */

include_once (__DIR__ . '/../../lib/common/common.php');
common_inc('test');
common_inc('sharding');

/**
 * Test connection to database by passing timestamp for sharding.
 *
 * @param $timestamp - timestamp
 * @return bool
 */
function TestSharding($timestamp)
{
    $res = sharding_getConnection($timestamp);
    if(is_object($res['connect']))
        return true;
    else
        return false;
}

/**
 * Test @see sharding_getUniqueId().
 *
 * @return bool
 */
function TestGetUniqueId()
{
    $id = sharding_getUniqueId();
    if(is_int($id))
        return true;
    else
        return false;
}

/**
 * Test @see sharding_setUniqueId().
 *
 * @return bool
 */
function TestSetUniqueId()
{
    $id = sharding_setUniqueId();
    if(is_int($id))
        return true;
    else
        return false;
}

TestGetIteration('TestSharding', ['1464614619'], true);
TestGetIteration('TestSharding', ['1466169819'], true);
TestGetIteration('TestSharding', ['1466121600'], true);
TestGetIteration('TestSharding', ['1468713600'], true);

/** TestGetUniqueId */
TestGetIteration('TestGetUniqueId', [], true);

/** TestSetUniqueId */
TestGetIteration('TestSetUniqueId', [], true);