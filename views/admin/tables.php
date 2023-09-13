<h1 class="page-header">Данные по таблицам</h1>


<?php

// фильтр
common_setAloneView(
    'admin/inc/filter'
);

?>

<div class="panel panel-default">
    <div class="panel-heading">Полный список</div>
    <!--table-->
    <table class="table table-responsive">
        <thead>
        <tr>

          <?php

          if (!empty($params['header']))
          {
            foreach ($params['header'] as $row)
            {
              if (!empty($row['name']) && !empty($row['in_list']))
              {
                echo '<th>' . $row['name'] . '</th>';
              }
            }
          }

          ?>

        </tr>
        </thead>
        <tbody>

        <?php

        // common_dd($params['header']);

        if (!empty($params['items']))
        {
          foreach ($params['items'] as $table => $info)
          {
            echo '<tr class="table_row">';
            if (!empty($params['header']['table']))
            {
              echo '<td>' . $table . '</td>';
            }
            if (!empty($params['header']['size']))
            {
              echo '<td>' . $info['size'] . '</td>';
            }
            if (!empty($params['header']['rows']))
            {
              echo '<td>' . number_format($info['rows'], 0, ' ', ' ') . '</td>';
            }
            echo '</tr>';
          }
        }

        ?>

        </tbody>
    </table>
</div>