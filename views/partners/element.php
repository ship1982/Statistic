<?php

// навигационная строка
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
      <h1 class="page-header"><?php echo (!empty($params['update']) ? 'Обновление' : 'Добавление') ?> нового партнера</h1>
      <div class="alert alert-info" role="alert">
          <p>На данной странице можно добавить\обновить данные о партнере. Только дабоавленные партнеры и их домены могут попадать в статистику счетчика.</p>
      </div>

      <?php if(!empty($params['error'])) { ?>
        <div class="alert alert-danger" role="alert">
          <?php foreach ($params['error'] as $error) { ?>
            <p><?php echo $error ?></p>
          <?php } ?>
        </div>
      <?php } ?>

      <?php if(!empty($params['success']) && $params['success'] > 0) { ?>
        
        <div class="alert alert-success" role="alert">
          <p>Запись успешно <?php echo (!empty($params['update']) ? 'обновлена' : 'добавлена') ?></p>
        </div>

      <?php } ?>

      <!--navbar navbar-default-->
      <nav class="navbar navbar-default">
        <!--container-fluid-->
        <div class="container-fluid">
          <!--navbar-header-->
          <div class="navbar-header">
            <div type="button" class="btn btn-primary navbar-btn">
              <a href="/partners/">Вернуться к списку событий</a>
            </div>
          </div>
          <!--/navbar-header-->
        </div>
        <!--/container-fluid-->
      </nav>
      <!--/navbar navbar-default-->

      <?php

      common_inc('html/form', 'Form');

      $form = new Form();
      echo $form->run([
          'method' => 'POST',
          'name' => 'add-group'
      ]);
      ?>

      <fieldset class="form-group">

        <?php

        $oldValue = '';
        if(!empty($_POST['old_name']))
          $oldValue = $_POST['old_name'];
        elseif (!empty($params['items'])
          && !empty($params['items'][0])
          && !empty($params['items'][0]['name'])
        )
          $oldValue = $params['items'][0]['name'];
        else
          $oldValue = '';

        ?>

        <?php echo $form->field('input', [
          'type' => 'text',
          'class' => 'form-control ci-input',
          'name' => 'name',
          'placeholder' => 'Партнер...',
          'value' => common_setValue($_POST, 'name'),
          'unique' => [
            'name' => 'old_name',
            'value' => $oldValue
          ]
        ]); ?>
          
      </fieldset>

      <fieldset class="form-group">
      
        <?php echo $form->field('text', [
          'name' => 'domains',
          'class' => 'form-control',
          'placeholder' => 'Введите список доменов...',
          'value' => common_setValue($_POST, 'domains'),
          'rows' => 10
        ]); ?>
          
      </fieldset>

      <fieldset class="form-group">
      
        <?php echo $form->field('text', [
          'type' => 'text',
          'class' => 'form-control ci-input',
          'name' => 'pixel',
          'readonly' => true,
          'placeholder' => 'Пиксель для установки на сайт партнера...',
          'value' => common_setValue($_POST, 'pixel'),
          'rows' => 10
        ]); ?>
          
      </fieldset>

      <button type="submit" class="btn btn-primary"><?php echo (empty($params['update']) ? 'Добавить' : 'Обновить'); ?></button>
      <?php

      echo $form->end();

      ?>
    </div>
    <!--/main-->
  </div>
  <!--/row-->
</div>
<!--/container-fluid-->