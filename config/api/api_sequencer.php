<?php

return [
    'condition_types' => [
        '=',
        '!=',
        '>',
        '<',
        '>=',
        '<=',
        'like',
        'between',
        'between_unix_time',
        'regexp'
    ],
    'limit_row' => 1000,
    'time_diff' => 30,
    'fields' => [
        'link_text',
        'domain_text',
        'device',
        'id',
        'seance',
        'order',
        'utm',
        'hour',
        'time',
        'uuid',
        'domain',
        'link',
        'step',
        'duration',
        'referer_link',
        'referer_domain',
        'utm_term',
        'utm_content',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        '#count(id)',
        'form'
    ],
    'fields_def' => [
        'id',
        'time',
        'uuid',
        'domain_text',
        'link_text',
    ]
];
