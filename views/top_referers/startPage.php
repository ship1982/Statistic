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
      <h1 class="page-header">TOP Referers</h1>
      <!--alert-->
      <div class="alert alert-info" role="alert">
        Рейтинг источников посетителей для партнерского домена. Составляется на основе данных о переходах по ссылке с внешнего домена на партнерский.<br>
        Каждый переход по ссылке +1 к рейтингу внешнего домена.
      </div>
      <!--/alert-->

      <?php

      common_setAloneView('top_referers/inc/filter', [
          'error' => $params['error'],
          'reportTypes' => $params['reportTypes'],
          'partnerDomains' => $params['partnerDomains'],
          'countOfPage' => $params['countOfPage']
      ]);

      common_setAloneView('top_referers/inc/result', [
          'reportTypes' => $params['reportTypes'],
          'result' => $params['result'],
          'filter' => $params['filter']
      ]);

      ?>

    </div>
    <!--/main-->
  </div>
  <!--/row-->
</div>
<!--/container-fluid-->
