<?php
// подлючаем API конструктора форм
common_inc('html/form', 'Form');
$form = new Form();
echo $form->run([
    'method' => 'POST',
    'name' => 'filter-top_referers'
]);
?>

<h2 class="sub-header">Настройка отчета:</h2>

<?php
if (!empty($params['error']))
{
  echo '<div class="alert alert-warning" role="alert">';
  echo common_showError($params['error']);
  echo '</div>';
}
?>

<input type="hidden" name="run" value="1">

<!--form-group-->
<fieldset>
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
</fieldset>
<!--/form-group-->

<!--form-group-->
<fieldset>
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
</fieldset>
<!--/form-group-->

<hr>

<!-- form-group row -->
<fieldset>
    <label for="report_type">Тип отчета:</label>
      <?php

      // тип отчета
      echo $form->field(
          'select', [
          'name' => 'report_type',
          'class' => 'form-control selectpicker',
          'id' => 'report_type-field'
      ], '', $params['reportTypes']
      );

      ?>
    <small>Источники внешних ссылок либо среди партнерских доменов, либо среди всех остальных доменов (исключая партнерские).</small>
</fieldset>
<!-- /form-group row -->

<hr>

<!-- form-group row -->
<fieldset>
    <label for="partner_domains">Партнерские домены:</label>
      <?php

      // вывод списка партнерских доменов
      echo $form->field(
          'select', [
          'name' => 'partner_domains[]',
          'data-live-search' => 'true',
          'class' => 'form-control selectpicker',
          'id' => 'partner_domains-field',
          'multiple' => 1
      ], '', $params['partnerDomains']
      );

      ?>
    <small>Подсчет рейтинга рефереров для одного или нескольких партнерских доменов</small>
</fieldset>
<!-- /form-group row -->

<!-- form-group row -->
<fieldset>
    <div class="checkbox">
        <label>

          <?php

          echo $form->field(
              'input', [
                  'name' => 'is_cross',
                  'type' => 'checkbox',
                  'value' => 1
              ]
          );

          ?>

            Отобразить рейтинг по пересечениям доменов
        </label>
    </div>
</fieldset>
<!-- /form-group row -->

<hr>

<!-- form-group row -->
<fieldset>
    <label for="count-field">Строк на странице:</label>
    <?php

      // строк на странице
      echo $form->field(
          'select', [
          'name' => 'count',
          'class' => 'form-control selectpicker',
          'id' => 'group-field',
      ], '', $params['countOfPage']
      );

    ?>
</fieldset>
<!-- /form-group row -->

<br>

<fieldset>
    <button type="submit" class="btn btn-primary">Показать</button>
    <button type="button" class="btn btn-primary" onclick="export_excel('table2excel','TopReferers_' + document.getElementById('dp1').value + '-' + document.getElementById('dp2').value);">Скачать</button>
</fieldset>

<?php echo $form->end(); ?>