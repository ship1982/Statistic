<?php

/**
 * Указыввает операторы, которые нужно применять к полю для фильтрации
 * Если поля здесь нет, значит к полю будет применен оператор =
 */
return [
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
	'domain_text' => [
		'operation' => 'REGEXP'
	],
	'link_text' => [
		'operation' => 'REGEXP'
	]
];