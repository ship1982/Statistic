<?php
/**
 * Created by PhpStorm.
 * User: vitalykhy
 * Date: 16.07.16
 * Time: 16:38
 */

$data = (!empty($params['result']) ? $params['result'] : []);

if(!empty($data)) { ?>

    <h2 class="sub-header">Результат:</h2>
    <!--table-responsive-->
    <div class="table-responsive">
        <!--table table-striped-->
				<?php $to_excel = 0; $to_excel_accordion = 0; ?>
        <table class="table table-striped" id="to_excel">

            <?php

            /** without group */
            //if(empty($params['group'])  || !empty($data['diff']))
            if(empty($data['diff']) && empty($params['group']) && !empty($data['allDomain']))
            {
            ?>

                <thead>
                    <tr>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Общее</th>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Боты</th>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Адблоки</th>
                    </tr>
                    <tr>
                        <th style="border-left: 1px solid;" rowspan="2">Домен: </th>
                        <th style="border-right: 1px solid;" rowspan="2">Количество:</th>
                        <th style="border-left: 1px solid;">Количество:</th>
                        <th style="border-right: 1px solid;">%:</th>
                        <th style="border-left: 1px solid;">Количество:</th>
                        <th style="border-right: 1px solid;">%:</th>
                    </tr>
                </thead>

            <?php }elseif(!empty($data['diff'])){
                ?>
                <thead>
                    <tr>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Общее</th>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Боты</th>
                        <th  style="border: 1px solid; text-align: center;" colspan="2">Адблоки</th>
                    </tr>
                    <tr>
                        <th style="border-left: 1px solid;" rowspan="2">Пересечение: </th>
                        <th style="border-right: 1px solid;" rowspan="2">Количество:</th>
                        <th style="border-left: 1px solid;">Количество:</th>
                        <th style="border-right: 1px solid;">%:</th>
                        <th style="border-left: 1px solid;">Количество:</th>
                        <th style="border-right: 1px solid;">%:</th>
                    </tr>
                </thead>
                <?php
            } ?>

            <tbody>
            <tr>
                <td>

            <?php

            if (empty($data['diff']))
            {
                /** without group */
                if(empty($params['group']))
                {
                    for ($i = 0; $i < $ic = count($data); $i++)
                    {
                    ?>

                    <tr>
                        <td>

                            <?php
                            if(!empty($data['allDomain']) && !empty($data[$i]['referrer'])
                                && array_key_exists($data[$i]['referrer'], $data['allDomain'])
                            )
                            {
                                echo $data['allDomain'][$data[$i]['referrer']];
																$to_excel = 1;
                            }else{
                                continue;
                            }
                            ?>

                        </td>
                        <td>

                            <?php
                            if(!empty($data[$i]['c']))
                            {
                                echo $data[$i]['c'];
																$to_excel = 1;
                            }
                            ?>

                        </td>
                        <td>
                            <?php
                            if(!empty($data[$i]['c_bots']))
                            {
                                echo $data[$i]['c_bots'];
                                $to_excel = 1;
                            }else{echo 0;}
                            ?>
                        </td>
                        <td>
                            <?php
                            if(!empty($data[$i]['c_bots']))
                            {
                                echo common_percent_from_number($data[$i]['c'], $data[$i]['c_bots']);
                                $to_excel = 1;
                            }else{echo 0;}
                            ?>
                        </td>
                        <td>
                            <?php
                            if(!empty($data[$i]['c_ads']))
                            {
                                echo $data[$i]['c_ads'];
                                $to_excel = 1;
                            }else{echo 0;}
                            ?>
                        </td>
                        <td>
                            <?php
                            if(!empty($data[$i]['c_ads']))
                            {
                                echo common_percent_from_number($data[$i]['c'], $data[$i]['c_ads']);
                                $to_excel = 1;
                            }else{echo 0;}
                            ?>
                        </td>
                    </tr>

                    <?php
                    }
                }
                else
                {
                    /** with group */
                    $arResult = [];
                    $lastKey = false;

                    for ($i = 0; $i < $ic = count($data); $i++)
                    {
                        if(!empty($data['allDomain']) && !empty($data[$i]['referrer'])
                            && array_key_exists($data[$i]['referrer'], $data['allDomain'])
                        )
                        {
                            $key = $params['group'][$data[$i]['referrer']];
                            if(empty($arResult[$key]['count']))
                                $arResult[$key]['count'] = 0;
                            $arResult[$key]['count'] += (!empty($data[$i]['c']) ? $data[$i]['c'] : 0);
                            if(empty($arResult[$key]['count_c_bots']))
                                $arResult[$key]['count_c_bots'] = 0;

                            $arResult[$key]['count_c_bots'] += (!empty($data[$i]['c_bots']) ? $data[$i]['c_bots'] : 0);

                            if(empty($arResult[$key]['count_c_ads']))
                                $arResult[$key]['count_c_ads'] = 0;

                            $arResult[$key]['count_c_ads'] += (!empty($data[$i]['c_ads']) ? $data[$i]['c_ads'] : 0);
                            if(empty($arResult[$key]['html']))
                                $arResult[$key]['html'] = '';
                            $arResult[$key]['html'] .= '<tr><td>' . $data['allDomain'][$data[$i]['referrer']] . '</td><td>' . (!empty($data[$i]['c']) ? $data[$i]['c'] : 0) . '</td><td>'. $data[$i]['c_bots'] .'</td><td>'. common_percent_from_number($data[$i]['c'], $data[$i]['c_bots']) .'</td><td>'. $data[$i]['c_ads'] .'</td><td>'. common_percent_from_number($data[$i]['c'], $data[$i]['c_ads']) .'</td></tr>';
                        }
                    }

                    ?>

                    <!--panel-group-->
                    <div class="panel-group <?php print((empty($data['diff']))?'table2excel':''); ?>" id="accordion">

                        <?php foreach ($arResult as $group => $arrayGroup) { ?>
												<?php $to_excel_accordion = 1; ?>
                            <!--panel-default-->
                            <div class="panel panel-default">
                                <!--panel-heading-->
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" data-parent="#accordion" href="#<?php echo $group ?>">
                                            <?php echo $group ?> - всего посетителей: <b><?php echo $arrayGroup['count'] . "</b>; - всего ботов: <b> {$arrayGroup['count_c_bots']}</b>; - всего адблоков: <b>{$arrayGroup['count_c_ads']}</b>"; ?></b>
                                        </a>
                                    </h4>
                                </div>
                                <!--/panel-heading-->
                                <!--panel-collapse collapse in-->
                                <div id="<?php echo $group ?>" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th colspan="2"><?php echo $group ?> - всего посетителей:
                                                    <?php echo $arrayGroup['count'] . "; - всего ботов: {$arrayGroup['count_c_bots']}; - всего адблоков: {$arrayGroup['count_c_ads']}"; ?></th></th>
                                                </tr>
                                                <tr>
                                                    <th colspan="2" style="text-align: center;">Общее</th>
                                                    <th colspan="2" style="text-align: center;">Боты</th>
                                                    <th colspan="2" style="text-align: center;">Адблоки</th>
                                                </tr>
                                                <tr>
                                                    <th rowspan="2">Домен: </th>
                                                    <th rowspan="2">Количество:</th>
                                                    <th>Количество:</th>
                                                    <th>%:</th>
                                                    <th>Количество:</th>
                                                    <th>%:</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php echo $arrayGroup['html'] ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <!--/panel-collapse collapse in-->
                            </div>
                            <!--/panel-default-->
                        <?php } ?>

                    </div>
                    <!--/panel-group-->

                    <?php
                }
            }
            else if(!empty($data['diff']))
            {
                foreach($data['diff'] as $domain => $countValues)
                {
									$to_excel = 1;
                    ?>

                    <tr>
                        <td><?php echo $domain; ?></td>
                        <td><?php echo (array_key_exists('c', $countValues))?$countValues['c']:0; ?></td>
                        <td><?php echo (array_key_exists('c_bots_c', $countValues))?$countValues['c_bots_c']:0; ?></td>
                        <td><?php echo (array_key_exists('c_bots_p', $countValues))?$countValues['c_bots_p']:0; ?></td>
                        <td><?php echo (array_key_exists('c_ads_c', $countValues))?$countValues['c_ads_c']:0; ?></td>
                        <td><?php echo (array_key_exists('c_ads_p', $countValues))?$countValues['c_ads_p']:0; ?></td>

                    </tr>

                    <?php
                }
            }

            ?>
            </td>
            </tr>
            </tbody>
        </table>				
				<script>
				var to_excel = <?php print($to_excel); ?>;
				var to_excel_accordion = <?php print($to_excel_accordion); ?>;
				if(to_excel >0 ){
					document.getElementById('to_excel').setAttribute('class', document.getElementById('to_excel').getAttribute('class') + ' table2excel');
				}else if(to_excel_accordion > 0){
					document.getElementById('accordion').setAttribute('class', document.getElementById('to_excel').getAttribute('class') + ' table2excel');
				}
				</script>
        <!--/table table-striped-->
    </div>
    <!--/table-responsive-->

<?php } ?>
