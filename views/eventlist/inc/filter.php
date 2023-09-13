<?php

use \html\form\ModernForm;

// подлючаем API конструктора форм
echo ModernForm::open([
  'method' => 'POST',
  'name' => 'filter-event'
]);

// проверяем, что список партнеров есть
$partnerList = [0 => '-- выберите партнера из списка --'];
if(!empty($params['partners']))
	$partnerList = $partnerList + $params['partners'];

// проверяем каналы
$channels = [];
if(!empty($params['_channels']))
	$channels = $params['_channels'];

// проверяем список групп из БД
$groupFromDB = [];
if(!empty($params['groupFromDB']))
	$groupFromDB = $params['groupFromDB'];

// провеярем поля для группировки
// $grouping = [0 => '-- без группировки --'];
$grouping = $params['grouping'];
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
<!--form-group-->
<div class="form-group">
  <div class="input-group date" data-provide="datepicker">
	  <?= ModernForm::input('text', 'from', [
	          'class' => 'form-control',
              'id' => 'dp1',
              'placeholder' => 'От'
      ]); ?>
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
    <?= ModernForm::input('text', 'to', [
        'class' => 'form-control',
        'id' => 'dp2',
        'placeholder' => 'До'
    ]); ?>
    <div class="input-group-addon">
    <span class="glyphicon glyphicon-th"></span>
    </div>
  </div>
  <small class="text-muted">Выберите дату, до которой (включительно) необходимо отобразить статистику.</small>
</div>
<!--/form-group-->
<!-- form-group row -->
<div class="form-group row">
  <label for="partner-field" class="col-sm-2 col-form-label">Партнер</label>
  <div class="col-sm-10">
    <?= ModernForm::select('select', 'partner', $partnerList, [
        'class' => 'form-control',
        'id' => 'partner-field'
    ]); ?>
  </div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="partner-field" class="col-sm-2 col-form-label">Домен</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'domain', [
        'class' => 'form-control',
        'id' => 'domain-field'
    ]); ?>
  </div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="partner-field" class="col-sm-2 col-form-label">Ссылка</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'link', [
        'class' => 'form-control',
        'id' => 'link-field'
    ]); ?>
  </div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="filter_channels-field" class="col-sm-2 col-form-label">Канал</label>
  <div class="col-sm-10">
    <?= ModernForm::multiselect('select', 'filter_channels', $channels, [
        'class' => 'form-control',
        'id' => 'filter_channels-field',
        'multiple' => 1
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="title-field" class="col-sm-2 col-form-label">Мета-title</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'title', [
        'class' => 'form-control selectpicker',
        'id' => 'title-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="keywords-field" class="col-sm-2 col-form-label">Мета-keywords</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'keywords', [
        'class' => 'form-control selectpicker',
        'id' => 'keywords-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="description-field" class="col-sm-2 col-form-label">Мета-description</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'description', [
        'class' => 'form-control selectpicker',
        'id' => 'description-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="event_type-field" class="col-sm-2 col-form-label">Тип события</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'event_type', [
        'class' => 'form-control selectpicker',
        'id' => 'event_type-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="event_category-field" class="col-sm-2 col-form-label">Категория события</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'event_category', [
        'class' => 'form-control selectpicker',
        'id' => 'event_category-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="event_label-field" class="col-sm-2 col-form-label">Лэйбл события</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'event_label', [
        'class' => 'form-control selectpicker',
        'id' => 'event_label-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="event_value-field" class="col-sm-2 col-form-label">Значение события</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'event_value', [
        'class' => 'form-control selectpicker',
        'id' => 'event_value-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->

<!-- UTM -->

<!-- form-group row -->
<div class="form-group row">
  <label for="utm_campaign-field" class="col-sm-2 col-form-label">Utm campaign</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'utm_campaign', [
        'class' => 'form-control selectpicker',
        'id' => 'utm_campaign-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="utm_content-field" class="col-sm-2 col-form-label">Utm content</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'utm_content', [
        'class' => 'form-control selectpicker',
        'id' => 'utm_content-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="utm_term-field" class="col-sm-2 col-form-label">Utm term</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'utm_term', [
        'class' => 'form-control selectpicker',
        'id' => 'utm_term-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="utm_medium-field" class="col-sm-2 col-form-label">Utm medium</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'utm_medium', [
        'class' => 'form-control selectpicker',
        'id' => 'utm_medium-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="utm_source-field" class="col-sm-2 col-form-label">Utm source</label>
  <div class="col-sm-10">
    <?= ModernForm::input('text', 'utm_source', [
        'class' => 'form-control selectpicker',
        'id' => 'utm_source-field'
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="ad-field" class="col-sm-2 col-form-label">Наличие AdBlock</label>
  <div class="col-sm-10">
    <?= ModernForm::select('select', 'ad', [
            0 => 'Не выбрано',
            1 => 'Нет',
            2 => 'Да'
    ], [
        'class' => 'form-control',
        'id' => 'ad-field'
    ]); ?>
  </div>
</div>
<!-- form-group row -->
<div class="form-group row">
    <label for="ad-field" class="col-sm-2 col-form-label">Бот</label>
    <div class="col-sm-10">
      <?= ModernForm::select('select', 'is_bot', [
          0 => 'Не выбрано',
          1 => 'Нет',
          2 => 'Да'
      ], [
          'class' => 'form-control',
          'id' => 'is_bot-field'
      ]); ?>
    </div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <div class="header-events-part">Группировка</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="group-field" class="col-sm-2 col-form-label">Группировать записи по:</label>
  <div class="col-sm-10">
    <?= ModernForm::multiselect('select', 'group', $grouping, [
        'class' => 'form-control selectpicker',
        'id' => 'group-field',
        'multiple' => true
    ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <div class="header-events-part">Пересечения</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <div class="header-events-part">
  	<div class="alert alert-warning" role="alert">
  	Данный подраздел случжит для подсчета пересечений между доменами как партнеров, так и МГТС\МТС
  	</div>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
	<div class="tab-pane" id="multiple">
    <label for="exampleSelect3">Группы доменов:</label>
        <?= ModernForm::multiselect('select', 'domaingroup', $groupFromDB, [
            'data-live-search' => true,
            'data-dropup-auto' => false,
            'multiple' => true,
            'class' => 'form-control selectpicker',
            'id' => 'exampleSelect3'
        ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <div class="header-events-part">Дополнительные параметры</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
  <label for="count-field" class="col-sm-2 col-form-label">Строк на странице:</label>
  <div class="col-sm-10">
      <?= ModernForm::select('select', 'count', $countOfPage, [
          'class' => 'form-control selectpicker',
          'id' => 'group-field',
      ]); ?>
	</div>
</div>
<!-- /form-group row -->
<!-- form-group row -->
<div class="form-group row">
    <label for="count-field" class="col-sm-2 col-form-label">Учитывать данные в рамках логина:</label>
    <div class="col-sm-10">
        <?= ModernForm::checkbox('md5login'); ?>
    </div>
</div>
<!-- /form-group row -->

<button type="submit" class="btn btn-primary">Найти</button>
<button type="button" class="btn btn-primary" onclick="export_excel('table2excel','События' + document.getElementById('dp1').value + '-' + document.getElementById('dp2').value);">Скачать</button>

<?= ModernForm::close(); ?>

<br>
<br>