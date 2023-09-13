		</div>
    <!--/row-->
</div>
<!--/container-fluid-->

<?php

echo common_setFooter([
    '<script>window.jQuery || document.write(\'<script src="'.$GLOBALS['conf']['web'].'/js/jquery.min.js"><\/script>\')</script>',
    $GLOBALS['conf']['web'] . '/bootstrap-3.3.7/js/bootstrap.min.js',
    $GLOBALS['conf']['web'].'/datepicker/js/bootstrap-datepicker.js',
    $GLOBALS['conf']['web'].'/datetimepicker/js/jquery.datetimepicker.full.js',
    $GLOBALS['conf']['web'].'/treeview/js/jquery.bootstrap-treeview.js',
    $GLOBALS['conf']['web'].'/js/holder.min.js',
    $GLOBALS['conf']['web'].'/js/ie10-viewport-bug-workaround.js',
    $GLOBALS['conf']['web'].'/js/jquery.table2excel.js',
    $GLOBALS['conf']['web'].'/js/main.js',
    $GLOBALS['conf']['web'].'/bundles/js/bootstrap-select.js',
    $GLOBALS['conf']['web'].'/bundles/js/defaults-ru_RU.js'
]);