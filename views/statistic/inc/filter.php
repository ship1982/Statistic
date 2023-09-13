<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 13:04
 */

common_inc('_fetcher');

?>

<!--form-->
<h2 class="sub-header">Фильтр:</h2>

<?php

if(!empty($params['error']))
{
    echo '<div class="alert alert-warning" role="alert">';
    echo common_showError($params['error']);
    echo '</div>';
}

$formName = '';
$formType = '';
$pageType = '';
if(!empty($params['type']))
{
    switch ($params['type'])
    {
        case 'domain':
            $formName = 'form-1';
            $formType = 'domain';
            $pageType = 'domain';
            break;
        case 'referrer':
            $formName = 'form-2';
            $formType = 'referrer';
            $pageType = 'referrer';
            break;
        case 'fastDomain':
            $formName = 'form-3';
            $formType = 'domain';
            $pageType = 'fastDomain';
            break;
        case 'fastReferrer':
            $formName = 'form-4';
            $formType = 'referrer';
            $pageType = 'fastReferrer';
            break;
    }
}

?>

<form name="<?php echo $formName?>" method="POST">
    <input type="hidden" name="form" value="<?php echo $formName ?>">

    <?php if($pageType == 'fastDomain' || $pageType == 'fastReferrer') { ?>

    <?php } else { ?>

        <!--form-group-->
        <fieldset class="form-group">
            <div class="input-group date" data-provide="datepicker">
                <input type="text" class="form-control" name="from" id="dp1" placeholder="От" value="<?php echo common_setValue($_POST, 'from') ?>">
                <div class="input-group-addon">
                    <span class="glyphicon glyphicon-th"></span>
                </div>
            </div>
            <small class="text-muted">Выберите дату, с которой необходимо отобразить статистику.</small>
        </fieldset>
        <!--/form-group-->
        <!--form-group-->
        <fieldset class="form-group">
            <div class="input-group date" data-provide="datepicker">
                <input type="text" class="form-control" name="to" placeholder="До" id="dp2" value="<?php echo common_setValue($_POST, 'to') ?>">
                <div class="input-group-addon">
                    <span class="glyphicon glyphicon-th"></span>
                </div>
            </div>
            <small class="text-muted">Выберите дату, до которой (включительно) необходимо отобразить статистику.</small>
        </fieldset>
        <!--/form-group-->

    <?php } ?>

    <!--form-group-->
    <fieldset class="form-group">
        <!--nav nav-tabs-->
        <ul class="nav nav-tabs" id="tabs_group" role="tablist">
            <li role="presentation" class="active">
                <a href="#one" aria-controls="home" role="tab" data-toggle="tab">Отдельные домены</a>
            </li>
            <li role="presentation">
                <a href="#multiple" aria-controls="profile" role="tab" data-toggle="tab">Группы доменов</a>
            </li>
        </ul>
        <!--/nav nav-tabs-->
        <!--tab-content-->
        <div class="tab-content">
            <input type="hidden" id="tab_opened" name="tab_opened" value="<?php echo ((!empty($_POST['tab_opened']) && $_POST['tab_opened'] == 'one') ? 'one' : 'multiple'); ?>">
            <div role="tabpanel" class="tab-pane active" id="one">
                <label for="exampleSelect2">Домены:</label>
                <select data-live-search="true" data-dropup-auto="false" multiple class="selectpicker form-control" name="<?php echo $formType ?>[]" id="exampleSelect2">

                    <?php

                    $resDomain = fetcher_getDomain(['show' => 1]);
                    $selectArray = [];
                    if(!empty($_POST[$formType]))
                        $selectArray = array_flip($_POST[$formType]);

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
            </div>
            <div role="tabpanel" class="tab-pane" id="multiple">
                <label for="exampleSelect2">Группы доменов:</label>
                <select data-live-search="true" data-dropup-auto="false" multiple class="selectpicker form-control" name="group[]" id="exampleSelect3">
                <?php

                common_inc('groupFilter');
                $rsGroup = gf_get();
                $selected = '';
                while($arGroup = mysqli_fetch_assoc($rsGroup))
                {
                    if(!empty($_POST['group']))
                    {
                        if(in_array($arGroup['id'], $_POST['group']))
                            $selected = 'selected="selected"';
                        else
                            $selected = '';
                    }

                    echo '<option ' . $selected . ' value="' . $arGroup['id'] . '">' . $arGroup['name'] . '</option>';
                }

                ?>
                </select>
            </div>
        </div>
        <!--/tab-content-->
        <script type="text/javascript">
            var filter = {
                init: function () {
                    filter.load();
                    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                        var a = e.target.href.split('#')[1];
                        $('#tab_opened').val(a);
                    });
                },
                load: function () {
                    var a = $('#tab_opened').val();
                    $('#tabs_group a[href="#' + a + '"]').tab('show');
                }
            };
        </script>
    </fieldset>
    <!--/form-group-->

    <?php if(!empty($params['type']) && $params['type'] == 'domain') { ?>

        <!--form-group-->
        <fieldset id="sum-checkbox" class="form-group">
            <div class="checkbox">
                <label>
                    <input <?php echo common_setCheckedSelected($_POST, 'sum') ?> name="sum" value="sum" type="checkbox"> Суммарное количество просмотров
                </label>
            </div>
            <small class="text-muted">При активации этой галочки будет выведено суммарное количество простмотров для выбранного или выбранных доменов. То есть одно обновление страницы - 1 просмотр. 10 обновлений - 10 прсомотров.</small>
        </fieldset>
        <!--/form-group-->
        <!--form-group-->
        <fieldset id="cross-checkbox" class="form-group">
            <div class="checkbox">
                <label>
                    <input <?php echo common_setCheckedSelected($_POST, 'diff') ?> name="diff" value="diff" type="checkbox" id="diff-checkbox" onchange="common.showSum()"> Пересечения по доменам
                </label>
            </div>
            <small class="text-muted">При активации галочки будет выведена статистика о том, сколько пользователей было и на том и на всех рассматриваемых доменах и на других сочетаниях доменов (предположим в выборке 4 домена, a,b,c,d. Надо выборку по пересечени по сочетаниям abcd, abc, adc, bdc, ac,ad,ab,cd,bd,bc).</small>
        </fieldset>

    <?php } ?>

    <?php if(!empty($params['type']) && $params['type'] == 'referrer') { ?>

        <!--form-group-->
        <fieldset id="cross-checkbox" class="form-group">
            <div class="checkbox">
                <label>
                    <input <?php echo common_setCheckedSelected($_POST, 'diff') ?> name="diff" value="diff" type="checkbox" id="diff-checkbox" onchange="common.showSum()"> Пересечения по referrer
                </label>
            </div>
            <small class="text-muted">При активации данный галочки по каждому домену будет показано сколько пользователей пришли на него с другого из находящихся в выборке доменов суммарно и с разбивкой на домены-источники.</small>
        </fieldset>
        <!--/form-group-->

    <?php } ?>

    <button type="submit" class="btn btn-primary">Показать</button>
		<button type="button" class="btn btn-primary" onclick="export_excel('table2excel','<?php print((!empty($params['type']) && $params['type'] == 'referrer')?'Реферер_':'Домены_'); ?>' + document.getElementById('dp1').value + '-' + document.getElementById('dp2').value);">Скачать</button>
</form>
<!--/form-->