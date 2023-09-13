<?php

common_setAloneView('statistic/inc/navbar');


?>
<style>
    .panel.panel-default, .panel-heading {
        width: 3000px;
    }

    .table.table-responsive {
        width: 3000px;
    }

    .form-group {
        margin-bottom: 5px;
    }

    .form-control {
        height: 30px;
    }
</style>
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
            <h1 class="page-header">События</h1>

          <?php common_setAloneView('eventlist/inc/help'); ?>

          <?php

          // фильтр
          common_setAloneView(
              'eventlist/inc/filter',
              $params
          );

          ?>

            <div class="panel panel-default">
                <div class="panel-heading">Список событий</div>
                <!--table-->
              <? $toExel = 0; ?>
                <table class="table table-responsive" id="to_excel">
                    <thead>
                    <tr>
                        <th></th>

                      <?php

                      if (!empty($params['header']))
                      {
                        foreach ($params['header'] as $row)
                        {
                          if (!empty($row))
                          {
                            echo '<th>' . $row . '</th>';
                          }
                        }
                      }

                      ?>

                    </tr>
                    </thead>
                    <tbody>

                    <?php

                    if (!empty($params['items']))
                    {
                      for ($i = 0; $i < $ic = count($params['items']); $i++)
                      {
                        $toExel = 1;
                        echo '<tr class="table_row">';
                        echo '<td></td>';
                        foreach ($params['header'] as $key => $trash)
                        {
                          if (!empty($trash))
                          {
                            echo '<td>' . $params['items'][$i][$key] . '</td>';
                          }
                        }
                        echo '</tr>';
                      }
                    }

                    ?>

                    </tbody>
                </table>
                <script>
                  if (<?= $toExel; ?> > 0) {
                    document.getElementById('to_excel').setAttribute('class', document.getElementById('to_excel').getAttribute('class') + ' table2excel');
                  }
                </script>
            </div>

        </div>
        <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->