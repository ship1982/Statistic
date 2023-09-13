<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 19.07.16
 * Time: 1:07
 */

require (__DIR__ . '/../lib/common/common.php');
common_inc('test');

TestGetModules([
    'perfomance/server',
    'sahrding/sharding',
    'database/database',
    'import/import',
    'fetcher/fetcher',
    'fast/fast'
]);