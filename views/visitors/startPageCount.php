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
      <h1 class="page-header">Статистика посещений</h1>

      	<?php common_setAloneView('visitors/inc/help'); ?>

      	<?php

      	// фильтр
      	common_setAloneView(
      		'visitors/inc/filter',
      		$params
      	);
      	
      	?>

				<div class="panel panel-default">
			    <div class="panel-heading">Список событий</div>
			    <!--table-->
			    <table class="table table-responsive">
		        <thead>
                    <tr>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Общее</th>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Боты</th>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Адблоки</th>
                    </tr>
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
					        		echo '<th style="border-left: 1px solid;">' . $row . '</th>';
			        		}
			        	}
			        	?>
				        <th style="border-left: 1px solid;">Количество</th>
                        <th style="border-left: 1px solid;">Количество:</th>
                        <th style="border-right: 1px solid;">%:</th>
                        <th style="border-left: 1px solid;">Количество:</th>
                        <th style="border-right: 1px solid;">%:</th>
			        </tr>
		        </thead>
		        <tbody>

			        <?php

			        if(!empty($params['items']))
			        {
			        	for ($i=0; $i < $ic = count($params['items']); $i++)
			        	{ 
			        		echo '<tr class="table_row">';
			        		foreach ($params['header'] as $key => $trash)
				        	{
				        		// выбираем поля исходя из группировки
				        		if(!empty($params['group'][0])
			        				&& $key == $params['group'][0]
			        				&& !empty($trash)
			        			)
                                {
                                  echo '<td>' . $params['items'][$i][$key] . '</td>';
                                }
				        	}
				        	// количество выводим обязательно по умолчанию
				        	echo '<td>' . common_setValue($params['items'][$i], 'cnt') . '</td>';1
                          ?>
                            <td>
                              <?php
                              if (!empty($data[$i]['c_bots']))
                              {
                                echo $data[$i]['c_bots'];
                              }
                              else
                              {
                                echo 0;
                              }
                              ?>
                            </td>
                            <td>
                              <?php
                              if (!empty($data[$i]['c_bots']))
                              {
                                echo common_percent_from_number($data[$i]['c'], $data[$i]['c_bots']);
                              }
                              else
                              {
                                echo 0;
                              }
                              ?>
                            </td>
                            <td>
                              <?php
                              if (!empty($data[$i]['c_ads']))
                              {
                                echo $data[$i]['c_ads'];
                              }
                              else
                              {
                                echo 0;
                              }
                              ?>
                            </td>
                            <td>
                              <?php
                              if (!empty($data[$i]['c_ads']))
                              {
                                echo common_percent_from_number($data[$i]['c'], $data[$i]['c_ads']);
                              }
                              else
                              {
                                echo 0;
                              }
                              ?>
                            </td>
                          <?php

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