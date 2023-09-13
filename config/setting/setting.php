<?php

/**
 * Configuration file for create a pixel js file.
 * File would be create in directory stat/web and name will be a stat.js
 * In production this file must be a compress. For example it will be a https://closure-compiler.appspot.com/home
 * After compress, replace s.js file in this (stat/web) directory.
 *
 * Key of array is a name of configuration.
 * Value - is a special tags in js code, which will be replaced by current value.
 */
return [
    'zionec' => [
        'protocol' => 'http',
        'plugin_url' => 'stat.mgts.zionec.ru',
        'gif_url' => 'stat.mgts.zionec.ru/pixel.gif',
        'pixel' => '//stat.mgts.zionec.ru/stat.js',
        'clear' => false
    ],
    'mgts' => [
        'protocol' => 'https',
        'gif_url' => 'count.mgts.ru/pixel.gif',
        'plugin_url' => 'count.mgts.ru',
        'pixel' => '//count.mgts.ru/s.js',
        'clear' => true
    ]
];