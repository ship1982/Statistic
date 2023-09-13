<?php
  common_setAloneView('statistic/inc/navbar');
?>
<style>
  .group_header{
    font-weight: bold;
    text-align: center;
  }
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
</style>
<script>
  //Цепочки
  var chains = {
    dps:0,
    dpe:0,
    dps_string:'',
    dpe_string:'',
    interval:'',
    filter_type:'interval',
    select_uuid: 'all_users',
    update_datetime_val: function(){     
			var dps = document.getElementById('dps');
			var dpe = document.getElementById('dpe');
      
      var dps_local = (new Date(chains.datetime_convert(dps.value)).getTime())/1000;
			var dpe_local = (new Date(chains.datetime_convert(dpe.value)).getTime())/1000;
			
			var offset = (new Date).getTimezoneOffset() * 60;
			
      chains.dps_string = dps.value;
      chains.dpe_string = dpe.value;
			chains.dps = (dps_local - offset);
			chains.dpe = (dpe_local- offset);
			chains.interval = (dps_local - offset) + '-' + (dpe_local- offset);
      //console.info(chains.interval);
    },
    datetime_convert: function(string_time){
      //Установим значения по умолчанию
      var date = '1970/01/01';
      var time = '00:00:00';
      var split1 = string_time.split('.');
      
      if(typeof(split1[0]) != 'undefined' && typeof(split1[1]) != 'undefined' && typeof(split1[2]) != 'undefined'){
        var split2 = split1[2].split(' ');
        date = split2[0] + '/' + split1[1] + '/' + split1[0];
        if(typeof(split2[1]) != 'indefined'){
          var split3 = split2[1].split(':');
          if(typeof(split3[0]) != 'undefined' && typeof(split3[1]) != 'undefined' && typeof(split3[2]) != 'undefined'){
            time = split3[0] + ':' + split3[1] + ':'+ split3[2];
          }else if(typeof(split3[0]) != 'undefined' && typeof(split3[1]) != 'undefined'){
            time = split3[0] + ':' + split3[1] + ':'+ '00';
          }
        }
      }
      return (date + ' ' + time);
    },
    getStat: function(id_table){
      
      var obj_result = {};
      obj_result.utm_conditions = utm_conditions.getResult();
      obj_result.page_conditions = page_conditions.getResult();
      obj_result.event_for_pages = event_for_pages.getResult();
      obj_result.param_for_pages = param_for_pages.getResult();
      obj_result.events_on_all_pages = this.getDataFromSelect('events_on_all_pages');
      obj_result.report_type = document.getElementById('report_types').value;
      obj_result.filter_type = this.filter_type;
      obj_result.partner = document.getElementById('partner').value;
      obj_result.dps = (this.filter_type == 'interval')?this.dps:0;
      obj_result.dpe = (this.filter_type == 'interval')?this.dpe:0;
      
      //console.info(obj_result);
      $.ajax({
        dataType: 'json',
        async: true,
        method: "POST",
        url: "<?php echo $GLOBALS['conf']['web']?>/chains/get/",
        data: obj_result,
        beforeSend: function(){
          console.info('Выполняется запрос данных...');
          var loading = document.getElementById('loading') || null;
          if(loading !== null){
            loading.setAttribute('style', '');
          }
          //Сбросим содержимое формы
          var data_chains = document.getElementById('data_chains') || null;
          if(data_chains !== null){
            data_chains.innerHTML = '';
          }
          var el_data_summ = document.getElementById('data_summ') || null;
          if(el_data_summ !== null){
            el_data_summ.innerHTML = 0;
          }
        },
        success: function (data){
          if(typeof(data.data != 'undefined')){
            data_parser.insert_rows_to_table(data.data, 'data_chains');
          }
          if(typeof(data.data_summ != 'undefined')){
            var el_data_summ = document.getElementById('data_summ') || null;
            if(el_data_summ !== null){
              el_data_summ.innerHTML = data.data_summ;
            }
          }
          if(typeof(data.count_bot != 'undefined')){
            var el_count_bot = document.getElementById('count_bot') || null;
            if(el_count_bot !== null){
              el_count_bot.innerHTML = data.count_bot;
            }
          }
          if(typeof(data.count_ad != 'undefined')){
            var el_count_ad = document.getElementById('count_ad') || null;
            if(el_count_ad !== null){
              el_count_ad.innerHTML = data.count_ad;
            }
          }
          console.info('Запрос успешно выполнен.');
          var loading = document.getElementById('loading') || null;
          if(loading !== null){
            loading.setAttribute('style', 'display:none;');
          }
          //data_parser.insert_rows_to_table(data, id_table);
          //console.info(data);
        },
        error: function (){
          var loading = document.getElementById('loading') || null;
          if(loading !== null){
            loading.setAttribute('style', 'display:none;');
          }
          console.info('Не удалось выполнить запрос для получения данных.');
        }
      });
    },
     /**
     * Возвращает выбранные элементы из select'а в виде массива
     * @param {DOM element select} select_name
     * @returns {array}
     * */
    getDataFromSelect: function(select_name){
      var select = document.querySelector('select[name="'+select_name+'"]');
      var options = select.querySelectorAll("option:checked");
      
      var data = [];
      for(var i in options){
        if(options[i].value !== undefined){
          data.push(options[i].value);
        }
      }
      return data;
    },
    
  };

  //Парсер данных
  var data_parser = {
    insert_chains:function(chains, data){
      //console.info(chains);
      var panel_chains = document.getElementById('panel_chains') || null;
      if(!panel_chains){return null;}
      panel_chains.innerHTML = '';
      
      for(var chain in chains){
        var panel_heading = document.createElement('div');
        panel_heading.setAttribute('class', 'panel-heading');

        var h4 = document.createElement('h4');
        h4.setAttribute('class', 'panel-title');

        var a = document.createElement('a');
        a.setAttribute('data-toggle', 'collapse');
        a.setAttribute('data-parent', '#panel_chains');
        a.setAttribute('href', '#chain_'+chains[chain]);
        a.setAttribute('class', 'collapsed');
        a.setAttribute('aria-expanded', 'false');
        a.innerHTML = 'Цепочка №<b>'+chains[chain]+'</b>:';

        h4.appendChild(a);
        panel_heading.appendChild(h4);
        panel_chains.appendChild(panel_heading);

        var panel_obj = document.createElement('div');
        panel_obj.setAttribute('id','chain_'+chains[chain]);
        panel_obj.setAttribute('class', 'panel-collapse collapse');
        panel_obj.setAttribute('aria-expanded', 'false');
        panel_obj.setAttribute('style', 'height: 0px;');
        
        var treeview_id = 'treeview_'+chains[chain];
        //Проверяем наличие данных для цепочки
        if(typeof(data[chains[chain]] != 'undefined')){
          var table = document.createElement('table');
          table.setAttribute('class', 'table table-striped table2excel');
          var tbody = document.createElement('tbody');
          tbody.setAttribute('id','table_'+chains[chain]);
          table.appendChild(tbody);
          
          var treeview = document.createElement('div');
          treeview.setAttribute('class', 'treeview');
          treeview.setAttribute('id', treeview_id);
        }
        
        var panel_body = document.createElement('div');
        panel_body.setAttribute('class','panel-body');
        panel_body.appendChild(treeview);
        
        //panel_body.appendChild(table);

        panel_obj.appendChild(panel_body);
        panel_chains.appendChild(panel_obj);
        
        //Проверяем наличие данных для цепочки
        if(typeof(data[chains[chain]] != 'undefined')){
          //Инициируем дерево
          var defaultData = 
          [
            {
              text: 'Parent 1',
              href: '#parent1',
              tags: ['4','2'],
              nodes: [
                {
                  text: 'Child 1',
                  href: '#child1',
                  tags: ['2'],
                  nodes: [
                    {
                      text: 'Grandchild 1',
                      href: '#grandchild1',
                      tags: ['0']
                    },
                    {
                      text: 'Grandchild 2',
                      href: '#grandchild2',
                      tags: ['0']
                    }
                  ]
                },
                {
                  text: 'Child 2',
                  href: '#child2',
                  tags: ['0']
                }
              ]
            },
            {
              text: 'Parent 2',
              href: '#parent2',
              tags: ['0']
            },
            {
              text: 'Parent 3',
              href: '#parent3',
               tags: ['0']
            },
            {
              text: 'Parent 4',
              href: '#parent4',
              tags: ['0']
            },
            {
              text: 'Parent 5',
              href: '#parent5'  ,
              tags: ['0']
            }
          ];
          $('#'+treeview_id).treeview({
            //color: "#428bca",
            showTags: true,
            data: defaultData
          });
          
          //var r = this.insert_rows_to_table(data[chains[chain]], 'table_'+chains[chain]);
          //console.info(r);
        }
      }
    },
    insert_rows_to_table: function(data, id_tbody, fields_in, type_data){
      var tbody = document.getElementById(id_tbody) || null;
      var table = tbody.parentNode;
      table.setAttribute('class', 'table table-striped table2excel');
      var fields = fields_in || null;
      var data_rows = data || [];
      if(!tbody){return null;}
      
      //Почистим содержимое таблицы
      tbody.innerHTML = '';
      
      for(var i in data_rows){
        var tr = document.createElement('tr');
        //Если были указаны определённые поля для вывода
        if(fields !== null){
          for(var j=0; j < fields.length; j++){
            var data_td = data_rows[i][fields[j]];
            if(typeof(data_td) !== 'undefined'){
              var td = document.createElement('td');
              td.innerHTML = data_td;
              tr.appendChild(td);
            }
          }
        //Если выводяться все поля
        }else{
          if(type_data == 'array'){
            for(var row in data_rows[i]){
              var data_td = data_rows[i][row];
              if(typeof(data_td) !== 'undefined'){
                var td = document.createElement('td');
                td.innerHTML = data_td;
                tr.appendChild(td);
              }
            }
          }else{
            var data_td_key = i;
            var data_td_val = data_rows[i];
            if(typeof(data_td_key) !== 'undefined' && typeof(data_td_val) !== 'undefined' ){
              //цепочки одного типа
              var td = document.createElement('td');
              td.innerHTML = data_td_key;
              tr.appendChild(td);
              //количество цепочек одного типа
              td = document.createElement('td');
              td.innerHTML = data_td_val[0];
              tr.appendChild(td);
              //количество ботов среди цепочек одного типа
              td = document.createElement('td');
              td.innerHTML = data_td_val[1];
              tr.appendChild(td);
              //количество адблоков среди цепочек одного типа
              td = document.createElement('td');
              td.innerHTML = data_td_val[2];
              tr.appendChild(td);
            }
          }
        }
        //Вставляем строки в тело таблицы
        tbody.appendChild(tr);
      }
      return data;
    }
  };
  
  //Пишем класс на JS :)))
  
   /**
   * Работает в пяти режимах:
   * 1. Создаёт статичные элементы для задания условий
   * 2. Создаёт элементы для задания условий, в котором при изменении сравниваемого значения, заполняется выпадающий список с вариантами занчений.
   * 3. Создаёт элементы для задания условий, в котором при изменении сравниваемого значения, заполняется выпадающий список с вариантами типов сравнений.
   * 4. Создаёт элементы для задания условий, в котором при изменении типа сравнения, заполняется выпадающий список с вариантами значений.
   * 5. Создаёт элементы для задания условий, в котором при изменении сравниваемого значения заполняется список с вариантами типов сравнения, а при изменении типа сравнения, заполняется выпадающий список с вариантами значений.
   * 
   * @this {BuilderOfConditions} - Это первый класс на JS
   * @param {string} name_object - Название объекта, необходимо для определённых типов условий, для вызова в определённых событий.
   * @param {int} behavoiur - Тип построения условия
   * @param {array|single_value} fields - 2-мерный массив сравниваемых значений
   * @param {array|single_value} types - 2-х или 3-х мерный массив типов условий
   * @param {array|single_value} values - 2-х, 3-х, 4-х мерный массив значений
   */
  function BuilderOfConditions(name_object, behaviour, fields1, fields2, types, values){
    this._name_object = name_object || '';
    this._behaviour = behaviour || 1;
    this._pref = '';
    this._counter = 0;
    this._fields_1 = fields1 || null;
    this._fields_2 = fields2 || null;
    this._types = types || null;
    this._values = values || null;
        
    /**
     * Строит уловие
     * @param {string} id_element - Идентификатор элемента, в который нужно добавить условие
     * @param {string} pref_in - Префикс для идентификаторов элементов условий
     * @returns {boolean}
     */
    this.addCondition = function(id_element){
      var element = document.getElementById(id_element) || null;
      //Если не нашли, куда добавлять условия
      if(element === null){return false;}
      
      this._pref = id_element;
      
      var fieldset = this.__createElement('fieldset', {
        class: 'scheduler-border',
        id: this._pref+'_fieldset_cond_'+this._counter
      });
      var legend =  this.__createElement('legend', {
        class: 'scheduler-border',
        align: 'right',
        onclick: this._name_object+'.delCondition(this)'
      });
      var span_close = this.__createElement('fieldset', {
        class: 'glyphicon glyphicon-remove-circle',
        title: 'Удалить условие',
        alt: 'Удалить условие'
      });
      
      legend.appendChild(span_close);
      fieldset.appendChild(legend);

      /**
       * Группа элементов для условий
       * @type null|BuilderOfConditions.__createConditionElements.div_group
       */
      var group_condition_elements = null;
      
      //В зависимости от поведения заполняем элементы условий
      switch (this._behaviour) {
        case 1:
          group_condition_elements = this.__createConditionElements(1, 0, 1, 1);
          break;
        case 2:
          group_condition_elements = this.__createConditionElements(0, 0, 1, 1);
          
          break;
        case 3:
          group_condition_elements = this.__createConditionElements(1, 0, 0, 1);
          
          break;
        case 4:
          group_condition_elements = this.__createConditionElements(1, 1, 1, 1);
          
          break;
      }
      
      if(group_condition_elements !== null && group_condition_elements.tagName){
        fieldset.appendChild(group_condition_elements);
      }
      //Добавляем созданное условие на страницу
      element.appendChild(fieldset);
      
      //В зависимости от поведения добавляем дополнительные функции
      switch (this._behaviour) {
        case 3:
          this.fill_select(this._pref+'_field_1_cond_'+this._counter, this._fields_1);
          var key = this.__getFirstKey(this._fields_1);
          var values = this.__get2dArrayFrom3dArray(key, this._values);
          this.fill_select(this._pref+'_value_cond_'+this._counter, values);
          
          //Добавим событие onchange для поля
          var field_el = document.getElementById(this._pref+'_field_1_cond_'+this._counter, this._fields_1);
          field_el.setAttribute('onchange', this._name_object+'.refill_select(this.value, \''+this._pref+'_value_cond_'+this._counter+'\', 1)');
          break;
        case 4:
          this.fill_select(this._pref+'_field_1_cond_'+this._counter, this._fields_1);
          var key = this.__getFirstKey(this._fields_1);
          var values = this.__get2dArrayFrom3dArray(key, this._fields_2);
          this.fill_select(this._pref+'_field_2_cond_'+this._counter, values);
          
          
          this.fill_select(this._pref+'_type_cond_'+this._counter, this._types);
          this.fill_select(this._pref+'_value_cond_'+this._counter, this._values);
          
          //Добавим событие onchange для поля
          var field_el = document.getElementById(this._pref+'_field_1_cond_'+this._counter, this._fields_1);
          field_el.setAttribute('onchange', this._name_object+'.refill_select(this.value, \''+this._pref+'_field_2_cond_'+this._counter+'\', 2)');
          break;
        default:
          this.fill_select(this._pref+'_field_1_cond_'+this._counter, this._fields_1);
          this.fill_select(this._pref+'_field_2_cond_'+this._counter, this._fields_2);
          this.fill_select(this._pref+'_type_cond_'+this._counter, this._types);
          this.fill_select(this._pref+'_value_cond_'+this._counter, this._values);
          break;
      }

      this._counter ++;
      
      return true;
    };
    
    /**
     * Возвращает ключ первого элемента из двумерного массива.
     * @param {array} array_2d - Двумерный массив 
     * @return {string|null}
     */
    this.__getFirstKey = function(array_2d){
        for (var key in array_2d) {
          return key;
          break;
        }
        return null;
    };
    
    /**
     * Возвращает 2-мерный массив из 3-х мерного, по ключу.
     * @param {string} key - Искомый ключ
     * @param {array} 3d_array - 3-х мерный массив
     * @returns {string|null} 
     */
    this.__get2dArrayFrom3dArray = function(key, array_3d){
      if(typeof(array_3d[key]) !== 'undefined'){
        return array_3d[key];
      }
      return null
    };

    /**
     * Возвращает DOM элементы для построения условия
     * @param {int} build_field_1_select : 1 - Если необходимо добавить список сравниваемых полей
     * @param {int} build_field_2_select : 1 - Если необходимо добавить список сравниваемых полей
     * @param {int} build_type_select : 1 - Если необходимо добвить список типов значений
     * @param {int} build_value_select : 1 - Если необходимо добавить [список значений/текстовое поле значения]
     * @returns {null|BuilderOfConditions.__createConditionElements.div_group}
     */
    this.__createConditionElements = function(build_field_1_select, build_field_2_select, build_type_select, build_value_select){
      var build_field_1_select = build_field_1_select || 0;
      var build_field_2_select = build_field_2_select || 0;
      var build_type_select = build_type_select || 0;
      var build_value_select = build_value_select || 0;      
      
      if(build_field_1_select === 0 && build_field_2_select === 0 && build_type_select === 0 && build_value_select === 0){return false;}
      
      var div_group = this.__createElement('div',{
        'class': 'group_condition'
      });

      
      //Проверяемое значение
      if(build_field_1_select === 1){
        var div_field_1_group = this.__createSelect('field_1_cond', 'Поле:');        
      }else{
        var div_field_1_group = this.__createHidden('field_1_cond', this._fields_1);
      }
      
      //Дополнительне проверяемое значение
      if(build_field_2_select === 1){
        var div_field_2_group = this.__createSelect('field_2_cond', 'Поле:');        
      }else{
        var div_field_2_group = this.__createHidden('field_2_cond', this._fields_2);
      }
    
      //Тип сравнения
      if(build_type_select === 1){
        var div_type_group = this.__createSelect('type_cond', 'Тип сравнения:');
      }else{
        var div_type_group = this.__createHidden('type_cond', this._types);
      }
      
      //Значение
      if(build_value_select === 1){
        //Если для поля значения передан массив, то выводим выпадающий список
        if(this.__getFirstKey(this._values) !== null){
          var div_value_group = this.__createSelect('value_cond', 'Значение:');
        }else{
          var div_value_group = this.__createInput('value_cond','Значение:','Введите текст');
        }
      }else{
        var div_value_group = this.__createHidden('value_cond', this._values);
      }
      
      var div_andor_group = this.__createAndOr('andor','И','ИЛИ');
      
      //Собираем все в общий fieldset
      div_group.appendChild(div_field_1_group);
      div_group.appendChild(div_field_2_group);
      div_group.appendChild(div_type_group);
      div_group.appendChild(div_value_group);
      div_group.appendChild(div_andor_group);
      
      return div_group;
    };

    this.__createHidden = function(hidden_name, value){
      var div_group = this.__createElement('div',{
        'class': 'form-group'
      });
      var hidden_field_cond = this.__createElement('input', {
        'type': 'hidden',
        'id': this._pref+'_'+hidden_name+'_'+this._counter,
        'name': this._pref+'_'+hidden_name+'['+this._counter+']',
        'value': value
      });
      div_group.appendChild(hidden_field_cond);
      return div_group;
    };

    this.__createSelect = function(select_name, label_text){
      var div_group = this.__createElement('div',{
        'class': 'form-group'
      });
      var label_cond = this.__createElement('label',{
        'for': this._pref+'_'+select_name+'_'+this._counter
      }, '&nbsp;'+label_text+'&nbsp;');

      var select_cond = this.__createElement('select',{
        'class': 'selectpicker',
        'data-live-search': 'true',
        'data-width': '150px',
        'name': this._pref+'_'+select_name+'['+this._counter+']',
        'id': this._pref+'_'+select_name+'_'+this._counter
      });
      div_group.appendChild(label_cond);
      div_group.appendChild(select_cond);
      return div_group;
    };
    
    this.__createInput = function(input_name, label_text, placeholder){
      var div_group = this.__createElement('div',{
        'class': 'form-group'
      });
      var label_cond = this.__createElement('label',{
        'for': this._pref+'_'+input_name+'_'+this._counter
      }, '&nbsp;'+label_text+'&nbsp;');
      
      var input_value_cond = this.__createElement('input',{
        'class': 'form-control',
        'type': 'text',
        'size': '35',
        'placeholder': placeholder,
        'name': this._pref+'_'+input_name+'['+this._counter+']',
        'id': this._pref+'_'+input_name+'_'+this._counter
      });
      
      div_group.appendChild(label_cond);
      div_group.appendChild(input_value_cond);
      return div_group;
    };
    
    this.__createAndOr = function(and_or_name, label_AND, label_OR){
      var div_andor_group = this.__createElement('div',{
        'class': 'btn-group',
        'data-toggle': 'buttons'
      });
      var label_andor_and = this.__createElement('label',{
        'class': 'btn btn-default active'
      });
      var label_andor_or = this.__createElement('label',{
        'class': 'btn btn-default '
      });
      var andor_cond_and = this.__createElement('input',{
        'type': 'radio',
        'value': 'AND',
        'name': this._pref+'_'+and_or_name+'['+this._counter+']',
        'id': this._pref+'_'+and_or_name+'_'+this._counter,
        'checked': 'checked'
      });
      var andor_cond_or = this.__createElement('input',{
        'type': 'radio',
        'value': 'OR',
        'name': this._pref+'_'+and_or_name+'['+this._counter+']',
        'id': this._pref+'_'+and_or_name+'_'+this._counter
      });
      
      label_andor_and.appendChild(andor_cond_and);
      label_andor_and.innerHTML = label_andor_and.innerHTML+label_AND;
      label_andor_or.appendChild(andor_cond_or);
      label_andor_or.innerHTML = label_andor_or.innerHTML+label_OR;
      div_andor_group.appendChild(label_andor_and);
      div_andor_group.appendChild(label_andor_or);
      return div_andor_group;
    };
    
    /**
     * Возвращает созданный DOM элемент.
     * @param {string} element_name
     * @param {array} arr_attributes
     * @param {string} inner_html
     * @returns {DOM element | null}
     */
    this.__createElement = function(element_name, arr_attributes, inner_html){
      element_name = element_name || null;
      arr_attributes = arr_attributes || null;
      inner_html = inner_html || null;
      
      if(element_name === null){return null}
      
      var element = document.createElement(element_name);
      
      if(arr_attributes !== null){
        for(var key in arr_attributes){
          element.setAttribute(key, arr_attributes[key]);
        }
      }
      
      if(inner_html !== null){
        element.innerHTML = inner_html;
      }
      
      return element;
    }

    /**
     * Удаляет условие по его идентификатору
     * @param {string} id_el - идентификатор условия
     * @returns {boolean}
     */
    this.delCondition = function(el){
      //Если не нашли, куда добавлять условия
      if(el === null){return false;}
      
      el = el.parentNode;
      
      el.parentNode.removeChild(el);
      
      //this._counter --;
      return true;
    };
     
    /**
     * Заполняет элемент select, значениями
     * @param {string} id_select
     * @param {array} data
     */
    this.fill_select = function(id_select, data){
      var select = document.getElementById(id_select) || null;

      if(select === null){return false;}
      
      select.innerHTML = '';
      
      var i = 0;
      var first_key = '';
      for(var key in data){
        if(i === 0){first_key = key;}
        i++;
        select.appendChild(this.__createElement('option', {'value':key},data[key]));
      }
      
      $('#'+id_select).selectpicker('show');
      $('#'+id_select).selectpicker('refresh');
      
      return first_key;
    };
    
    this.refill_select = function(key, id_select, type){
      select = document.getElementById(id_select) || null;
      if(select === null){return false;}
      
      
      switch (type) {
        case 1:
          var values = this.__get2dArrayFrom3dArray(key, this._values);
          break;
        case 2:
          var values = this.__get2dArrayFrom3dArray(key, this._fields_2);
          break;
        default:
          var values = [];
          break;
      }
      this.fill_select(id_select, values);
    };
    
    //Метод возвращает все значения текущего набоа условий
    this.getResult = function(){
      var group_element = document.getElementById(this._pref) || null;
      if(group_element === null){return '';}
      
      //Получаем условия
      var group_query_elements = group_element.querySelectorAll('fieldset div.group_condition');
      //console.info(group_query_elements);
      
      var obj_result = [];
      
      //Получаем элементы со значениями для каждого условия
      //for(var i=0; i < this._counter; i++){
      for(var i in group_query_elements){
        var group_condition = group_query_elements[i];
        if(!group_condition.querySelector){continue;}
        //console.info(group_condition);
        
        var field_1_cond = group_condition.querySelector('div div select[name^="'+this._pref+'_field_1_cond'+'"], div div input[type="hidden"][name^="'+this._pref+'_field_1_cond'+'"]');
        //console.info(field_1_cond);
        var field_2_cond = group_condition.querySelector('div div select[name^="'+this._pref+'_field_2_cond'+'"], div div input[type="hidden"][name^="'+this._pref+'_field_2_cond'+'"]');
        
        var type_cond = group_condition.querySelector('div div select[name^="'+this._pref+'_type_cond'+'"], div div input[type="hidden"][name^="'+this._pref+'_type_cond'+'"]');
        var value_cond = group_condition.querySelector('div div select[name^="'+this._pref+'_value_cond'+'"], div div input[name^="'+this._pref+'_value_cond'+'"], div div input[type="hidden"][name^="'+this._pref+'_value_cond'+'"]');
        var andor = group_condition.querySelector('div div input[name^="'+this._pref+'_andor'+'"]:checked, div div input[type="hidden"][name^="'+this._pref+'_andor'+'"]');
        
        field_1_cond  = (field_1_cond !== null)?field_1_cond.value:'';
        field_2_cond  = (field_2_cond !== null)?field_2_cond.value:'';
        type_cond     = (type_cond !== null)?type_cond.value:'';
        value_cond    = (value_cond !== null)?value_cond.value:'';
        andor         = (andor !== null)?andor.value:'AND';
        
        
        
        obj_result[i] = {};
        
        obj_result[i].field_1_cond = field_1_cond;
        obj_result[i].field_2_cond = field_2_cond;
        obj_result[i].type_cond = type_cond;
        obj_result[i].value_cond = value_cond;
        obj_result[i].andor = andor;
      }
      
      //console.info(obj_result);
      return obj_result || [];
    };
    
    
    /**
     * Выводит объект в консоль
     * @param {type} obj - Любой объект
     */
    this.console = function(obj){
      console.info(obj);
    };
    
    /**
     * Выводит alert сообщение
     * @param {type} string - Строка
     */
    this.alert = function(string){
      alert(string);
    };
    
    /**
     * Выводит в консоль внутреннюю закрытую переменную
     */
    this.consoleInternalParam = function(){
      console.info(this._name_object);
    };
  }
  
  //Подготовим нужные данные для построителя условий
  var utm_labels = JSON.parse('<?php echo json_encode($params['utm_labels'], JSON_UNESCAPED_UNICODE); ?>');
  var events_on_certain_pages = JSON.parse('<?php echo json_encode($params['events_on_certain_pages'], JSON_UNESCAPED_UNICODE); ?>');
  var change_elements_from_pages = JSON.parse('<?php echo json_encode($params['change_elements_from_pages'], JSON_UNESCAPED_UNICODE); ?>');
  var events_on_all_pages = JSON.parse('<?php echo json_encode($params['events_on_all_pages'], JSON_UNESCAPED_UNICODE); ?>');
  var pages = JSON.parse('<?php echo json_encode($params['pages'], JSON_UNESCAPED_UNICODE); ?>');
  var list_conditions_type = JSON.parse('<?php echo json_encode($params['list_conditions_type'], JSON_UNESCAPED_UNICODE); ?>');
  var list_conditions_event_for_pages = JSON.parse('<?php echo json_encode($params['list_conditions_event_for_pages'], JSON_UNESCAPED_UNICODE); ?>');
  
  
  
  var fields_for_services = JSON.parse('<?php echo json_encode($params['fields_for_services'], JSON_UNESCAPED_UNICODE); ?>');
  var fields_for_services_ext = JSON.parse('<?php echo json_encode($params['fields_for_services_ext'], JSON_UNESCAPED_UNICODE); ?>');
  
  
  /**
   * UTM метки
   * @type BuilderOfConditions
   */
  var utm_conditions  = new BuilderOfConditions('utm_conditions', 1, utm_labels, null, list_conditions_type);
  /**
   * Страницы
   * @type BuilderOfConditions
   */
  var page_conditions = new BuilderOfConditions('page_conditions', 2, 'link', null, list_conditions_type);
  /**
   * События для страниц
   * @type Arguments
   */
  var event_for_pages = new BuilderOfConditions('event_for_pages', 3, pages, null, 1, events_on_certain_pages);
  
  /**
   * Параметры для страниц
   * @type BuilderOfConditions
   */
  var param_for_pages = new BuilderOfConditions('param_for_pages', 4, pages, change_elements_from_pages, list_conditions_type);
  
  function getFilterType(){
    //Получим тип фильтрации
    return document.forms['form_chains'].elements['filter_type'].value;
  }
  
  function changeVisibleInterval(status){
    chains.filter_type = status;
    var el = document.getElementById('interval_elements');
    if(status === 'last_session'){
      el.classList.add('hide');
    }else if(status === 'interval'){
      el.classList.remove('hide');
    }
  }
  
  function changeViewTable(type_report){
    var table = document.getElementById('result_table') || null;

    if(table !== null){
      var count_fields = 0;
      table.innerHTML = '';
      var thead = document.createElement('thead');
      var tbody = document.createElement('tbody');
      tbody.setAttribute('id', 'data_chains');
      var tfoot = document.createElement('tfoot');
      tfoot.setAttribute('class', 'table-hover');
      
      var thead_tr_1 = document.createElement('tr');
      var thead_tr_2 = document.createElement('tr');
      
      switch (type_report) {
        case undefined:
        case 'visits_of_pages':
        case 'actions_of_the_user':
        case 'change_of_parameters':
        case 'action_elements':
        case 'events':
          
          var th_name = document.createElement('th');
          th_name.innerHTML = 'Цепочка';
          thead_tr_1.appendChild(th_name);
          
          break;
        case 'combination_of_services_simple':
          
          var fields = ['Цепочка'],
          count_fields = 0;
          for(var i in fields){
            var th_name = document.createElement('th');
            th_name.innerHTML = fields[i];
            thead_tr_1.appendChild(th_name);
            
            count_fields ++;
          }
          
          break;
        case 'combination_of_services_ext':
          
          var fields = ['Цепочка'],
          count_fields = 0;
          for(var i in fields){
            var th_name = document.createElement('th');
            th_name.innerHTML = fields[i];
            thead_tr_1.appendChild(th_name);
            
            count_fields ++;
          }
          
          break;
        default:

            break;
      }
      
      //Колонка количество
      var th_count = document.createElement('th');
      th_count.setAttribute('width', '100');
      th_count.innerHTML = 'Количество';
      thead_tr_1.appendChild(th_count);

      //Колонка боты
      var th_bots = document.createElement('th');
      th_bots.setAttribute('width', '100');
      th_bots.innerHTML = 'Боты';
      thead_tr_1.appendChild(th_bots);

      //Колонка адблоки
      var th_ads = document.createElement('th');
      th_ads.setAttribute('width', '100');
      th_ads.innerHTML = 'Адблоки';
      thead_tr_1.appendChild(th_ads);

      //Строка в заголовке таблицы с общим количеством
      var td_all_count = document.createElement('th');
      td_all_count.setAttribute('style', 'text-align:right');
      td_all_count.setAttribute('colspan', count_fields);
      td_all_count.innerHTML = 'Общее количество:';
      var td_summ = document.createElement('th');
      td_summ.setAttribute('id', 'data_summ');
      td_summ.innerHTML = 0;
      var bot_summ = document.createElement('th');
      bot_summ.setAttribute('id', 'count_bot');
      bot_summ.innerHTML = 0;
      var ad_summ = document.createElement('th');
      ad_summ.setAttribute('id', 'count_ad');
      ad_summ.innerHTML = 0;

      thead_tr_2.appendChild(td_all_count);
      thead_tr_2.appendChild(td_summ);
      thead_tr_2.appendChild(bot_summ);
      thead_tr_2.appendChild(ad_summ);
      
      thead.appendChild(thead_tr_1);
      thead.appendChild(thead_tr_2);
          
      table.appendChild(thead);
      table.appendChild(tbody);
      table.appendChild(tfoot);
      
    }
  }
  
