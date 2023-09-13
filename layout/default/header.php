<?php

if(is_file(__DIR__.'/../../lib/common/common.php')){
    include_once(__DIR__.'/../../lib/common/common.php');
    $url_host = $GLOBALS['conf']['web'];
}else{
    $url_host = '';
}

echo common_setHeader(
    'Статистика сайта', [
    $url_host.'/js/pickme/pickmeup.min.css',
    $url_host.'/js/select2/css/select2.min.css',
    $url_host.'/bundles/main-v2.css',
    $url_host.'/bundles/css/font-awesome.min.css'
], [
        $url_host.'/js/jquery.min.js',
        $url_host.'/js/pickme/jquery.pickmeup.min.js',
        $url_host.'/js/select2/js/select2.min.js',
        $url_host.'/js/main.js'
    ]
);

echo '<style>
body{
    color: #000;
    background: 0 0;
    font: 12px/18px \'Open Sans\',"Lucida Grande","Lucida Sans Unicode",Arial,Helvetica,Verdana,sans-serif;
    overflow: hidden;
}
</style>';