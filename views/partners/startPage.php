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
      <h1 class="page-header">Партнеры</h1>

				<!-- navbar navbar-default -->
        <nav class="navbar navbar-default">
			    <!--container-fluid-->
			    <div class="container-fluid">
		        <!--navbar-header-->
		        <div class="navbar-header">
	            <div type="button" class="btn btn-success navbar-btn">
	            	<?php
	            	$param = common_setValue($_GET, 'id');
	            	if(!empty($param)) $param = '?id=' . $param;
	            	?>
	              <a href="/partners/add/<?php echo $param ?>">Добавить партнера</a>
	            </div>
		        </div>
		        <!--/navbar-header-->
			    </div>
			    <!--/container-fluid-->
				</nav>
				<!-- /navbar navbar-default -->

				<div class="panel panel-default">
			    <div class="panel-heading">Список партнеров</div>
			    <!--table-->
			    <table class="table">
		        <thead>
			        <tr>
			        	<th></th>
			        
			        	<?php 

			        	if(!empty($params['header']))
			        	{
			        		foreach ($params['header'] as $row)
			        		{
			        			if(!empty($row))
			        				echo '<th>' . $row . '</th>';
			        		}
			        	}

			        	?>
				        
			        </tr>
		        </thead>
		        <tbody>

			        <?php

			        if(!empty($params['items']))
			        {
			        	for ($i=0; $i < $ic = count($params['items']); $i++)
			        	{ 
			        		echo '<tr class="table_row" onclick="location.href=/partners/">';
			        		echo '<td>
		                        <span onclick="common.partners.update(\'' . $params['items'][$i]['id'] . '\',event)" class="editable_class glyphicon glyphicon-pencil" aria-hidden="true"></span>
		                        <span onclick="common.partners.delete(\'' . $params['items'][$i]['id'] . '\',event)" class="glyphicon glyphicon-remove" aria-hidden="true"></span>
		                    </td>';
			        		foreach ($params['header'] as $key => $trash)
				        	{
				        		if(!empty($trash))
				        			echo '<td>' . $params['items'][$i][$key] . '</td>';
				        	}
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