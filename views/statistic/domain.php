<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 15.07.16
 * Time: 12:54
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
            <h1 class="page-header">Статистика по доменам</h1>
            <!--alert-->
            <div class="alert alert-info" role="alert">
                В данном разделе можно отобразить статистику по доменам.
                Вы можете узнать сколько уникальных пользователей (браузеров) посетили тот или иной домен.
                Также можно узнать сколько не уникальных посетителей было на том или дугом домене.
                <br>
                <b>Уникальный посетитель (англ. unique visitor)</b> — неповторяющийся пользователь, обладающий уникальными характеристиками и зашедший на сайт в течение определённого промежутка времени.
                Под промежутком времени чаще всего подразумеваются календарные сутки.
                Уникальные характеристики пользователя — это совокупность тех данных, которые позволяют отличать одного пользователя от другого: IP-адрес, браузер, регистрационные данные.
                Каждый пользователь считается уникальным, если при заходе на сайт его комбинация данных отличается от других.
                При повторном заходе пользователя на сайт он уже не считается уникальным, и его посещение считается просмотром.
                При этом, если один и тот же пользователь зашёл на сайт несколько раз под разными браузерами или с помощью разных компьютеров, то его посещения будут считаться уникальными.
            </div>
            <!--/alert-->
            
            <?php

            common_setAloneView('statistic/inc/filter', [
                'error' => $params['error'],
                'type' => 'domain'
            ]);
                        
            common_setAloneView('statistic/contentDomain', [
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
    <!--/row-->
</div>
<!--/container-fluid-->