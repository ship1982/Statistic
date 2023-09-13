<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 26.07.16
 * Time: 11:15
 */

include_once (__DIR__ . '/../../lib/common/common.php');
$GLOBALS['conf'] = common_conf();
common_inc('test');
common_inc('_fetcher');

/**
 * Test @see fetcher_dayMonthTimestamp().
 *
 * @param bool $timestamp - time for which the timestamp beginning of the month will be generated
 * @param bool $month - if passed, then generation will be executed for this month
 * @param int $compare - value for compare with result
 * @return bool
 */
function TestDayMonthTimestamp($timestamp = false, $month = false, $compare = 0)
{
    $res = fetcher_dayMonthTimestamp($timestamp, $month);
    if($res == $compare)
        return true;
    else
        return false;
}

/**
 * Test @see fetcher_getTimeRange().
 *
 * @param array $time array with $time[from] and $time[to]
 * @param $compare - array for comparing with result.
 * @return bool
 */
function TestGetTimeRange($time = [], $compare = [])
{
    $res = fetcher_getTimeRange($time);
    /** for two month */
    if(!empty($res[0])
        && !empty($res[1])
        && !empty($compare[0])
        && !empty($compare[1])
    )
    {
        if($res[0] == $compare[0]
            && $res[1] == $compare[1]
        )
            return true;
        else
            return false;
    }
    /** for one month */
    if(!empty($res[0]) && !empty($compare[0]))
    {
       if($res[0] == $compare[0])
           return true;
       else
           return false;
    }

    return false;
}

/**
 * Test @see fetcher_getByDomain().
 *
 * @param string $type . default is count.
 * It is mean, that record by domain will be counted (unique visits).
 * If @property $type will be a sum, that means,
 * that record will be summed by day field (all visits)
 * @param array $where - where conditions by the table.
 * @param array $in
 * @return bool
 */
function TestGetByDomain_($type = 'count', $where = [], $in = [])
{
    $res = fetcher_getByDomain($type, $where, $in);
    if(!empty($res[0])
        && !empty($res[0]['domain'])
        && !empty($res[0]['c'])
    )
        return true;
    else
        return false;
}

/**
 * Test @see fetcher_getDomain().
 *
 * @return bool
 */
function TestGetDomain()
{
    $res = fetcher_getDomain();
    if($a = mysqli_fetch_assoc($res))
        return true;
    else
        return false;
}

/**
 * Test @see fetcher_getAllVariants().
 *
 * @param array $data
 * @param array $compare
 * @return bool
 */
function TestGetAllVariants($data = [], $compare = [])
{
    $res = fetcher_getAllVariants($data);
    if($res == $compare)
        return true;
    else
        return false;
}

/**
 * Test @see fetcher_arrayIntersect().
 *
 * @param array $a
 * @param array $b
 * @param array $compare
 * @return bool
 */
function TestArrayIntersect($a = [], $b = [], $compare = [])
{
    $res = fetcher_arrayIntersect($a, $b);
    if($res == $compare)
        return true;
    else
        return false;
}

/**
 * Test @see fetcher_compareByDomain().
 *
 * @param array $domain
 * @param array $time
 * @return bool
 */
function TestCompareByDomain_($domain = [], $time = [])
{
    $res = fetcher_compareByDomain($domain, $time);

$compare = [
        'mgts.ru -> invoice.mgts.ru' => 9846,
        'mgts.ru -> block.mgts.ru' => 6706,
        'invoice.mgts.ru -> block.mgts.ru' => 165,
        'mgts.ru -> invoice.mgts.ru -> block.mgts.ru' => 165
    ];
    if($res == $compare)
        return true;
    else
        return false;
}

/**
 * Test @see fetcher_getStartReferrer().
 *
 * @param string $type
 * @param array $where
 * @param array $in
 * @return bool
 */
function TestGetStartReferrer_($type = 'count', $where = [], $in = [])
{
    $res = fetcher_getStartReferrer($type, $where, $in);
    if(!empty($res[0])
        && !empty($res[0]['referrer'])
        && !empty($res[0]['c'])
    )
        return true;
    else
        return false;

}

/**
 * Test @see fetcher_getStartReferrer().
 *
 * @param string $type
 * @param array $where
 * @param array $in
 * @return bool
 */
function TestGetAllReferrer_($type = 'count', $where = [], $in = [])
{
    $res = fetcher_getAllReferrer($type, $where, $in);
    if(!empty($res[0])
        && !empty($res[0]['referrer'])
        && !empty($res[0]['c'])
    )
        return true;
    else
        return false;

}

