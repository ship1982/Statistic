<h1 class="page-header">Реклама</h1>
<!-- navbar navbar-default -->
<nav class="navbar navbar-default">
    <!--container-fluid-->
    <div class="container-fluid">
        <!--navbar-header-->
        <div class="navbar-header">
            <div type="button" class="btn btn-success navbar-btn">
                <a href="/ads/add/">Добавить рекламу</a>
            </div>
        </div>
        <!--/navbar-header-->
    </div>
    <!--/container-fluid-->
</nav>
<!-- /navbar navbar-default -->

<?php

// фильтр
/*common_setAloneView(
	'eventlist/inc/filter',
	$params
);*/

?>

<div class="panel panel-default">
    <div class="panel-heading">Рекламные объявления</div>
    <!--table-->
    <table class="table table-responsive">
        <thead>
        <tr>
            <th></th>

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

        if (!empty($params['items'][0]))
        {
          for ($i = 0; $i < $ic = count($params['items']); $i++)
          {
            echo '<tr class="table_row">';
            echo '<td>
                <span onclick="common.model.update(\'' . common_setValue($params, 'url') . 'update/' . $params['items'][$i]['id'] . '/\',event)" class="editable_class glyphicon glyphicon-pencil" aria-hidden="true"></span>
                <span onclick="common.model.delete(\'' . common_setValue($params, 'url') . 'delete/' . $params['items'][$i]['id'] . '/\',event)" class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                </td>';
            foreach ($params['header'] as $key => $row)
            {
              if (!empty($row['name']) && !empty($row['in_list']))
              {
                if ('script' == $key)
                {
                  echo '<td><textarea readonly="1">' . $params['items'][$i][$key] . '</textarea></td>';
                }
                else
                {
                  echo '<td>' . $params['items'][$i][$key] . '</td>';
                }
              }
            }
            echo '</tr>';
          }
        }

        ?>

        </tbody>
    </table>
</div>