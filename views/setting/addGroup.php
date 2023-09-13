<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 31.08.16
 * Time: 15:57
 */


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
            <h1 class="page-header">Настройки групп для фильтрации</h1>


            <?php

            common_setAloneView('setting/group/list');

            ?>


        </div>
        <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->