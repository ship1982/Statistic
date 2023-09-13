<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 11:19
 */
if(is_file(__DIR__.'/../../lib/common/common.php')){
  include_once(__DIR__.'/../../lib/common/common.php');
  $url_host = $GLOBALS['conf']['web'];
}
else
  $url_host = '';

echo common_setHeader(
  'Статистика сайта', [
  $url_host.'/bootstrap-3.3.7/css/bootstrap.min.css',
  $url_host.'/css/ie10.css',
  $url_host.'/css/dashboard.css',
  $url_host.'/datepicker/css/datepicker.css',
  $url_host.'/datetimepicker/css/jquery.datetimepicker.min.css',
  $url_host.'/treeview/css/jquery.bootstrap-treeview.css',
  $url_host.'/bundles/css/bootstrap-select.min.css',
  $url_host.'/bundles/css/font-awesome.min.css'
], [
    '<!--[if lt IE 9]><script src="'.$url_host.'/js/ie8-responsive-file-warning.js"></script><![endif]-->',
    $url_host . '/js/ie-emulation-modes-warning.js',
    $url_host . '/bundles/js/html5shiv.min.js',
    $url_host . '/bundles/js/respond.min.js'
  ]
);