/**
 * Test @see fetcher_compareByReferrer().
 *
 * @param array $domain
 * @param array $time
 * @return bool
 */
function TestCompareByReferrer_($domain = [], $time = [])
{
    $res = fetcher_compareByReferrer($domain, $time);
    $compare = [
        'mgts.ru' => 174544,
        'block.mgts.ru' => 73,
        'invoice.mgts.ru' => 983,
        'mgts.ru -> invoice.mgts.ru' => 0,
        'mgts.ru -> block.mgts.ru' => 0,
        'invoice.mgts.ru -> block.mgts.ru' => 0,
        'mgts.ru -> invoice.mgts.ru -> block.mgts.ru' => 0
    ];
    if($res == $compare)
        return true;
    else
        return false;
}

/** TestDayMonthTimestamp */
TestGetIteration('TestDayMonthTimestamp', [1469521715, false, 1467320400], true);

/** TestGetTimeRange */
TestGetIteration('TestGetTimeRange', [['from' => 1467320414, 'to' => 1469523553], [1467320400]], true);
TestGetIteration('TestGetTimeRange', [['from' => 1466942097, 'to' => 1469523553], [1464728400, 1469998800]], true);

/** TestGetByDomain_ */
TestGetIteration('TestGetByDomain_', ['count', [
    'from' => 1467320414,
    'to' => 1469523553,
    'domain' => 1
]], true);

TestGetIteration('TestGetByDomain_', ['sum', [
    'from' => 1467320414,
    'to' => 1469523553,
    'domain' => 1
]], true);

TestGetIteration('TestGetByDomain_', ['count', [
    'from' => 1467320414,
    'to' => 1469523553
], ['domain' => [7, 59, 1]]], true);

/** TestGetDomain */
TestGetIteration('TestGetDomain', [], true);

/** TestGetAllVariants */
TestGetIteration('TestGetAllVariants', [[1, 2, 3, 4], [
    0 => [
        0 => 1,
        1 => 2
    ],
    1 => [
        0 => 1,
        1 => 3
    ],
    2 => [
        0 => 2,
        1 => 3
    ],
    3 => [
        0 => 1,
        1 => 2,
        2 => 3
    ],
    4 => [
        0 => 1,
        1 => 4,
    ],
    5 => [
        0 => 2,
        1 => 4,
    ],
    6 => [
        0 => 1,
        1 => 2,
        2 => 4,
    ],
    7 => [
        0 => 3,
        1 => 4,
    ],
    8 => [
        0 => 1,
        1 => 3,
        2 => 4,
    ],
    9 => [
        0 => 2,
        1 => 3,
        2 => 4,
    ],
    10 => [
        0 => 1,
        1 => 2,
        2 => 3,
        3 => 4,
    ],
]], true);

/** TestArrayIntersect */
TestGetIteration('TestArrayIntersect', [[1,2,3],[1,3,6,7], [1,3]], true);

/** TestCompareByDomain_ */
TestGetIteration('TestCompareByDomain_', [[7, 59, 1], ['from' => 1464786953, 'to' => 1466601353]], true);

/** TestGetStartReferrer_ */
TestGetIteration('TestGetStartReferrer_', ['count', [
    'from' => 1467320414,
    'to' => 1469523553,
    'referrer' => 1
]], true);

TestGetIteration('TestGetStartReferrer_', ['sum', [
    'from' => 1467320414,
    'to' => 1469523553,
    'referrer' => 1
]], true);

TestGetIteration('TestGetStartReferrer_', ['count', [
    'from' => 1467320414,
    'to' => 1469523553
], ['referrer' => [1, 9, 4]]], true);

/** TestGetAllReferrer_ */
TestGetIteration('TestGetAllReferrer_', ['count', [
    'from' => 1467320414,
    'to' => 1469523553,
    'referrer' => 1
]], true);

TestGetIteration('TestGetAllReferrer_', ['sum', [
    'from' => 1467320414,
    'to' => 1469523553,
    'referrer' => 1
]], true);

TestGetIteration('TestGetAllReferrer_', ['count', [
    'from' => 1467320414,
    'to' => 1469523553
], ['referrer' => [7, 59, 1]]], true);

/** TestCompareByReferrer_ */
TestGetIteration('TestCompareByReferrer_', [[7, 59, 1], ['from' => 1464786953, 'to' => 1466601353]], true);
