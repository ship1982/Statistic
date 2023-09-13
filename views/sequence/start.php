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
            <h1 class="page-header">Последовательности</h1>
            
            <?php

            common_setAloneView('sequence/inc/filter', [
                'model' => $params['model'],
                'domains' => $params['domains'],
                'usertypes' => $params['usertypes'],
                'conversion' => $params['conversion']
            ]);
                        
            common_setAloneView('sequence/inc/content', [
                'model' => $params['model']
            ]);
            
            ?>
            
            
        </div>
        <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->