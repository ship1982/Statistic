<h1 class="page-header">Добавление\обновление новой рекламы</h1>
<div class="alert alert-info" role="alert">
    <p>На данной странице можно добавить\обновить данные о рекламном объявлении.</p>
</div>

<?php if (!empty($params['error']))
{ ?>
    <div class="alert alert-danger" role="alert">
      <?php foreach ($params['error'] as $error)
      { ?>
          <p><?php echo $error ?></p>
      <?php } ?>
    </div>
<?php } ?>

<?php if (!empty($params['success']) && $params['success'] > 0)
{ ?>

    <div class="alert alert-success" role="alert">
        <p>Запись успешно <?php echo(!empty($params['update']) ? 'обновлена' : 'добавлена') ?></p>
    </div>

<?php } ?>

<!--navbar navbar-default-->
<nav class="navbar navbar-default">
    <!--container-fluid-->
    <div class="container-fluid">
        <!--navbar-header-->
        <div class="navbar-header">
            <div class="btn btn-primary navbar-btn">
                <a href="<?= common_setValue($params, 'return_url'); ?>">Вернуться к списку событий</a>
            </div>
        </div>
        <!--/navbar-header-->
    </div>
    <!--/container-fluid-->
</nav>
<!--/navbar navbar-default-->

<?php

// common_pre($params);

common_inc('html/form', 'Form');

$form = new Form();
echo $form->run([
    'method' => 'POST',
    'name' => 'add-ads',
    'enctype' => "multipart/form-data"
]);
?>

<fieldset class="form-group">

  <?php

  if (!empty($params['header']['name']))
  {
    echo $form->field('input', [
        'type' => 'text',
        'class' => 'form-control ci-input',
        'name' => 'name',
        'placeholder' => 'Название рекламы...',
        'value' => common_setValue($_POST, 'name')
    ]);
  }

  ?>

</fieldset>

<fieldset class="form-group">

  <?php

  if (!empty($params['header']['url']))
  {
    echo $form->field('input', [
        'type' => 'text',
        'class' => 'form-control ci-input',
        'name' => 'url',
        'placeholder' => 'Рекламная ссылка...',
        'value' => common_setValue($_POST, 'url')
    ]);
  }

  ?>

</fieldset>

<fieldset class="form-group">

  <?php

  if (!empty($params['header']['partner']))
  {
    echo $form->field('select', [
        'class' => 'form-control ci-input',
        'name' => 'partner',
    ], '', $params['partners']);
  }

  ?>

</fieldset>

<fieldset class="form-group">

  <?php

  if (!empty($params['header']['script']))
  {
    echo $form->field('text', [
        'class' => 'form-control ci-input',
        'name' => 'script',
        'readonly' => 1,
        'rows' => 14,
        'value' => common_setValue($_POST, 'script')
    ]);
  }

  ?>

</fieldset>

<fieldset class="form-group">

  <?php

  if (!empty($params['header']['content_type']))
  {
    echo $form->field('select', [
        'class' => 'form-control ci-input',
        'name' => 'content_type',
        'id' => 'filed_content_type',
    ], '', [
        1 => 'Баннер',
        2 => 'HTML'
    ]);
  }

  ?>

</fieldset>

<fieldset class="form-group" id="field_content">

  <?php

  if (!empty($params['header']['content']))
  {
    echo $form->field('text', [
        'class' => 'form-control ci-input',
        'name' => 'content',
        'placeholder' => 'Содержимое рекламы',
        'value' => common_setValue($_POST, 'content')
    ]);
  }

  ?>

</fieldset>

<?php

if (!empty($params['header']['id']))
{
  echo $form->field('input', [
      'type' => 'hidden',
      'name' => 'id',
      'value' => common_setValue($_POST, 'id')
  ]);
}

?>

<button type="submit" class="btn btn-primary"><?php echo(empty($_POST['id']) ? 'Добавить' : 'Обновить'); ?></button>
<?php

echo $form->end();

?>

<script>
  $(document).ready(function () {
    <?php if(empty($_POST['content_type'])) { ?>
    banner.setFile();
    <?php } else if(1 == $_POST['content_type']) { ?>
    banner.setFile('<?php echo common_setValue($_POST, 'content'); ?>');
    <?php } else if(2 == $_POST['content_type']) { ?>
    banner.setText('<?php echo common_setValue($_POST, 'content'); ?>');
    <?php } ?>
  });
</script>
