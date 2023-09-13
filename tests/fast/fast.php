<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 27.07.16
 * Time: 15:43
 */

include_once (__DIR__ . '/../../lib/common/common.php');
$GLOBALS['conf'] = common_conf();
common_inc('test');
common_inc('_fast');

/**
 * Test @see fast_getByDomain().
 *
 * @param string $type
 * @param array $where
 * @param array $in
 * @return bool
 */
function TestGetByDomain($type = 'count', $where = [], $in = [])
{
    $res = fast_getByDomain($type, $where, $in);
    if(!empty($res[0])
        && !empty($res[0]['domain'])
        && !empty($res[0]['c'])
    )
        return true;
    else
        return false;
}

/**
 * Test @see fast_userByDomain().
 *
 * @param array $domains
 * @param array $time
 * @return bool
 */
function TestUserByDomain($domains = [], $time = [])
{
    $res = fast_userByDomain($domains, $time);
    if(!empty($res) && is_array($res))
        return true;
    else
        return false;
}

/**
 * Test @see fast_compareByDomain().
 *
 * @param array $domains
 * @param array $time
 * @return bool
 */
function TestCompareByDomain($domains = [], $time = [])
{
    $res = fast_compareByDomain($domains, $time);
    if(!empty($res) && is_array($res))
        return true;
    else
        return false;
}

/**
 * Test @see fast_getStartReferrer().
 *
 * @param string $type
 * @param array $where
 * @param array $in
 * @return bool
 */
function TestGetStartReferrer($type = 'count', $where = [], $in = [])
{
    $res = fast_getStartReferrer($type, $where, $in);
    if(!empty($res[0])
        && !empty($res[0]['referrer'])
        && !empty($res[0]['c'])
    )
        return true;
    else
        return false;
}

/**
 * Test @see fast_userByReferrer().
 *
 * @param array $domains
 * @param array $time
 * @return bool
 */
function TestUserByReferrer($domains = [], $time = [])
{
    $res = fast_userByReferrer($domains, $time);
    if(!empty($res) && is_array($res))
        return true;
    else
        return false;
}

/**
 * Test @see fast_getAllReferrer().
 *
 * @param string $type
 * @param array $where
 * @param array $in
 * @return bool
 */
function TestGetAllReferrer($type = 'count', $where = [], $in = [])
{
    $res = fast_getAllReferrer($type, $where, $in);
    if(!empty($res[0])
        && !empty($res[0]['referrer'])
        && !empty($res[0]['c'])
    )
        return true;
    else
        return false;
}

/**
 * Test @see fast_compareByReferrer().
 *
 * @param array $referrer
 * @param array $time
 * @return bool
 */
function TestCompareByReferrer($referrer = [], $time = [])
{
    $res = fast_compareByReferrer($referrer, $time);
    if(!empty($res) && is_array($res))
        return true;
    else
        return false;
}

/** TestGetByDomain */
TestGetIteration('TestGetByDomain', ['count', [
    'from' => 1467887374,
    'to' => 1467887915,
    'domain' => 1
]], true);
TestGetIteration('TestGetByDomain', ['sum', [
    'from' => 1467887374,
    'to' => 1467887915,
    'domain' => 1
]], true);
TestGetIteration('TestGetByDomain', ['count', [
    'from' => 1467887374,
    'to' => 1467887915,
], ['domain' => [1, 9, 4]]], true);

/** TestUserByDomain */
TestGetIteration('TestUserByDomain', [
    [
        1,
        112
    ], [
        'from' => 1467887374,
        'to' => 1467887915
    ]], true);

/** TestCompareByDomain */
TestGetIteration('TestCompareByDomain', [
    [
        1,
        112
    ], [
        'from' => 1467887374,
        'to' => 1467887915
    ]], true);

/** TestGetStartReferrer */
TestGetIteration('TestGetStartReferrer', ['count', [
    'from' => 1467925114,
    'to' => 1467925414,
    'start_referrer' => 7
]], true);
TestGetIteration('TestGetStartReferrer', ['sum', [
    'from' => 1467925114,
    'to' => 1467925414,
    'start_referrer' => 7
]], true);
TestGetIteration('TestGetStartReferrer', ['count', [
    'from' => 1467925114,
    'to' => 1467955414,
], ['referrer' => [1, 7, 59]]], true);

/** TestUserByDomain */
TestGetIteration('TestUserByReferrer', [
    [
        1,
        7
    ], [
        'from' => 1466102564,
        'to' => 1467007625
    ]], true);

/** TestCompareByReferrer */
TestGetIteration('TestCompareByReferrer', [
    [
        1,
        7
    ], [
        'from' => 1466102564,
        'to' => 1467007625
    ]], true);

/** TestGetAllReferrer */
TestGetIteration('TestGetAllReferrer', ['count', [
    'from' => 1461586315,
    'to' => 1467887915,
    'enum_referrer' => 1
]], true);
TestGetIteration('TestGetAllReferrer', ['sum', [
    'from' => 1461586315,
    'to' => 1467887915,
    'enum_referrer' => 1
]], true);
TestGetIteration('TestGetAllReferrer', ['count', [
    'from' => 1461586315,
    'to' => 1467887915,
], ['enum_referrer' => [1, 3]]], true);
