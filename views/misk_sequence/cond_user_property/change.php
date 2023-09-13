<?php
common_setAloneView('statistic/inc/navbar');
?>
<style>
  fieldset.scheduler-border {
    border: 1px groove #ddd !important;
    padding: 0 1.4em 1.4em 1.4em !important;
    margin: 0 0 1.5em 0 !important;
    -webkit-box-shadow:  0px 0px 0px 0px #000;
    box-shadow:  0px 0px 0px 0px #000;
  }

  legend.scheduler-border {
    cursor: pointer;
    width:inherit; /* Or auto */
    padding:0 10px; /* To give a bit of padding on the left and right */
    border-bottom:none;
  }
  input.btn-add-cond{
    float:right;
  }
  #form_construct{
    display:inline-block;
  }
  .hide_el{
    display: none;
  }
	.datetimepicker{
		width: 140px;
	}
</style>
<script type="text/javascript">

	/*
   * Задаём счетчик групп элементов.
   * Это возня необходима из-за radio кнопок,
   * иначе не везде устанавливаются значения,
   * в виду специфики самого элемента radio.
   */

	var conditionsUserProperty = {
		counter: 0, //Счётчик групп услолвий
		update: <?php print(($params['update']) ? 1 : 0); ?>, //Статус обновления групп условий
		
    //Список полей для сравниваемого поля
    list_fields: JSON.parse('<?php echo json_encode($params['list_fields'], JSON_UNESCAPED_UNICODE); ?>'),
		///Список полей, для которых будут выборочные условия
		list_fields_selective_conditions: JSON.parse('<?php echo json_encode($params['list_fields_selective_conditions'], JSON_UNESCAPED_UNICODE); ?>'),
		//Список полей, для которых будут подгружатся значения через ajax
		list_fields_load_values: JSON.parse('<?php echo json_encode($params['list_fields_load_values'], JSON_UNESCAPED_UNICODE); ?>'),
		//Список полей и условий, для которых должен подгружаться datepicker
		list_dp: JSON.parse('<?php echo json_encode($params['list_dp'], JSON_UNESCAPED_UNICODE); ?>'),
		//Список полей, для которых будут подгружаться значения в виде статичного выпадающего списка
		list_fields_static_values: JSON.parse('<?php echo json_encode($params['list_fields_static_values'], JSON_UNESCAPED_UNICODE); ?>'),
		//Список всех типов сравнения
		list_conditions_type: JSON.parse('<?php echo json_encode($params['list_conditions_type'], JSON_UNESCAPED_UNICODE); ?>'),
		//Условия, которые следует изменять
		list_conditions_data: JSON.parse('<?php echo json_encode($params['conditions'], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS); ?>'),
		
		//Функция загружает в селект список городов средствами ajax
		get_list_city: function (text, id_select) {
		
			text = text || '';
			
			//Получаем выпадающий список, используемый в качестве подсказок
			var select_el_ul = document.getElementById(id_select).parentNode;
			select_el_ul = select_el_ul.getElementsByTagName('div')[0].getElementsByTagName('ul')[0];
			select_el_ul.innerHTML = "";

			//Если длина символов >= трёх, то делаем запрос к данным.
			if (text.length > 2)
			{
				$.ajax({
					dataType: 'json',
					async: true,
					method: "POST",
					url: "<?php echo $GLOBALS['conf']['web']?>/sequence/get.listcity/",
					data:
					{
						string_like: text
					},
					success: function (data)
					{
						//Очищаем список значений
						document.getElementById(id_select).innerHTML = "";
						//Если данные получили, то ими и заполняем список значений
						if (data.cities)
						{
							for (var city_row in data.cities)
							{
								var el_option = document.createElement('option');
								el_option.setAttribute('value', data.cities[city_row].id);
								el_option.innerHTML = data.cities[city_row].city;
								//Добавляем элемент списка
								document.getElementById(id_select).appendChild(el_option);
								$('#' + id_select).selectpicker('refresh');
							}
						}
					}
				});
			}
		},
		
		//Загружает в селект список провайдеров средствами ajax
		get_list_ips: function (text, id_select) {

			text = text || '';
			
			//Получаем выпадающий список, используемый в качестве подсказок
			var select_el_ul = document.getElementById(id_select).parentNode;
			select_el_ul = select_el_ul.getElementsByTagName('div')[0].getElementsByTagName('ul')[0];
			select_el_ul.innerHTML = "";
			//Если длина символов >= трёх, то делаем запрос к данным.
			if (text.length > 2)
			{
				$.ajax({
					dataType: 'json',
					async: true,
					method: "POST",
					url: "<?php echo $GLOBALS['conf']['web']?>/sequence/get.listips/",
					data:
					{
						string_like: text
					},
					success: function (data) 
					{
						//Очищаем список значений
						document.getElementById(id_select).innerHTML = "";
						
						//Если данные получили, то ими и заполняем список значений
						if (data.ips)
						{
							for (var ips_row in data.ips)
							{
								var el_option = document.createElement('option');
								el_option.setAttribute('value', data.ips[ips_row].id);
								el_option.innerHTML = data.ips[ips_row].ips;
								//Добавляем элемент списка
								document.getElementById(id_select).appendChild(el_option);
								$('#' + id_select).selectpicker('refresh');
							}
						}
					}
				});
			}
		},
		
		//Загружает в селект список диапазонов адресов средствами ajax
		get_list_ip_diap: function (text, id_select) {

			text = text || '';
			
			//Получаем выпадающий список, используемый в качестве подсказок
			var select_el_ul = document.getElementById(id_select).parentNode;
			select_el_ul = select_el_ul.getElementsByTagName('div')[0].getElementsByTagName('ul')[0];
			select_el_ul.innerHTML = "";
			//Если длина символов >= трёх, то делаем запрос к данным.
			if (text.length > 1)
			{
				$.ajax({
					dataType: 'json',
					async: true,
					method: "POST",
					url: "<?php echo $GLOBALS['conf']['web']?>/sequence/get.listipdiap/",
					data:
					{
						string_like: text
					},
					success: function (data) 
					{
						//Очищаем список значений
						document.getElementById(id_select).innerHTML = "";
						
						//Если данные получили, то ими и заполняем список значений
						if (data.ip_diap)
						{
							for (var ip_diap_row in data.ip_diap)
							{
								var el_option = document.createElement('option');
								el_option.setAttribute('value', data.ip_diap[ip_diap_row].id);
								el_option.innerHTML = data.ip_diap[ip_diap_row].prefix;
								//Добавляем элемент списка
								document.getElementById(id_select).appendChild(el_option);
								$('#' + id_select).selectpicker('refresh');
							}
						}
					}
				});
			}
		},
		
		//Заполняет список типов сравнения
		//А также управляет
		fill_type_cond: function (select_element, select_value, counter_cond) {
			
			//Значение в выпадающем списке по умолчанию
			var default_select_field = 0;
			
			//Получаем выпадающие списки
			var value_cond = document.getElementById('value_cond_' + counter_cond);
			var type_cond = document.getElementById('type_cond_' + counter_cond);
			var value_select_cond = document.getElementById('value_select_cond_' + counter_cond);
			
			//Очищаем условие
			value_cond.value = "";
			type_cond.innerHTML = "";
			value_select_cond.innerHTML = "";			
			
			//Если выбрано поле, которое влияет на тип сравнения
			if (this.list_fields_selective_conditions[select_value])
			{
				//Массив идентификаторов полей, которые будут вставлены
				var arr_type_cond = this.list_fields_selective_conditions[select_value];

				//Заполняем типы сравнения, только определённым значениями
				for (var id_type_cond in arr_type_cond)
				{
					var el_option = document.createElement('option');
					//Устанавливаем параметры элемента списка
					el_option.setAttribute('value', arr_type_cond[id_type_cond]);
					el_option.innerHTML = this.list_conditions_type[arr_type_cond[id_type_cond]];
					//Добавляем элемент списка
					document.getElementById('type_cond_' + counter_cond).appendChild(el_option);
				}
			} else
			{//Иначе генерируем стандартный список типов сравнения

				//Заполняем типы сравнения
				for (var list_type in this.list_conditions_type)
				{
					var el_option = document.createElement('option');
					//Устанавливаем параметры элемента списка
					el_option.setAttribute('value', list_type);
					el_option.innerHTML = this.list_conditions_type[list_type];
					//Добавляем элемент списка
					document.getElementById('type_cond_' + counter_cond).appendChild(el_option);
				}
			}
			
			/**
			* Если выбрано поле, которое влияет на сравниваемое значение,
			* и данные необходимо загрузить с помощью ajax,
			* то вместо текстового поля, будем выводить выпадающий список со значениями,
			* которые получаем с помощью отдельных функций.
			* */
			if (this.list_fields_load_values[select_value])
			{
				var arr_value_cond = this.list_fields_load_values[select_element.value]; //Массив идентификаторов полей, которые будут вставлены
				
				try
				{
					//Получаем текстовое поле в выпадающем списке
					var select_el_input = document.getElementById('value_select_cond_' + counter_cond).parentNode;
					select_el_input = select_el_input.getElementsByTagName('div')[0].getElementsByTagName('div')[0].getElementsByTagName('input')[0];
					
					//Добавляем событие
					if (arr_value_cond == 'geo')
					{
						select_el_input.setAttribute('onkeyup', "conditionsUserProperty.get_list_city(this.value,'" + "value_select_cond_" + counter_cond + "');");
					}
					else if (arr_value_cond == 'ips')
					{
						select_el_input.setAttribute('onkeyup', "conditionsUserProperty.get_list_ips(this.value,'" + "value_select_cond_" + counter_cond + "');");
					}
					else if (arr_value_cond == 'ip_diap')
					{
						select_el_input.setAttribute('onkeyup', "conditionsUserProperty.get_list_ip_diap(this.value,'" + "value_select_cond_" + counter_cond + "');");
					}
				}
				catch (err) {
				//console.info('Не удалось установить событие.');
				}
			}
			else if (this.list_fields_static_values[select_value])
			{
				
			/**
			* Если выбрано поле, которое влияет на сравниваемое значение,
			* и данные необходимо загрузить из конфиг. списка list_fields_static_values,
			* то вместо текстового поля, будем выводить выпадающий список со значениями,
			* которые получаем из list_fields_static_values.
			* */
				
				//Получаем значения, которые подгружаются в выпадающий список
				var data_select = this.list_fields_static_values[select_value];
				
				//Получаем выпадающий список, используемый в качестве подсказок
				var select_el_ul = value_select_cond.parentNode;
				select_el_ul = select_el_ul.getElementsByTagName('div')[0].getElementsByTagName('ul')[0];
				select_el_ul.innerHTML = "";
				
				//Очищаем список значений
				value_select_cond.innerHTML = "";
				//console.info(this.list_fields_static_values[select_value]);


				var cntr_option = 0;
				for (var data_select_row in data_select)
				{
					var el_option = document.createElement('option');
					el_option.setAttribute('value', data_select_row);
					
					if (cntr_option == 0)
					{
						el_option.setAttribute('selected', 'selected');
						cntr_option ++;
					}
					el_option.innerHTML = data_select[data_select_row];
					
					//Добавляем элемент списка
					value_select_cond.appendChild(el_option);
				}
			}
			
			//Вызовем обработчик отображения элементов группы условий
			this.hide_show_group_el(counter_cond);
		},
		
		/**
		* Функция обработчик отображения элементов группы условий
		 */
		hide_show_group_el: function(counter_cond){
			
			//Получаем элементы Сравниваемое поле; Тип сравнения; Группа выбора дат.
			var field_cond = document.getElementById('field_' + counter_cond);
			var type_cond = document.getElementById('type_cond_' + counter_cond);
			var value_cond = document.getElementById('value_cond_' + counter_cond);
			var value_select_cond = document.getElementById('value_select_cond_' + counter_cond);
			var dp_block_cond = document.getElementById('dp_block_cond_' + counter_cond);
			var dps = document.getElementById('dps_' + counter_cond);
			var dpe = document.getElementById('dpe_' + counter_cond);
			
			
			//Обновляем выпадающие списки
			$('#' + 'type_cond_' + counter_cond).selectpicker('refresh');
			$('#' + 'value_select_cond_' + counter_cond).selectpicker('refresh');
			//Обновляем элементы выбора дат			
			$('#' + 'dps_' + counter_cond).datetimepicker('refresh');
			$('#' + 'dpe_' + counter_cond).datetimepicker('refresh');
			
			/**
			* Прицепим обработчики событий для элементов дат
			*/
			$('#' + 'dps_' + counter_cond).datetimepicker({
				onSelectDate:function($dtp,current,input){
					conditionsUserProperty.update_datetime_val(counter_cond);
				},
				onSelectTime:function($dtp,current,input){
					conditionsUserProperty.update_datetime_val(counter_cond);
				},
			});

			$('#' + 'dpe_' + counter_cond).datetimepicker({
				onSelectDate:function($dtp,current,input){
					conditionsUserProperty.update_datetime_val(counter_cond);
				},
				onSelectTime:function($dtp,current,input){
					conditionsUserProperty.update_datetime_val(counter_cond);
				}
			});
				
			if (this.list_fields_load_values[field_cond.value]){
				//Если требуется выпадающий список со значениями из ajax
				value_cond.classList.add('hide');
				value_select_cond.classList.remove('hide');
				if(typeof(value_select_cond.parentNode) !== "undefined"){
					value_select_cond.parentNode.classList.remove('hide');
				}
				$('#' + 'value_select_cond_' + counter_cond).selectpicker('show');
				$('#' + 'value_select_cond_' + counter_cond).selectpicker('refresh');
				dp_block_cond.classList.add('hide');
			}
			else if (this.list_fields_static_values[field_cond.value]){
				//Если требуется выпадающий список с определёнными значениями
				value_cond.classList.add('hide');
				value_select_cond.classList.remove('hide');
				if(typeof(value_select_cond.parentNode) !== "undefined"){
					value_select_cond.parentNode.classList.remove('hide');
				}
				$('#' + 'value_select_cond_' + counter_cond).selectpicker('show');
				$('#' + 'value_select_cond_' + counter_cond).selectpicker('refresh');
				dp_block_cond.classList.add('hide');
			}
			else if(this.list_dp[field_cond.value] && this.list_dp[field_cond.value] == type_cond.value){
				//Если требуются поля ввода дат
				$('#' + 'value_select_cond_' + counter_cond).selectpicker('hide');
				value_cond.classList.add('hide');
				dp_block_cond.classList.remove('hide');
			}else{
				//Если не требуется никаких изменений для ввода значения
				$('#' + 'value_select_cond_' + counter_cond).selectpicker('hide');
				value_cond.classList.remove('hide');
				dp_block_cond.classList.add('hide');
			}
		},
		
		update_datetime_val: function(counter_cond){
			var value_cond = document.getElementById('value_cond_' + counter_cond);
			var dps = document.getElementById('dps_' + counter_cond);
			var dpe = document.getElementById('dpe_' + counter_cond);
			var dps_local = (new Date(dps.value + ':00').getTime())/1000;
			var dpe_local = (new Date(dpe.value + ':00').getTime())/1000;
			
			var offset = (new Date).getTimezoneOffset() * 60;
			console.info(offset);
			
			value_cond.value = (dps_local - offset) + '-' + (dpe_local- offset);

		},
		/*
		 * Добавляет группу условий.
		 * Эта функция вызывается в случае добавления группы условий,
		 * при загрузке страницы и при каждом добавлении новой группы условий.
		 */
		add: function () {
			//Создаём элементы из шаблона
		/*	
    var a = document.getElementById('condition_tpl').innerHTML,
				b = a.replace(/'{{counter}}'/g, this.counter);
    */
			var div = document.createElement('div');
			//div.innerHTML = b;
			var field_name = 'field_' + this.counter;
			var type_cond_name = 'type_cond_' + this.counter;
			var dps_name = 'dps_' + this.counter;
			var dpe_name = 'dpe_' + this.counter;
      
      //Создадим нужные элементы для условия
      var fieldset = document.createElement('fieldset');
      fieldset.setAttribute('class', 'scheduler-border');
      
      var legend = document.createElement('legend');
      legend.setAttribute('class', 'scheduler-border');
      legend.setAttribute('align', 'right');
      legend.setAttribute('onclick', 'conditionsUserProperty.remove(this);');
      
      var span_legend = document.createElement('span');
      span_legend.setAttribute('class', 'glyphicon glyphicon-remove-circle');
      legend.appendChild(span_legend);
      
      var br = document.createElement('br');
      
      fieldset.appendChild(legend);
      fieldset.appendChild(br);
      
      var div_form_construct = document.createElement('div');
      div_form_construct.setAttribute('id', 'form_construct');
      
      /**
       * Элемент Сравниваемое поле
       */
      var div_form_group_field = document.createElement('div');
      div_form_group_field.setAttribute('class', 'form-group');
      
      var label_field = document.createElement('label');
      label_field.setAttribute('for', 'value_cond');
      label_field.innerHTML = 'Сравниваемое поле:';
      
      var select_field = document.createElement('select');
      select_field.setAttribute('data-live-search', 'true');
      select_field.setAttribute('data-width', '150px');
      select_field.setAttribute('class', 'selectpicker');
      select_field.setAttribute('name', 'field['+this.counter+']');
      select_field.setAttribute('id', 'field_'+this.counter);
      select_field.setAttribute('onchange', 'conditionsUserProperty.fill_type_cond(this, this.value, '+this.counter+');');
      
      
      //Заполним список сравниваемых полей
      for(var el_field in this.list_fields){
        var select_option = document.createElement('option');
        select_option.setAttribute('value', el_field);
        select_option.innerHTML = this.list_fields[el_field];
        select_field.appendChild(select_option);
      }
      
      div_form_group_field.appendChild(label_field);
      div_form_group_field.appendChild(select_field);
      
      /**
       * Элемент Тип сравнения
       */
      var div_form_group_type = document.createElement('div');
      div_form_group_type.setAttribute('class', 'form-group');
      
      var label_type = document.createElement('label');
      label_type.setAttribute('for', 'value_cond');
      label_type.innerHTML = 'Тип сравнения:';
      
      var select_type = document.createElement('select');
      select_type.setAttribute('data-live-search', 'true');
      select_type.setAttribute('data-width', '150px');
      select_type.setAttribute('class', 'selectpicker');
      select_type.setAttribute('name', 'type_cond['+this.counter+']');
      select_type.setAttribute('id', 'type_cond_'+this.counter);
      select_type.setAttribute('onchange', 'conditionsUserProperty.hide_show_group_el('+this.counter+');');
      
      div_form_group_type.appendChild(label_type);
      div_form_group_type.appendChild(select_type);
      
      /**
       * Элемент Значение
       */
      var div_label_value = document.createElement('label');
      div_label_value.setAttribute('for', 'value_cond');
      div_label_value.innerHTML = 'Значение:';
      
      var div_form_group_value = document.createElement('div');
      div_form_group_value.setAttribute('class', 'form-group hide_el');
      
      var input_value = document.createElement('input');
      input_value.setAttribute('class', 'form-control hide');
      input_value.setAttribute('type', 'text');
      input_value.setAttribute('placeholder', 'Условие');
      input_value.setAttribute('size', '35');
      input_value.setAttribute('name', 'value_cond['+this.counter+']');
      input_value.setAttribute('id', 'value_cond_'+this.counter);
      
      var select_value = document.createElement('select');
      select_value.setAttribute('class', 'selectpicker');
      select_value.setAttribute('name', 'value_select_cond['+this.counter+']');
      select_value.setAttribute('id', 'value_select_cond_'+this.counter);
      select_value.setAttribute('title', 'Начните набирать условие');
      select_value.setAttribute('placeholder', 'Начните набирать');
      select_value.setAttribute('data-live-search', 'true');
      select_value.setAttribute('data-width', '150px');
      
      var div_dp_block_cond = document.createElement('div');
      div_dp_block_cond.setAttribute('class', 'hide');
      div_dp_block_cond.setAttribute('id', 'dp_block_cond_'+this.counter);
      
      var div_input_group_dps = document.createElement('div');
      div_input_group_dps.setAttribute('class', 'input-group');
      
      var input_dps = document.createElement('input');
      input_dps.setAttribute('class', 'form-control datetimepicker');
      input_dps.setAttribute('type', 'text');
      input_dps.setAttribute('onchange', 'conditionsUserProperty.update_datetime_val('+this.counter+');');
      input_dps.setAttribute('name', 'dps['+this.counter+']');
      input_dps.setAttribute('id', 'dps_'+this.counter);
      input_dps.setAttribute('value', '');
      input_dps.setAttribute('placeholder', 'С');
      
      var div_input_group_addon_dps = document.createElement('div');
      div_input_group_addon_dps.setAttribute('class', 'input-group-addon');
      
      var span_dps = document.createElement('span');
      span_dps.setAttribute('class', 'glyphicon glyphicon-th');
      
      div_input_group_addon_dps.appendChild(span_dps);
      
      div_input_group_dps.appendChild(input_dps);
      div_input_group_dps.appendChild(div_input_group_addon_dps);

      var div_input_group_dpe = document.createElement('div');
      div_input_group_dpe.setAttribute('class', 'input-group');
      
      var input_dpe = document.createElement('input');
      input_dpe.setAttribute('class', 'form-control datetimepicker');
      input_dpe.setAttribute('type', 'text');
      input_dpe.setAttribute('onchange', 'conditionsUserProperty.update_datetime_val('+this.counter+');');
      input_dpe.setAttribute('name', 'dpe['+this.counter+']');
      input_dpe.setAttribute('id', 'dpe_'+this.counter);
      input_dpe.setAttribute('value', '');
      input_dpe.setAttribute('placeholder', 'По');
      
      var div_input_group_addon_dpe = document.createElement('div');
      div_input_group_addon_dpe.setAttribute('class', 'input-group-addon');
      
      var span_dpe = document.createElement('span');
      span_dpe.setAttribute('class', 'glyphicon glyphicon-th');
      
      div_input_group_addon_dpe.appendChild(span_dpe);
      
      div_input_group_dpe.appendChild(input_dpe);
      div_input_group_dpe.appendChild(div_input_group_addon_dpe);
      
      div_dp_block_cond.appendChild(div_input_group_dps);
      div_dp_block_cond.appendChild(div_input_group_dpe);
      
      div_form_group_value.appendChild(input_value);
      div_form_group_value.appendChild(select_value);
      div_form_group_value.appendChild(div_dp_block_cond);
      
      /**
       * Элемент И/ИЛИ
       */
      var div_form_group_andor = document.createElement('div');
      div_form_group_andor.setAttribute('class', 'form-group');
      
      var div_button_andor = document.createElement('div');
      div_button_andor.setAttribute('class', 'btn-group');
      div_button_andor.setAttribute('data-toggle', 'buttons');
      
      var button_andor_and = document.createElement('input');
      button_andor_and.setAttribute('type', 'radio');
      button_andor_and.setAttribute('name', 'andor['+this.counter+']');
      button_andor_and.setAttribute('value', 'AND');
      button_andor_and.setAttribute('checked', 'checked');
      
      var label_andor_and = document.createElement('label');
      label_andor_and.setAttribute('class', 'btn btn-default active');
      
      label_andor_and.appendChild(button_andor_and);
      label_andor_and.innerHTML += 'И';
      
      var button_andor_or = document.createElement('input');
      button_andor_or.setAttribute('type', 'radio');
      button_andor_or.setAttribute('name', 'andor['+this.counter+']');
      button_andor_or.setAttribute('value', 'OR');
      
      var label_andor_or = document.createElement('label');
      label_andor_or.setAttribute('class', 'btn btn-default');
      
      label_andor_or.appendChild(button_andor_or);
      label_andor_or.innerHTML += 'ИЛИ';
      
      div_button_andor.appendChild(label_andor_and);
      div_button_andor.appendChild(label_andor_or);
      
      div_form_group_andor.appendChild(div_button_andor);
      
      /**
       * Вставка элементов в условие
       */
      div_form_construct.appendChild(div_form_group_field);
      div_form_construct.appendChild(div_form_group_type);
      div_form_construct.appendChild(div_label_value);
      div_form_construct.appendChild(div_form_group_value);
      div_form_construct.appendChild(div_form_group_andor);
      
      fieldset.appendChild(div_form_construct);
      
      div.appendChild(fieldset);
			
			//Добавляем группу условий на страницу
			document.getElementById('conditions').appendChild(div);
			
			/*
		  * Сразу обновим список типов сравнений
		  * И в нём же будет выполнена проверка установленного поля и,
		  * при необходимости, текстовое поле значения будет заменено на выпадающий список.
		  */
		 
			this.fill_type_cond(document.getElementById(type_cond_name), 1, this.counter);
			
			//Покажем выпадающие списки, нужно для корректной работы bootstrap-select
			$('#' + field_name).selectpicker('show');
			$('#' + type_cond_name).selectpicker('show');
			$('#' + dps_name).datetimepicker('refresh');
			$('#' + dpe_name).datetimepicker('refresh');
			this.counter++;
		},
		/*
	   * Функция выполняет установку события для выпадающих списков значений,
	   * в зависимости от поля.
		 * Эта функция вызывается в случае изменения группы условий,
		 * при загрузке страницы.
	  */
		init: function () {

			for (var i = 0; i < this.counter; i++)
			{
				var name_value_select_cond = 'value_select_cond_' + i;
				
				//Вызовем обработчик отображения элементов группы условий
				this.hide_show_group_el(i);
				
				try
				{
					$('#' + name_value_select_cond).on('loaded.bs.select', function (e)
						{
							//console.info("conditionsUserProperty.get_list_city(this.value,'" + name_value_select_cond + "');");
							var field_value = e.target.parentNode.parentNode.parentNode.parentNode.querySelector('.selectpicker').value;
							
							//Получаем текстовое поле в выпадающем списке
							var select_el_input = e.target.parentNode.getElementsByTagName('div')[0].getElementsByTagName('div')[0].getElementsByTagName('input')[0];
							
							//Получаем идентификатор выпадающего списка значений
							var id_value_select_cond = e.target.parentNode.getElementsByTagName('select')[0].getAttribute('id');
							
							if (conditionsUserProperty.list_fields_load_values[field_value])
							{
								arr_value_cond = conditionsUserProperty.list_fields_load_values[field_value];
								//Добавляем событие
								if (arr_value_cond == 'geo')
								{
									select_el_input.setAttribute('onkeyup', "conditionsUserProperty.get_list_city(this.value,'" + id_value_select_cond + "');");
								}
								else if (arr_value_cond == 'ips')
								{
									select_el_input.setAttribute('onkeyup', "conditionsUserProperty.get_list_ips(this.value,'" + id_value_select_cond + "');");
								}
								else if (arr_value_cond == 'ip_diap')
								{
									select_el_input.setAttribute('onkeyup', "conditionsUserProperty.get_list_ip_diap(this.value,'" + id_value_select_cond + "');");
								}
							}
						});
				}
				catch (err) {
				//console.info('Не удалось установить событие.');
				}
			}
		},
		
		//Удаляет группу условий
		remove: function (cond_element) {
      el = cond_element.parentNode;
      
      el.parentNode.removeChild(el);
			//cond_element.parentElement.remove();
		},
		
		//Отправляет форму с условиями
		save: function () {
			document.getElementById('conditions').submit();
		}
	};
