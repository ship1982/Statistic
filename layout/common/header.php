<?php

echo common_setHeader(
    'Статистика сайта', [
    $GLOBALS['conf']['web'].'/bootstrap-3.3.7/css/bootstrap.min.css',
    $GLOBALS['conf']['web'].'/css/ie10.css',
    $GLOBALS['conf']['web'].'/css/dashboard.css',
    $GLOBALS['conf']['web'].'/datepicker/css/datepicker.css',
    $GLOBALS['conf']['web'].'/datetimepicker/css/jquery.datetimepicker.min.css',
    $GLOBALS['conf']['web'].'/treeview/css/jquery.bootstrap-treeview.css',
    $GLOBALS['conf']['web'].'/bundles/css/bootstrap-select.min.css',
    $GLOBALS['conf']['web'].'/bundles/css/font-awesome.min.css'
], [
        $GLOBALS['conf']['web'].'/js/jquery.min.js',
        '<!--[if lt IE 9]><script src="'.$GLOBALS['conf']['web'].'/js/ie8-responsive-file-warning.js"></script><![endif]-->',
        $GLOBALS['conf']['web'].'/js/ie-emulation-modes-warning.js',
        $GLOBALS['conf']['web'].'/bundles/js/html5shiv.min.js',
        $GLOBALS['conf']['web'].'/bundles/js/respond.min.js'
    ]
);

common_setAloneView('statistic/inc/navbar');

?>

<!--container-fluid-->
<div class="container-fluid">
  <!--row-->
  <div class="row">
    <!--col-sm-3 col-md-2 sidebar-->
    <div class="col-sm-3 col-md-2 sidebar">
      <?php common_setAloneView('menu/menu'); ?>
    </div>
    <!--/col-sm-3 col-md-2 sidebar-->
    <!--main-->
    <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">