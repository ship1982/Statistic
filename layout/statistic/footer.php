<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 11:20
 */

if(is_file(__DIR__.'/../../lib/common/common.php')){
    include_once(__DIR__.'/../../lib/common/common.php');
    $url_host = $GLOBALS['conf']['web'];
}else{
    $url_host = '';
}

echo common_setFooter([
    $url_host.'/js/jquery.min.js',
    '<script>window.jQuery || document.write(\'<script src="'.$url_host.'/js/jquery.min.js"><\/script>\')</script>',
    $url_host . '/bootstrap-3.3.7/js/bootstrap.min.js',
    $url_host.'/datepicker/js/bootstrap-datepicker.js',
    $url_host.'/datetimepicker/js/jquery.datetimepicker.full.js',
    $url_host.'/treeview/js/jquery.bootstrap-treeview.js',
    $url_host.'/js/holder.min.js',
    $url_host.'/js/ie10-viewport-bug-workaround.js',
    $url_host.'/js/jquery.table2excel.js',
    $url_host.'/js/main.js',
    $url_host.'/bundles/js/bootstrap-select.js',
    $url_host.'/bundles/js/defaults-ru_RU.js'
]);