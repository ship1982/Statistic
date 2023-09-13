<?php

/**
 * Указыввает операторы, которые нужно применять к полю для фильтрации
 * Если поля здесь нет, значит к полю будет применен оператор =
 */
return [
	'title' => [
		'operation' => 'REGEXP'
	],
	'keywords' => [
		'operation' => 'REGEXP'
	],
	'description' => [
		'operation' => 'REGEXP'
	],
	'event_type' => [
		'operation' => 'REGEXP'
	],
	'event_value' => [
		'operation' => 'REGEXP'
	],
	'event_category' => [
		'operation' => 'REGEXP'
	],
	'event_label' => [
		'operation' => 'REGEXP'
	],
	'utm_campaign' => [
		'operation' => 'REGEXP'
	],
	'utm_content' => [
		'operation' => 'REGEXP'
	],
	'utm_term' => [
		'operation' => 'REGEXP'
	],
	'utm_medium' => [
		'operation' => 'REGEXP'
	],
	'utm_source' => [
		'operation' => 'REGEXP'
	],
	'domain' => [
		'operation' => 'REGEXP'
	],
	'link' => [
		'operation' => 'REGEXP'
	]
];