<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 19.07.16
 * Time: 1:28
 */

include_once (__DIR__ . '/../../lib/common/common.php');
$GLOBALS['conf'] = common_conf();
common_inc('test');
common_inc('_import');

/**
 * Check that string from mts or mgts.
 *
 * @param bool $str - url for search
 * @return bool
 */
function TestAllow($str)
{
    $res = import_allow($str);
    if($res === true)
        return true;
    else
        return false;
}

/**
 * Check domain in the table,
 * if not exist, then paste it and return id.
 *
 * @param bool $domain - domain string
 * @param bool $onlySelect - do only select
 * @return bool
 */
function TestSetDomain($domain = false, $onlySelect = false)
{
    $res = import_setDomain($domain, $onlySelect);
    if(is_int($res))
        return $res;
    else
        return false;
}

/**
 * Check link in the table,
 * if not exist, then paste it and return id.
 *
 * @param string $link - link for checking
 * @return bool|int
 */
function TestSetLink($link = '')
{
    $res = import_setLink($link);
    if(is_int($res))
        return $res;
    else
        return false;
}

/**
 * Returns a beginning of given day of the month,
 * which present by current timestamp.
 *
 * @param int $timestamp - timestamp, for which calculate a timestamp
 * @param int $compare - value for compare with result.
 * @return bool
 */
function TestDayStartTimestamp($timestamp = 0, $compare = 0)
{
    $res = import_dayStartTimestamp($timestamp);
    if($res == $compare)
        return true;
    else
        return false;
}

/**
 * Set data in counter_domain.
 *
 * @param array $data - data to insert
 * @param int $compare - value, which will be compared with day field from database.
 * @param bool $delete - if true, then delete this row in database
 * @return bool
 */
function TestSetCounterDomain($data = [], $compare = 0, $delete = false)
{
    import_setCounterDomain($data);
    $res = import_setDomain('test.mgts.ru', true);

    if(!$res)
        return false;
    $a = select_db(
        $data['time'],
        'counter_domain',
        ['*'],
        ['domain' => $res]
    );
    if($b = mysqli_fetch_assoc($a))
    {
        if($b['day'] == $compare)
        {
            if($delete)
                delete_db($data['time'], 'counter_domain', ['domain' => $res]);

            return true;
        }
        else
            return false;
    }
    else
        return false;
}

/**
 * Set data in counter_link.
 *
 * @param array $data - data to insert
 * @param int $compare - value, which will be compared with day field from database.
 * @param bool $delete - if true, then delete this row in database
 * @return bool
 */
function TestSetCounterLink($data = [], $compare = 0, $delete = false)
{
    import_setCounterLink($data);
    $res = import_setLink($data['link'], true);

    if(!$res)
        return false;
    $a = select_db(
        $data['time'],
        'counter_link',
        ['*'],
        ['link' => $res]
    );
    if($b = mysqli_fetch_assoc($a))
    {
        if($b['day'] == $compare)
        {
            if($delete)
                delete_db($data['time'], 'counter_link', ['link' => $res]);

            return true;
        }
        else
            return false;
    }
    else
        return false;
}

/**
 * Return a domain by link.
 *
 * @param $url - url for parsing
 * @param $compare - value, which is the comparison result.
 * @return bool
 */
function TestParseDomain($url, $compare)
{
    $res = import_parseDomain($url);
    if($res == $compare)
        return true;
    else
        return false;
}

/**
 * Set data in counter_ref_link.
 *
 * @param array $data - data to insert
 * @param int $compare - value, which will be compared with day field from database.
 * @param bool $delete - if true, then delete this row in database
 * @return bool
 */
