<?php

$data = (!empty($params['result']) ? $params['result'] : []);
$filter = common_setValue($params, 'filter');

if(!empty($data)) { ?>

    <h2 class="sub-header">Результат:</h2>
    <!--table-responsive-->
    <div class="table-responsive">
        <!--table table-striped-->
        <table class="table table-striped table2excel">
        	<thead>
                <tr>
                    <th style="border: 1px solid; text-align: center;" colspan="3">Общее</th>
                    <th style="border: 1px solid; text-align: center; width: 20%" colspan="2">Боты</th>
                    <th style="border: 1px solid; text-align: center; width: 20%" colspan="2">Адблоки</th>
                </tr>
                <tr>
                    <?php

                    $additionalColumn = '';
                    switch ($filter)
                    {
                        case 'region':
                        case 'crossCity':
                            echo '<th rowspan="2" style="border-left: 1px solid; width: 450px;">Город:</th>';
                            break;
                        case 'provider':
                        case 'crossProvider':
                            echo '<th rowspan="2" style="border-left: 1px solid; width: 450px;">Провайдер:</th>';
                            break;

                        default:
                            echo '<th rowspan="2" style="border-left: 1px solid; width: 450px;">Ссылка:</th>';
                            break;
                    }

                    ?>
                    <th rowspan="2">Количество:</th>
                    <th style="border-right: 1px solid;" rowspan="2">Домен:</th>
                    <th style="border-left: 1px solid;">Количество:</th>
                    <th style="border-right: 1px solid;">%:</th>
                    <th style="border-left: 1px solid;">Количество:</th>
                    <th style="border-right: 1px solid;">%:</th>
                </tr>
        	</thead>
        	<tbody>
        	<?php 

        	switch ($filter)
            {
                case 'region':
                case 'crossCity':
                    require('showContentCity.php');
                    break;
                case 'provider':
                case 'crossProvider':
                    require('showContentProvider.php');
                    break;
                
                default:
                    require('showContentTop.php');
                    break;
            }

        	?>
        	</tbody>
        </table>

<?php } ?>