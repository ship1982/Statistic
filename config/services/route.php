<?php

/**
 * Содержит ссылки на сервисы и другие опции по сервисам.
 * Если url локальный (сервис находится на той же машине, что и этот код),
 * То url должна вести к файлу подключения сервиса (путь к файлу)
 * Если сервис удаленный (находится на другой машине) то адрес должен начинаться с //
 */
return [
	'top_detalizer' => [
		'url' => realpath(__DIR__ . '/../../services/top_detalizer/index.php')
	],
	'sequence' => [
		'url' => realpath(__DIR__ . '/../../services/sequencer/index.php')
	],
	'mysqlqueue' => [
		'url' => realpath(__DIR__ . '/../../services/mysqlqueue/index.php')
	],
	'orderutm' => [
		'url' => realpath(__DIR__ . '/../../services/orderutm/index.php')
	],
	'events' => [
		'url' => realpath(__DIR__ . '/../../services/events/index.php')
	],
	'partners' => [
		'url' => realpath(__DIR__ . '/../../services/partners/index.php')
	],
	'eventlist' => [
		'url' => realpath(__DIR__ . '/../../services/eventlist/index.php')
	],
	'userlist' => [
		'url' => realpath(__DIR__ . '/../../services/userlist/index.php')
	],
    'visitors' => [
        'url' => realpath(__DIR__ . '/../../services/visitors/index.php')
    ],
    'top_referers' => [
        'url' => realpath(__DIR__ . '/../../services/top_referers/index.php')
    ]
];