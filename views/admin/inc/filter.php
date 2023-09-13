<?php

// подлючаем API конструктора форм
common_inc('html/form', 'Form');
$form = new Form();
echo $form->run([
    'method' => 'POST',
    'name' => 'filter-event'
]);

?>

<!--form-group-->
<div class="form-group">
    <div class="input-group date" data-provide="datepicker">
      <?php

      echo $form->field(
          'input', [
              'name' => 'from',
              'class' => 'form-control',
              'id' => 'dp1',
              'placeholder' => "От",
              'value' => $form->getValue('from')
          ]
      );

      ?>
        <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
        </div>
    </div>
    <small class="text-muted">Выберите дату, с которой необходимо отобразить статистику.</small>
</div>
<!--/form-group-->

<!--form-group-->
<div class="form-group">
    <div class="input-group date" data-provide="datepicker">
      <?php

      echo $form->field(
          'input', [
              'name' => 'to',
              'class' => 'form-control',
              'id' => 'dp2',
              'placeholder' => 'До',
              'value' => $form->getValue('to')
          ]
      );

      ?>
        <div class="input-group-addon">
            <span class="glyphicon glyphicon-th"></span>
        </div>
    </div>
    <small class="text-muted">Выберите дату, до которой (включительно) необходимо отобразить статистику.</small>
</div>
<!--/form-group-->

<div style="margin-top: 20px"></div>
<button type="submit" class="btn btn-primary">Найти</button>
<div style="margin-bottom: 20px"></div>