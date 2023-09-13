<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 28.07.16
 * Time: 22:43
 */

require (__DIR__ . '/../lib/common/common.php');
common_inc('setting');

if(empty($argv[1])
    || empty($argv[2])
    || empty($argv[3])
)
{
    echo "\n";
    echo "- первым параметром нужно передать название таблицы. Например dirty.\n";
    echo "- вторым параметром нужно передать номер месяца, для которого будет создана шардируемая таблица. Например 6 (июнь)\n";
    echo "- третьим параметром нужно передать год, для которого будет создана шардируемая таблица. Например 2016\n";
    echo "\n";
    exit;
}

echo "\n";
echo "Название шардируемой таблицы: " . setting_shardingTableName($argv[1], $argv[2], $argv[3]) . "\n";
echo "\n";