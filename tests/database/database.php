<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 18.07.16
 * Time: 16:45
 */

include_once (__DIR__ . '/../../lib/common/common.php');
common_inc('test');
common_inc('_database');

/**
 * Do select query for database.
 *
 * @param bool $shardKey - key for sharding.
 * @param $table - table for query. In query name of table will be a $table_$shardKey
 * @param array $select - select array
 * @param array $where - where clause
 * @param array $sort - sort clause
 * @param string $limit - count of records
 * @param array $in - where IN clause
 * @return int
 */
function TestSelect($shardKey, $table, $select, $where = [], $sort = [], $limit = '', $in = [])
{
    $res = select_db($shardKey, $table, $select, $where, $sort, $limit , $in);
    if($a = mysqli_fetch_assoc($res))
        return $a['name'];
    else
        return 0;
}

/**
 * Insert query.
 *
 * @param string $shardKey - sharding key
 * @param string $table - name of table
 * @param array $params - where clause
 * Where clause must contain name field.
 * @param array $update - if pass, then query build as INSERT UPDATE
 * @param bool $isUpdate
 * @return bool
 */
function TestInsert($shardKey = '', $table = '', $params = [], $update = [], $isUpdate = false)
{
    if(empty($params['name']))
        exit("\n\nМассив параметров обязательно должен содержать поле name!\n\n");

    insert_db($shardKey, $table, $params, $update);
    if(!$isUpdate)
        $name = $params['name'];
    else
        $name = $update['name'];

    $res = select_db(
        $shardKey,
        $table, ['*'], [
        'name' => $name
    ]);
    if($a = mysqli_fetch_assoc($res))
    {
        if($a['name'] == $name)
            return true;
        else
            return false;
    }
    else
        return false;
}

/**
 * Update query.
 *
 * @param string $shardKey - key for sharding
 * @param $table - table name
 * @param array $params - data for updating
 * data must contain 'name' field.
 * @param array $where - where clause
 * Where clause must contain 'name' field.
 * @return bool
 */
function TestUpdate($shardKey = '', $table, $params = [], $where = [])
{
    if(empty($where['name']) || empty($params['name']))
        exit("\n\nМассив параметров обязательно должен содержать поле name!\n\n");

    update_db($shardKey, $table, $params, $where);
    $res = select_db(
        $shardKey,
        $table, ['*'], [
        'name' => $params['name']
    ]);
    if($a = mysqli_fetch_assoc($res))
    {
        if($a['name'] == $params['name'])
            return true;
        else
            return false;
    }
    else
        return false;
}

/**
 * Delete query.
 *
 * @param string $shardKey - key for sharding
 * @param $table - name of table
 * @param $where - where clause
 * Where clause must contain 'name' field.
 * @return bool
 */
function TestDelete($shardKey = '', $table, $where = [])
{
    if(empty($where['name']))
        exit("\n\nМассив параметров обязательно должен содержать поле name!\n\n");

    delete_db($shardKey, $table, $where);
    $res = select_db(
        $shardKey,
        $table, ['*'], [
        'name' => $where['name']
    ]);
    if($a = mysqli_fetch_assoc($res))
    {
        if(empty($a['name']))
            return true;
        else
            return false;
    }
    else
        return true;
}

/**
 * Test @see query_db().
 *
 * @param string $shardKey
 * @param string $table
 * @param string $query
 * @return int
 */
function TestQuery($shardKey = '', $table = '', $query = '')
{
    $res = query_db($shardKey, $table, $query);
    if($a = mysqli_fetch_assoc($res))
        return $a['name'];
    else
        return 0;
}

/** INSERT */
/** Test not shard query. */
TestGetIteration('TestInsert', [
    1,
    'test',
    ['name' => 'test_domain1'],
], true);
TestGetIteration('TestInsert', [
    1,
    'test',
    ['name' => 'test_domain1'],
    ['name' => 'mgts.ru'],
    true
], true);
/** Test shard query. */
TestGetIteration('TestInsert', [
    '1469612712',
    'test',
    ['name' => 'test_domain'],
], true);

/** SELECT */
/** Test not shard query. */
TestGetIteration('TestSelect', [
    1,
    'test',
    ['*'],
    ['name' => 'mgts.ru'],
], 'mgts.ru');
TestGetIteration('TestQuery', [
    1,
    'test',
    'SELECT `name` WHERE `name`=\'mgts.ru\'',
], 'mgts.ru');
/** Test shard query. */
TestGetIteration('TestSelect', [
    '1469612712',
    'test',
    ['*'],
    ['name' => 'test_domain'],
], 'test_domain');
TestGetIteration('TestQuery', [
    1469612712,
    'test',
    'SELECT `name` WHERE `name`=\'mgts.ru\'',
], 'test_domain');

/** UPDATE */
/** Test not shard query. */
TestGetIteration('TestUpdate', [
    1,
    'test',
    ['name' => 'mgts.ru'],
    ['name' => 'test_domain'],
], true);
/** Test shard query. */
TestGetIteration('TestUpdate', [
    '1469612712',
    'test',
    ['name' => 'test_domain'],
    ['name' => 'test_domain_new'],
], true);

/** DELETE */
/** Test not shard query. */
TestGetIteration('TestDelete', [
    1,
    'test',
    ['name' => 'mgts.ru']
], true);
/** Test shard query. */
TestGetIteration('TestDelete', [
    '1469612712',
    'test',
    ['name' => 'test_domain']
], true);