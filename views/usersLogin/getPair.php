<?php

use \html\form\ModernForm;

?>
<div>
    <h1>Поиск пользоваетля по логину \ uuid</h1>
    <!-- Nav tabs -->
    <ul id="myTabs" class="nav nav-tabs">
        <li role="presentation" class="<?= ('uuid' === $params['field'] ? 'active' : ''); ?>">
            <a href="#login" aria-controls="home">Поиск по логину</a>
        </li>
        <li role="presentation" class="<?= ('login' === $params['field'] ? 'active' : ''); ?>">
            <a href="#uuid" aria-controls="profile">Поиск по uuid</a>
        </li>
    </ul>
  <?= ModernForm::open([
      'method' => 'POST',
      'id' => 'myForm'
  ]); ?>
    <!-- Tab panes -->
    <div class="tab-content">
        <div role="tabpanel" class="ID-tabcontent-block tab-pane <?= ('uuid' === $params['field'] ? 'active' : ''); ?>" id="login">
            <div class="form-group">
                <label for="login">Введите логин пользователя (телефон):</label>
              <?= ModernForm::input('text', 'login', [
                  'class' => 'form-control',
                  'id' => 'login'
              ]); ?>
                <small>Телефон следект вводить без +7. Формат ввода: 89111234567</small>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane ID-tabcontent-block <?= ('login' === $params['field'] ? 'active' : ''); ?>" id="uuid">
            <div class="form-group">
                <label for="uuid">Введите uuid пользователя:</label>
              <?= ModernForm::input('text', 'uuid', [
                  'class' => 'form-control',
                  'id' => 'uuid'
              ]); ?>
        </div>
    </div>
    <input type="submit" class="btn btn-default" value="Получить">
  <?= ModernForm::close(); ?>
</div>
<script>
  $('#myTabs a').click(function (e) {
    e.preventDefault();
    $(this).tab('show');
    var id = $('#myTabs').find('li:not(.active) a').attr('href'),
      form = $('#myForm'),
      activeTab = form.find('.activeTab'),
      value = id.substr(1);
    if (activeTab.length > 0) {
      activeTab.val(value);
    } else {
      form.append('<input type="hidden" class="activeTab" name="activeTab" value="' + value + '">');
    }
  });
  (function () {
    var id = $('#myTabs').find('li:not(.active) a').attr('href'),
      form = $('#myForm'),
      value = id.substr(1);
    form.append('<input type="hidden" class="activeTab" name="activeTab" value="' + value + '">')
  })();
</script>
<div class="ID-content">
  <?php if (!empty($params['data'])) { ?>
    <table class="table table-bordered">
        <thead>
        <tr>
            <th><?= common_setValue($params, 'name'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php for ($i = 0; $i < $ic = count($params['data']); $i++) { ?>
            <tr><td><?= $params['data'][$i][$params['field']]; ?></td></tr>
        <?php } ?>
        </tbody>
    </table>
  <?php } ?>
</div>