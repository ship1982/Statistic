<?php

// подлючаем API конструктора форм
common_inc('html/form', 'Form');
$form = new Form();
echo $form->run([
  'method' => 'POST',
  'name' => 'get_info_by_user_type',
  'id' => 'get_info_by_user_type'
]);

// проверяем, что список доменов есть
$domainList = [];
if(!empty($params['domains'])){
    $domainList = $params['domains'];
    if(empty($domainList[0])){
        $domainList[0] = '-- выберите домен из списка --';
        ksort($domainList);
    }
}

// проверяем, что список пользовательских типов есть
$usertypesList = [];
if(!empty($params['usertypes']))
  $usertypesList = $params['usertypes'];

// проверяем, что список конверсий есть
$conversionList = [];
if(!empty($params['conversion']))
  $conversionList = $params['conversion'];

?>


<?php if(!empty($params['model']['error'])) { ?>
	<div class="alert alert-warning" role="alert">
	<?php foreach ($params['model']['error'] as $error) { ?>
		<p><?php echo $error; ?></p>
	<?php } ?>
	</div>
<?php } ?>


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
  <hr>
  <fieldset>
		<label for="group_of_user">Последний домен:</label>
			
			<?php

      echo $form->field(
        'select', [
          'name' => 'sequencer_domain[]',
          'class' => 'form-control',
          'id' => 'sequencer_domain'
        ], '', $domainList
      );

      ?>

		<small>В данном фильтре можно выбрать последний домен, на который заходил пользователь.</small>
	</fieldset>
  <hr>
	<fieldset>
		<label for="group_of_user">Тип пользователя:</label>
			
      <?php

      echo $form->field(
        'select', [
          'name' => 'group_of_user[]',
          'onchange' => 'filterSequencer.checkUserType($(this))',
          'data-live-search' => 'true',
          'data-dropup-auto' => 'false',
          'multiple' => 1,
          'class' => 'form-control selectpicker',
          'id' => 'group_of_user'
        ], '', $usertypesList
      );

      ?>

		<small>В данном фильтре можно выбрать тип пользователя, по которому в дальнейшем будет происходить фильтрация последовательностей.</small>
	</fieldset>
	<fieldset>
		<div class="checkbox">
      <label>

        <?php

        echo $form->field(
          'input', [
            'name' => 'count_of_site',
            'type' => 'checkbox',
            'value' => 1
          ]
        );

        ?>

         Убрать пути, состояще из одного сайта
      </label>
    </div>
    <small>С помощью данной галочки можно убрать пути, в которых фигурирует только один сайт (имеется ввиду полностью url) и отобразить пути по сайту, в которых более одного шага.</small>
	</fieldset>
	<fieldset id="type_of_conversion_fieldset">
		<label for="type_of_conversion">Тип конверсии:</label>

      <?php

      echo $form->field(
        'select', [
          'name' => 'type_of_conversion[]',
          'data-live-search' => 'true',
          'data-dropup-auto' => 'false',
          'class' => 'selectpicker form-control',
          'id' => 'type_of_conversion'
        ], '', $conversionList
      );

      ?>
			
		<small>
		<ul>
			<li>при первом взаимодействии - конверсия на первом и втором шаге</li>
			<li>при срединном взаимодействии - конверсия на шаге, отличном от первого и второго, но не последнем</li>
			<li>на последнем шаге - конверсия на последнем шаге пути пользователя</li>
		</ul>
		</small>
	</fieldset>
	<fieldset>
		<br>
		<button type="submit" class="btn btn-primary">Показать</button>
		<button type="button" class="btn btn-primary" onclick="export_excel('table2excel', 'Последовательности_' + document.getElementById('dp1').value + '-' + document.getElementById('dp2').value);">Скачать</button>
	</fieldset>

  <?php echo $form->end(); ?>