function TestSetCounterRefLink($data = [], $compare = 0, $delete = false)
{
    import_setCounterRefLink($data);
    $res = import_setLink($data['link'], true);

    if(!$res)
        return false;
    $a = select_db(
        $data['time'],
        'counter_ref_link',
        ['*'],
        ['link' => $res]
    );
    if($b = mysqli_fetch_assoc($a))
    {
        if($b['day'] == $compare)
        {
            if($delete)
                delete_db($data['time'], 'counter_ref_link', ['link' => $res]);

            return true;
        }
        else
            return false;
    }
    else
        return false;
}

/**
 * Set data in counter_ref_domain.
 *
 * @param array $data - data to insert
 * @param int $compare - value, which will be compared with day field from database.
 * @param bool $delete - if true, then delete this row in database
 * @return bool
 */
function TestSetCounterRefDomain($data = [], $compare = 0, $delete = false)
{
    import_setCounterRefDomain($data);
    $res = import_setDomain($data['domain'], true);

    if(!$res)
        return false;
    $a = select_db(
        $data['time'],
        'counter_ref_domain',
        ['*'],
        ['domain' => $res]
    );
    if($b = mysqli_fetch_assoc($a))
    {
        if($b['day'] == $compare)
        {
            if($delete)
                delete_db($data['time'], 'counter_ref_domain', ['domain' => $res]);

            return true;
        }
        else
            return false;
    }
    else
        return false;
}

/**
 * Write a file with user by domain by day.
 * A unique of record is defined by day @see import_dayStartTimestamp
 * Name of file is a $time . '_' . $domain_id
 * File is located in @property $GLOBALS['conf']['user_log_dir'].
 *
 * @param array $data - data to insert
 * @param bool $delete - if true, then delete file.
 * @param bool $create - if true, create a @see import_setCounterDomain
 * @return bool
 */
function TestSetDomainByFile($data = [], $delete = false, $create = false)
{
    if($create)
        import_setCounterDomain($data);

    import_setDomainByFile($data);
    $GLOBALS['conf'] = common_conf();
    $array['time'] = '-10800';
    $array['domain'] = import_setDomain($data['domain'], true);
    $file = $GLOBALS['conf']['user_log_dir'] . '/' . $array['time'] . '_' . $array['domain'];
    if(file_exists($file))
    {
        if(filesize($file) != 31)
            return false;

        if($delete)
        {
            $res = import_setDomain('test.mgts.ru', true);
            $a = select_db(
                $data['time'],
                'counter_domain',
                ['*'],
                ['domain' => $res]
            );
            if($b = mysqli_fetch_assoc($a))
                delete_db($data['time'], 'counter_domain', ['domain' => $res]);

            unlink($file);
        }

        return true;
    }
    else
        return false;
}

/**
 * Write a file with user by referrer by day.
 * Name of file is a $time . '_' . md5($referrer)
 * File is located in @property $GLOBALS['conf']['user_ref_dir']
 *
 * @param array $data @see import_set()
 * @param bool $delete - if true, then delete file.
 * @param bool $create - if true, create a @see import_setCounterRefDomain
 * @return int
 */
function TestSetReferrerByFile($data = [], $delete = false, $create = false)
{
    if($create)
        import_setCounterRefDomain($data);

    import_setReferrerByFile($data);
    $GLOBALS['conf'] = common_conf();
    $array['time'] = '-10800';
    $array['referrer'] = import_setDomain($data['referrer'], true);

    $file = $GLOBALS['conf']['user_ref_dir'] . '/' . $array['time'] . '_' . $array['referrer'];
    if(file_exists($file))
    {
        if(filesize($file) != 31)
            return false;

        if($delete)
        {
            $res = import_setDomain($data['referrer'], true);
            $a = select_db(
                $data['time'],
                'counter_ref_domain',
                ['*'],
                ['referrer' => $res]
            );
            if($b = mysqli_fetch_assoc($a))
                delete_db($data['time'], 'counter_ref_domain', ['referrer' => $res]);

            unlink($file);
        }

        return true;
    }
    else
        return false;
}

/**
 * Test @see import_setDirtyLog()
 *
 * @param array $data - @see import_set()
 * @return bool
 */
