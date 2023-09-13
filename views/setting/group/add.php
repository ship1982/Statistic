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
            <h1 class="page-header">Добавление новой группы</h1>
            <div class="alert alert-info" role="alert">
                <p>Поля <b>Название</b> и <b>Домены</b> являются обязательными для заполнения.</p>
            </div>

            <?php if(!empty($params['error'])) { ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $params['error'] ?>
                </div>
            <?php } ?>

            <?php

            if(!empty($params['result'])
                && $params['result'] > 0
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
                            <a href="/groupfilter/">Вернуться к списку групп</a>
                        </div>
                    </div>
                    <!--/navbar-header-->
                </div>
                <!--/container-fluid-->
            </nav>
            <!--/navbar navbar-default-->

            <form action="" method="POST" name="add-group">
                <div class="input-group input-group custom-input">
                    <?php if(empty($params['update'])) { ?>

                        <input type="text" class="form-control ci-input" name="name" placeholder="Название группы" value="<?php echo common_setValue($_POST, 'name'); ?>">

                    <?php } else { ?>

                        <input type="text" class="form-control ci-input" name="name" placeholder="Название группы" value="<?php echo common_setValue($params['result'], 'name'); ?>">
                        <input type="hidden" class="form-control ci-input" name="oldname" value="<?php echo common_setValue($params['result'], 'name'); ?>">

                    <?php } ?>
                    <small class="text-muted">Данное поле является уникальным. То есть не может быть одинакового названия группы.</small>
                </div>
                <!--form-group-->
                <fieldset class="form-group">
                    <label for="exampleSelect2">Домены:</label>
                    <select data-live-search="true" multiple class="selectpicker form-control" name="domain[]" id="exampleSelect2">

                        <?php

                        common_inc('_fetcher');

                        $resDomain = fetcher_getDomain();

                        $selectArray = [];

                        /** update clause */
                        if(!empty($params['result']['value']))
                            $_POST['domain'] = json_decode($params['result']['value']);
                        /** end update clause */

                        if(!empty($_POST['domain']))
                            $selectArray = array_flip($_POST['domain']);

                        $data = [];
                        while($arDomain = mysqli_fetch_assoc($resDomain))
                        {
                            $GLOBALS['app']['allDomain'][$arDomain['id']] = $arDomain['name'];
                            if(empty($selectArray))
                                $selected = '';
                            else
                            {
                                if(!empty($selectArray[$arDomain['id']])
                                    || (isset($selectArray[$arDomain['id']]) && $selectArray[$arDomain['id']] === 0)
                                )
                                    $selected = 'selected';
                                else
                                    $selected = '';
                            }

                            echo '<option '.$selected.' value="'.$arDomain['id'].'">'.$arDomain['name'].'</option>';
                        }

                        unset($selectArray);

                        ?>

                    </select>
                </fieldset>
                <!--/form-group-->

                <button type="submit" class="btn btn-primary"><?php echo (empty($params['update']) ? 'Сохранить' : 'Обновить'); ?></button>
            </form>
        </div>
        <!--/main-->
    </div>
    <!--/row-->
</div>
<!--/container-fluid-->