<?php if(!empty($params['model'])
 && empty($params['model']['error'])
 ) { ?>

<h2 class="sub-header">Результат:</h2>
<!-- table-responsive -->
<div class="table-responsive">
    <!--table table-striped-->
    <table class="table table-striped table2excel">
        <thead>
            <tr>
                <th  style="border: 1px solid; text-align: center;" colspan="2">Общее</th>
                <th  style="border: 1px solid; text-align: center;">Боты</th>
                <th  style="border: 1px solid; text-align: center;">Адблоки</th>
            </tr>
            <tr>
                <th style="border-left: 1px solid;" rowspan="2">Количество:</th>
                <th style="border-right: 1px solid;" rowspan="2">Дополнительная информация:</th>
                <th style="border-left: 1px solid;">Количество:</th>
                <th style="border-left: 1px solid;">Количество:</th>
            </tr>
        </thead>
        <tbody>
            <?php echo showInfoByPath($params['model']); ?>
	    </tbody>
	</table>
	<!--table table-striped-->
</div>
<!-- table-responsive -->

<?php } ?>