<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 31.08.16
 * Time: 16:41
 */
?>

<!--navbar navbar-default-->
<nav class="navbar navbar-default">
    <!--container-fluid-->
    <div class="container-fluid">
        <!--navbar-header-->
        <div class="navbar-header">
            <div type="button" class="btn btn-success navbar-btn">
                <a href="/groupfilter/add/">Добавить группу</a>
            </div>
        </div>
        <!--/navbar-header-->
    </div>
    <!--/container-fluid-->
</nav>
<!--/navbar navbar-default-->

<!--panel panel-default-->
<div class="panel panel-default">
    <div class="panel-heading">Список групп</div>
    <!--table-->
    <table class="table">
        <thead>
        <th></th>
        <th>ID</th>
        <th>Название</th>
        <th>Домены</th>
        </thead>
        <tbody>
        <?php

        common_inc('groupFilter');

        $rsTable = gf_get();
        $empty = true;
        while($arTable = mysqli_fetch_assoc($rsTable))
        {
            $empty = false;
            $value = json_decode($arTable['value']);
            $strValue = gf_compareDomain($value);
            echo '
                <tr>
                    <td>
                        <span onclick="common.setting.update(' . $arTable['id'] . ')" class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
                        <span onclick="common.setting.delete(' . $arTable['id'] . ')" class="glyphicon glyphicon-remove" aria-hidden="true"></span>
                    </td>
                    <td>' . $arTable['id'] . '</td>
                    <td>' . $arTable['name'] . '</td>
                    <td>' . $strValue . '</td>
                </tr>';
        }

        if($empty)
        {
            echo '<tr><td colspan="3" align="center">Не создано ниодной группы.</td></tr>';
        }

        ?>

        </tbody>
    </table>
    <!--/table-->
</div>
<!--/panel panel-default-->