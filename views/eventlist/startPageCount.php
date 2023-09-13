<?php

common_setAloneView('statistic/inc/navbar');


?>

<style>
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
			    <table class="table table-responsive">
                    <thead>
                        <tr>
                            <?php

                            // заголовок таблицы состоит из поля группировки и количества
                            if(!empty($params['header']))
                            {
                                // смотрим все поля заголовка с именованием
                                foreach ($params['header'] as $code => $row)
                                {
                                    if(!empty($params['group'][0])
                                        && $code == $params['group'][0]
                                        && !empty($row)
                                    )
                                        echo '<th>' . $row . '</th>';
                                }
                            }

                            ?>

                            <th>Количество</th>
                            <th>Количество Ботов</th>
                            <th>% Ботов</th>
                            <th>Количество Адблоков</th>
                            <th>% Адблоков</th>
                        </tr>
                    </thead>
		        <tbody>
                <?
			        if(!empty($params['items']))
			        {
			        	for ($i=0; $i < $ic = count($params['items']); $i++)
			        	{ 
			        		echo '<tr class="table_row">';
			        		foreach ($params['items'][$i] as $index => $item)
                  {
                    if (!empty($params['header'][$index]))
                    {
                      ?>
                        <td data-id="<?= $index; ?>"><?= $item; ?></td><?
                    }
                  }
				        	$cnt = common_getVariable($params,['items', $i, 'cnt'], 0);
                  $c_bots = common_getVariable($params,['items', $i, 'c_bots'], 0);
				        	$c_ads = common_getVariable($params,['items', $i, 'c_ads'], 0);
				        	// количество выводим обязательно по умолчанию
				        	echo '<td>' . $cnt . '</td>';
				        	echo '<td>' . $c_bots . '</td>';
				        	echo '<td>' . common_percent_from_number($cnt, $c_bots) . '</td>';
				        	echo '<td>' . $c_ads . '</td>';
				        	echo '<td>' . common_percent_from_number($cnt, $c_ads) . '</td>';
				        	echo '</tr>';
			        	}
			        }
                    ?>
                    </tbody>
                </table>
            </div>

        </div>
        <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->