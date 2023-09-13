<?php
common_setAloneView('statistic/inc/navbar');
?>
<style>
  fieldset.scheduler-border {
      border: 1px groove #ddd !important;
      padding: 0 1.4em 1.4em 1.4em !important;
      margin: 0 0 1.5em 0 !important;
      -webkit-box-shadow:  0px 0px 0px 0px #000;
      box-shadow:  0px 0px 0px 0px #000;
  }

  legend.scheduler-border {
      cursor: pointer;
      width:inherit; /* Or auto */
      padding:0 10px; /* To give a bit of padding on the left and right */
      border-bottom:none;
  }
  input.btn-add-cond{
      float:right;
  }
  #form_construct{
      display:inline-block;
  }
  .cursor_poiner{
      cursor: pointer;
  }
  .the-table {
      table-layout: fixed;
      word-wrap: break-word;
  }
</style>

<div class="container-fluid">
  <div class="row">
    <div class="col-sm-3 col-md-2 sidebar">
        <?php common_setAloneView('menu/menu'); ?>
    </div>
    <!--main-->
    <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
      <h1 class="page-header">Конструктор условий для получения статистики по пользователю.</h1>
      <div class="alert alert-info" role="alert">
        В данном разделе содержится конструктор условий, который позволяет подготовить условия,
        применяемые для получения статистики по пользователю.
      </div>
			<div class="header">
				<input type="button" class="btn btn-primary" onclick="window.location = '/condition_user_property/add/';" value="Добавить группу условий">
      </div>
			<br />
      <!--panel panel-default-->
      <div class="panel panel-default">
        <div class="panel-heading">Условия</div>
        <!--table-->
        <table class="table .table-condensed the-table">
          <thead>
            <tr>
              <th>Действие</th>
              <th>ID</th>
              <th>Название</th>
              <th>Условия</th>
            </tr>
          </thead>
          <tbody>
              <?php
              common_inc('misk_sequence');

              $rsTable = getUserPropertyCond();
              $empty = true;
              while ($arTable = mysqli_fetch_assoc($rsTable)) {
                  $empty = false;
                  echo '
                <tr>
                    <td>
                        <span onclick="common.conditions.update(\'' . $arTable['id'] . '\')" class="cursor_poiner glyphicon glyphicon-pencil" aria-hidden="true"></span>
                        <!--<span onclick="if(confirm(\'Вы действительно желаете удалить условие ' . $arTable['id'] . ' ?\')){common.conditions.delete(\'' . $arTable['id'] . '\');}" class="cursor_poiner glyphicon glyphicon-remove" aria-hidden="true"></span>-->
                    </td>
                    <td>' . $arTable['id'] . '</td>
                    <td>' . $arTable['name'] . '</td>
                    <td>' . $arTable['json_cond'] . '</td>
                </tr>';
              }

              if ($empty) {
                  echo '<tr><td colspan="3" align="center">Не создано ни одного условия.</td></tr>';
              }
              ?>
          </tbody>
        </table>
        <!--/table-->
      </div>
      <!--/panel panel-default-->

    </div>
    <!--/main-->
  </div>
</div>