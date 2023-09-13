<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 31.08.16
 * Time: 17:10
 */

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
            <h1 class="page-header">Добавление нового события</h1>
            <div class="alert alert-info" role="alert">
                <p>На данной странице можно создать событие, которое в дальнейшем можно будет вызывать на сайте для сбора информации и составления статистики по этому событию.</p>
            </div>

            <?php if(!empty($params['error'])) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php foreach ($params['error'] as $error) { ?>
                        <p><?php echo $error ?></p>
                    <?php } ?>
                </div>
            <?php } ?>

            <?php

            if(!empty($params['success']) && $params['success'] > 0
                && empty($params['update'])
            ) {

                ?>
                <div class="alert alert-success" role="alert">
                    <p>Запись успешно добавлена</p>
                </div>

            <?php } ?>

            <!--navbar navbar-default-->
            <nav class="navbar navbar-default">
                <!--container-fluid-->
                <div class="container-fluid">
                    <!--navbar-header-->
                    <div class="navbar-header">
                        <div type="button" class="btn btn-primary navbar-btn">
                            <a href="/events/">Вернуться к списку событий</a>
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
            
                <?php echo $form->field('input', [
                    'type' => 'text',
                    'class' => 'form-control ci-input',
                    'name' => 'name',
                    'placeholder' => 'Название события...',
                    'value' => common_setValue($_POST, 'name'),
                ]); ?>
                <small class="text-muted">название события может содержать любой текст, который будет служить описанием этого события. Нпример событие на отправку формы home/id</small>
            </fieldset>

            <fieldset class="form-group">
            
                <?php echo $form->field('select', [
                    'name' => 'event',
                    'class' => 'form-control',
                ], '', [
                    'click' => 'Клик',
                    'view' => 'Показ'
                ]); ?>
                <small class="text-muted">в типе события указывается действие на событие. То есть это <b>click</b> - клик мышкой по элементу, <b>view</b> - показ элемента.</small>
            </fieldset>

            <fieldset class="form-group">
            
                <?php echo $form->field('input', [
                    'type' => 'text',
                    'class' => 'form-control ci-input',
                    'name' => 'category',
                    'placeholder' => 'Категория события...',
                    'value' => common_setValue($_POST, 'category'),
                ]); ?>
                <small class="text-muted">категория события - это условное разделение событий на категории. Например может быть категория banners или категория media.</small>
            </fieldset>

            <fieldset class="form-group">

                <?php

                $oldValue = '';
                if(!empty($_POST['old_label']))
                    $oldValue = $_POST['old_label'];
                elseif (!empty($params['items'])
                    && !empty($params['items'][0])
                    && !empty($params['items'][0]['label'])
                )
                    $oldValue = $params['items'][0]['label'];
                else
                    $oldValue = '';

                ?>

                <?php echo $form->field('input', [
                    'type' => 'text',
                    'class' => 'form-control ci-input',
                    'name' => 'label',
                    'placeholder' => 'Ярлык события...',
                    'value' => common_setValue($_POST, 'label'),
                    'unique' => [
                        'name' => 'old_label',
                        'value' => $oldValue
                    ]
                ]); ?>
                <small class="text-muted">ярлык события - это уникальное значение для события. Может содержать шаблоны. Шаблон обрамляется в фигурные скобки. например some_{comapny}_text.</small>
            </fieldset>

            <fieldset class="form-group">
            
                <?php echo $form->field('input', [
                    'type' => 'text',
                    'class' => 'form-control ci-input',
                    'name' => 'value',
                    'placeholder' => 'Значение события...',
                    'value' => common_setValue($_POST, 'value'),
                ]); ?>
                <small class="text-muted">категория события - это условное разделение событий на категории. Например может быть категория banners или категория media.</small>
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