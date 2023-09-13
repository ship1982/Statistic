<?php

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
            <h1 class="page-header">Статистика по top100</h1>
            
            <?php

            common_setAloneView('top/inc/filter', [
                'error' => $params['error'],
                'type' => 'domain'
            ]);
      
            common_setAloneView('top/content', [
                'result' => common_setValue($params, 'result'),
                'group' => common_setValue($params, 'group'),
                'filter' => common_setValue($params, 'filter')
            ]);
            
            ?>
            
            
        </div>
        <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->