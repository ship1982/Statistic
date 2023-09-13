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
			    <div class="panel-heading">Общее количество</div>
			    <!--table-->
			    <table class="table table-responsive">
		        <thead>
			        <tr>
			        	<th>Группа</th>
				        <th>Количество</th>
			        </tr>
		        </thead>
		        <tbody>

		        <?php if(!empty($params['all'])) { ?>
		        	<?php foreach ($params['all'] as $group => $count) { ?>
		        		<tr>
		        		<td><?php echo $group; ?></td>
		        		<td><?php echo $count; ?></td>
		        		</tr>
		        	<?php } ?>
		        <?php } ?>

			      </tbody>
			    </table>
				</div>

				<div class="panel panel-default">
			    <div class="panel-heading">Пересечения</div>
					<!--table-->
			    <table class="table table-responsive">
		        <thead>
			        <tr>
			        	<th>Пересечение</th>
				        <th>Количество</th>
			        </tr>
		        </thead>
		        <tbody>

		        <?php if(!empty($params['diff'])) { ?>
		        	<?php foreach ($params['diff'] as $cross => $count) { ?>
		        		<tr>
		        		<td><?php echo $cross; ?></td>
		        		<td><?php echo $count; ?></td>
		        		</tr>
		        	<?php } ?>
		        <?php } ?>

			      </tbody>
			    </table>
			  </div>
            
      </div>
      <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->