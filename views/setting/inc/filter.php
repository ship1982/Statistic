<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 13:04
 */

common_inc('_fetcher');

?>

<h2 class="sub-header">Фильтр:</h2>

<?php

if(!empty($params['error']))
{
    echo '<div class="alert alert-warning" role="alert">';
    echo common_showError($params['error']);
    echo '</div>';
}

?>

<!--filter-form-setting-->
<form name="filter-form-setting" method="POST">
    <input type="hidden" name="form" value="filter-form-setting">

    <!--form-group-->
    <fieldset class="form-group">
        <label for="exampleSelect2">Домены:</label>
        <select data-live-search="true" multiple class="selectpicker form-control" name="domain[]" id="exampleSelect2">

            <?php

            $resDomain = fetcher_getDomain();
            
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
    </fieldset>
    <!--/form-group-->

    <button type="submit" class="btn btn-primary">Выбрать</button>
</form>
<!--/filter-form-setting-->