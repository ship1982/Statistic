<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 16.07.16
 * Time: 18:25
 */

common_setAloneView('statistic/inc/navbar');

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
            <?php common_setAloneView('menu/menu', ['menu' => 'fast']); ?>
        </div>
        <!--main-->
        <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
            <h1 class="page-header">Статистика по referrer</h1>
            <div class="alert alert-info" role="alert">
                В данном разделе можно отобразить статистику по referrer за час, начиная с текущего момента. То есть если сейчас время 23-35, то статистика будет доступна за период с 22-35 до 23-35.
                <br>По умолчанию фильтрация происходит по уникальным первыичным referrer'ам.
                <br>Вы можете узнать для скольки пользователей (браузеров) выбранный домен был первым в цепочке посещений.
                <br>
                <b>Referer (от ошибочного написания англ. referrer — отсылающий, направляющий)</b> — в протоколе HTTP один из заголовков запроса клиента.
                Содержит URL источника запроса.
                Если перейти с одной страницы на другую, referer будет содержать адрес первой страницы.
            </div>

            <?php

            common_setAloneView('statistic/inc/filter', [
                'error' => $params['error'],
                'type' => 'fastReferrer'
            ]);

            common_setAloneView('statistic/contentReferrer', [
                'result' => array_merge(
                    (array) $GLOBALS['app'],
                    (array) $params['result']
                ),
                'group' => $params['group']
            ]);

            ?>
        </div>
        <!--/main-->
    </div>
</div>