</script>
<div class="container-fluid">
  <div class="row">
    <div class="col-sm-3 col-md-2 sidebar">
			<?php common_setAloneView('menu/menu'); ?>
    </div>
    <!--main-->
    <div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
      <h1 class="page-header">Конструктор условий для получения статистики по пользователю.</h1>
      <div class="alert alert-info" role="alert">
        Поле "<b>Название условия</b>" является информационным.
        <b>Варианты сравнений</b> меняются, в зависимости от <b>сравниваемого поля.</b>
        <br />
        Если выбрано поле "<b>Города</b>" или "<b>Провайдеры</b>", то типом сравнения будет "<b>Точное соответствие</b>".
        <br />
        Если выбрано поле "<b>Диапазон IP</b>", то типом сравнения будет "<b>Маска сети</b>".
        <br />
        Также, в этих случаях, поле "<b>Значение</b>" будет заменено выпадающим списком, в которое будут добавлятся варианты, по мере набора символов.
        <br />
        Для полей "Шаг", "Продожительность" - указывается диапазон чере тире, пример: 1483218000-1483218018.
        <br />
        Для поля "Время", когда "Тип сравнения" = "Интервал" - появляются дополнительные элементы "Календари", позволяющие выбрать дату и время.
        <br />
        Если сохраняемый набор условий уже есть в БД, то сохранения не произойдёт, и будет выведено об этом уведомление.
				<br />
				Если ни одного условия не указано, то сохранения не произойдёт.
      </div>
      <div class="header">
        <input type="button" class="btn btn-primary" onclick="window.location = '/condition_user_property/';" value="Вернутся к списку условий">
      </div>
      <br />
			
      <form id="conditions" name="conditions" method="post" class="form-inline">

        <div class="input-group input-group custom-input">
          <input type="text" class="form-control ci-input" name="name" placeholder="Название условия" value="<?php (!empty($params['conditions']['name'])) ? print($params['conditions']['name']) : ''; ?>">
          <input type="hidden" name="id" value="<?php (!empty($params['conditions']['id'])) ? print($params['conditions']['id']) : ''; ?>">
          <small class="text-muted">Произвольное название группы условий.</small>
        </div>
				<?php
					$invers = (!empty($params['conditions']['invers']) && $params['conditions']['invers'] == 1)?1:0;
				?>
				<div class="input-group input-group custom-input">
					<div class="btn-group" data-toggle="buttons">
						<label class="btn btn-default <?php print(($invers != 1) ? 'active' : ''); ?>">
							<input name="invers" type="radio" value="0" <?php print(($invers != 1) ? 'checked="checked"' : ''); ?>>Не инверсировать условия
						</label>
						<label class="btn btn-default <?php print(($invers == 1) ? 'active' : ''); ?>">
							<input name="invers" type="radio" value="1" <?php print(($invers == 1) ? 'checked="checked"' : ''); ?>>Инверсировать условия
						</label>
					</div>
					<br />
					<small class="text-muted">Иногда появляется необходимоть инверсировать битовый результат для всей группы условий, для этих целей достаточно нажать кнопку "Инверсировать условия".</small>
				</div>
				<?php
				//Если производится операция обновления, и данные есть
				if ($params['update'] && !empty($params['conditions']) && !empty($params['conditions']['json_cond']))
				{//Если есть условия, то заполняем форму
					$counter = 0;
					foreach ($params['conditions']['json_cond'] as $key_json_cond => $val_json_cond):
						//Получим значения групы условий
						$param_field = $val_json_cond['field'];
						$param_type_cond = $val_json_cond['type_cond'];
						$param_value_cond = (string)$val_json_cond['value_cond'];
						$param_andor = $val_json_cond['andor'];
						$dps = '';
						$dpe = '';
									
						?>

						<fieldset class="scheduler-border">
							<legend align="right" class="scheduler-border" onclick="conditionsUserProperty.remove(this);"><span class="glyphicon glyphicon-remove-circle"></span></legend>
							<br />
							<div id="form_construct">

								<div class="form-group">
									<label for="field">Сравниваемое поле:</label>
									<select data-live-search="true" data-width="150px" class="selectpicker" name="field[<?php print($counter); ?>]" id="field_<?php print($counter); ?>" onchange="conditionsUserProperty.fill_type_cond(this, this.value, <?php print($counter); ?>);">
										<?php
										//Заполняем сравниваемые поля
										foreach ($params['list_fields'] as $key_list_fields => $val_list_fields) {
											$selected = ($param_field == $key_list_fields) ? 'selected="selected"' : '';
											print("<option value='$key_list_fields' $selected>$val_list_fields</option>");
										}
										?>
									</select>
								</div>
								
								<div class="form-group">
									<label for="type_cond">Тип сравнения:</label>
									<select data-live-search="true" data-width="150px" class="selectpicker" name="type_cond[<?php print($counter); ?>]" id="type_cond_<?php print($counter); ?>" onchange="conditionsUserProperty.hide_show_group_el(<?php print($counter); ?>);">
										<?php
										/*
										 * Статус фильтра типов сравнений
										 */
										$filter_conditions_type = (!empty($params['list_fields_selective_conditions'][$param_field]))?true:false;
										foreach ($params['list_conditions_type'] as $key_list_conditions_type => $val_list_conditions_type) {
											$selected = ($param_type_cond == $key_list_conditions_type) ? 'selected="selected"' : '';
											
											//Если для сравниваемого поля используются только определённые типы сравнения
											if ($filter_conditions_type) {												
												//Выбираем только, те, которые есть в list_fields_selective_conditions для выбранного поля
												if (in_array($key_list_conditions_type, $params['list_fields_selective_conditions'][$param_field])) {
													print("<option value='$key_list_conditions_type' $selected>$val_list_conditions_type</option>");
												}
											} else {
												print("<option value='$key_list_conditions_type' $selected>$val_list_conditions_type</option>");
											}
										}
										?>
									</select>
								</div>
								
								<label for="value_cond">Значение:</label>
								<div class="form-group">
									
									<?php
									
									//Статус заполнения данных ajax запросом
									$load_ajax_value = (!empty($params['list_fields_load_values'][$param_field]))?true:false;
									//Статус заполнения данных из конфиг. списка list_fields_static_values
									$load_static_value = (!empty($params['list_fields_static_values'][$param_field]))?true:false;
									//Статус заполнения элементов дат
									$time_data = (!empty($params['list_dp'][$param_field])
											&& $params['list_dp'][$param_field] == $param_type_cond)?true:false;
									
									//Статус применения выпадающего списка для выбора значений
									$select_fill = ($load_ajax_value || $load_static_value)?true:false;
									?>
									<input class="form-control  <?php print(($select_fill) ? 'hide' : ''); ?>" size="35" id="value_cond_<?php print($counter); ?>" name="value_cond[<?php print($counter); ?>]" type="text" placeholder="Условие" value="<?php print($param_value_cond); ?>">
									<select class="val_select selectpicker <?php print(($select_fill) ? '' : 'hide'); ?>" title="Начните набирать условие" data-live-search="true" data-width="150px" placeholder="Начните набирать" name="value_select_cond[<?php print($counter); ?>]" id="value_select_cond_<?php print($counter); ?>">
										<?php
										
											$data_select = null;
											if ($param_value_cond && $load_ajax_value)
											{//Если значения подгружаются ajax
												common_inc('misk_sequence');
												switch ($params['list_fields_load_values'][$param_field])
												{
													case 'geo':
														$data_select = getCityFromId($param_value_cond);
														print("<option value='$param_value_cond' selected='selected'>{$data_select['city']}</option>");
														break;
													case 'ips':
														$data_select = getIpsFromId($param_value_cond);
														print("<option value='$param_value_cond' selected='selected'>{$data_select['ips']}</option>");
														break;
													case 'ip_diap':
														$data_select = getIpDiapFromId($param_value_cond);
														print("<option value='$param_value_cond' selected='selected'>{$data_select['prefix']}</option>");
														break;
												}
											} elseif (($param_value_cond || $param_value_cond === 0 || $param_value_cond === '0') && $load_static_value)
											{//Если значения загружаются из конфиг. списка list_fields_static_values
												$list_select = $params['list_fields_static_values'][$param_field];

												foreach ($list_select as $key_list => $val_list)
												{
													$selected = ($param_value_cond == $key_list) ? 'selected="selected"' : '';
													print("<option value='$key_list' $selected>$val_list</option>");
												}
											} elseif (($param_value_cond) && $time_data){
												//Если значение является интервалом времени
												$dp_arr = explode('-',$param_value_cond);
												$dps = ($dp_arr[0])?gmdate('Y/m/d H:i', $dp_arr[0]):'';
												$dpe = ($dp_arr[1])?gmdate('Y/m/d H:i', $dp_arr[1]):'';
											}
										?>

									</select>
									<div class="hide" id="dp_block_cond_<?php print($counter); ?>">
										<div class="input-group">
											<input type="text" class="form-control datetimepicker" onchange="conditionsUserProperty.update_datetime_val(<?php print($counter); ?>);" placeholder="С" name="dps[<?php print($counter); ?>]" id="dps_<?php print($counter); ?>" att="<?php print($dps); ?>" value="<?php print($dps); ?>">
											<div class="input-group-addon">
													<span class="glyphicon glyphicon-th"></span>
											</div>
										</div>
										-
										<div class="input-group">
											<input type="text" class="form-control datetimepicker" onchange="conditionsUserProperty.update_datetime_val(<?php print($counter); ?>);" placeholder="По" name="dpe[<?php print($counter); ?>]" id="dpe_<?php print($counter); ?>" att="<?php print($dpe); ?>" value="<?php print($dpe); ?>">
											<div class="input-group-addon">
													<span class="glyphicon glyphicon-th"></span>
											</div>
										</div>
									</div>
								</div>

								<div class="form-group">
									<div class="btn-group" data-toggle="buttons">
										<label class="btn btn-default <?php print(($param_andor == 'AND') ? 'active' : ''); ?>">
											<input name="andor[<?php print($counter); ?>]" type="radio" value="AND" <?php print(($param_andor == 'AND') ? 'checked="checked"' : ''); ?>>И
										</label>
										<label class="btn btn-default <?php print(($param_andor == 'OR') ? 'active' : ''); ?>">
											<input name="andor[<?php print($counter); ?>]" type="radio" value="OR" <?php print(($param_andor == 'OR') ? 'checked="checked"' : ''); ?>>ИЛИ
										</label>
									</div>
								</div>
							</div>


						</fieldset>
						<?php $counter++; ?>
						<script>
					window.conditionsUserProperty.counter = <?php print($counter); ?>;
						</script>
					<?php endforeach; ?><?php
				} elseif($params['update'])
				{//Если производится обновление и данных нет
					print('Условий нет');
				}
				?>
				</form>
				
      <br />
				
      <input type="button" class="btn btn-primary" onclick="conditionsUserProperty.add();" value="Добавить условие">
      <input type="button" class="btn btn-primary" onclick="conditionsUserProperty.save();" value="Сохранить">

			<?php if (!empty($params['result_change'])): ?>
				<h2 class="sub-header">Результат отправки формы:</h2>
				<pre>
					<?php
					print_r($params['result_change']);
					?>
				</pre>
			<?php endif; ?>

    </div>
    <!--/main-->
  </div>
