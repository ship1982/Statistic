<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 20.04.16
 * Time: 23:58
 */

return [
    'db' => array(
        '1' => array(
            'db_user' => 'stat',
            'db_pass' => '*',
            'db_name' => 'stat',
            'db_type' => 'localhost',
            'sharding_by' => 4
        ),
        '2' => array(
            'db_user' => 'stat',
            'db_pass' => '*',
            'db_name' => 'mts_stat',
            'db_type' => 'localhost',
            'sharding_by' => 4
        )
    )
];