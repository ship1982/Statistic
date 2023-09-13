<?php

/**
 * List of services or libs, which using table dirty.
 * Index of this element, is a order in bitmask in MYSQL field position.
 * Each libs or services, which using this file ,ust be have the position in the comment,
 * and using it for building mask for get query.
 * Index must not be changed!
 */
return [
	'botChecker',
	'ipChecker', // 2
	'topCity', // 4
	'topPage', // 8
	'topProvider', // 16
	'sequencer/form2link', // 32
	'sequencer/userType', // 64
	'sequencer/usersequencer', // 128
	'topDetalizer/topDetalizer', // 256
	'topDetalizer/topDetalizerCity', // 512
	'topDetalizer/topDetalizerProvider' // 1024
];