function TestSetDirtyLog($data = [])
{
    $res = import_setDirtyLog($data);
    if(!empty($res))
    {
        $answer = select_db(
            $data['time'],
            'dirty',
            ['*'],
            ['id' => $res]
        );
        if($a = mysqli_fetch_assoc($answer))
            return true;
        else
            return false;
    }
    else
        return false;
}

/**
 * Test @see import_setCounterStartRefDomain().
 *
 * @param array $data - @see import_set()
 * @param bool $delete - if true, then delete file.
 * @param bool $create - if true, then create a row.
 * @return bool
 */
function TestSetCounterStartRefDomain($data = [], $delete = false, $create = false)
{
    if($create)
        import_setStartCounterRefDomain($data);

    import_setCounterStartRefDomain($data);
    $GLOBALS['conf'] = common_conf();
    $array['time'] = '-10800';
    $array['start_referrer'] = import_setDomain($data['start_referrer'], true);
    $file = $GLOBALS['conf']['user_ref_dir'] . '/start_' . $array['time'] . '_' . $array['start_referrer'];
    if(file_exists($file))
    {

        if(filesize($file) != 31)
            return false;

        if($delete)
        {
            $res = import_setDomain($data['start_referrer'], true);
            $a = select_db(
                $data['time'],
                'start_referrer',
                ['*'],
                ['referrer' => $res]
            );
            if($b = mysqli_fetch_assoc($a))
                delete_db($data['time'], 'start_referrer', ['referrer' => $res]);

            unlink($file);
        }

        return true;
    }
    else
        return false;
}

/**
 * Test @see import_setStartCounterRefDomain().
 *
 * @param array $data - @see import_set()
 * @param int $compare - value, which will be compared with day field from database.
 * @param bool $delete - if true, then delete this row in database
 * @return bool
 */
function TestSetStartCounterRefDomain($data = [], $compare = 0, $delete = false)
{
    import_setStartCounterRefDomain($data);
    $res = import_setDomain($data['start_referrer'], true);

    if(!$res)
        return false;
    $a = select_db(
        $data['time'],
        'start_referrer',
        ['*'],
        ['referrer' => $res]
    );
    if($b = mysqli_fetch_assoc($a))
    {
        if($b['day'] == $compare)
        {
            if($delete)
                delete_db($data['time'], 'start_referrer', ['referrer' => $res]);

            return true;
        }
        else
            return false;
    }
    else
        return false;
}

/** TestAllow */
TestGetIteration('TestAllow', ['http://psdoasoda.ru'], false);
TestGetIteration('TestAllow', ['http://mgts.ru'], true);
TestGetIteration('TestAllow', ['http://mts.ru'], true);
TestGetIteration('TestAllow', ['http://her.mtsyet.ru'], false);

/** TestSetDomain */
TestGetIteration('TestSetDomain', ['mgts.ru', true], true);
TestGetIteration('TestSetDomain', ['mgts.ru'], true);
TestGetIteration('TestSetDomain', ['mrts.ru'], false);
TestGetIteration('TestSetDomain', ['test.mgts.ru_'], true);
TestGetIteration('TestSetDomain', [''], true);

/** TestSetLink */
TestGetIteration('TestSetLink', ['mgts.ru', true], true);
TestGetIteration('TestSetLink', ['mgts.ru'], true);
TestGetIteration('TestSetLink', ['mrts.ru'], false);
TestGetIteration('TestSetLink', ['test.mgts.ru_'], true);
TestGetIteration('TestSetLink', [''], false);

/** TestDayStartTimestamp */
TestGetIteration('TestDayStartTimestamp', [1469104874, 1469048400], true);
TestGetIteration('TestDayStartTimestamp', [1464739200, 1464728400], true);
TestGetIteration('TestDayStartTimestamp', [1465909921, 1465851600], true);