</div>

<!--Шаблон набора полей-->
<template id="condition_tpl" style="display:none;">
  <fieldset class="scheduler-border">
    <legend align="right" class="scheduler-border" onclick="conditionsUserProperty.remove(this);"><span class="glyphicon glyphicon-remove-circle"></span></legend>
    <br />
    <div id="form_construct">
      <div class="form-group">
        <label for="field">Сравниваемое поле:</label>
        <select data-live-search="true" data-width="150px" class="selectpicker" name="field['{{counter}}']" id="field_'{{counter}}'" onchange="conditionsUserProperty.fill_type_cond(this, this.value, '{{counter}}');">
					<?php foreach ($params['list_fields'] as $key => $val): ?>
						<option value="<?php print($key); ?>"><?php print($val); ?></option>
					<?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="type_cond">Тип сравнения:</label>
        <select data-live-search="true" data-width="150px" class="selectpicker" name="type_cond['{{counter}}']" id="type_cond_'{{counter}}'"  onchange="conditionsUserProperty.hide_show_group_el('{{counter}}');">
          <!--<optgroup label="Соответствует"></optgroup>-->
        </select>
      </div>

			<label for="value_cond">Значение:</label>
      <div class="form-group hide_el">
        <input class="form-control hide" size="35" id="value_cond_'{{counter}}'" name="value_cond['{{counter}}']" type="text" placeholder="Условие">
        <select class="selectpicker" title="Начните набирать условие" data-live-search="true" data-width="150px" placeholder="Начните набирать" name="value_select_cond['{{counter}}']" id="value_select_cond_'{{counter}}'">

        </select>
				<div class="hide" id="dp_block_cond_'{{counter}}'">
					<div class="input-group">
						<input type="text" class="form-control datetimepicker" onchange="conditionsUserProperty.update_datetime_val('{{counter}}');" placeholder="С" name="dps['{{counter}}']" id="dps_'{{counter}}'" value="">
						<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
						</div>
					</div>
					-
					<div class="input-group">
						<input type="text" class="form-control datetimepicker" onchange="conditionsUserProperty.update_datetime_val('{{counter}}');" placeholder="По" name="dpe['{{counter}}']" id="dpe_'{{counter}}'" value="">
						<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
						</div>
					</div>
				</div>
      </div>

      <div class="form-group">
        <div class="btn-group" data-toggle="buttons">
          <label class="btn btn-default active">
            <input name="andor['{{counter}}']" type="radio" value="AND" checked="checked">И
          </label>
          <label class="btn btn-default">
            <input name="andor['{{counter}}']" type="radio" value="OR">ИЛИ
          </label>
        </div>
      </div>
    </div>

  </fieldset>

</template>
<!--/Шаблон набора полей-->