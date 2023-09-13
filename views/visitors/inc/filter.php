<?php

// подлючаем API конструктора форм
common_inc('html/form', 'Form');
$form = new Form();
echo $form->run([
  'method' => 'POST',
  'name' => 'filter-visitors'
]);

// проверяем каналы
$channels = [];
if(!empty($params['_channels']))
	$channels = $params['_channels'];

// проверяем список групп из БД
$groupFromDB = [];
if(!empty($params['groupFromDB']))
	$groupFromDB = $params['groupFromDB'];

// провеярем поля для группировки
$grouping = [0 => '-- без группировки --'];
if(!empty($params['grouping']))
	$grouping = array_merge(
		$grouping,
		$params['grouping']
	);

// провеярем поля для количества элементов на странице
$countOfPage = [];
if(!empty($params['count']))
	$countOfPage = $params['count'];

// вывод партнеров
?>

<?php
if(!empty($params['error']))
{
    echo '<div class="alert alert-warning" role="alert">';
    echo common_showError($params['error']);
    echo '</div>';
}
?>
<input type="hidden" name="submit-form" value="1">
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

<!-- form-group row -->
<div class="form-group row">
  <label for="partner-field" class="col-sm-2 col-form-label">Домен</label>
  <div class="col-sm-10">

		<?php
		echo $form->field(
			'input', [
				'name' => 'domain_text',
				'class' => 'form-control',
				'id' => 'domain-field',
				'value' => $form->getValue('domain_text')
			]
		);

		?>

  </div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <label for="partner-field" class="col-sm-2 col-form-label">Ссылка</label>
  <div class="col-sm-10">

		<?php
		echo $form->field(
			'input', [
				'name' => 'link_text',
				'class' => 'form-control',
				'id' => 'link-field',
				'value' => $form->getValue('link_text')
			]
		);

		?>

  </div>
</div>
<!-- /form-group row -->


<!-- form-group row -->
<div class="form-group row">
  <label for="filter_channels-field" class="col-sm-2 col-form-label">Канал</label>
  <div class="col-sm-10">
		<?php

		// вывод каналов
		echo $form->field(
			'select', [
				'name' => 'filter_channels[]',
				'class' => 'form-control selectpicker',
				'id' => 'filter_channels-field',
				'multiple' => 1
			], '', $channels
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- UTM -->
<!-- form-group row -->
<div class="form-group row">
  <label for="utm_campaign-field" class="col-sm-2 col-form-label">Utm campaign</label>
  <div class="col-sm-10">
		<?php

		// utm_campaign
		echo $form->field(
			'input', [
				'name' => 'utm_campaign',
				'class' => 'form-control selectpicker',
				'id' => 'utm_campaign-field',
				'value' => $form->getValue('utm_campaign')
			] 
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <label for="utm_content-field" class="col-sm-2 col-form-label">Utm content</label>
  <div class="col-sm-10">
		<?php

		// utm_content
		echo $form->field(
			'input', [
				'name' => 'utm_content',
				'class' => 'form-control selectpicker',
				'id' => 'utm_content-field',
				'value' => $form->getValue('utm_content')
			] 
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <label for="utm_term-field" class="col-sm-2 col-form-label">Utm term</label>
  <div class="col-sm-10">
		<?php

		// utm_term
		echo $form->field(
			'input', [
				'name' => 'utm_term',
				'class' => 'form-control selectpicker',
				'id' => 'utm_term-field',
				'value' => $form->getValue('utm_term')
			] 
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <label for="utm_medium-field" class="col-sm-2 col-form-label">Utm medium</label>
  <div class="col-sm-10">
		<?php

		// utm_medium
		echo $form->field(
			'input', [
				'name' => 'utm_medium',
				'class' => 'form-control selectpicker',
				'id' => 'utm_medium-field',
				'value' => $form->getValue('utm_medium')
			] 
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <label for="utm_source-field" class="col-sm-2 col-form-label">Utm source</label>
  <div class="col-sm-10">
		<?php

		// utm_source
		echo $form->field(
			'input', [
				'name' => 'utm_source',
				'class' => 'form-control selectpicker',
				'id' => 'utm_source-field',
				'value' => $form->getValue('utm_source')
			] 
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <div class="header-events-part">
  	Группировка
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="group-field" class="col-sm-2 col-form-label">Группировать записи по:</label>
  <div class="col-sm-10">
		<?php

		// группировка
		echo $form->field(
			'select', [
				'name' => 'group[]',
				'class' => 'form-control selectpicker',
				'id' => 'group-field'
			], '', $grouping
		);

		?>
	</div>
</div>
<!-- /form-group row -->

<!-- form-group row -->
<div class="form-group row">
  <div class="header-events-part">
  	Дополнительные параметры
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="count-field" class="col-sm-2 col-form-label">Строк на странице:</label>
  <div class="col-sm-10">
		<?php

		// строк на странице
		echo $form->field(
			'select', [
				'name' => 'count',
				'class' => 'form-control selectpicker',
				'id' => 'group-field',
			], '', $countOfPage
		);

		?>
	</div>
</div>
<!-- /form-group row -->


<button type="submit" class="btn btn-primary">Найти</button>


<?php echo $form->end(); ?>

<br>
<br>