<?php

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

?>

<form name="top-filter" method="POST">
    <input type="hidden" name="form" value="top-filter">
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

		<!--form-group-->
		<h4 class="sub-header">Детализация по дням недели и часам</h4>
        <fieldset class="form-group">
        <table class="table table-bordered table-condensed table-responsive">
        <thead>
        <tr>
        	<th colspan="2">пн</th>
        	<th colspan="2">вт</th>
        	<th colspan="2">ср</th>
        	<th colspan="2">чт</th>
        	<th colspan="2">пт</th>
        	<th colspan="2">сб</th>
        	<th colspan="2">вс</th>
        </tr>
        </thead>
        	<tbody>
        		<tr>
        		<?php

        		function getOptions($week = 1, $prefix = 's')
        		{
        			$array = [
		        		'all',
		        		'00',
		        		'01',
		        		'02',
		        		'03',
		        		'04',
		        		'05',
		        		'06',
		        		'07',
		        		'08',
		        		'09',
		        		'10',
		        		'11',
		        		'12',
		        		'13',
		        		'14',
		        		'15',
		        		'16',
		        		'17',
		        		'18',
		        		'19',
		        		'20',
		        		'21',
		        		'22',
		        		'23'
	        		];
	        		$optionHTML = '';
	        		foreach ($array as $value)
	        		{
	        			if(!empty($_POST['day_' . $week . '_' . $prefix])
	        				&& $_POST['day_' . $week . '_' . $prefix] == $value
	        			)
	        				$selected = ' selected="selected" ';
	        			else
	        				$selected = ' ';
	        			$optionHTML .= '<option' . $selected . 'value="'. $value .'">'. $value .'</option>';
	        		}

	        		return $optionHTML;
        		}

        		?>
        		<?php for ($i=1; $i < 8; $i++) { ?>
        			
        			<td>
	        			<select onchange="topDetalizer.checkValue(event, <?php echo $i ?>, 's')" id="day_<?php echo $i ?>_s" name="day_<?php echo $i ?>_s" class="form-control small-padding">
						  <?php echo getOptions($i, 's') ?>
						</select>
	        		</td>
	        		<td>
	        			<select onchange="topDetalizer.checkValue(event, <?php echo $i ?>, 'e')" id="day_<?php echo $i ?>_e" name="day_<?php echo $i ?>_e" class="form-control small-padding">
						  <?php echo getOptions($i, 'e') ?>
						</select>
	        		</td>

        		<?php } ?>
	        		
        		</tr>
        	</tbody>
        </table>
        <small>
        	Первая колонка в дне недели служит начальным часом отсчета.
        	Вторая колонка в дне недели служит конечным часом отсчета.
        	Если нужно посчитать статистику с <b>20</b> вечера до <b>8</b> утра, то в первой колонке нужно выставить <b>20</b>, а во второй - <b>8</b>.
        	Если же не нужно делать почасовую разбивку для дня недели, то необходимо выставить в обеих колонках <b>all</b>.
        </small>           

        </fieldset>
        <!--/form-group-->

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
	                <select data-live-search="true" data-dropup-auto="false" multiple class="selectpicker form-control" name="domain[]" id="exampleSelect2">

	                    <?php

	                    $resDomain = fetcher_getDomain(['show' => 1]);
	                    $selectArray = [];
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
        <fieldset class="form-group">
        	<div class="label-h">Тип запроса:</div>
            <div class="radio">
                <label>
                    <input <?php echo common_setCheckedSelected($_POST, 'top_detail', 'top1') ?> name="top_detail" value="top1" type="radio"> топ по ссылкам
                </label>
            </div>
            <div class="radio">
                <label>
                    <input <?php echo common_setCheckedSelected($_POST, 'top_detail', 'top2') ?> name="top_detail" value="top2" type="radio"> топ по городам
                </label>
            </div>
            <div class="radio">
                <label>
                    <input <?php echo common_setCheckedSelected($_POST, 'top_detail', 'top3') ?> name="top_detail" value="top3" type="radio"> топ по провайдерам
                </label>
            </div>
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

    <button type="submit" class="btn btn-primary">Показать</button>
		<script>
			function get_val_check (){
				var nameRadio = document.getElementsByName('top_detail');
				var rezultatRadio = '';
				for (var i = 0; i < nameRadio.length; i++) {
					if (nameRadio[i].checked) {
							rezultatRadio = nameRadio[i].labels[0].innerText;       
					}
				}
				//Если тип запроса не выбран
				if(rezultatRadio == ''){
					rezultatRadio = nameRadio[0].labels[0].innerText;
				}
				return rezultatRadio;
			}
		</script>
		<button type="button" class="btn btn-primary" onclick="export_excel('table2excel',get_val_check() + '_' + document.getElementById('dp1').value + '-' + document.getElementById('dp2').value);">Скачать</button>
</form>
<!--/form-->