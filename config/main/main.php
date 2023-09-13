<?php

/**
 * Массив основных конфигурационных переменных
 */
return [
    'root' => __DIR__ . '/../..',
    'web' => common_get_url_host() . '',
    'log_dir' => __DIR__ . '/../../../log',
    'log_done' => __DIR__ . '/../../../log_done',
    'user_log_dir' => __DIR__ . '/../../../user_by_domain',
    'user_ref_dir' => __DIR__ . '/../../../user_by_referrer',
    'syslog' => __DIR__ . '/../../../syslog',
    'regexp' => [
        'mgts.ru',
        'mts.ru',
        'mgts.zionec.ru'
    ],
    'nginx_log' => '/var/log/nginx',
    'listShardTable' => [
        'counter_domain',
        'counter_link',
        'counter_ref_domain',
        'counter_ref_link',
        'dirty',
        'l_sequence_4_user',
        'start_referrer',
        'topReferers_linksCount',
        'topReferers_links'
    ],
    'server' => [
        'dev' => [
            'ad_path' => 'http://stat.mgts.zionec.ru/ad/',
            'protocol' => 'http://'
        ],
        'prod' => [
            'ad_path' => 'http://count.mgts.ru/ad/',
            'protocol' => 'https://'
        ]
    ]
];