</script>

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
      <h1 class="page-header">Цепочки событий</h1>
      <div class="alert alert-info" role="alert">
        Отображают события и действия пользователях за время их активности на сайте.
        <hr />
        <h4>Принцип указания интервалов:</h4>
        <small>Интервал поиска может быть выполнен либо в указанное время, либо по последним сессиям.</small>
        <br />
        <br />
        <p><b>За указанное время:</b> - Для указания конкретного интервала времени следует нажать на кнопку "Период времени" и в появивщихся элементах для ввода дат указываем необходимый интервал.
          <br />
          В этом случае будут проверятся записи, время добавления которых попадает в указанный интервал.
        </p>
        <p>
          <b>По последним сессиям:</b> - Для поиска данных по последним сессиям следует нажать на кнопку "Последние сессии".
          <br />
          В этом случае: происходит выборка последних сессий для 10 000 пользователей, и уже после этого проверяются записи на соответствие фильтрам.
        </p>
        <br />
        <h4>Принцип работы фильтров:</h4>
        Каждая группа фильтров накладывает ограничения на выборку по средствам логического <b>И</b>.
        К примеру, если задан фильтр <b>"UTM метки"</b> и задан фильтр <b>"Страницы"</b>, то будут возвращены те данные, в которых оба фильтра являются истинными.
        <br />
        Для каждого фильтра можно задать несколько условий, разграничивая их логическими <b>"И/ИЛИ"</b>,
        однако следует помнить что индикатор <b>"И/ИЛИ"</b> устанавливается между текущим условием и предыдущим.
        <br />
        <p>
        <br /><b>UTM метки</b> - накладывает ограничения на такие поля как: <b>utm_campaign</b>, <b>utm_content</b>, <b>utm_term</b>, <b>utm_medium</b>, <b>utm_source</b> и <b>referer_link</b>.
        <br /><b>Страницы</b> - накладывает ограничение на поле link, которое хранит в себе данные о посещённой странице.
        <br /><b>Общие события для всех страниц</b> - накладывает ограничение на события, которые могут быть на любой странице, включая манипуляции пользователя на странице.
        <br /><b>События для страниц</b> - накладывает ограничения на те события, которые отвечают за манипуляции пользователя на странице, к примеру выбор чекбокса, передвижение слайдера.
        <br /><b>Изменение параметров на странице</b> - накладывает ограничения на изменение значения каких либо параметров на странице.
        <br />&nbsp;&nbsp;&nbsp;&nbsp;Для чекбоксов значение может быть 0 или 1 (при этом проверяется поле, соответствующее этому чекбоксу.)
        <br />&nbsp;&nbsp;&nbsp;&nbsp;Для слайдеров значение может быть произвольным (при этом проверяется поле, соответствующее значению этого слайдера, обычно это поля <b>...rate_num</b>.)
        
        
        </p>
      </div>
      <legend align="left" class="scheduler-border">Интервал поиска:</legend>
      <hr />
      <form name='form_chains' method="post" class="form-inline">
        <input type="hidden" name="partner" id="partner" value="<?php print(common_getVariable($GLOBALS, [
            'conf',
            'chains',
            'partner',
            'number'
        ], '')); ?>" />
        <div class="btn-group" data-toggle="buttons">
          <label class="btn btn-default active">
            <input name="filter_type" type="radio" value="interval" onchange="changeVisibleInterval(this.value);" checked="checked">Период времени
          </label>
          <label class="btn btn-default">
            <input name="filter_type" type="radio" value="last_session"  onchange="changeVisibleInterval(this.value);">Последние сессии
          </label>
        </div>
        <br />
        <small class="text-muted">Выберите Тип фильтрации цепочек, <br />можно выбирать в диапазоне дат, либо по последним сессиям.</small>
        <hr />
        <div id="interval_elements" class="form-group">
          <div class="form-inline">          
            <div class="input-group" style="float: left;">
              <input type="text" class="form-control form-inline datetimepicker" onchange="chains.update_datetime_val();" placeholder="С" name="dps" id="dps" att="" value="">
              <div class="input-group-addon">
                  <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>
            <div class="input-group" style="float: left;">
              <input type="text" class="form-control form-inline datetimepicker" onchange="chains.update_datetime_val();" placeholder="По" name="dpe" id="dpe" att="" value="">
              <div class="input-group-addon">
                  <span class="glyphicon glyphicon-th"></span>
              </div>
            </div>          
          </div>
          <br />
          <br />
          <small class="text-muted">Укажите интервал, в котором необходимо отобразить цепочки.</small>
        </div>
        <br />
        <br />
          
          <br>
          <div id="form_construct">
            <legend align="left" class="scheduler-border">Фильтры:</legend>
            <hr />
            <fieldset class="scheduler-border" id="utm_labels">
              <legend align="left" class="scheduler-border">UTM метки</legend>
              <input type="button" class="btn btn-primary" title="Добавить условие" value="Добавить условие" onclick="utm_conditions.addCondition('utm_labels');" />
            </fieldset>
            <fieldset class="scheduler-border" id="pages">
              <legend align="left" class="scheduler-border">Страницы</legend>
              <input type="button" class="btn btn-primary" title="Добавить условие" value="Добавить условие" onclick="page_conditions.addCondition('pages');" />
            </fieldset>
            <fieldset class="scheduler-border">
              <legend align="left" class="scheduler-border">События</legend>
              <h4 align="left" class="scheduler-border">Общие события для всех страниц</h4>
              <select data-live-search="true" data-width="auto" data-actions-box="true" class="selectpicker" name="events_on_all_pages" multiple="multiple">
                <?php
                common_inc('_database');
                $id_partner = common_getVariable($GLOBALS, [
                  'conf',
                  'chains',
                  'partner',
                  'number'
                 ], 0);
                $query_db = simple_query("SELECT DISTINCT `event_label` FROM `event_list` WHERE `partner` = '$id_partner'");
                $all_events = return_mysqli_results($query_db);
                for($i = 0; $i <count($all_events); $i++):
                ?>
                <option value="<?php print($all_events[$i]['event_label']); ?>"><?php print($all_events[$i]['event_label']); ?></option>
                <?php endfor; ?>
              </select>
              <br />
              <small class="text-muted">Если необходимо, то в выпадающем списке можно выбрать общие события для всех страниц.</small>
              <hr />
              <fieldset class="scheduler-border" id="event_pages">
                <legend align="left" class="scheduler-border">События для страниц</legend>
                <input type="button" class="btn btn-primary" title="Добавить условие" value="Добавить условие" onclick="event_for_pages.addCondition('event_pages');" />
              </fieldset>
            </fieldset>
            <fieldset class="scheduler-border" id="param_pages">
              <legend align="left" class="scheduler-border">Изменение параметров на странице</legend>
              <input type="button" class="btn btn-primary" title="Добавить условие" value="Добавить условие" onclick="param_for_pages.addCondition('param_pages');" />
            </fieldset>

            <?php
              //common_pre($GLOBALS['conf']['chains']);
            ?>
          </div>
        <br />
      </form>
      <br />
      <div class="alert alert-info" role="alert">
        <h4>Типы отчётов:</h4>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>Цепочки посещений страниц</b> - 
          Отражают цепочки страниц, которые посетили пользователи во время своей активности на сайте.
        <br /></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>Цепочки действий пользователя</b> - 
          Отражают цепочки действий пользователя на страницах за время своей активности на сайте.
        <br /></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>События</b> - 
          Отражает все события, которые происходили на страницах во время активности на сайте.
        <br /></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>События параметров</b> - 
          Отражает события, относящиеся к действиям пользователя, на страницах, за время своей активности на сайте.
        <br /></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>Изменения параметров</b> - 
          [Пока не реализовано]. Отражает старые и новые значения параметров, при оформлении заявок.
        <br /></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>Комбинации услуг упрощённая</b> - 
          Отражает комбинацию услуг, выбранных пользователями во время оформления заявки.
        <br /></p>
        <p>&nbsp;&nbsp;&nbsp;&nbsp;<b>Комбинации услуг расширенная</b> - 
          Отражает расширенную комбинацию услуг, выбранных пользователями во время оформления заявки.
          В этом случае отражаются значения услуг.
        <br /></p>
      </div>
      <select data-live-search="true" data-width="auto" class="selectpicker" name="report_types" id="report_types" onchange="changeViewTable(this.value);" >
        <optgroup label="Цепочки">
          <option value="visits_of_pages">Цепочки посещений страниц</option>
          <option value="actions_of_the_user">Цепочки действий пользователя</option>
        </optgroup>
        <optgroup label="События">
          <option value="events">События</option>
          <option value="action_elements">События параметров</option>
        </optgroup>
        <optgroup label="Популярность параметров">
          <option value="change_of_parameters" disabled>Изменения параметров</option>
        </optgroup>
        <optgroup label="Комбинации услуг">
          <option value="combination_of_services_simple">Комбинации услуг упрощённая</option>
          <option value="combination_of_services_ext">Комбинации услуг расширенная</option>
        </optgroup>
      </select>
      <input type="button" class="btn btn-primary" onclick="chains.getStat('data_chains');" value="Показать" />
      <button type="button" class="btn btn-primary" onclick="var name_export = 'Цепочки'; if(chains.dps>0){name_export += '_'+chains.dps_string}; if(chains.dpe>0){name_export += '_'+chains.dpe_string}; export_excel('table2excel', name_export);">Скачать</button>
      <small id="loading" style="display: none;"><img src="../loading.gif" height="30px;" width="30px" /></small>
      <br />
      <small class="text-muted">Выберите тип отчёта.</small>
      <br />
      <br />
      <div class="panel-heading"></div>
        <div>
          <div class="panel panel-default" id="panel_chains">

          </div>
          <table id="result_table" class="table table-striped table2excel">
           
          </table>
          <script>
            //Первоначально оформим страницу
            changeViewTable();
          </script>
        </div>
      <br />
    </div>
    <!--/main-->
  </div>
  <!--/row-->
</div>
<!--/container-fluid-->