/** TestSetCounterDomain */
$data = [
    'time' => 1461014108,
    'referrer' => 'http://test.mgts.ru',
    'start_referrer' => 'http://test.mgts.ru',
    'pixel_status' => '304',
    'domain' => 'test.mgts.ru',
    'link' => 'http://test.mgts.ru',
    'stat_domain' => 'stat.mgts.ru',
    'pixel' => '',
    'uuid' => '146099008468193787328696256670',
    'os' => 'Linux',
    'browser' => 'Chrome',
    'ip' => '94.25.229.119'
];
TestGetIteration('TestSetCounterDomain', [$data, 1], true);
TestGetIteration('TestSetCounterDomain', [$data, 2, true], true);

/** TestSetCounterLink */
TestGetIteration('TestSetCounterLink', [$data, 1], true);
TestGetIteration('TestSetCounterLink', [$data, 2, true], true);

/** TestParseDomain */
TestGetIteration('TestParseDomain', ['http://test.mgts.ru', 'test.mgts.ru'], true);

/** TestSetCounterRefLink */
TestGetIteration('TestSetCounterRefLink', [$data, 1], true);
TestGetIteration('TestSetCounterRefLink', [$data, 2, true], true);

/** TestSetCounterRefDomain */
TestGetIteration('TestSetCounterRefDomain', [$data, 1], true);
TestGetIteration('TestSetCounterRefDomain', [$data, 2, true], true);

$data = [
    'time' => 1,
    'pixel_status' => '304',
    'referrer' => 'test.mgts.ru',
    'start_referrer' => 'http://test.mgts.ru',
    'domain' => 'test.mgts.ru',
    'link' => 'http://test.mgts.ru',
    'stat_domain' => 'stat.mgts.ru',
    'pixel' => '',
    'uuid' => '146099008468193787328696256670',
    'os' => 'Linux',
    'browser' => 'Chrome',
    'ip' => '94.25.229.119'
];

/** TestSetDomainByFile */
TestGetIteration('TestSetDomainByFile', [$data], true);
TestGetIteration('TestSetDomainByFile', [$data, false, true], true);
TestGetIteration('TestSetDomainByFile', [$data, true], true);

/** TestSetReferrerByFile */
TestGetIteration('TestSetReferrerByFile', [$data], true);
TestGetIteration('TestSetReferrerByFile', [$data, false, true], true);
TestGetIteration('TestSetReferrerByFile', [$data, true], true);

$data = [
    'time' => 1461014108,
    'referrer' => 'http://test.mgts.ru',
    'start_referrer' => 'test.mgts.ru',
    'pixel_status' => '1',
    'domain' => 'test.mgts.ru',
    'link' => 'http://test.mgts.ru',
    'stat_domain' => 'stat.mgts.ru',
    'pixel' => '',
    'uuid' => '146099008468193787328696256670',
    'os' => '16',
    'browser' => '4',
    'ip' => '1578755447'
];
/** TestSetDirtyLog */
TestGetIteration('TestSetDirtyLog', [$data], true);

$data = [
    'time' => 1,
    'referrer' => 'test.mgts.ru',
    'start_referrer' => 'test.mgts.ru',
    'pixel_status' => '1',
    'domain' => 'test.mgts.ru',
    'link' => 'http://test.mgts.ru',
    'stat_domain' => 'stat.mgts.ru',
    'pixel' => '',
    'uuid' => '146099008468193787328696256670',
    'os' => '16',
    'browser' => '4',
    'ip' => '1578755447'
];
/** TestSetStartCounterRefDomain */
TestGetIteration('TestSetStartCounterRefDomain', [$data, 1], true);
TestGetIteration('TestSetStartCounterRefDomain', [$data, 2, true], true);

/** TestSetCounterStartRefDomain */
TestGetIteration('TestSetCounterStartRefDomain', [$data], true);
TestGetIteration('TestSetCounterStartRefDomain', [$data, false, true], true);
TestGetIteration('TestSetCounterStartRefDomain', [$data, true], true);