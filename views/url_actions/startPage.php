<?php
common_setAloneView('statistic/inc/navbar');

$data = (!empty($params['result']) ? $params['result'] : []);
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
      <h1 class="page-header">URL-акции</h1>
        <form method="post">
            <fieldset>
                <button type="submit" class="btn btn-primary" name="seances" value="1">Сеансы</button>
                <button type="submit" class="btn btn-primary" name="events" value="1">События (Заказ "Интернет")</button>
                <button type="submit" class="btn btn-primary" name="events" value="2">События (прочие услуги)</button>
              <?php
              if (!empty($data))
              {
                ?>
                  <button type="button" class="btn btn-primary" onclick="export_excel('table2excel','UrlActions');">
                      Скачать
                  </button>
                <?php
              }
              ?>
            </fieldset>
        </form><br>
      <?php

      if (!empty($data))
      {
        ?>
          <table class="table table-striped table2excel">
              <thead>
              <tr>
                <?php
                for ($i = 0; $i < count($params['resultColNames']); $i++)
                {
                ?>
                    <th><?= $params['resultColNames'][$i] ?></th>
                <?php
                }
                ?>
              </tr>
              </thead>
              <tbody>
              <?php
              foreach ($data as $key=>$value)
              {
                ?>
                  <tr>
                      <td><?= $value['provider'] ?></td>
                      <td><?= $value['url'] ?></td>
                      <td><?= $value['cnt'] ?></td>
                  </tr>
                <?php
              }
              ?>
              </tbody>
          </table>
        <?php
      }
      elseif (!empty($_POST))
      {
        ?>
          <h3><i>Ничего не найдено</i></h3>
        <?php
      }
      ?>

    </div>
    <!--/main-->
  </div>
  <!--/row-->
</div>
<!--/container-fluid-->
